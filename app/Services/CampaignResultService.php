<?php

namespace App\Services;

use App\Enums\ElectoralOffice;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CampaignResultService
{
    public function __construct(
        private readonly ElectoralJurisdictionValidator $jurisdiction,
        private readonly CampaignEvidenceIntegrityService $evidenceIntegrity
    ) {}

    public function assertStation(
        int $contestId,
        int $stationId
    ): object {
        $contest = DB::table('aspirant_contests')
            ->where('id', $contestId)->first();

        abort_if($contest === null, 404);

        $office = ElectoralOffice::tryFrom($contest->office);
        abort_if($office === null, 422);

        $this->jurisdiction->validateApplication(
            $office,
            [
                'county_id' => $contest->county_id,
                'constituency_id' => $contest->constituency_id,
                'ward_id' => $contest->ward_id,
            ],
            'polling_station',
            $stationId
        );

        $exists = DB::table('electoral_register_snapshots')
            ->where('dataset_id', $contest->electoral_dataset_id)
            ->where('polling_station_id', $stationId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'polling_station_id' => 'Station is not in this electoral dataset.',
            ]);
        }

        return $contest;
    }

    public function submit(
        User $actor,
        int $contestId,
        array $data
    ): int {
        return DB::transaction(function () use ($actor, $contestId, $data) {
            $stationId = (int) $data['polling_station_id'];
            $contest = $this->assertStation($contestId, $stationId);

            $assigned = DB::table('active_field_assignment_slots')
                ->where('aspirant_contest_id', $contestId)
                ->where('applicant_user_id', $actor->id)
                ->where('electoral_dataset_id', $contest->electoral_dataset_id)
                ->where('requested_role', 'polling_station_agent')
                ->where('geographic_level', 'polling_station')
                ->where('geographic_id', $stationId)
                ->exists();

            abort_unless($assigned, 403);

            $votes = $data['candidate_votes'];

            if (array_sum($votes) !== (int) $data['valid_votes']) {
                throw ValidationException::withMessages([
                    'candidate_votes' =>
                        'Candidate votes must equal declared valid votes.',
                ]);
            }

            $existing = DB::table('campaign_result_submissions')
                ->where('aspirant_contest_id', $contestId)
                ->where('electoral_polling_station_id', $stationId)
                ->where('status', 'verified')
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'polling_station_id' =>
                        'This campaign already has verified results for this station.',
                ]);
            }

            $id = DB::table('campaign_result_submissions')
                ->insertGetId([
                    'aspirant_contest_id' => $contestId,
                    'electoral_polling_station_id' => $stationId,
                    'submitted_by' => $actor->id,
                    'valid_votes' => $data['valid_votes'],
                    'rejected_votes' => $data['rejected_votes'],
                    'candidate_votes' => json_encode(
                        $votes,
                        JSON_THROW_ON_ERROR
                    ),
                    'status' => 'pending',
                    'notes' => $data['notes'] ?? null,
                    'submitted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'campaign.result.submitted',
                'entity_type' => 'campaign_result_submission',
                'entity_id' => $id,
                'metadata' => [
                    'aspirant_contest_id' => $contestId,
                    'polling_station_id' => $stationId,
                ],
            ]);

            return $id;
        }, 3);
    }

    public function review(
        User $actor,
        int $contestId,
        int $submissionId,
        string $decision,
        ?string $reason
    ): void {
        abort_unless(
            Gate::forUser($actor)->allows(
                'campaign.review-applications',
                $contestId
            ),
            403
        );

        DB::transaction(function () use (
            $actor, $contestId, $submissionId, $decision, $reason
        ) {
            $submission = DB::table('campaign_result_submissions')
                ->where('id', $submissionId)
                ->where('aspirant_contest_id', $contestId)
                ->lockForUpdate()
                ->first();

            abort_if($submission === null, 404);

            if ($submission->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending results can be reviewed.',
                ]);
            }

            $this->assertStation(
                $contestId,
                (int) $submission->electoral_polling_station_id
            );

            if ($decision === 'verified') {
                $votes = json_decode(
                    $submission->candidate_votes,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                if (
                    ! is_array($votes)
                    || $votes === []
                    || array_sum($votes) !== (int) $submission->valid_votes
                ) {
                    throw ValidationException::withMessages([
                        'candidate_votes' => 'Vote reconciliation failed.',
                    ]);
                }

                $evidenceFiles = DB::table('campaign_result_evidence')
                    ->where('campaign_result_submission_id', $submissionId)
                    ->get();

                if ($evidenceFiles->isEmpty()) {
                    throw ValidationException::withMessages([
                        'evidence' => 'Upload result evidence before approval.',
                    ]);
                }

                foreach ($evidenceFiles as $evidence) {
                    $this->evidenceIntegrity->validate(
                        $contestId,
                        $submissionId,
                        $evidence
                    );
                }

                $alreadyVerified = DB::table('campaign_result_submissions')
                    ->where('aspirant_contest_id', $contestId)
                    ->where(
                        'electoral_polling_station_id',
                        $submission->electoral_polling_station_id
                    )
                    ->where('status', 'verified')
                    ->exists();

                if ($alreadyVerified) {
                    throw ValidationException::withMessages([
                        'status' => 'Station already has verified results.',
                    ]);
                }
            }

            if ($decision === 'verified') {
                $conflictingSlot = DB::table('campaign_verified_result_slots')
                    ->where('aspirant_contest_id', $contestId)
                    ->where(
                        'electoral_polling_station_id',
                        $submission->electoral_polling_station_id
                    )
                    ->exists();

                if ($conflictingSlot) {
                    throw ValidationException::withMessages([
                        'status' => 'Station already has verified results.',
                    ]);
                }

                DB::table('campaign_verified_result_slots')
                    ->insert([
                        'aspirant_contest_id' => $contestId,
                        'electoral_polling_station_id' =>
                            $submission->electoral_polling_station_id,
                        'campaign_result_submission_id' => $submissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            DB::table('campaign_result_submissions')
                ->where('id', $submissionId)
                ->where('status', 'pending')
                ->update([
                    'status' => $decision,
                    'verified_by' => $decision === 'verified'
                        ? $actor->id : null,
                    'verified_at' => $decision === 'verified'
                        ? now() : null,
                    'notes' => $reason ?? $submission->notes,
                    'updated_at' => now(),
                ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'campaign.result.' . $decision,
                'entity_type' => 'campaign_result_submission',
                'entity_id' => $submissionId,
                'metadata' => [
                    'aspirant_contest_id' => $contestId,
                    'reason' => $reason,
                ],
            ]);
        }, 3);
    }
}
