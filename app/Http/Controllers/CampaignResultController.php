<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\CampaignEvidenceIntegrityService;
use App\Services\CampaignResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampaignResultController extends Controller
{
    public function __construct(
        private readonly CampaignResultService $results,
        private readonly CampaignEvidenceIntegrityService $evidenceIntegrity
    ) {}

    public function submit(Request $request, int $contestId): JsonResponse
    {
        $data = $request->validate([
            'polling_station_id' => ['required', 'integer', 'min:1'],
            'valid_votes' => ['required', 'integer', 'min:0', 'max:100000'],
            'rejected_votes' => ['required', 'integer', 'min:0', 'max:100000'],
            'candidate_votes' => ['required', 'array', 'min:1', 'max:100'],
            'candidate_votes.*' => [
                'required', 'integer', 'min:0', 'max:100000',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $id = $this->results->submit(
            $request->user(),
            $contestId,
            $data
        );

        return response()->json([
            'status' => 'pending',
            'submission_id' => $id,
        ], 201);
    }

    public function evidence(
        Request $request,
        int $contestId,
        int $submissionId
    ): JsonResponse {
        $data = $request->validate([
            'file' => [
                'required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240',
            ],
            'form_type' => ['nullable', 'string', 'max:40'],
        ]);

        $submission = DB::table('campaign_result_submissions')
            ->where('id', $submissionId)
            ->where('aspirant_contest_id', $contestId)
            ->first();

        abort_if($submission === null, 404);

        $this->results->assertStation(
            $contestId,
            (int) $submission->electoral_polling_station_id
        );

        $contestDatasetId = DB::table('aspirant_contests')
            ->where('id', $contestId)
            ->value('electoral_dataset_id');

        abort_if($contestDatasetId === null, 404);

        $assigned = DB::table('active_field_assignment_slots')
            ->where('aspirant_contest_id', $contestId)
            ->where('electoral_dataset_id', $contestDatasetId)
            ->where('applicant_user_id', $request->user()->id)
            ->where('requested_role', 'polling_station_agent')
            ->where('geographic_level', 'polling_station')
            ->where(
                'geographic_id',
                $submission->electoral_polling_station_id
            )->exists();

        $reviewer = Gate::forUser($request->user())->allows(
            'campaign.review-applications',
            $contestId
        );

        abort_unless($assigned || $reviewer, 403);

        if ($submission->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Evidence can only be added to pending submissions.',
            ]);
        }

        $file = $data['file'];
        $hash = hash_file('sha256', $file->getRealPath());

        $path = $file->store(
            "campaign-results/{$contestId}/{$submissionId}",
            'local'
        );

        if ($path === false) {
            abort(500, 'Evidence storage failed.');
        }

        try {
            $id = DB::transaction(function () use (
                $request, $contestId, $submissionId, $path, $hash, $data
            ) {
                $pending = DB::table('campaign_result_submissions')
                    ->where('id', $submissionId)
                    ->where('aspirant_contest_id', $contestId)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->exists();

                abort_unless($pending, 409);

                $id = DB::table('campaign_result_evidence')
                    ->insertGetId([
                        'campaign_result_submission_id' => $submissionId,
                        'uploaded_by' => $request->user()->id,
                        'file_path' => $path,
                        'file_hash' => $hash,
                        'form_type' => $data['form_type'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'campaign.result.evidence_uploaded',
                    'entity_type' => 'campaign_result_evidence',
                    'entity_id' => $id,
                    'metadata' => [
                        'aspirant_contest_id' => $contestId,
                        'submission_id' => $submissionId,
                        'sha256' => $hash,
                    ],
                ]);

                return $id;
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return response()->json([
            'status' => 'stored',
            'evidence_id' => $id,
            'sha256' => $hash,
        ], 201);
    }


    public function downloadEvidence(
        Request $request,
        int $contestId,
        int $submissionId,
        int $evidenceId
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        abort_unless(
            Gate::forUser($request->user())->allows(
                'campaign.review-applications',
                $contestId
            ),
            403
        );

        $evidence = DB::table('campaign_result_evidence as evidence')
            ->join(
                'campaign_result_submissions as submissions',
                'submissions.id',
                '=',
                'evidence.campaign_result_submission_id'
            )
            ->where('evidence.id', $evidenceId)
            ->where('submissions.id', $submissionId)
            ->where('submissions.aspirant_contest_id', $contestId)
            ->select(
                'evidence.id',
                'evidence.file_path',
                'evidence.file_hash'
            )
            ->first();

        abort_if($evidence === null, 404);

        try {
            $resolved = $this->evidenceIntegrity->validate(
                $contestId,
                $submissionId,
                $evidence
            );
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $message = $errors['evidence'][0] ?? '';

            if ($message === 'Evidence integrity verification failed.') {
                abort(409, $message);
            }

            abort(404);
        }

        $actualHash = hash_file('sha256', $resolved);

        abort_unless(
            is_string($actualHash),
            409,
            'Evidence integrity verification failed.'
        );

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'campaign.result.evidence_downloaded',
            'entity_type' => 'campaign_result_evidence',
            'entity_id' => $evidenceId,
            'metadata' => [
                'aspirant_contest_id' => $contestId,
                'submission_id' => $submissionId,
                'sha256' => $actualHash,
            ],
        ]);

        return response()->download(
            $resolved,
            'campaign-evidence-'.$evidenceId.'.'
                . strtolower(pathinfo($resolved, PATHINFO_EXTENSION)),
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function review(
        Request $request,
        int $contestId,
        int $submissionId
    ): JsonResponse {
        $data = $request->validate([
            'decision' => [
                'required', Rule::in(['verified', 'rejected']),
            ],
            'reason' => [
                'required_if:decision,rejected',
                'nullable', 'string', 'min:10', 'max:2000',
            ],
        ]);

        $this->results->review(
            $request->user(),
            $contestId,
            $submissionId,
            $data['decision'],
            $data['reason'] ?? null
        );

        return response()->json(['status' => $data['decision']]);
    }

    public function tally(
        Request $request,
        int $contestId
    ): JsonResponse {
        abort_unless(
            Gate::forUser($request->user())->allows(
                'campaign.view',
                $contestId
            ),
            403
        );

        $submissions = DB::table('campaign_result_submissions')
            ->where('aspirant_contest_id', $contestId)
            ->where('status', 'verified')
            ->orderBy('id')
            ->get();

        $totals = [];

        foreach ($submissions as $submission) {
            $votes = json_decode(
                $submission->candidate_votes,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            foreach ($votes as $candidate => $count) {
                $totals[$candidate] =
                    ($totals[$candidate] ?? 0) + $count;
            }
        }

        return response()->json([
            'campaign_id' => $contestId,
            'verified_submissions' => $submissions->count(),
            'valid_votes' => $submissions->sum('valid_votes'),
            'rejected_votes' => $submissions->sum('rejected_votes'),
            'candidate_totals' => $totals,
        ]);
    }
}
