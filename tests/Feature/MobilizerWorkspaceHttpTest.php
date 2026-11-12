<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobilizerWorkspaceHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('mobilizer.workspace'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_unassigned_user_is_denied(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('mobilizer.workspace'))
            ->assertForbidden();
    }

    public function test_authenticated_user_with_pending_application_is_denied(): void
    {
        $user = $this->makeUser();

        $this->makeApplication($user, 'pending');

        $this->actingAs($user)
            ->get(route('mobilizer.workspace'))
            ->assertForbidden();
    }

    public function test_approved_mobilizer_awaiting_deployment_sees_status(): void
    {
        $user = $this->makeUser();

        $this->makeApplication($user, 'approved');

        $this->actingAs($user)
            ->get(route('mobilizer.workspace'))
            ->assertOk()
            ->assertSee('Awaiting deployment');
    }


    public function test_active_mobilizer_sees_deployment(): void
    {
        $user = $this->makeUser();
        $applicationId = $this->makeApplication($user, 'approved');
        $this->makeDeployment($applicationId, $user);

        $this->actingAs($user)
            ->get(route('mobilizer.workspace'))
            ->assertOk()
            ->assertSee('Active deployment')
            ->assertDontSee('Awaiting deployment');
    }

    public function test_ended_deployment_is_not_active(): void
    {
        $user = $this->makeUser();
        $applicationId = $this->makeApplication($user, 'approved');
        $this->makeDeployment($applicationId, $user, 'ended');

        $this->actingAs($user)
            ->get(route('mobilizer.workspace'))
            ->assertOk()
            ->assertSee('Awaiting deployment')
            ->assertDontSee('Active deployment');
    }

    public function test_polling_station_agent_cannot_access_workspace(): void
    {
        $user = $this->makeUser();
        $applicationId = $this->makeApplication(
            $user,
            'approved',
            'polling_station_agent'
        );

        $this->makeDeployment($applicationId, $user);

        $this->actingAs($user)
            ->get(route('mobilizer.workspace'))
            ->assertForbidden();
    }

    public function test_mismatched_slot_user_does_not_grant_access(): void
    {
        $user = $this->makeUser();
        $otherUser = $this->makeUser();

        $applicationId = $this->makeApplication($user, 'approved');

        $this->makeDeployment(
            $applicationId,
            $otherUser
        );

        $this->actingAs($otherUser)
            ->get(route('mobilizer.workspace'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('mobilizer.workspace'))
            ->assertOk()
            ->assertSee('Awaiting deployment');
    }

    private function makeDeployment(
        int $applicationId,
        User $slotUser,
        string $status = 'assigned'
    ): void {
        $application = DB::table('role_applications')
            ->where('id', $applicationId)
            ->first();

        $assignmentId = DB::table('field_assignments')
            ->insertGetId([
                'role_application_id' => $applicationId,
                'assigned_by' => $application->applicant_user_id,
                'status' => $status,
                'assigned_at' => now(),
                'ended_at' => $status === 'ended' ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('active_field_assignment_slots')->insert([
            'field_assignment_id' => $assignmentId,
            'aspirant_contest_id' => $application->aspirant_contest_id,
            'applicant_user_id' => $slotUser->id,
            'electoral_dataset_id' => $application->electoral_dataset_id,
            'requested_role' => $application->requested_role,
            'geographic_level' => $application->geographic_level,
            'geographic_id' => $application->county_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Workspace Test Mobilizer',
            'email' => 'workspace-'.bin2hex(random_bytes(8)).'@example.test',
            'password' => bcrypt('test-password'),
        ]);
    }

    private function makeApplication(
        User $user,
        string $status,
        string $role = 'mobilizer'
    ): int
    {
        $owner = $this->makeUser();

        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id,
            'display_name' => 'Workspace Test Campaign',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contestId = DB::table('aspirant_contests')->insertGetId([
            'contest_identity_key' => 'workspace-'.bin2hex(random_bytes(16)),
            'aspirant_id' => $aspirantId,
            'electoral_dataset_id' => 1,
            'election_year' => 2027,
            'office' => 'president',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'applicant_user_id' => $user->id,
            'electoral_dataset_id' => 1,
            'requested_role' => $role,
            'geographic_level' => 'county',
            'county_id' => 1,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
