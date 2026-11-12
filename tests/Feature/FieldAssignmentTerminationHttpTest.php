<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FieldAssignmentTerminationHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $admin;
    private User $reviewer;
    private User $outsider;
    private User $otherOwner;

    private int $contestId;
    private int $otherContestId;
    private int $applicationId;
    private int $assignmentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('http-owner');
        $this->admin = $this->makeUser('http-admin');
        $this->reviewer = $this->makeUser('http-reviewer');
        $this->outsider = $this->makeUser('http-outsider');
        $this->otherOwner = $this->makeUser('http-other-owner');
        $applicant = $this->makeUser('http-applicant');

        $aspirantId = $this->makeAspirant($this->owner);

        $this->contestId = $this->makeContest($aspirantId);

        $this->otherContestId = $this->makeContest(
            $this->makeAspirant($this->otherOwner)
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

    public function test_guest_cannot_end_assignment(): void
    {
        $this->postJson($this->endpoint())
            ->assertUnauthorized();

        $this->assertActive();
    }

    public function test_owner_can_end_assignment(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint(), [
                'reason' => 'Agent unavailable',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ended')
            ->assertJsonPath('assignment_id', $this->assignmentId);

        $this->assertEnded();
    }

    public function test_active_admin_can_end_assignment(): void
    {
        $this->actingAs($this->admin)
            ->postJson($this->endpoint())
            ->assertOk();

        $this->assertEnded();
    }

    public function test_reviewer_cannot_end_assignment(): void
    {
        $this->actingAs($this->reviewer)
            ->postJson($this->endpoint())
            ->assertForbidden();

        $this->assertActive();
    }

    public function test_outsider_cannot_end_assignment(): void
    {
        $this->actingAs($this->outsider)
            ->postJson($this->endpoint())
            ->assertForbidden();

        $this->assertActive();
    }

    public function test_other_campaign_assignment_returns_404(): void
    {
        $this->actingAs($this->otherOwner)
            ->postJson($this->endpoint($this->otherContestId))
            ->assertNotFound();

        $this->assertActive();
    }

    public function test_repeated_termination_returns_422(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint())
            ->assertOk();

        $this->postJson($this->endpoint())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame(
            1,
            DB::table('audit_logs')
                ->where('action', 'field_assignment.ended')
                ->where('entity_id', $this->assignmentId)
                ->count()
        );
    }

    public function test_oversized_reason_returns_422(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint(), [
                'reason' => str_repeat('X', 5001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->assertActive();
    }

    public function test_non_string_reason_returns_422(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint(), [
                'reason' => ['invalid'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->assertActive();
    }

    public function test_termination_preserves_approved_application(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint())
            ->assertOk();

        $this->assertDatabaseHas('role_applications', [
            'id' => $this->applicationId,
            'status' => 'approved',
        ]);

        $this->assertEnded();
    }

    public function test_termination_creates_attributable_audit(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint(), [
                'reason' => 'Agent replaced',
            ])
            ->assertOk();

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

    private function endpoint(?int $contestId = null): string
    {
        return route('campaign.assignments.end', [
            'contestId' => $contestId ?? $this->contestId,
            'assignmentId' => $this->assignmentId,
        ]);
    }

    private function assertActive(): void
    {
        $this->assertDatabaseHas('field_assignments', [
            'id' => $this->assignmentId,
            'status' => 'assigned',
            'ended_at' => null,
        ]);
    }

    private function assertEnded(): void
    {
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
