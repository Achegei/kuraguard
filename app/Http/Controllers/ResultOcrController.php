<?php

namespace App\Http\Controllers;

use App\Models\ResultSubmission;
use App\Services\HandwrittenResultOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResultOcrController extends Controller
{
    public function extract(
        Request $request,
        ResultSubmission $submission,
        HandwrittenResultOcrService $ocr
    ): View {
        abort_unless(Auth::id() === 1, 403);

        $submission->load('pollingStation.election');

        abort_unless(
            $submission->pollingStation?->election?->is_demo === true,
            403
        );

        abort_unless($submission->status === 'pending', 403);

        $validated = $request->validate([
            'result_image' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
        ]);

        $image = $validated['result_image'];

        try {
            $result = $ocr->extract($image->getRealPath());

            return view('verification.ocr-review', [
                'submission' => $submission,
                'extracted' => $result,
                'expectedCandidates' => $submission
                    ->candidateResults()
                    ->with('candidate')
                    ->get(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('verification.show', $submission->id)
                ->withErrors([
                    'ocr' => 'AI extraction could not be completed. '
                        . 'Please retry with a clear image.',
                ]);
        }
    }
}
