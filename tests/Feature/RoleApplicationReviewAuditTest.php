<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RoleApplicationReviewService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoleApplicationReviewAuditTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $outsider;
    private int $contestId;
    private int $applicationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->createAuditUser('Audit Owner');
        $this->outsider = $this->createAuditUser('Audit Outsider');

        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $this->owner->id,
            'display_name' => 'Audit Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->contestId = DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'test-' . bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $applicant = $this->createAuditUser('Audit Applicant');

        $this->applicationId = DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $this->contestId,
            'applicant_user_id' => $applicant->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'national_coordinator',
            'geographic_level' => 'national',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_approval_creates_attributable_audit_record(): void
    {
        $this->reviews()->approve(
            $this->owner,
            $this->contestId,
            $this->applicationId,
            'Credentials verified.'
        );

        $audit = $this->auditRecord();

        $this->assertNotNull($audit);
        $this->assertSame($this->owner->id, (int) $audit->user_id);
        $this->assertSame('role_application.approved', $audit->action);

        $metadata = json_decode($audit->metadata, true);

        $this->assertSame(
            $this->contestId,
            $metadata['aspirant_contest_id']
        );
        $this->assertSame('pending', $metadata['previous_status']);
        $this->assertSame('approved', $metadata['new_status']);
        $this->assertSame(
            'Credentials verified.',
            $metadata['review_notes']
        );

        $this->assertSame(1, $this->auditCount());
    }

    public function test_rejection_creates_audit_record(): void
    {
        $this->reviews()->reject(
            $this->owner,
            $this->contestId,
            $this->applicationId,
            'Application incomplete.'
        );

        $audit = $this->auditRecord();

        $this->assertNotNull($audit);
        $this->assertSame('role_application.rejected', $audit->action);
        $this->assertSame(1, $this->auditCount());
    }

    public function test_unauthorized_review_creates_no_audit(): void
    {
        try {
            $this->reviews()->approve(
                $this->outsider,
                $this->contestId,
                $this->applicationId
            );

            $this->fail('Expected authorization failure.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(0, $this->auditCount());
        $this->assertPending();
    }

    public function test_repeated_review_does_not_duplicate_audit(): void
    {
        $this->reviews()->approve(
            $this->owner,
            $this->contestId,
            $this->applicationId
        );

        try {
            $this->reviews()->reject(
                $this->owner,
                $this->contestId,
                $this->applicationId
            );

            $this->fail('Expected duplicate review rejection.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertSame(1, $this->auditCount());

        $this->assertDatabaseHas('role_applications', [
            'id' => $this->applicationId,
            'status' => 'approved',
        ]);
    }

    public function test_audit_failure_rolls_back_review(): void
    {
        $connection = DB::connection();

        $connection->statement(<<<'SQL'
CREATE TRIGGER prevent_review_audit_insert
BEFORE INSERT ON audit_logs
WHEN NEW.entity_type = 'role_application'
BEGIN
    SELECT RAISE(ABORT, 'Simulated audit failure');
END
SQL
        );

        try {
            $this->reviews()->approve(
                $this->owner,
                $this->contestId,
                $this->applicationId
            );

            $this->fail('Expected audit insertion failure.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString(
                'Simulated audit failure',
                $e->getMessage()
            );
        } finally {
            $connection->statement(
                'DROP TRIGGER IF EXISTS prevent_review_audit_insert'
            );
        }

        $this->assertPending();
        $this->assertSame(0, $this->auditCount());
    }

    private function reviews(): RoleApplicationReviewService
    {
        return app(RoleApplicationReviewService::class);
    }

    private function auditRecord(): ?object
    {
        return DB::table('audit_logs')
            ->where('entity_type', 'role_application')
            ->where('entity_id', $this->applicationId)
            ->whereIn('action', [
                'role_application.approved',
                'role_application.rejected',
            ])
            ->first();
    }

    private function auditCount(): int
    {
        return DB::table('audit_logs')
            ->where('entity_type', 'role_application')
            ->where('entity_id', $this->applicationId)
            ->count();
    }

    private function assertPending(): void
    {
        $this->assertDatabaseHas('role_applications', [
            'id' => $this->applicationId,
            'status' => 'pending',
            'reviewed_by' => null,
        ]);
    }

    private function createAuditUser(string $name): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name))
                . '-' . uniqid() . '@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }
}
