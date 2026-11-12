<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CampaignDiscoveryConsentController extends Controller
{
    private function ownedContest(Request $request, int $contestId): object
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $contest = DB::table('aspirant_contests as contests')
            ->join('aspirants as aspirants', 'aspirants.id', '=', 'contests.aspirant_id')
            ->where('contests.id', $contestId)
            ->where('aspirants.user_id', $user->id)
            ->select('contests.id', 'contests.office', 'contests.election_year',
                'contests.scenario_discovery_consent', 'aspirants.display_name')
            ->first();

        abort_if($contest === null, 403);
        return $contest;
    }

    public function show(Request $request, int $contestId): View
    {
        return view('campaigns.discovery-settings', [
            'contest' => $this->ownedContest($request, $contestId),
        ]);
    }

    public function update(Request $request, int $contestId): RedirectResponse
    {
        $contest = $this->ownedContest($request, $contestId);
        $validated = $request->validate([
            'scenario_discovery_consent' => ['required', 'boolean'],
        ]);

        DB::table('aspirant_contests')
            ->where('id', $contest->id)
            ->update([
                'scenario_discovery_consent' => (bool) $validated['scenario_discovery_consent'],
                'updated_at' => now(),
            ]);

        return redirect()->route('campaign.discovery-settings.show', ['contestId' => $contest->id])
            ->with('status', (bool) $validated['scenario_discovery_consent']
                ? 'Your aspirant identity is now discoverable in same-seat KuraGuard comparisons.'
                : 'Your aspirant identity is no longer discoverable in newly loaded comparisons.');
    }
}
