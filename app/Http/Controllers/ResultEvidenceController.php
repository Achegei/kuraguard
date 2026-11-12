<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Election;
use App\Models\ResultEvidence;
use App\Models\ResultSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ResultEvidenceController extends Controller
{
    public function store(
        Request $request,
        ResultSubmission $submission
    ): RedirectResponse {
        abort_unless($request->user()?->id === 1, 403);

        $validated = $request->validate([
            'evidence' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'form_type' => [
                'required',
                'in:simulated_result_form',
            ],
        ]);

        $demoElection = Election::query()
            ->where('is_demo', true)
            ->firstOrFail();

        $submission->load('pollingStation');

        abort_unless(
            $submission->pollingStation?->election_id === $demoElection->id,
            404
        );

        if ($submission->status !== 'pending') {
            throw ValidationException::withMessages([
                'evidence' => 'Evidence can only be added to pending submissions.',
            ]);
        }

        $file = $validated['evidence'];
        $hash = hash_file('sha256', $file->getRealPath());

        $path = $file->store(
            'kuraguard/demo-evidence/submission-' . $submission->id,
            'local'
        );

        if (!$path) {
            throw ValidationException::withMessages([
                'evidence' => 'The evidence file could not be stored.',
            ]);
        }

        try {
            DB::transaction(function () use (
                $request,
                $submission,
                $validated,
                $path,
                $hash
            ) {
                $locked = ResultSubmission::query()
                    ->lockForUpdate()
                    ->findOrFail($submission->id);

                if ($locked->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'evidence' => 'The submission is no longer pending.',
                    ]);
                }

                $evidence = ResultEvidence::create([
                    'result_submission_id' => $locked->id,
                    'file_path' => $path,
                    'file_hash' => $hash,
                    'form_type' => $validated['form_type'],
                    'uploaded_by' => $request->user()->id,
                ]);

                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'result_evidence.uploaded',
                    'entity_type' => 'result_evidence',
                    'entity_id' => $evidence->id,
                    'metadata' => [
                        'result_submission_id' => $locked->id,
                        'file_hash' => $hash,
                        'form_type' => $validated['form_type'],
                        'demo' => true,
                    ],
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return redirect()
            ->route('verification.show', $submission)
            ->with('success', 'Simulated evidence uploaded and audit record created.');
    }
}
