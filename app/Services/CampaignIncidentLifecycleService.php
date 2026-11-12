<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CampaignIncidentLifecycleService
{
    private const TRANSITIONS = [
        'open' => ['acknowledged', 'escalated'],
        'acknowledged' => ['investigating', 'escalated'],
        'investigating' => ['escalated', 'resolved'],
        'escalated' => ['investigating', 'resolved'],
        'resolved' => [],
    ];

    public function transition(
        User $actor,
        int $contestId,
        int $incidentId,
        string $toStatus,
        ?string $notes = null
    ): void {
        Gate::forUser($actor)->authorize(
            'campaign.manage-incidents',
            $contestId
        );

        $toStatus = trim($toStatus);
        $notes = $notes === null ? null : trim($notes);

        if (! array_key_exists($toStatus, self::TRANSITIONS)) {
            throw ValidationException::withMessages([
                'status' => 'Unknown incident status.',
            ]);
        }

        if ($notes !== null && mb_strlen($notes) > 5000) {
            throw ValidationException::withMessages([
                'notes' => 'Notes must not exceed 5000 characters.',
            ]);
        }

        if ($toStatus === 'resolved' && ($notes === null || $notes === '')) {
            throw ValidationException::withMessages([
                'notes' => 'Resolution notes are required.',
            ]);
        }

        DB::transaction(function () use (
            $actor,
            $contestId,
            $incidentId,
            $toStatus,
            $notes
        ): void {
            $incident = DB::table('campaign_incidents')
                ->where('id', $incidentId)
                ->where('aspirant_contest_id', $contestId)
                ->lockForUpdate()
                ->first();

            abort_if($incident === null, 404);

            $fromStatus = (string) $incident->status;

            $allowed = self::TRANSITIONS[$fromStatus] ?? [];

            if (! in_array($toStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Invalid incident status transition.',
                ]);
            }

            $changes = [
                'status' => $toStatus,
                'updated_at' => now(),
            ];

            if ($toStatus === 'resolved') {
                $changes['resolved_at'] = now();
            }

            $updated = DB::table('campaign_incidents')
                ->where('id', $incidentId)
                ->where('aspirant_contest_id', $contestId)
                ->where('status', $fromStatus)
                ->update($changes);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'status' => 'Incident status changed concurrently.',
                ]);
            }

            DB::table('campaign_incident_status_history')
                ->insert([
                    'campaign_incident_id' => $incidentId,
                    'aspirant_contest_id' => $contestId,
                    'changed_by' => $actor->id,
                    'from_status' => $fromStatus,
                    'to_status' => $toStatus,
                    'notes' => $notes,
                    'created_at' => now(),
                ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'campaign.incident.status_changed',
                'entity_type' => 'campaign_incident',
                'entity_id' => $incidentId,
                'metadata' => [
                    'aspirant_contest_id' => $contestId,
                    'from_status' => $fromStatus,
                    'to_status' => $toStatus,
                    'notes' => $notes,
                ],
            ]);
        }, 3);
    }
}
