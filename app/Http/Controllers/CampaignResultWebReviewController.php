<?php

namespace App\Http\Controllers;

use App\Services\CampaignResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CampaignResultWebReviewController extends Controller
{
    public function __construct(
        private readonly CampaignResultService $results
    ) {}

    public function __invoke(
        Request $request,
        int $contestId,
        int $submissionId
    ): RedirectResponse {
        abort_unless(
            Gate::forUser($request->user())->allows(
                'campaign.review-applications',
                $contestId
            ),
            403
        );

        $data = $request->validate([
            'decision' => [
                'required',
                Rule::in(['verified', 'rejected']),
            ],
            'reason' => [
                'required_if:decision,rejected',
                'nullable',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);

        $this->results->review(
            $request->user(),
            $contestId,
            $submissionId,
            $data['decision'],
            $data['reason'] ?? null
        );

        return redirect()
            ->route('campaign.operations.monitor', [
                'contestId' => $contestId,
            ])
            ->with(
                'status',
                'Result submission #'.$submissionId.' '
                    .$data['decision'].' successfully.'
            );
    }
}
