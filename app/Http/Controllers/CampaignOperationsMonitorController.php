<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampaignOperationsMonitorController extends Controller
{
    public function index(Request $request, int $contestId): View
    {
        abort_unless(
            Gate::forUser($request->user())->allows(
                'campaign.view',
                $contestId
            ),
            403
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
                'contests.electoral_dataset_id',
                'aspirants.display_name',
            ])
            ->first();

        abort_if($contest === null, 404);

        $stationReports = DB::table('campaign_station_reports')
            ->where('aspirant_contest_id', $contestId)
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $incidents = DB::table('campaign_incidents')
            ->where('aspirant_contest_id', $contestId)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $resultSubmissions = DB::table(
            'campaign_result_submissions as submissions'
        )
            ->leftJoin(
                'campaign_result_evidence as evidence',
                'evidence.campaign_result_submission_id',
                '=',
                'submissions.id'
            )
            ->where('submissions.aspirant_contest_id', $contestId)
            ->select([
                'submissions.id',
                'submissions.electoral_polling_station_id',
                'submissions.submitted_by',
                'submissions.valid_votes',
                'submissions.rejected_votes',
                'submissions.status',
                'submissions.submitted_at',
                'submissions.created_at',
            ])
            ->selectRaw('COUNT(evidence.id) AS evidence_count')
            ->groupBy(
                'submissions.id',
                'submissions.electoral_polling_station_id',
                'submissions.submitted_by',
                'submissions.valid_votes',
                'submissions.rejected_votes',
                'submissions.status',
                'submissions.submitted_at',
                'submissions.created_at'
            )
            ->orderByDesc('submissions.created_at')
            ->limit(100)
            ->get();

        $statistics = [
            'station_reports' => DB::table('campaign_station_reports')
                ->where('aspirant_contest_id', $contestId)
                ->distinct()
                ->count('electoral_polling_station_id'),

            'checked_in_stations' => DB::table('campaign_station_reports')
                ->where('aspirant_contest_id', $contestId)
                ->where('attendance_status', 'checked_in')
                ->distinct()
                ->count('electoral_polling_station_id'),

            'open_incidents' => DB::table('campaign_incidents')
                ->where('aspirant_contest_id', $contestId)
                ->where('status', '!=', 'resolved')
                ->count(),

            'pending_results' => DB::table('campaign_result_submissions')
                ->where('aspirant_contest_id', $contestId)
                ->where('status', 'pending')
                ->count(),

            'verified_results' => DB::table('campaign_result_submissions')
                ->where('aspirant_contest_id', $contestId)
                ->where('status', 'verified')
                ->count(),
        ];

        $canManageIncidents = Gate::forUser($request->user())->allows(
            'campaign.manage-incidents',
            $contestId
        );

        $canReviewResults = Gate::forUser($request->user())->allows(
            'campaign.review-applications',
            $contestId
        );

        $incidentHistory = collect();

        if ($incidents->isNotEmpty()) {
            $incidentIds = $incidents
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $incidentHistory = DB::table(
                'campaign_incident_status_history as history'
            )
                ->join(
                    'users as actors',
                    'actors.id',
                    '=',
                    'history.changed_by'
                )
                ->where(
                    'history.aspirant_contest_id',
                    $contestId
                )
                ->whereIn(
                    'history.campaign_incident_id',
                    $incidentIds
                )
                ->select([
                    'history.id',
                    'history.campaign_incident_id',
                    'history.from_status',
                    'history.to_status',
                    'history.notes',
                    'history.created_at',
                    'actors.name as changed_by_name',
                ])
                ->orderBy('history.created_at')
                ->orderBy('history.id')
                ->get()
                ->groupBy('campaign_incident_id');
        }

        $evidenceBySubmission = collect();

        if ($canReviewResults && $resultSubmissions->isNotEmpty()) {
            $submissionIds = $resultSubmissions
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $evidenceBySubmission = DB::table(
                'campaign_result_evidence as evidence'
            )
                ->join(
                    'campaign_result_submissions as submissions',
                    'submissions.id',
                    '=',
                    'evidence.campaign_result_submission_id'
                )
                ->where(
                    'submissions.aspirant_contest_id',
                    $contestId
                )
                ->whereIn('submissions.id', $submissionIds)
                ->select([
                    'evidence.id',
                    'evidence.campaign_result_submission_id',
                    'evidence.form_type',
                ])
                ->orderBy('evidence.id')
                ->get()
                ->groupBy('campaign_result_submission_id');
        }

        return view('campaigns.operations-monitor', compact(
            'contest',
            'stationReports',
            'incidents',
            'resultSubmissions',
            'statistics',
            'canReviewResults',
            'canManageIncidents',
            'incidentHistory',
            'evidenceBySubmission'
        ));
    }
}
