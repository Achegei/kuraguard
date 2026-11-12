<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampaignReportPageController extends Controller
{
    private function contest(int $contestId): object
    {
        return DB::table('aspirant_contests as contests')
            ->join(
                'aspirants as aspirants',
                'aspirants.id',
                '=',
                'contests.aspirant_id'
            )
            ->where('contests.id', $contestId)
            ->select([
                'contests.*',
                'aspirants.display_name',
            ])
            ->firstOrFail();
    }

    public function tally(Request $request, int $contestId): View
    {
        abort_unless(
            Gate::forUser($request->user())->allows('campaign.view', $contestId),
            403
        );

        $contest = $this->contest($contestId);

        $submissions = DB::table('campaign_result_submissions')
            ->where('aspirant_contest_id', $contestId)
            ->where('status', 'verified')
            ->orderBy('id')
            ->get();

        $candidateTotals = [];

        foreach ($submissions as $submission) {
            $votes = json_decode(
                $submission->candidate_votes,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            foreach ($votes as $candidate => $count) {
                $candidateTotals[$candidate] =
                    ($candidateTotals[$candidate] ?? 0) + $count;
            }
        }

        arsort($candidateTotals);

        return view('campaigns.report-page', [
            'contest' => $contest,
            'type' => 'tally',
            'verifiedSubmissions' => $submissions->count(),
            'validVotes' => $submissions->sum('valid_votes'),
            'rejectedVotes' => $submissions->sum('rejected_votes'),
            'candidateTotals' => $candidateTotals,
            'auditRecords' => null,
        ]);
    }

    public function audit(Request $request, int $contestId): View
    {
        abort_unless(
            Gate::forUser($request->user())->allows(
                'campaign.review-applications',
                $contestId
            ),
            403
        );

        $contest = $this->contest($contestId);

        $records = DB::table('audit_logs as audit')
            ->join('role_applications as applications', function ($join) use ($contestId) {
                $join->on('applications.id', '=', 'audit.entity_id')
                    ->where('applications.aspirant_contest_id', '=', $contestId);
            })
            ->where('audit.entity_type', 'role_application')
            ->whereIn('audit.action', [
                'role_application.approved',
                'role_application.rejected',
            ])
            ->select([
                'audit.id',
                'audit.action',
                'audit.user_id',
                'audit.created_at',
            ])
            ->orderByDesc('audit.id')
            ->paginate(25);

        return view('campaigns.report-page', [
            'contest' => $contest,
            'type' => 'audit',
            'verifiedSubmissions' => 0,
            'validVotes' => 0,
            'rejectedVotes' => 0,
            'candidateTotals' => [],
            'auditRecords' => $records,
        ]);
    }
}
