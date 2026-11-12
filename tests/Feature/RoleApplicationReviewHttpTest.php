<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleApplicationReviewHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $reviewer;
    private User $outsider;
    private User $applicant;
    private int $contestId;
    private int $otherContestId;
    private int $applicationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeFixtureUser('owner');
        $this->reviewer = $this->makeFixtureUser('reviewer');
        $this->outsider = $this->makeFixtureUser('outsider');
        $this->applicant = $this->makeFixtureUser('applicant');
        $otherOwner = $this->makeFixtureUser('other-owner');

        $aspirantId = $this->makeFixtureAspirant($this->owner);
        $otherAspirantId = $this->makeFixtureAspirant($otherOwner);

        $this->contestId = $this->makeFixtureContest($aspirantId);
        $this->otherContestId = $this->makeFixtureContest(
            $otherAspirantId
        );

        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $aspirantId,
            'user_id' => $this->reviewer->id,
            'role' => 'campaign_reviewer',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->applicationId = $this->makeFixtureApplication(
            $this->contestId
        );
    }

    public function test_guest_cannot_approve_application(): void
    {
        $this->postJson($this->endpoint('approve'))
            ->assertUnauthorized();

        $this->assertPending();
    }

    public function test_guest_cannot_reject_application(): void
    {
        $this->postJson($this->endpoint('reject'))
            ->assertUnauthorized();

        $this->assertPending();
    }

    public function test_owner_can_approve_application(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint('approve'), [
                'review_notes' => 'Verified by owner.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('role_applications', [
            'id' => $this->applicationId,
            'status' => 'approved',
            'reviewed_by' => $this->owner->id,
            'review_notes' => 'Verified by owner.',
        ]);
    }

    public function test_reviewer_can_reject_application(): void
    {
        $this->actingAs($this->reviewer)
            ->postJson($this->endpoint('reject'), [
                'review_notes' => 'Incomplete details.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'rejected');

        $this->assertDatabaseHas('role_applications', [
            'id' => $this->applicationId,
            'status' => 'rejected',
            'reviewed_by' => $this->reviewer->id,
        ]);
    }

    public function test_outsider_is_forbidden(): void
    {
        $this->actingAs($this->outsider)
            ->postJson($this->endpoint('approve'))
            ->assertForbidden();

        $this->assertPending();
    }

    public function test_reviewer_cannot_access_other_campaign(): void
    {
        $otherApplication = $this->makeFixtureApplication(
            $this->otherContestId
        );

        $this->actingAs($this->reviewer)
            ->postJson(
                $this->endpoint(
                    'approve',
                    $this->otherContestId,
                    $otherApplication
                )
            )
            ->assertForbidden();

        $this->assertDatabaseHas('role_applications', [
            'id' => $otherApplication,
            'status' => 'pending',
        ]);
    }

    public function test_application_from_other_contest_returns_404(): void
    {
        $otherApplication = $this->makeFixtureApplication(
            $this->otherContestId
        );

        $this->actingAs($this->owner)
            ->postJson(
                $this->endpoint(
                    'approve',
                    $this->contestId,
                    $otherApplication
                )
            )
            ->assertNotFound();

        $this->assertDatabaseHas('role_applications', [
            'id' => $otherApplication,
            'status' => 'pending',
        ]);
    }

    public function test_invalid_review_notes_are_rejected(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint('approve'), [
                'review_notes' => str_repeat('X', 5001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review_notes');

        $this->assertPending();
    }

    public function test_review_decision_cannot_be_repeated(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->endpoint('approve'))
            ->assertOk();

        $this->postJson($this->endpoint('reject'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('role_applications', [
            'id' => $this->applicationId,
            'status' => 'approved',
            'reviewed_by' => $this->owner->id,
        ]);
    }

    private function endpoint(
        string $action,
        ?int $contestId = null,
        ?int $applicationId = null
    ): string {
        return route('campaign.applications.'.$action, [
            'contestId' => $contestId ?? $this->contestId,
            'applicationId' => $applicationId ?? $this->applicationId,
        ]);
    }

    private function assertPending(): void
    {
        $this->assertDatabaseHas('role_applications', [
            'id' => $this->applicationId,
            'status' => 'pending',
            'reviewed_by' => null,
        ]);
    }

    private function makeFixtureUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'HTTP Review '.$suffix,
            'email' => 'http-review-'.$suffix.'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function makeFixtureAspirant(User $user): int
    {
        return DB::table('aspirants')->insertGetId([
            'user_id' => $user->id,
            'display_name' => 'HTTP Review Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeFixtureContest(int $aspirantId): int
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

    private function makeFixtureApplication(int $contestId): int
    {
        return DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'applicant_user_id' => $this->applicant->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'national_coordinator',
            'geographic_level' => 'national',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
