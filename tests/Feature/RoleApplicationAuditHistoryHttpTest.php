<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleApplicationAuditHistoryHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $reviewer;
    private User $outsider;
    private User $otherOwner;
    private User $applicant;

    private int $contestId;
    private int $otherContestId;
    private int $applicationId;
    private int $otherApplicationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('owner');
        $this->reviewer = $this->makeUser('reviewer');
        $this->outsider = $this->makeUser('outsider');
        $this->otherOwner = $this->makeUser('other-owner');
        $this->applicant = $this->makeUser('applicant');

        $aspirantId = $this->makeAspirant($this->owner);
        $otherAspirantId = $this->makeAspirant($this->otherOwner);

        $this->contestId = $this->makeContest($aspirantId);
        $this->otherContestId = $this->makeContest($otherAspirantId);

        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $aspirantId,
            'user_id' => $this->reviewer->id,
            'role' => 'campaign_reviewer',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->applicationId = $this->makeApplication(
            $this->contestId
        );

        $this->otherApplicationId = $this->makeApplication(
            $this->otherContestId
        );
    }

    public function test_owner_can_view_campaign_audit_history(): void
    {
        $this->makeAudit($this->applicationId);

        $this->actingAs($this->owner)
            ->getJson($this->endpoint())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.action',
                'role_application.approved'
            )
            ->assertJsonPath(
                'data.0.metadata.new_status',
                'approved'
            );
    }

    public function test_active_reviewer_can_view_history(): void
    {
        $this->makeAudit($this->applicationId);

        $this->actingAs($this->reviewer)
            ->getJson($this->endpoint())
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_cannot_view_history(): void
    {
        $this->getJson($this->endpoint())
            ->assertUnauthorized();
    }

    public function test_outsider_cannot_view_history(): void
    {
        $this->makeAudit($this->applicationId);

        $this->actingAs($this->outsider)
            ->getJson($this->endpoint())
            ->assertForbidden();
    }

    public function test_campaign_audit_history_is_isolated(): void
    {
        $ownAuditId = $this->makeAudit($this->applicationId);

        $otherAuditId = $this->makeAudit(
            $this->otherApplicationId
        );

        $this->actingAs($this->owner)
            ->getJson($this->endpoint())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownAuditId)
            ->assertDontSee('"id":'.$otherAuditId, false);

        $this->getJson(
            $this->endpoint($this->otherContestId)
        )->assertForbidden();
    }

    public function test_unrelated_audit_events_are_excluded(): void
    {
        $this->makeAudit($this->applicationId);

        $this->makeAudit(
            $this->applicationId,
            'result_submission.approved',
            'result_submission'
        );

        $this->makeAudit(
            $this->applicationId,
            'role_application.created'
        );

        $this->actingAs($this->owner)
            ->getJson($this->endpoint())
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_pagination_is_bounded_and_ordered(): void
    {
        for ($i = 0; $i < 105; $i++) {
            $this->makeAudit($this->applicationId);
        }

        $this->actingAs($this->owner)
            ->getJson($this->endpoint().'?per_page=100')
            ->assertOk()
            ->assertJsonCount(100, 'data')
            ->assertJsonPath('total', 105)
            ->assertJsonPath('per_page', 100)
            ->assertJsonPath('last_page', 2);
    }

    public function test_invalid_pagination_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->getJson($this->endpoint().'?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    private function endpoint(?int $contestId = null): string
    {
        return route('campaign.applications.audit-history', [
            'contestId' => $contestId ?? $this->contestId,
        ]);
    }

    private function makeAudit(
        int $applicationId,
        string $action = 'role_application.approved',
        string $entityType = 'role_application'
    ): int {
        return DB::table('audit_logs')->insertGetId([
            'user_id' => $this->owner->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $applicationId,
            'metadata' => json_encode([
                'aspirant_contest_id' => $this->contestId,
                'previous_status' => 'pending',
                'new_status' => 'approved',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Audit History '.$suffix,
            'email' => 'audit-history-'.$suffix.'@example.test',
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
            'display_name' => 'Audit History Aspirant',
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

    private function makeApplication(int $contestId): int
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
