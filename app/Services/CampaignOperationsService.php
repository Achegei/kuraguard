<?php

namespace App\Services;

use App\Enums\ElectoralOffice;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignOperationsService
{
    public function __construct(
        private readonly ElectoralJurisdictionValidator $jurisdiction
    ) {}

    private function assignment(
        User $actor,
        int $contestId,
        int $stationId
    ): object {
        $contest = DB::table('aspirant_contests')
            ->where('id', $contestId)
            ->first();

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

        $registered = DB::table('electoral_register_snapshots')
            ->where('dataset_id', $contest->electoral_dataset_id)
            ->where('polling_station_id', $stationId)
            ->exists();

        if (! $registered) {
            throw ValidationException::withMessages([
                'polling_station_id' =>
                    'Station is absent from the campaign electoral dataset.',
            ]);
        }

        $assignment = DB::table('active_field_assignment_slots')
            ->where('aspirant_contest_id', $contestId)
            ->where('applicant_user_id', $actor->id)
            ->where('electoral_dataset_id', $contest->electoral_dataset_id)
            ->where('requested_role', 'polling_station_agent')
            ->where('geographic_level', 'polling_station')
            ->where('geographic_id', $stationId)
            ->first();

        abort_unless($assignment !== null, 403);

        return $assignment;
    }

    public function reportStation(
        User $actor,
        int $contestId,
        array $data
    ): int {
        return DB::transaction(function () use (
            $actor,
            $contestId,
            $data
        ): int {
            $stationId = (int) $data['polling_station_id'];

            $assignment = $this->assignment(
                $actor,
                $contestId,
                $stationId
            );

            $existing = DB::table('campaign_station_reports')
                ->where('aspirant_contest_id', $contestId)
                ->where('electoral_polling_station_id', $stationId)
                ->where(
                    'field_assignment_id',
                    $assignment->field_assignment_id
                )
                ->first();

            $values = [
                'attendance_status' => $data['attendance_status'],
                'reported_turnout' => $data['reported_turnout'] ?? null,
                'notes' => $data['notes'] ?? null,
                'updated_at' => now(),
            ];

            if ($data['attendance_status'] === 'checked_in') {
                $values['checked_in_at'] =
                    $existing?->checked_in_at ?? now();
            }

            if ($existing) {
                DB::table('campaign_station_reports')
                    ->where('id', $existing->id)
                    ->update($values);

                $reportId = (int) $existing->id;
            } else {
                $reportId = DB::table('campaign_station_reports')
                    ->insertGetId(array_merge($values, [
                        'aspirant_contest_id' => $contestId,
                        'electoral_polling_station_id' => $stationId,
                        'field_assignment_id' =>
                            $assignment->field_assignment_id,
                        'created_at' => now(),
                    ]));
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'campaign.station_report.saved',
                'entity_type' => 'campaign_station_report',
                'entity_id' => $reportId,
                'metadata' => [
                    'aspirant_contest_id' => $contestId,
                    'polling_station_id' => $stationId,
                ],
            ]);

            return $reportId;
        }, 3);
    }

    public function reportIncident(
        User $actor,
        int $contestId,
        array $data
    ): int {
        return DB::transaction(function () use (
            $actor,
            $contestId,
            $data
        ): int {
            $stationId = (int) $data['polling_station_id'];

            $this->assignment($actor, $contestId, $stationId);

            $incidentId = DB::table('campaign_incidents')
                ->insertGetId([
                    'aspirant_contest_id' => $contestId,
                    'electoral_polling_station_id' => $stationId,
                    'reported_by' => $actor->id,
                    'category' => $data['category'],
                    'severity' => $data['severity'],
                    'description' => $data['description'],
                    'status' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'campaign.incident.reported',
                'entity_type' => 'campaign_incident',
                'entity_id' => $incidentId,
                'metadata' => [
                    'aspirant_contest_id' => $contestId,
                    'polling_station_id' => $stationId,
                    'severity' => $data['severity'],
                ],
            ]);

            return $incidentId;
        }, 3);
    }
}
