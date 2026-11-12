<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignLandingHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('campaign.landing'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_campaign_goes_to_command_centre(): void
    {
        $user = $this->makeUser('no-campaign');

        $this->actingAs($user)
            ->get(route('campaign.landing'))
            ->assertRedirect(route('command-centre.index'));
    }

    public function test_single_campaign_redirects_to_dashboard(): void
    {
        $owner = $this->makeUser('single');
        $contestId = $this->makeContest($owner);

        $this->actingAs($owner)
            ->get(route('campaign.landing'))
            ->assertRedirect(
                route('campaign.dashboard', [
                    'contestId' => $contestId,
                ])
            );
    }

    public function test_multiple_campaigns_display_selection_page(): void
    {
        $user = $this->makeUser('multiple');

        $this->makeContest(
            $user,
            'First Campaign'
        );

        $otherOwner = $this->makeUser('other-owner');

        $this->makeContest(
            $otherOwner,
            'Second Campaign'
        );

        $this->makeMembership(
            $otherOwner,
            $user
        );

        $this->actingAs($user)
            ->get(route('campaign.landing'))
            ->assertOk()
            ->assertViewIs('campaigns.select')
            ->assertSee('First Campaign')
            ->assertSee('Second Campaign');
    }

    public function test_selection_excludes_unauthorized_campaigns(): void
    {
        $user = $this->makeUser('authorized');

        $this->makeContest(
            $user,
            'Visible Campaign'
        );

        $memberOwner = $this->makeUser('member-owner');

        $this->makeContest(
            $memberOwner,
            'Shared Campaign'
        );

        $this->makeMembership(
            $memberOwner,
            $user
        );

        $outsider = $this->makeUser('outsider-owner');

        $this->makeContest(
            $outsider,
            'Private Campaign'
        );

        $this->actingAs($user)
            ->get(route('campaign.landing'))
            ->assertOk()
            ->assertSee('Visible Campaign')
            ->assertSee('Shared Campaign')
            ->assertDontSee('Private Campaign');
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Landing HTTP '.$suffix,
            'email' => 'landing-http-'.uniqid().'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function makeContest(
        User $owner,
        string $displayName = 'Test Campaign'
    ): int {
        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id,
            'display_name' => $displayName,
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
        User $owner,
        User $member
    ): void {
        $aspirantId = DB::table('aspirants')
            ->where('user_id', $owner->id)
            ->value('id');

        DB::table('aspirant_memberships')->insert([
            'aspirant_id' => $aspirantId,
            'user_id' => $member->id,
            'role' => 'campaign_admin',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
