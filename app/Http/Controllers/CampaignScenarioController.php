<?php

namespace App\Http\Controllers;

use App\Enums\ElectoralOffice;
use App\Services\ElectoralScopeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CampaignScenarioController extends Controller
{
    public function index(
        Request $request,
        int $contestId,
        ElectoralScopeResolver $resolver
    ): View {
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
                'contests.county_id',
                'contests.constituency_id',
                'contests.ward_id',
                'aspirants.display_name',
            ])
            ->first();

        abort_if($contest === null, 404);

        $office = ElectoralOffice::from($contest->office);
        $level = $office->scopeLevel();

        $geographicId = match ($level) {
            'county' => $contest->county_id,
            'constituency' => $contest->constituency_id,
            'ward' => $contest->ward_id,
            default => null,
        };

        $scope = $resolver->resolve(
            $office,
            $geographicId === null ? null : (int) $geographicId,
            (int) $contest->electoral_dataset_id
        );

        // Only explicitly published platform declarations may be listed to other campaigns.
        // Default OFF: enable only after publication-consent policy is implemented.
        $registeredCompetitors = collect();
        if (config('kuraguard.scenario_public_aspirant_discovery', false)) {
            $registeredCompetitors = DB::table('aspirant_contests as other_contests')
                ->join('aspirants as other_aspirants', 'other_aspirants.id', '=', 'other_contests.aspirant_id')
                ->where('other_contests.id', '<>', $contest->id)
                ->where('other_contests.scenario_discovery_consent', true)
                ->where('other_contests.office', $contest->office)
                ->where('other_contests.election_year', $contest->election_year)
                ->where('other_contests.electoral_dataset_id', $contest->electoral_dataset_id)
                ->when($level === 'ward', fn ($query) => $query->where('other_contests.ward_id', $contest->ward_id))
                ->when($level === 'constituency', fn ($query) => $query->where('other_contests.constituency_id', $contest->constituency_id))
                ->when($level === 'county', fn ($query) => $query->where('other_contests.county_id', $contest->county_id))
                ->when(!in_array($level, ['ward', 'constituency', 'county'], true), fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('other_aspirants.display_name')
                ->select('other_contests.id as contest_id', 'other_aspirants.display_name as name')
                ->get();
        }

        return view('campaigns.scenarios.index', [
            'contest' => $contest,
            'scope' => $scope,
            'registeredCompetitors' => $registeredCompetitors,
        ]);
    }
}
