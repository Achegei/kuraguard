<?php

namespace App\Http\Controllers;

use App\Enums\ElectoralOffice;
use App\Services\AspirantOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AspirantOnboardingController extends Controller
{
    public function create(): View
    {
        return view('aspirants.create', [
            'offices' => array_map(
                fn (ElectoralOffice $office): array => [
                    'value' => $office->value,
                    'label' => $office->label(),
                    'scope_level' => $office->scopeLevel(),
                ],
                ElectoralOffice::cases()
            ),
        ]);
    }

    public function store(
        Request $request,
        AspirantOnboardingService $onboarding
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'display_name' => [
                'required',
                'string',
                'max:150',
            ],
            'election_year' => [
                'required',
                'integer',
                'between:2022,2100',
            ],
            'office' => [
                'required',
                Rule::enum(ElectoralOffice::class),
            ],
            'county_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'constituency_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'ward_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $office = ElectoralOffice::from(
            $validated['office']
        );

        $contestId = $onboarding->createContest(
            userId: (int) $user->id,
            displayName: $validated['display_name'],
            electionYear: (int) $validated['election_year'],
            office: $office,
            countyId: isset($validated['county_id'])
                ? (int) $validated['county_id']
                : null,
            constituencyId: isset($validated['constituency_id'])
                ? (int) $validated['constituency_id']
                : null,
            wardId: isset($validated['ward_id'])
                ? (int) $validated['ward_id']
                : null
        );

        return redirect()
            ->route('campaign.dashboard', [
                'contestId' => $contestId,
            ])
            ->with(
                'status',
                'Your aspirant campaign has been created successfully.'
            );
    }
}
