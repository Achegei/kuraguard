<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgentActivationIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Activation Test User',
            'email' => 'activation-'.bin2hex(random_bytes(8)).'@example.test',
            'password' => bcrypt('TestPassword123'),
        ]);
    }

    private function makeInvitation(
        string $role = 'mobilizer',
        bool $expired = false
    ): array {
        $owner = $this->makeUser();

        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id,
            'display_name' => 'Activation Test Campaign',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contestId = DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'activation-'.bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $email = 'invite-'.bin2hex(random_bytes(8)).'@example.test';

        $level = $role === 'polling_station_agent'
            ? 'polling_station'
            : 'county';

        $applicationId = DB::table('public_role_applications')
            ->insertGetId([
                'aspirant_contest_id' => $contestId,
                'requested_role' => $role,
                'full_name' => 'Invited Field Applicant',
                'email' => $email,
                'phone' => '+254700000001',
                'electoral_dataset_id' => 1,
                'geographic_level' => $level,
                'county_id' => 1,
                'constituency_id' => $level === 'polling_station' ? 1 : null,
                'ward_id' => $level === 'polling_station' ? 1 : null,
                'polling_centre_id' => $level === 'polling_station' ? 1 : null,
                'polling_station_id' => $level === 'polling_station' ? 1 : null,
                'status' => 'approved',
                'reviewed_by' => $owner->id,
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $token = bin2hex(random_bytes(32));

        $invitationId = DB::table('agent_activation_invitations')
            ->insertGetId([
                'public_role_application_id' => $applicationId,
                'aspirant_contest_id' => $contestId,
                'email' => $email,
                'token_hash' => hash('sha256', $token),
                'invited_by' => $owner->id,
                'expires_at' => $expired
                    ? now()->subMinute()
                    : now()->addDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return [
            'token' => $token,
            'email' => $email,
            'application_id' => $applicationId,
            'invitation_id' => $invitationId,
            'contest_id' => $contestId,
        ];
    }

    public function test_new_mobilizer_activation_creates_account_and_links_application(): void
    {
        $fixture = $this->makeInvitation();

        $response = $this->post(
            route('agent.activation.store', [
                'token' => $fixture['token'],
            ]),
            [
                'password' => 'SecurePassword123',
                'password_confirmation' => 'SecurePassword123',
            ]
        );

        $response->assertRedirect(route('mobilizer.workspace'));

        $user = User::where('email', $fixture['email'])->firstOrFail();

        $this->assertAuthenticatedAs($user);

        $publicApplication = DB::table('public_role_applications')
            ->where('id', $fixture['application_id'])
            ->first();

        $this->assertEquals(
            $user->id,
            $publicApplication->linked_user_id
        );

        $this->assertNotNull(
            $publicApplication->linked_role_application_id
        );

        $this->assertDatabaseHas('role_applications', [
            'id' => $publicApplication->linked_role_application_id,
            'applicant_user_id' => $user->id,
            'requested_role' => 'mobilizer',
            'status' => 'approved',
        ]);

        $this->assertNotNull(
            DB::table('agent_activation_invitations')
                ->where('id', $fixture['invitation_id'])
                ->value('used_at')
        );
    }


    public function test_existing_account_can_activate_matching_invitation(): void
    {
        $fixture = $this->makeInvitation();

        $user = User::create([
            'name' => 'Existing Mobilizer',
            'email' => $fixture['email'],
            'password' => bcrypt('ExistingPassword123'),
        ]);

        $this->actingAs($user)
            ->post(route('agent.activation.store', [
                'token' => $fixture['token'],
            ]))
            ->assertRedirect(route('mobilizer.workspace'));

        $this->assertDatabaseHas('public_role_applications', [
            'id' => $fixture['application_id'],
            'linked_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('role_applications', [
            'applicant_user_id' => $user->id,
            'requested_role' => 'mobilizer',
            'status' => 'approved',
        ]);
    }

    public function test_wrong_authenticated_account_cannot_activate_invitation(): void
    {
        $fixture = $this->makeInvitation();

        $wrongUser = $this->makeUser();

        $this->actingAs($wrongUser)
            ->post(route('agent.activation.store', [
                'token' => $fixture['token'],
            ]), [
                'password' => 'SecurePassword123',
                'password_confirmation' => 'SecurePassword123',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => $fixture['email'],
        ]);

        $this->assertNull(
            DB::table('public_role_applications')
                ->where('id', $fixture['application_id'])
                ->value('linked_user_id')
        );
    }

    public function test_revoked_invitation_is_rejected(): void
    {
        $fixture = $this->makeInvitation();

        DB::table('agent_activation_invitations')
            ->where('id', $fixture['invitation_id'])
            ->update(['revoked_at' => now()]);

        $this->post(route('agent.activation.store', [
            'token' => $fixture['token'],
        ]), [
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', [
            'email' => $fixture['email'],
        ]);
    }

    public function test_polling_station_agent_retains_existing_activation_redirect(): void
    {
        $fixture = $this->makeInvitation('polling_station_agent');

        $this->post(route('agent.activation.store', [
            'token' => $fixture['token'],
        ]), [
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ])->assertRedirect(route('campaign.landing'));

        $this->assertDatabaseHas('role_applications', [
            'aspirant_contest_id' => $fixture['contest_id'],
            'requested_role' => 'polling_station_agent',
            'geographic_level' => 'polling_station',
            'status' => 'approved',
        ]);
    }

    public function test_invalid_invitation_token_is_rejected(): void
    {
        $this->post(route('agent.activation.store', [
            'token' => str_repeat('a', 64),
        ]), [
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ])->assertNotFound();
    }

    public function test_used_invitation_cannot_be_replayed(): void
    {
        $fixture = $this->makeInvitation();

        DB::table('agent_activation_invitations')
            ->where('id', $fixture['invitation_id'])
            ->update(['used_at' => now()]);

        $this->post(
            route('agent.activation.store', [
                'token' => $fixture['token'],
            ]),
            [
                'password' => 'SecurePassword123',
                'password_confirmation' => 'SecurePassword123',
            ]
        )->assertNotFound();

        $this->assertDatabaseMissing('users', [
            'email' => $fixture['email'],
        ]);
    }

    public function test_expired_invitation_is_rejected(): void
    {
        $fixture = $this->makeInvitation(expired: true);

        $this->post(
            route('agent.activation.store', [
                'token' => $fixture['token'],
            ]),
            [
                'password' => 'SecurePassword123',
                'password_confirmation' => 'SecurePassword123',
            ]
        )->assertNotFound();

        $this->assertDatabaseMissing('users', [
            'email' => $fixture['email'],
        ]);
    }
}
