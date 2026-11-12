<?php

namespace App\Http\Controllers;

use App\Services\CampaignLandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignLandingController extends Controller
{
    public function index(
        Request $request,
        CampaignLandingService $landing
    ): RedirectResponse|View {
        $user = $request->user();

        abort_unless($user !== null, 401);

        $contests = $landing->authorizedContests(
            (int) $user->id
        );

        if ($contests->isEmpty()) {
            return view('campaigns.select', [
                'contests' => $contests,
            ]);
        }

        if ($contests->count() === 1) {
            return redirect()->route(
                'campaign.dashboard',
                ['contestId' => $contests->first()->id]
            );
        }

        return view(
            'campaigns.select',
            ['contests' => $contests]
        );
    }
}
