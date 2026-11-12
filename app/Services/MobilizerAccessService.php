<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MobilizerAccessService
{
    public function activeAssignments(int $userId): Collection
    {
        return DB::table('active_field_assignment_slots as slots')
            ->join(
                'field_assignments as assignments',
                'assignments.id',
                '=',
                'slots.field_assignment_id'
            )
            ->join(
                'role_applications as applications',
                'applications.id',
                '=',
                'assignments.role_application_id'
            )
            ->join(
                'aspirant_contests as contests',
                'contests.id',
                '=',
                'slots.aspirant_contest_id'
            )
            ->join(
                'aspirants as aspirants',
                'aspirants.id',
                '=',
                'contests.aspirant_id'
            )
            ->where('slots.applicant_user_id', $userId)
            ->where('slots.requested_role', 'mobilizer')
            ->where('assignments.status', 'assigned')
            ->whereNull('assignments.ended_at')
            ->where('applications.status', 'approved')
            ->where('applications.requested_role', 'mobilizer')
            ->whereColumn(
                'applications.applicant_user_id',
                'slots.applicant_user_id'
            )
            ->whereColumn(
                'applications.aspirant_contest_id',
                'slots.aspirant_contest_id'
            )
            ->whereColumn(
                'applications.electoral_dataset_id',
                'slots.electoral_dataset_id'
            )
            ->whereColumn(
                'contests.electoral_dataset_id',
                'slots.electoral_dataset_id'
            )
            ->select([
                'slots.id',
                'slots.field_assignment_id',
                'slots.aspirant_contest_id',
                'slots.electoral_dataset_id',
                'slots.geographic_level',
                'slots.geographic_id',
                'contests.office',
                'contests.election_year',
                'aspirants.display_name',
            ])
            ->orderBy('slots.aspirant_contest_id')
            ->orderBy('slots.id')
            ->get();
    }

    public function canAccess(int $userId, int $contestId): bool
    {
        return $this->activeAssignments($userId)
            ->contains(
                fn ($assignment): bool =>
                    (int) $assignment->aspirant_contest_id === $contestId
            );
    }
}
