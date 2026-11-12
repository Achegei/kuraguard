<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignPersonnelDashboardHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private int $contestId;
    private int $otherContestId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('owner');
        $otherOwner = $this->makeUser('other-owner');

        $this->contestId = $this->makeContest($this->owner);
        $this->otherContestId = $this->makeContest($otherOwner);
    }

    public function test_empty_personnel_summary_has_zero_counts(): void
    {
        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('No active personnel assignments yet.')
            ->assertViewHas('personnelByRole', function ($rows) {
                return count($rows) === 6
                    && array_sum(array_column($rows, 'count')) === 0;
            })
            ->assertViewHas('deploymentsByLevel', function ($rows) {
                return count($rows) === 6
                    && array_sum(array_column($rows, 'count')) === 0;
            });
    }

    public function test_active_personnel_are_grouped_by_role(): void
    {
        $this->makeAssignment(
            $this->contestId,
            'national_coordinator',
            'national',
            0,
            'National Team Member'
        );

        $this->makeAssignment(
            $this->contestId,
            'county_coordinator',
            'county',
            1,
            'County Team Member'
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertViewHas('personnelByRole', function ($rows) {
                $counts = array_column($rows, 'count', 'role');

                return $counts['national_coordinator'] === 1
                    && $counts['county_coordinator'] === 1
                    && $counts['polling_station_agent'] === 0;
            });
    }

    public function test_deployments_are_grouped_by_geography(): void
    {
        $this->makeAssignment(
            $this->contestId,
            'national_coordinator',
            'national',
            0,
            'National Geography Member'
        );

        $this->makeAssignment(
            $this->contestId,
            'county_coordinator',
            'county',
            1,
            'County Geography Member'
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertViewHas('deploymentsByLevel', function ($rows) {
                $counts = array_column($rows, 'count', 'level');

                return $counts['national'] === 1
                    && $counts['county'] === 1
                    && $counts['polling_station'] === 0;
            });
    }

    public function test_other_campaign_personnel_are_excluded(): void
    {
        $this->makeAssignment(
            $this->contestId,
            'national_coordinator',
            'national',
            0,
            'Visible Campaign Personnel'
        );

        $this->makeAssignment(
            $this->otherContestId,
            'national_coordinator',
            'national',
            0,
            'Secret Other Campaign Personnel'
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('Visible Campaign Personnel')
            ->assertDontSee('Secret Other Campaign Personnel')
            ->assertViewHas('statistics', function ($statistics) {
                return $statistics['active_assignments'] === 1;
            })
            ->assertViewHas('personnelByRole', function ($rows) {
                $counts = array_column($rows, 'count', 'role');

                return $counts['national_coordinator'] === 1;
            });
    }

    public function test_national_assignment_displays_national(): void
    {
        $this->makeAssignment(
            $this->contestId,
            'national_coordinator',
            'national',
            0,
            'National Display Member'
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('National Display Member')
            ->assertSee('National')
            ->assertDontSee('Location #0');
    }

    public function test_ended_assignment_is_excluded_from_active_dashboard(): void
    {
        $this->makeAssignment(
            $this->contestId,
            'national_coordinator',
            'national',
            0,
            'Former Campaign Coordinator'
        );

        $assignment = DB::table('field_assignments')
            ->join(
                'role_applications',
                'role_applications.id',
                '=',
                'field_assignments.role_application_id'
            )
            ->where(
                'role_applications.aspirant_contest_id',
                $this->contestId
            )
            ->select('field_assignments.id')
            ->first();

        $this->assertNotNull($assignment);

        $this->assertSame(
            1,
            DB::table('active_field_assignment_slots')
                ->where('field_assignment_id', $assignment->id)
                ->count()
        );

        app(\App\Services\FieldAssignmentService::class)
            ->endAssignment(
                $this->owner,
                $this->contestId,
                $assignment->id,
                'Dashboard termination lifecycle test'
            );

        $this->assertDatabaseHas('field_assignments', [
            'id' => $assignment->id,
            'status' => 'ended',
        ]);

        $this->assertDatabaseMissing('active_field_assignment_slots', [
            'field_assignment_id' => $assignment->id,
        ]);

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('No active personnel assignments yet.')
            ->assertViewHas('recentAssignments', function ($rows) {
                return count($rows) === 0;
            })
            ->assertViewHas('statistics', function ($statistics) {
                return $statistics['active_assignments'] === 0;
            })
            ->assertViewHas('personnelByRole', function ($rows) {
                return array_sum(array_column($rows, 'count')) === 0;
            })
            ->assertViewHas('deploymentsByLevel', function ($rows) {
                return array_sum(array_column($rows, 'count')) === 0;
            });
    }

    private function endpoint(): string
    {
        return route('campaign.dashboard', [
            'contestId' => $this->contestId,
        ]);
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => $suffix,
            'email' => 'personnel-test-'
                .uniqid().'-'.$suffix.'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function makeContest(User $owner): int
    {
        $aspirantId = DB::table('aspirants')->insertGetId([
            'user_id' => $owner->id,
            'display_name' => 'Personnel Test Aspirant',
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

    private function makeAssignment(
        int $contestId,
        string $role,
        string $level,
        int $geographicId,
        string $name
    ): void {
        $person = $this->makeUser($name);

        $applicationId = DB::table('role_applications')
            ->insertGetId([
                'aspirant_contest_id' => $contestId,
                'applicant_user_id' => $person->id,
                'electoral_dataset_id' => 1,
                'requested_role' => $role,
                'geographic_level' => $level,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $assignmentId = DB::table('field_assignments')
            ->insertGetId([
                'role_application_id' => $applicationId,
                'assigned_by' => $this->owner->id,
                'status' => 'assigned',
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('active_field_assignment_slots')->insert([
            'field_assignment_id' => $assignmentId,
            'aspirant_contest_id' => $contestId,
            'applicant_user_id' => $person->id,
            'electoral_dataset_id' => 1,
            'requested_role' => $role,
            'geographic_level' => $level,
            'geographic_id' => $geographicId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
