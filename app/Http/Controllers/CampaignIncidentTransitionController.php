<?php

namespace App\Http\Controllers;

use App\Services\CampaignIncidentLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CampaignIncidentTransitionController extends Controller
{
    public function __construct(
        private readonly CampaignIncidentLifecycleService $lifecycle
    ) {}

    public function __invoke(
        Request $request,
        int $contestId,
        int $incidentId
    ): JsonResponse|RedirectResponse {
        Gate::forUser($request->user())->authorize(
            'campaign.manage-incidents',
            $contestId
        );

        $data = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in([
                    'acknowledged',
                    'investigating',
                    'escalated',
                    'resolved',
                ]),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $this->lifecycle->transition(
            $request->user(),
            $contestId,
            $incidentId,
            $data['status'],
            $data['notes'] ?? null
        );

        if (! $request->expectsJson()) {
            return redirect()
                ->route('campaign.operations.monitor', [
                    'contestId' => $contestId,
                ])
                ->with(
                    'status',
                    'Incident #'.$incidentId
                    .' updated to '
                    .str_replace('_', ' ', $data['status'])
                    .'.'
                );
        }

        return response()->json([
            'status' => 'success',
            'incident_id' => $incidentId,
            'incident_status' => $data['status'],
        ]);
    }
}
