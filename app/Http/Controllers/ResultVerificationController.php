<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\ResultSubmission;
use Illuminate\View\View;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class ResultVerificationController extends Controller
{
    public function show(ResultSubmission $submission): View
    {
        $election = Election::query()
            ->where('is_demo', true)
            ->firstOrFail();

        $submission->load([
            'pollingStation',
            'agent',
            'candidateResults.candidate',
            'evidence',
            'verifier',
        ]);

        abort_unless(
            $submission->pollingStation?->election_id === $election->id,
            404
        );

        $candidateResults = $submission->candidateResults
            ->sortBy(function ($result) {
                return $result->candidate?->name ?? '';
            })
            ->values();

        $calculatedVotes = $candidateResults->sum('votes');

        $totalsMatch = (
            $calculatedVotes === $submission->valid_votes
        );

        return view('verification.show', compact(
            'election',
            'submission',
            'candidateResults',
            'calculatedVotes',
            'totalsMatch'
        ));
    }

    public function approve(Request $request, ResultSubmission $submission): RedirectResponse
    {
        $this->authorizeDemoReviewer($request);

        DB::transaction(function () use ($request, $submission) {
            $submission = ResultSubmission::query()
                ->lockForUpdate()
                ->findOrFail($submission->id);

            $this->assertPendingDemoSubmission($submission);

            $results = $submission->candidateResults()
                ->with('candidate')
                ->get();

            $electionId = $submission->pollingStation->election_id;

            if (
                $results->isEmpty() ||
                $results->contains(fn ($result) =>
                    !$result->candidate ||
                    $result->candidate->election_id !== $electionId ||
                    $result->votes < 0
                ) ||
                $results->pluck('candidate_id')->unique()->count() !== $results->count() ||
                (int) $results->sum('votes') !== $submission->valid_votes
            ) {
                throw ValidationException::withMessages([
                    'verification' => 'Candidate vote records failed validation.',
                ]);
            }

            $alreadyVerified = ResultSubmission::query()
                ->where('polling_station_id', $submission->polling_station_id)
                ->where('status', 'verified')
                ->whereKeyNot($submission->id)
                ->exists();

            if ($alreadyVerified) {
                throw ValidationException::withMessages([
                    'verification' => 'This station already has a verified submission.',
                ]);
            }

            if (!$submission->evidence()->exists()) {
                throw ValidationException::withMessages([
                    'verification' => 'Evidence is required before approval.',
                ]);
            }

            $submission->update([
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'result_submission.approved',
                'entity_type' => 'result_submission',
                'entity_id' => $submission->id,
                'metadata' => [
                    'polling_station_id' => $submission->polling_station_id,
                    'valid_votes' => $submission->valid_votes,
                    'rejected_votes' => $submission->rejected_votes,
                    'demo' => true,
                ],
            ]);
        });

        return redirect()
            ->route('verification.show', $submission)
            ->with('success', 'Submission approved and audit record created.');
    }

    public function reject(Request $request, ResultSubmission $submission): RedirectResponse
    {
        $this->authorizeDemoReviewer($request);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $submission, $validated) {
            $submission = ResultSubmission::query()
                ->lockForUpdate()
                ->findOrFail($submission->id);

            $this->assertPendingDemoSubmission($submission);

            $submission->update([
                'status' => 'rejected',
                'notes' => $validated['reason'],
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'result_submission.rejected',
                'entity_type' => 'result_submission',
                'entity_id' => $submission->id,
                'metadata' => [
                    'polling_station_id' => $submission->polling_station_id,
                    'reason' => $validated['reason'],
                    'demo' => true,
                ],
            ]);
        });

        return redirect()
            ->route('verification.show', $submission)
            ->with('success', 'Submission rejected and audit record created.');
    }

    private function authorizeDemoReviewer(Request $request): void
    {
        abort_unless($request->user()?->id === 1, 403);
    }

    private function assertPendingDemoSubmission(ResultSubmission $submission): void
    {
        $submission->load('pollingStation');

        $demoElection = Election::query()
            ->where('is_demo', true)
            ->firstOrFail();

        abort_unless(
            $submission->pollingStation?->election_id === $demoElection->id,
            404
        );

        if ($submission->status !== 'pending') {
            throw ValidationException::withMessages([
                'verification' => 'Only pending submissions can be reviewed.',
            ]);
        }
    }

}
