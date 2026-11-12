<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignLoginRedirectTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Campaign Login Test',
            'email' => 'campaign-login-'.uniqid().'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    public function test_successful_login_redirects_to_campaign_landing(): void
    {
        $user = $this->makeUser();

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'test-password',
        ])->assertRedirect(route('campaign.landing'));

        $this->assertAuthenticatedAs($user);
    }


    public function test_approved_mobilizer_login_redirects_to_workspace(): void
    {
        $user = $this->makeUser();

        DB::table('role_applications')->insert([
            'aspirant_contest_id' => $this->makeContest(),
            'applicant_user_id' => $user->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'mobilizer',
            'geographic_level' => 'county',
            'county_id' => 1,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'test-password',
        ])->assertRedirect(route('mobilizer.workspace'));
    }

    public function test_pending_mobilizer_login_keeps_existing_destination(): void
    {
        $user = $this->makeUser();

        DB::table('role_applications')->insert([
            'aspirant_contest_id' => $this->makeContest(),
            'applicant_user_id' => $user->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'mobilizer',
            'geographic_level' => 'county',
            'county_id' => 1,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'test-password',
        ])->assertRedirect(route('campaign.landing'));
    }

    public function test_mobilizer_login_preserves_intended_destination(): void
    {
        $user = $this->makeUser();

        DB::table('role_applications')->insert([
            'aspirant_contest_id' => $this->makeContest(),
            'applicant_user_id' => $user->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'mobilizer',
            'geographic_level' => 'county',
            'county_id' => 1,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'url.intended' => route('mobilizer.workspace'),
        ])->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'test-password',
        ])->assertRedirect(route('mobilizer.workspace'));
    }

    private function makeContest(): int
    {
        $owner = $this->makeUser();

        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id,
            'display_name' => 'Login Test Campaign',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'login-'.bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }


    public function test_campaign_owner_with_mobilizer_role_keeps_campaign_landing(): void
    {
        $user = $this->makeUser();

        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $user->id,
            'display_name' => 'Mixed Role Campaign',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ownedContestId = DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'mixed-'.bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_applications')->insert([
            'aspirant_contest_id' => $ownedContestId,
            'applicant_user_id' => $user->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'mobilizer',
            'geographic_level' => 'county',
            'county_id' => 1,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'test-password',
        ])->assertRedirect(route('campaign.landing'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = $this->makeUser();

        $this->from(route('login'))
            ->post(route('login.submit'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_intended_destination_is_preserved(): void
    {
        $user = $this->makeUser();

        $this->withSession([
            'url.intended' => route('command-centre.index'),
        ])->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'test-password',
        ])->assertRedirect(route('command-centre.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalidates_authenticated_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guest_cannot_access_logout_route(): void
    {
        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
