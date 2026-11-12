<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CampaignDashboardController extends Controller
{
    public function index(
        Request $request,
        int $contestId
    ): View {
        abort_unless(
            $request->user() !== null,
            401
        );

        $contest = DB::table('aspirant_contests as contests')
            ->join(
                'aspirants as aspirants',
                'aspirants.id',
                '=',
                'contests.aspirant_id'
            )
            ->where('contests.id', $contestId)
            ->select([
                'contests.id',
                'contests.office',
                'contests.election_year',
                'contests.declaration_status',
                'contests.electoral_dataset_id',
                'aspirants.display_name',
            ])
            ->first();

        abort_if($contest === null, 404);

        $applications = DB::table('role_applications')
            ->where('aspirant_contest_id', $contestId);

        $statistics = [
            'total_applications' => (clone $applications)->count(),
            'pending_applications' => (clone $applications)
                ->where('status', 'pending')
                ->count(),
            'approved_applications' => (clone $applications)
                ->where('status', 'approved')
                ->count(),
            'rejected_applications' => (clone $applications)
                ->where('status', 'rejected')
                ->count(),
            'active_assignments' => DB::table(
                'active_field_assignment_slots'
            )
                ->where('aspirant_contest_id', $contestId)
                ->count(),
        ];

        $recentApplications = DB::table(
            'role_applications as applications'
        )
            ->join(
                'users as applicants',
                'applicants.id',
                '=',
                'applications.applicant_user_id'
            )
            ->where(
                'applications.aspirant_contest_id',
                $contestId
            )
            ->select([
                'applications.id',
                'applications.requested_role',
                'applications.geographic_level',
                'applications.status',
                'applications.created_at',
                'applicants.name as applicant_name',
            ])
            ->orderByDesc('applications.created_at')
            ->orderByDesc('applications.id')
            ->limit(20)
            ->get();

        $roleLabels = [
            'national_coordinator' => 'National Coordinator',
            'county_coordinator' => 'County Coordinator',
            'constituency_coordinator' => 'Constituency Coordinator',
            'ward_coordinator' => 'Ward Coordinator',
            'polling_centre_coordinator' => 'Polling Centre Coordinator',
            'polling_station_agent' => 'Polling Station Agent',
        ];

        $levelLabels = [
            'national' => 'National',
            'county' => 'County',
            'constituency' => 'Constituency',
            'ward' => 'Ward',
            'polling_centre' => 'Polling Centre',
            'polling_station' => 'Polling Station',
        ];

        $roleCounts = DB::table('active_field_assignment_slots')
            ->where('aspirant_contest_id', $contestId)
            ->select('requested_role')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('requested_role')
            ->pluck('total', 'requested_role');

        $levelCounts = DB::table('active_field_assignment_slots')
            ->where('aspirant_contest_id', $contestId)
            ->select('geographic_level')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('geographic_level')
            ->pluck('total', 'geographic_level');

        $personnelByRole = [];

        foreach ($roleLabels as $role => $label) {
            $personnelByRole[] = [
                'role' => $role,
                'label' => $label,
                'count' => (int) ($roleCounts[$role] ?? 0),
            ];
        }

        $deploymentsByLevel = [];

        foreach ($levelLabels as $level => $label) {
            $deploymentsByLevel[] = [
                'level' => $level,
                'label' => $label,
                'count' => (int) ($levelCounts[$level] ?? 0),
            ];
        }

        $recentAssignments = DB::table(
            'active_field_assignment_slots as slots'
        )
            ->join(
                'users as personnel',
                'personnel.id',
                '=',
                'slots.applicant_user_id'
            )
            ->where('slots.aspirant_contest_id', $contestId)
            ->select([
                'slots.id',
                'slots.requested_role',
                'slots.geographic_level',
                'slots.geographic_id',
                'slots.created_at',
                'personnel.name as personnel_name',
            ])
            ->orderByDesc('slots.created_at')
            ->orderByDesc('slots.id')
            ->limit(20)
            ->get();

        // Campaign-specific election-day operations statistics.
        $stationReports = DB::table('campaign_station_reports')
            ->where('aspirant_contest_id', $contestId);

        $incidents = DB::table('campaign_incidents')
            ->where('aspirant_contest_id', $contestId);

        $resultSubmissions = DB::table('campaign_result_submissions')
            ->where('aspirant_contest_id', $contestId);

        $verifiedResults = (clone $resultSubmissions)
            ->where('status', 'verified');

        $operationsStatistics = [
            'station_reports' => (clone $stationReports)
                ->distinct()
                ->count('electoral_polling_station_id'),

            'checked_in_stations' => (clone $stationReports)
                ->where('attendance_status', 'checked_in')
                ->distinct()
                ->count('electoral_polling_station_id'),

            'open_incidents' => (clone $incidents)
                ->whereIn('status', [
                    'open',
                    'acknowledged',
                    'investigating',
                    'escalated',
                ])
                ->count(),

            'critical_incidents' => (clone $incidents)
                ->whereIn('status', [
                    'open',
                    'acknowledged',
                    'investigating',
                    'escalated',
                ])
                ->where('severity', 'critical')
                ->count(),

            'pending_results' => (clone $resultSubmissions)
                ->where('status', 'pending')
                ->count(),

            'verified_results' => (clone $verifiedResults)
                ->count(),

            'verified_valid_votes' => (clone $verifiedResults)
                ->sum('valid_votes'),

            'verified_rejected_votes' => (clone $verifiedResults)
                ->sum('rejected_votes'),
        ];

        return view(
            'campaigns.dashboard',
            compact(
                'contest',
                'statistics',
                'recentApplications',
                'personnelByRole',
                'deploymentsByLevel',
                'recentAssignments',
                'operationsStatistics'
            )
        );
    }
}
