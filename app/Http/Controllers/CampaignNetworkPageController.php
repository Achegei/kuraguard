<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampaignNetworkPageController extends Controller
{
    private function contest(int $contestId): object
    {
        return DB::table('aspirant_contests as contests')
            ->join(
                'aspirants',
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

    public function stations(
        Request $request,
        int $contestId
    ): View {
        abort_unless(
            Gate::forUser($request->user())
                ->allows('campaign.view', $contestId),
            403
        );

        $contest = $this->contest($contestId);

        $reports = DB::table('campaign_station_reports')
            ->where('aspirant_contest_id', $contestId)
            ->orderByDesc('updated_at')
            ->paginate(25);

        $statistics = [
            'reported' => DB::table('campaign_station_reports')
                ->where('aspirant_contest_id', $contestId)
                ->distinct()
                ->count('electoral_polling_station_id'),

            'checked_in' => DB::table('campaign_station_reports')
                ->where('aspirant_contest_id', $contestId)
                ->where('attendance_status', 'checked_in')
                ->distinct()
                ->count('electoral_polling_station_id'),

            'results' => DB::table('campaign_result_submissions')
                ->where('aspirant_contest_id', $contestId)
                ->count(),

            'incidents' => DB::table('campaign_incidents')
                ->where('aspirant_contest_id', $contestId)
                ->count(),
        ];

        $page = 'stations';

        return view(
            'campaigns.network-page',
            compact('contest', 'reports', 'statistics', 'page')
        );
    }

    public function agents(
        Request $request,
        int $contestId
    ): View {
        abort_unless(
            Gate::forUser($request->user())
                ->allows('campaign.assign-agents', $contestId),
            403
        );

        $contest = $this->contest($contestId);

        $applications = DB::table('role_applications')
            ->where('role_applications.aspirant_contest_id', $contestId)
            ->select('role_applications.*')
            ->selectSub(
                DB::table('field_assignments')
                    ->select('field_assignments.id')
                    ->whereColumn(
                        'field_assignments.role_application_id',
                        'role_applications.id'
                    )
                    ->where('field_assignments.status', 'assigned')
                    ->whereNull('field_assignments.ended_at')
                    ->orderByDesc('field_assignments.id')
                    ->limit(1),
                'active_assignment_id'
            )
            ->orderByDesc('role_applications.id')
            ->paginate(25);

        $statistics = [
            'applications' => DB::table('role_applications')
                ->where('aspirant_contest_id', $contestId)
                ->count(),

            'assignments' => DB::table('field_assignments as assignments')
                ->join(
                    'role_applications as applications',
                    'applications.id',
                    '=',
                    'assignments.role_application_id'
                )
                ->where('applications.aspirant_contest_id', $contestId)
                ->where('assignments.status', 'assigned')
                ->whereNull('assignments.ended_at')
                ->count(),

            'public_applications' => DB::table(
                'public_role_applications'
            )
                ->where('aspirant_contest_id', $contestId)
                ->count(),
        ];

        $page = 'agents';

        return view(
            'campaigns.network-page',
            compact(
                'contest',
                'applications',
                'statistics',
                'page'
            )
        );
    }
}
