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

class FieldAssignmentServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $admin;
    private User $reviewer;
    private User $outsider;
    private User $applicant;
    private int $aspirantId;
    private int $contestId;
    private int $otherContestId;
    private int $applicationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('owner');
        $this->admin = $this->makeUser('admin');
        $this->reviewer = $this->makeUser('reviewer');
        $this->outsider = $this->makeUser('outsider');
        $this->applicant = $this->makeUser('applicant');

        $this->aspirantId = $this->makeAspirant($this->owner);
        $this->contestId = $this->makeContest($this->aspirantId);

        $otherOwner = $this->makeUser('other-owner');
        $this->otherContestId = $this->makeContest(
            $this->makeAspirant($otherOwner)
        );

        $this->makeMembership($this->admin, 'campaign_admin');
        $this->makeMembership($this->reviewer, 'campaign_reviewer');

        $this->applicationId = $this->makeApplication(
            $this->contestId,
            'approved'
        );
    }

    public function test_owner_assigns_approved_application(): void
    {
        $id = $this->assign($this->owner);

        $this->assertGreaterThan(0, $id);

        $this->assertDatabaseHas('field_assignments', [
            'id' => $id,
            'role_application_id' => $this->applicationId,
            'assigned_by' => $this->owner->id,
            'status' => 'assigned',
        ]);

        $this->assertNotNull(
            DB::table('field_assignments')
                ->where('id', $id)
                ->value('assigned_at')
        );
    }

    public function test_active_admin_can_assign(): void
    {
        $id = $this->assign($this->admin);

        $this->assertDatabaseHas('field_assignments', [
            'id' => $id,
            'assigned_by' => $this->admin->id,
        ]);
    }

    public function test_reviewer_cannot_assign(): void
    {
        $this->expectForbidden(function (): void {
            $this->assign($this->reviewer);
        });

        $this->assertSame(0, $this->assignmentCount());
    }

    public function test_outsider_cannot_assign(): void
    {
        $this->expectForbidden(function (): void {
            $this->assign($this->outsider);
        });

        $this->assertSame(0, $this->assignmentCount());
    }

    public function test_pending_application_cannot_be_assigned(): void
    {
        $this->setApplicationStatus('pending');

        $this->expectValidation('status', function (): void {
            $this->assign($this->owner);
        });

        $this->assertSame(0, $this->assignmentCount());
    }

    public function test_rejected_application_cannot_be_assigned(): void
    {
        $this->setApplicationStatus('rejected');

        $this->expectValidation('status', function (): void {
            $this->assign($this->owner);
        });

        $this->assertSame(0, $this->assignmentCount());
    }

    public function test_cross_campaign_application_is_not_found(): void
    {
        try {
            $this->service()->assign(
                $this->owner,
                $this->contestId,
                $this->makeApplication($this->otherContestId, 'approved')
            );

            $this->fail('Expected HTTP 404.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }

        $this->assertSame(0, $this->assignmentCount());
    }

    public function test_duplicate_assignment_is_rejected(): void
    {
        $firstId = $this->assign($this->owner);

        $this->expectValidation('role_application_id', function (): void {
            $this->assign($this->owner);
        });

        $this->assertSame(1, $this->assignmentCount());

        $this->assertDatabaseHas('field_assignments', [
            'id' => $firstId,
            'role_application_id' => $this->applicationId,
        ]);
    }

    public function test_inconsistent_geography_is_rejected(): void
    {
        DB::table('role_applications')
            ->where('id', $this->applicationId)
            ->update(['county_id' => 1]);

        $this->expectValidation('county_id', function (): void {
            $this->assign($this->owner);
        });

        $this->assertSame(0, $this->assignmentCount());
    }

    public function test_assignment_creates_attributable_audit(): void
    {
        $id = $this->assign($this->owner);

        $audit = DB::table('audit_logs')
            ->where('entity_type', 'field_assignment')
            ->where('entity_id', $id)
            ->where('action', 'field_assignment.created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($this->owner->id, (int) $audit->user_id);

        $metadata = json_decode($audit->metadata, true);

        $this->assertSame(
            $this->contestId,
            $metadata['aspirant_contest_id']
        );

        $this->assertSame(
            $this->applicationId,
            $metadata['role_application_id']
        );

        $this->assertSame(
            $this->applicant->id,
            (int) $metadata['applicant_user_id']
        );
    }

    public function test_audit_failure_rolls_back_assignment(): void
    {
        DB::statement(<<<'SQL'
CREATE TRIGGER prevent_field_assignment_audit
BEFORE INSERT ON audit_logs
WHEN NEW.entity_type = 'field_assignment'
BEGIN
    SELECT RAISE(ABORT, 'Simulated assignment audit failure');
END
SQL
        );

        try {
            try {
                $this->assign($this->owner);

                $this->fail('Expected audit failure.');
            } catch (QueryException $exception) {
                $this->assertStringContainsString(
                    'Simulated assignment audit failure',
                    $exception->getMessage()
                );
            }
        } finally {
            DB::statement(
                'DROP TRIGGER IF EXISTS prevent_field_assignment_audit'
            );
        }

        $this->assertSame(0, $this->assignmentCount());

        $this->assertSame(
            0,
            DB::table('audit_logs')
                ->where('entity_type', 'field_assignment')
                ->count()
        );
    }

    private function service(): FieldAssignmentService
    {
        return app(FieldAssignmentService::class);
    }

    private function assign(User $user): int
    {
        return $this->service()->assign(
            $user,
            $this->contestId,
            $this->applicationId
        );
    }

    private function assignmentCount(): int
    {
        return DB::table('field_assignments')
            ->where('role_application_id', $this->applicationId)
            ->count();
    }

    private function setApplicationStatus(string $status): void
    {
        DB::table('role_applications')
            ->where('id', $this->applicationId)
            ->update(['status' => $status]);
    }

    private function expectForbidden(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected HTTP 403.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
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
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Assignment Test '.$suffix,
            'email' => 'assignment-'.$suffix.'-'
                . uniqid() . '@example.test',
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
            'display_name' => 'Assignment Test Aspirant',
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
        User $user,
        string $role
    ): void {
        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $this->aspirantId,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeApplication(
        int $contestId,
        string $status
    ): int {
        return DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'applicant_user_id' => $this->applicant->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'national_coordinator',
            'geographic_level' => 'national',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
