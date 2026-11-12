<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FieldAssignmentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FieldAssignmentTerminationServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $admin;
    private User $reviewer;
    private User $outsider;
    private int $contestId;
    private int $otherContestId;
    private int $applicationId;
    private int $assignmentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('owner');
        $this->admin = $this->makeUser('admin');
        $this->reviewer = $this->makeUser('reviewer');
        $this->outsider = $this->makeUser('outsider');
        $applicant = $this->makeUser('applicant');
        $otherOwner = $this->makeUser('other-owner');

        $aspirantId = $this->makeAspirant($this->owner);

        $this->contestId = $this->makeContest($aspirantId);

        $this->otherContestId = $this->makeContest(
            $this->makeAspirant($otherOwner)
        );

        $this->makeMembership(
            $aspirantId,
            $this->admin,
            'campaign_admin'
        );

        $this->makeMembership(
            $aspirantId,
            $this->reviewer,
            'campaign_reviewer'
        );

        $this->applicationId = DB::table('role_applications')
            ->insertGetId([
                'aspirant_contest_id' => $this->contestId,
                'applicant_user_id' => $applicant->id,
                'electoral_dataset_id' => 1,
                'requested_role' => 'national_coordinator',
                'geographic_level' => 'national',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->assignmentId = DB::table('field_assignments')
            ->insertGetId([
                'role_application_id' => $this->applicationId,
                'assigned_by' => $this->owner->id,
                'status' => 'assigned',
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('active_field_assignment_slots')->insert([
            'field_assignment_id' => $this->assignmentId,
            'aspirant_contest_id' => $this->contestId,
            'applicant_user_id' => DB::table('role_applications')
                ->where('id', $this->applicationId)
                ->value('applicant_user_id'),
            'electoral_dataset_id' => 1,
            'requested_role' => 'national_coordinator',
            'geographic_level' => 'national',
            'geographic_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_owner_can_end_assignment(): void
    {
        $this->end($this->owner, 'Agent unavailable');

        $this->assertDatabaseHas('field_assignments', [
            'id' => $this->assignmentId,
            'status' => 'ended',
        ]);

        $this->assertNotNull(
            DB::table('field_assignments')
                ->where('id', $this->assignmentId)
                ->value('ended_at')
        );
    }

    public function test_active_admin_can_end_assignment(): void
    {
        $this->end($this->admin);

        $this->assertDatabaseHas('field_assignments', [
            'id' => $this->assignmentId,
            'status' => 'ended',
        ]);
    }

    public function test_reviewer_cannot_end_assignment(): void
    {
        $this->expectHttp(403, function (): void {
            $this->end($this->reviewer);
        });

        $this->assertActive();
    }

    public function test_outsider_cannot_end_assignment(): void
    {
        $this->expectHttp(403, function (): void {
            $this->end($this->outsider);
        });

        $this->assertActive();
    }

    public function test_other_campaign_assignment_returns_404(): void
    {
        $otherAspirantId = DB::table('aspirant_contests')
            ->where('id', $this->otherContestId)
            ->value('aspirant_id');

        $otherOwnerId = DB::table('aspirants')
            ->where('id', $otherAspirantId)
            ->value('user_id');

        $otherOwner = User::findOrFail($otherOwnerId);

        $this->expectHttp(404, function () use ($otherOwner): void {
            $this->service()->endAssignment(
                $otherOwner,
                $this->otherContestId,
                $this->assignmentId
            );
        });

        $this->assertActive();
    }

    public function test_repeated_termination_is_rejected(): void
    {
        $this->end($this->owner);

        $this->expectValidation('status', function (): void {
            $this->end($this->owner);
        });

        $this->assertSame(
            1,
            DB::table('audit_logs')
                ->where('action', 'field_assignment.ended')
                ->where('entity_id', $this->assignmentId)
                ->count()
        );
    }

    public function test_reason_cannot_exceed_limit(): void
    {
        $this->expectValidation('reason', function (): void {
            $this->end($this->owner, str_repeat('X', 5001));
        });

        $this->assertActive();
    }

    public function test_termination_preserves_application_and_history(): void
    {
        $this->end($this->owner);

        $this->assertDatabaseHas('role_applications', [
            'id' => $this->applicationId,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('field_assignments', [
            'id' => $this->assignmentId,
            'role_application_id' => $this->applicationId,
            'assigned_by' => $this->owner->id,
            'status' => 'ended',
        ]);
    }

    public function test_termination_creates_attributable_audit(): void
    {
        $this->end($this->owner, 'Agent replaced');

        $audit = DB::table('audit_logs')
            ->where('action', 'field_assignment.ended')
            ->where('entity_type', 'field_assignment')
            ->where('entity_id', $this->assignmentId)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($this->owner->id, (int) $audit->user_id);

        $metadata = json_decode($audit->metadata, true);

        $this->assertSame(
            $this->contestId,
            $metadata['aspirant_contest_id']
        );

        $this->assertSame('Agent replaced', $metadata['reason']);
    }

    public function test_audit_failure_rolls_back_termination(): void
    {
        DB::statement(<<<'SQL'
CREATE TRIGGER prevent_assignment_termination_audit
BEFORE INSERT ON audit_logs
WHEN NEW.action = 'field_assignment.ended'
BEGIN
    SELECT RAISE(ABORT, 'Simulated termination audit failure');
END
SQL
        );

        try {
            try {
                $this->end($this->owner);
                $this->fail('Expected audit failure.');
            } catch (QueryException $exception) {
                $this->assertStringContainsString(
                    'Simulated termination audit failure',
                    $exception->getMessage()
                );
            }
        } finally {
            DB::statement(
                'DROP TRIGGER IF EXISTS prevent_assignment_termination_audit'
            );
        }

        $this->assertActive();
    }

    private function service(): FieldAssignmentService
    {
        return app(FieldAssignmentService::class);
    }

    private function end(
        User $actor,
        ?string $reason = null
    ): void {
        $this->service()->endAssignment(
            $actor,
            $this->contestId,
            $this->assignmentId,
            $reason
        );
    }

    private function assertActive(): void
    {
        $this->assertDatabaseHas('field_assignments', [
            'id' => $this->assignmentId,
            'status' => 'assigned',
            'ended_at' => null,
        ]);
    }

    private function expectHttp(
        int $status,
        callable $operation
    ): void {
        try {
            $operation();
            $this->fail('Expected HTTP exception.');
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    private function expectValidation(
        string $field,
        callable $operation
    ): void {
        try {
            $operation();
            $this->fail('Expected validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                $field,
                $exception->errors()
            );
        }
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Termination Test '.$suffix,
            'email' => 'termination-'.$suffix.'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function makeAspirant(User $user): int
    {
        return DB::table('aspirants')->insertGetId([
            'user_id' => $user->id,
            'display_name' => 'Termination Test Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeContest(int $aspirantId): int
    {
        return DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'test-' . bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeMembership(
        int $aspirantId,
        User $user,
        string $role
    ): void {
        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $aspirantId,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
