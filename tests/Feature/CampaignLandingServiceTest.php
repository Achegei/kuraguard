<?php

namespace Tests\Feature;

use App\Services\CampaignLandingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignLandingServiceTest extends TestCase
{
    use DatabaseTransactions;

    private CampaignLandingService $landing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->landing = app(CampaignLandingService::class);
    }

    public function test_owner_discovers_own_campaign(): void
    {
        $owner = $this->makeUser('owner');
        $contest = $this->makeContest($owner);

        $ids = $this->landing
            ->authorizedContests($owner)
            ->pluck('id')
            ->all();

        $this->assertEquals([$contest], $ids);
    }

    public function test_active_member_discovers_campaign(): void
    {
        $owner = $this->makeUser('owner-active');
        $member = $this->makeUser('member-active');

        $contest = $this->makeContest($owner);

        $this->makeMembership(
            $owner,
            $member,
            'campaign_admin',
            'active'
        );

        $ids = $this->landing
            ->authorizedContests($member)
            ->pluck('id')
            ->all();

        $this->assertEquals([$contest], $ids);
    }

    public function test_inactive_member_cannot_discover_campaign(): void
    {
        $owner = $this->makeUser('owner-inactive');
        $member = $this->makeUser('member-inactive');

        $this->makeContest($owner);

        $this->makeMembership(
            $owner,
            $member,
            'campaign_admin',
            'inactive'
        );

        $this->assertCount(
            0,
            $this->landing->authorizedContests($member)
        );
    }

    public function test_multiple_memberships_do_not_duplicate_campaign(): void
    {
        $owner = $this->makeUser('owner-duplicate');
        $member = $this->makeUser('member-duplicate');

        $contest = $this->makeContest($owner);

        $this->makeMembership(
            $owner,
            $member,
            'campaign_admin',
            'active'
        );

        $this->makeMembership(
            $owner,
            $member,
            'campaign_reviewer',
            'active'
        );

        $ids = $this->landing
            ->authorizedContests($member)
            ->pluck('id')
            ->all();

        $this->assertEquals([$contest], $ids);
    }

    public function test_unrelated_user_cannot_discover_campaign(): void
    {
        $owner = $this->makeUser('owner-isolation');
        $outsider = $this->makeUser('outsider');

        $this->makeContest($owner);

        $this->assertCount(
            0,
            $this->landing->authorizedContests($outsider)
        );
    }

    private function makeUser(string $suffix): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Landing Test '.$suffix,
            'email' => 'landing-'.uniqid().'-'.$suffix.'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeContest(int $ownerId): int
    {
        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $ownerId,
            'display_name' => 'Landing Test Aspirant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
        int $ownerId,
        int $memberId,
        string $role,
        string $status
    ): void {
        $aspirantId = DB::table('aspirants')
            ->where('user_id', $ownerId)
            ->value('id');

        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $aspirantId,
            'user_id' => $memberId,
            'role' => $role,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
