<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CampaignRecruitmentController extends Controller
{
    public function index(int $contestId): View
    {
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
                'contests.public_recruitment_enabled',
                'contests.public_recruitment_open',
                'aspirants.display_name',
            ])
            ->first();

        abort_if($contest === null, 404);

        $applications = DB::table('public_role_applications')
            ->where('aspirant_contest_id', $contestId);

        $statistics = [
            'total' => (clone $applications)->count(),
            'pending' => (clone $applications)
                ->where('status', 'pending')
                ->count(),
            'approved' => (clone $applications)
                ->where('status', 'approved')
                ->count(),
            'rejected' => (clone $applications)
                ->where('status', 'rejected')
                ->count(),
        ];

        $reviewApplications = DB::table('public_role_applications')
            ->where('aspirant_contest_id', $contestId)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view(
            'campaigns.recruitment',
            [
                'contest' => $contest,
                'statistics' => $statistics,
                'applications' => $reviewApplications,
            ]
        );
    }

    public function update(
        Request $request,
        int $contestId
    ): RedirectResponse {
        $data = $request->validate([
            'public_recruitment_enabled' => [
                'required',
                'boolean',
            ],
            'public_recruitment_open' => [
                'required',
                'boolean',
            ],
        ]);

        $updated = DB::table('aspirant_contests')
            ->where('id', $contestId)
            ->update([
                'public_recruitment_enabled' =>
                    (bool) $data['public_recruitment_enabled'],
                'public_recruitment_open' =>
                    (bool) $data['public_recruitment_open'],
                'updated_at' => now(),
            ]);

        abort_if($updated === 0, 404);

        return redirect()
            ->route('campaign.recruitment.index', $contestId)
            ->with(
                'status',
                'Recruitment settings updated successfully.'
            );
    }
}
