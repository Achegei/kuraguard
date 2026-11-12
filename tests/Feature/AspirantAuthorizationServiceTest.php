<?php

namespace Tests\Feature;

use App\Services\AspirantAuthorizationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AspirantAuthorizationServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AspirantAuthorizationService $authorization;
    private int $ownerId;
    private int $otherOwnerId;
    private int $adminId;
    private int $reviewerId;
    private int $inactiveId;
    private int $outsiderId;
    private int $aspirantId;
    private int $contestId;
    private int $otherContestId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorization = app(
            AspirantAuthorizationService::class
        );

        $this->ownerId = $this->createUser('owner');
        $this->otherOwnerId = $this->createUser('other-owner');
        $this->adminId = $this->createUser('admin');
        $this->reviewerId = $this->createUser('reviewer');
        $this->inactiveId = $this->createUser('inactive');
        $this->outsiderId = $this->createUser('outsider');

        $this->aspirantId = $this->createAspirant(
            $this->ownerId
        );

        $otherAspirantId = $this->createAspirant(
            $this->otherOwnerId
        );

        $this->contestId = $this->createContest(
            $this->aspirantId
        );

        $this->otherContestId = $this->createContest(
            $otherAspirantId
        );

        $this->createMembership(
            $this->aspirantId,
            $this->adminId,
            'campaign_admin',
            'active'
        );

        $this->createMembership(
            $this->aspirantId,
            $this->reviewerId,
            'campaign_reviewer',
            'active'
        );

        $this->createMembership(
            $this->aspirantId,
            $this->inactiveId,
            'campaign_admin',
            'inactive'
        );
    }

    public function test_incident_management_permission_matrix(): void
    {
        $cases = [
            'owner' => [$this->ownerId, $this->contestId, true],
            'active admin' => [$this->adminId, $this->contestId, true],
            'reviewer' => [$this->reviewerId, $this->contestId, false],
            'inactive admin' => [$this->inactiveId, $this->contestId, false],
            'outsider' => [$this->outsiderId, $this->contestId, false],
            'cross campaign admin' => [
                $this->adminId,
                $this->otherContestId,
                false
            ],
        ];

        foreach ($cases as $label => [$userId, $contestId, $expected]) {
            $this->assertSame(
                $expected,
                $this->authorization->canManageIncidents(
                    $userId,
                    $contestId
                ),
                "Service permission failed: {$label}"
            );

            $user = \App\Models\User::findOrFail($userId);

            $this->assertSame(
                $expected,
                Gate::forUser($user)->allows(
                    'campaign.manage-incidents',
                    $contestId
                ),
                "Gate permission failed: {$label}"
            );
        }
    }

    public function test_owner_has_campaign_permissions(): void
    {
        $this->assertTrue(
            $this->authorization->canViewCampaign(
                $this->ownerId,
                $this->contestId
            )
        );

        $this->assertTrue(
            $this->authorization->canReviewApplications(
                $this->ownerId,
                $this->contestId
            )
        );

        $this->assertTrue(
            $this->authorization->canAssignAgents(
                $this->ownerId,
                $this->contestId
            )
        );
    }

    public function test_active_admin_has_management_permissions(): void
    {
        $this->assertTrue(
            $this->authorization->canReviewApplications(
                $this->adminId,
                $this->contestId
            )
        );

        $this->assertTrue(
            $this->authorization->canAssignAgents(
                $this->adminId,
                $this->contestId
            )
        );
    }

    public function test_reviewer_cannot_assign_agents(): void
    {
        $this->assertTrue(
            $this->authorization->canReviewApplications(
                $this->reviewerId,
                $this->contestId
            )
        );

        $this->assertFalse(
            $this->authorization->canAssignAgents(
                $this->reviewerId,
                $this->contestId
            )
        );
    }

    public function test_inactive_membership_is_denied(): void
    {
        $this->assertFalse(
            $this->authorization->canViewCampaign(
                $this->inactiveId,
                $this->contestId
            )
        );

        $this->assertFalse(
            $this->authorization->canAssignAgents(
                $this->inactiveId,
                $this->contestId
            )
        );
    }

    public function test_unrelated_user_is_denied(): void
    {
        $this->assertFalse(
            $this->authorization->canViewCampaign(
                $this->outsiderId,
                $this->contestId
            )
        );

        $this->assertFalse(
            $this->authorization->canReviewApplications(
                $this->outsiderId,
                $this->contestId
            )
        );
    }

    public function test_cross_campaign_access_is_denied(): void
    {
        $this->assertFalse(
            $this->authorization->canViewCampaign(
                $this->adminId,
                $this->otherContestId
            )
        );

        $this->assertFalse(
            $this->authorization->canReviewApplications(
                $this->adminId,
                $this->otherContestId
            )
        );

        $this->assertFalse(
            $this->authorization->canAssignAgents(
                $this->ownerId,
                $this->otherContestId
            )
        );
    }


    public function test_gate_owner_has_all_permissions(): void
    {
        $owner = \App\Models\User::findOrFail($this->ownerId);

        $this->assertTrue(
            Gate::forUser($owner)->allows(
                'campaign.view',
                $this->contestId
            )
        );

        $this->assertTrue(
            Gate::forUser($owner)->allows(
                'campaign.review-applications',
                $this->contestId
            )
        );

        $this->assertTrue(
            Gate::forUser($owner)->allows(
                'campaign.assign-agents',
                $this->contestId
            )
        );
    }

    public function test_gate_reviewer_cannot_assign_agents(): void
    {
        $reviewer = \App\Models\User::findOrFail(
            $this->reviewerId
        );

        $this->assertTrue(
            Gate::forUser($reviewer)->allows(
                'campaign.review-applications',
                $this->contestId
            )
        );

        $this->assertFalse(
            Gate::forUser($reviewer)->allows(
                'campaign.assign-agents',
                $this->contestId
            )
        );
    }

    public function test_gate_denies_cross_campaign_access(): void
    {
        $admin = \App\Models\User::findOrFail($this->adminId);

        $this->assertFalse(
            Gate::forUser($admin)->allows(
                'campaign.view',
                $this->otherContestId
            )
        );

        $this->assertFalse(
            Gate::forUser($admin)->allows(
                'campaign.review-applications',
                $this->otherContestId
            )
        );

        $this->assertFalse(
            Gate::forUser($admin)->allows(
                'campaign.assign-agents',
                $this->otherContestId
            )
        );
    }

    private function createUser(string $suffix): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Authorization Test '.$suffix,
            'email' => 'auth-test-'.$suffix.'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAspirant(int $userId): int
    {
        return DB::table('aspirants')->insertGetId([
            'user_id' => $userId,
            'display_name' => 'Authorization Test Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createContest(int $aspirantId): int
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

    private function createMembership(
        int $aspirantId,
        int $userId,
        string $role,
        string $status
    ): void {
        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $aspirantId,
            'user_id' => $userId,
            'role' => $role,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
