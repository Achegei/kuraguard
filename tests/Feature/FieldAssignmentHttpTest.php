<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FieldAssignmentHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $admin;
    private User $reviewer;
    private User $outsider;
    private User $applicant;

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

        $otherOwner = $this->makeUser('other-owner');

        $aspirantId = $this->makeAspirant($this->owner);
        $otherAspirantId = $this->makeAspirant($otherOwner);

        $this->contestId = $this->makeContest($aspirantId);
        $this->otherContestId = $this->makeContest(
            $otherAspirantId
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

        $this->applicationId = $this->makeApplication(
            $this->contestId
        );
    }

    public function test_guest_cannot_assign(): void
    {
        $this->postJson($this->endpoint())
            ->assertUnauthorized();

        $this->assertNoAssignment();
    }

    public function test_owner_can_assign_approved_application(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint())
            ->assertCreated()
            ->assertJsonPath('status', 'assigned')
            ->assertJsonPath(
                'application_id',
                $this->applicationId
            )
            ->assertJsonStructure([
                'message',
                'assignment_id',
                'application_id',
                'status',
            ]);

        $this->assertDatabaseHas('field_assignments', [
            'role_application_id' => $this->applicationId,
            'assigned_by' => $this->owner->id,
            'status' => 'assigned',
        ]);
    }

    public function test_active_admin_can_assign(): void
    {
        $this->actingAs($this->admin)
            ->postJson($this->endpoint())
            ->assertCreated();

        $this->assertDatabaseHas('field_assignments', [
            'role_application_id' => $this->applicationId,
            'assigned_by' => $this->admin->id,
        ]);
    }

    public function test_reviewer_cannot_assign(): void
    {
        $this->actingAs($this->reviewer)
            ->postJson($this->endpoint())
            ->assertForbidden();

        $this->assertNoAssignment();
    }

    public function test_outsider_cannot_assign(): void
    {
        $this->actingAs($this->outsider)
            ->postJson($this->endpoint())
            ->assertForbidden();

        $this->assertNoAssignment();
    }

    public function test_pending_application_cannot_be_assigned(): void
    {
        $this->setApplicationStatus('pending');

        $this->actingAs($this->owner)
            ->postJson($this->endpoint())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertNoAssignment();
    }

    public function test_rejected_application_cannot_be_assigned(): void
    {
        $this->setApplicationStatus('rejected');

        $this->actingAs($this->owner)
            ->postJson($this->endpoint())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertNoAssignment();
    }

    public function test_cross_campaign_application_returns_404(): void
    {
        $otherApplication = $this->makeApplication(
            $this->otherContestId
        );

        $this->actingAs($this->owner)
            ->postJson(
                $this->endpoint(
                    $this->contestId,
                    $otherApplication
                )
            )
            ->assertNotFound();

        $this->assertDatabaseMissing('field_assignments', [
            'role_application_id' => $otherApplication,
        ]);
    }

    public function test_duplicate_assignment_returns_422(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint())
            ->assertCreated();

        $this->postJson($this->endpoint())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'role_application_id'
            );

        $this->assertSame(
            1,
            DB::table('field_assignments')
                ->where(
                    'role_application_id',
                    $this->applicationId
                )
                ->count()
        );
    }

    public function test_inconsistent_geography_returns_422(): void
    {
        DB::table('role_applications')
            ->where('id', $this->applicationId)
            ->update(['county_id' => 1]);

        $this->actingAs($this->owner)
            ->postJson($this->endpoint())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('county_id');

        $this->assertNoAssignment();
    }

    public function test_assignment_creates_audit_record(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson($this->endpoint())
            ->assertCreated();

        $assignmentId = $response->json('assignment_id');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'field_assignment.created',
            'entity_type' => 'field_assignment',
            'entity_id' => $assignmentId,
        ]);
    }

    private function endpoint(
        ?int $contestId = null,
        ?int $applicationId = null
    ): string {
        return route('campaign.applications.assign', [
            'contestId' => $contestId ?? $this->contestId,
            'applicationId' => $applicationId
                ?? $this->applicationId,
        ]);
    }

    private function assertNoAssignment(): void
    {
        $this->assertDatabaseMissing('field_assignments', [
            'role_application_id' => $this->applicationId,
        ]);
    }

    private function setApplicationStatus(string $status): void
    {
        DB::table('role_applications')
            ->where('id', $this->applicationId)
            ->update(['status' => $status]);
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Assignment HTTP '.$suffix,
            'email' => 'assignment-http-'.$suffix.'@example.test',
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
            'display_name' => 'Assignment HTTP Aspirant',
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

    private function makeApplication(int $contestId): int
    {
        return DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'applicant_user_id' => $this->applicant->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'national_coordinator',
            'geographic_level' => 'national',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
