<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignDashboardHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $outsider;
    private User $applicant;
    private int $contestId;
    private int $otherContestId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('owner');
        $this->outsider = $this->makeUser('outsider');
        $this->applicant = $this->makeUser('applicant');

        $otherOwner = $this->makeUser('other-owner');

        $aspirantId = $this->makeAspirant(
            $this->owner,
            'Dashboard Primary Aspirant'
        );

        $otherAspirantId = $this->makeAspirant(
            $otherOwner,
            'Dashboard Other Aspirant'
        );

        $this->contestId = $this->makeContest($aspirantId);
        $this->otherContestId = $this->makeContest(
            $otherAspirantId
        );
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->getJson($this->endpoint())
            ->assertUnauthorized();
    }

    public function test_owner_can_view_dashboard(): void
    {
        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('Campaign Dashboard')
            ->assertSee('Dashboard Primary Aspirant');
    }

    public function test_outsider_is_forbidden(): void
    {
        $this->actingAs($this->outsider)
            ->get($this->endpoint())
            ->assertForbidden();
    }

    public function test_owner_cannot_access_another_campaign(): void
    {
        $this->actingAs($this->owner)
            ->get($this->endpoint($this->otherContestId))
            ->assertForbidden();
    }

    public function test_application_statistics_are_contest_scoped(): void
    {
        $this->makeApplication(
            $this->contestId,
            'pending'
        );

        $this->makeApplication(
            $this->otherContestId,
            'approved'
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertViewHas('statistics', function ($statistics) {
                return $statistics['total_applications'] === 1
                    && $statistics['pending_applications'] === 1
                    && $statistics['approved_applications'] === 0;
            });
    }

    public function test_other_campaign_applications_are_not_visible(): void
    {
        $this->makeApplication(
            $this->contestId,
            'pending',
            'national_coordinator'
        );

        $this->makeApplication(
            $this->otherContestId,
            'approved',
            'other_campaign_secret_role'
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('national coordinator')
            ->assertDontSee('other campaign secret role');
    }

    public function test_empty_dashboard_renders(): void
    {
        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee(
                'No role applications have been submitted yet.'
            )
            ->assertViewHas('statistics', function ($statistics) {
                return $statistics['total_applications'] === 0
                    && $statistics['active_assignments'] === 0;
            });
    }


    public function test_election_day_statistics_are_zero_without_reports(): void
    {
        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('Election-Day Operations')
            ->assertViewHas('operationsStatistics', function ($statistics) {
                return count($statistics) === 8
                    && array_sum(array_map('intval', $statistics)) === 0;
            });
    }

    public function test_election_day_statistics_count_operational_records(): void
    {
        $stationId = $this->registeredStationId();

        $assignmentId = $this->makeStationAssignment($stationId);

        DB::table('campaign_station_reports')->insert([
            'aspirant_contest_id' => $this->contestId,
            'electoral_polling_station_id' => $stationId,
            'field_assignment_id' => $assignmentId,
            'attendance_status' => 'checked_in',
            'checked_in_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('campaign_incidents')->insert([
            'aspirant_contest_id' => $this->contestId,
            'electoral_polling_station_id' => $stationId,
            'reported_by' => $this->applicant->id,
            'category' => 'voter_assistance',
            'severity' => 'critical',
            'description' => 'Test critical incident.',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->makeResultSubmission($this->contestId, $stationId, 'pending', 900, 20);
        $this->makeResultSubmission($this->contestId, $stationId, 'verified', 100, 3);

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertViewHas('operationsStatistics', function ($statistics) {
                return $statistics === [
                    'station_reports' => 1,
                    'checked_in_stations' => 1,
                    'open_incidents' => 1,
                    'critical_incidents' => 1,
                    'pending_results' => 1,
                    'verified_results' => 1,
                    'verified_valid_votes' => 100,
                    'verified_rejected_votes' => 3,
                ];
            });
    }

    public function test_election_day_statistics_exclude_other_campaign(): void
    {
        $stationId = $this->registeredStationId();

        DB::table('campaign_incidents')->insert([
            'aspirant_contest_id' => $this->otherContestId,
            'electoral_polling_station_id' => $stationId,
            'reported_by' => $this->applicant->id,
            'category' => 'other_campaign',
            'severity' => 'critical',
            'description' => 'Other campaign private incident.',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->makeResultSubmission(
            $this->otherContestId,
            $stationId,
            'verified',
            5000,
            100
        );

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertViewHas('operationsStatistics', function ($statistics) {
                return $statistics['open_incidents'] === 0
                    && $statistics['critical_incidents'] === 0
                    && $statistics['verified_results'] === 0
                    && $statistics['verified_valid_votes'] == 0
                    && $statistics['verified_rejected_votes'] == 0;
            });
    }


    public function test_incident_statistics_cover_all_lifecycle_statuses(): void
    {
        $stationId = $this->registeredStationId();

        $incidents = [
            ['open', 'critical'],
            ['acknowledged', 'critical'],
            ['investigating', 'high'],
            ['escalated', 'critical'],
            ['resolved', 'critical'],
        ];

        foreach ($incidents as [$status, $severity]) {
            DB::table('campaign_incidents')->insert([
                'aspirant_contest_id' => $this->contestId,
                'electoral_polling_station_id' => $stationId,
                'reported_by' => $this->applicant->id,
                'category' => 'lifecycle_statistics_test',
                'severity' => $severity,
                'description' => 'Lifecycle status: '.$status,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('campaign_incidents')->insert([
            'aspirant_contest_id' => $this->otherContestId,
            'electoral_polling_station_id' => $stationId,
            'reported_by' => $this->applicant->id,
            'category' => 'other_campaign_statistics_test',
            'severity' => 'critical',
            'description' => 'Other campaign unresolved incident.',
            'status' => 'escalated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->get($this->endpoint())
            ->assertOk()
            ->assertSee('Unresolved Incidents')
            ->assertSee('Unresolved Critical Incidents')
            ->assertViewHas(
                'operationsStatistics',
                function ($statistics) {
                    return $statistics['open_incidents'] === 4
                        && $statistics['critical_incidents'] === 3;
                }
            );
    }

    private function registeredStationId(): int
    {
        $stationId = (int) DB::table('electoral_register_snapshots')
            ->where('dataset_id', 1)
            ->value('polling_station_id');

        if ($stationId < 1) {
            throw new \RuntimeException(
                'Disposable electoral dataset has no registered polling station.'
            );
        }

        return $stationId;
    }

    private function makeStationAssignment(int $stationId): int
    {
        $applicationId = DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $this->contestId,
            'applicant_user_id' => $this->applicant->id,
            'electoral_dataset_id' => 1,
            'requested_role' => 'polling_station_agent',
            'geographic_level' => 'polling_station',
            'polling_station_id' => $stationId,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('field_assignments')->insertGetId([
            'role_application_id' => $applicationId,
            'assigned_by' => $this->owner->id,
            'status' => 'assigned',
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeResultSubmission(
        int $contestId,
        int $stationId,
        string $status,
        int $validVotes,
        int $rejectedVotes
    ): int {
        return DB::table('campaign_result_submissions')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'electoral_polling_station_id' => $stationId,
            'submitted_by' => $this->applicant->id,
            'valid_votes' => $validVotes,
            'rejected_votes' => $rejectedVotes,
            'candidate_votes' => json_encode([
                'candidate_a' => $validVotes,
            ]),
            'status' => $status,
            'submitted_at' => now(),
            'verified_at' => $status === 'verified' ? now() : null,
            'verified_by' => $status === 'verified'
                ? $this->owner->id
                : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function endpoint(?int $contestId = null): string
    {
        return route('campaign.dashboard', [
            'contestId' => $contestId ?? $this->contestId,
        ]);
    }

    private function makeUser(string $suffix): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Dashboard HTTP '.$suffix,
            'email' => 'dashboard-http-'.$suffix.'@example.test',
            'password' => bcrypt('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function makeAspirant(
        User $user,
        string $displayName
    ): int {
        return DB::table('aspirants')->insertGetId([
            'user_id' => $user->id,
            'display_name' => $displayName,
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

    private function makeApplication(
        int $contestId,
        string $status,
        string $role = 'national_coordinator'
    ): int {
        return DB::table('role_applications')->insertGetId([
            'aspirant_contest_id' => $contestId,
            'applicant_user_id' => $this->applicant->id,
            'electoral_dataset_id' => 1,
            'requested_role' => $role,
            'geographic_level' => 'national',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
