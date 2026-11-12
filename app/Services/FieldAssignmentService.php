<?php

namespace App\Services;

use App\Enums\ElectoralFieldRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FieldAssignmentService
{
    public function __construct(
        private readonly RoleApplicationValidator $validator
    ) {
    }


    public function endAssignment(
        User $actor,
        int $contestId,
        int $assignmentId,
        ?string $reason = null
    ): void {
        if (! Gate::forUser($actor)->allows(
            'campaign.assign-agents',
            $contestId
        )) {
            abort(403, 'Not authorized to end campaign assignments.');
        }

        if ($reason !== null && mb_strlen($reason) > 5000) {
            throw ValidationException::withMessages([
                'reason' => 'Termination reason must not exceed 5000 characters.',
            ]);
        }

        DB::transaction(function () use (
            $actor,
            $contestId,
            $assignmentId,
            $reason
        ): void {
            $assignment = DB::table('field_assignments as fa')
                ->join(
                    'role_applications as ra',
                    'ra.id',
                    '=',
                    'fa.role_application_id'
                )
                ->where('fa.id', $assignmentId)
                ->where('ra.aspirant_contest_id', $contestId)
                ->select([
                    'fa.id',
                    'fa.role_application_id',
                    'fa.status',
                    'fa.ended_at',
                    'ra.applicant_user_id',
                    'ra.requested_role',
                    'ra.geographic_level',
                    'ra.electoral_dataset_id',
                ])
                ->lockForUpdate()
                ->first();

            if (! $assignment) {
                abort(404, 'Assignment not found in this campaign.');
            }

            if (
                $assignment->status !== 'assigned'
                || $assignment->ended_at !== null
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Only active assignments can be ended.',
                ]);
            }

            $updated = DB::table('field_assignments')
                ->where('id', $assignmentId)
                ->where('status', 'assigned')
                ->whereNull('ended_at')
                ->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'status' => 'Assignment is no longer active.',
                ]);
            }

            $released = DB::table('active_field_assignment_slots')
                ->where('field_assignment_id', $assignmentId)
                ->delete();

            if ($released !== 1) {
                throw ValidationException::withMessages([
                    'field_assignment_id' =>
                        'Active appointment registry is inconsistent. '
                        . 'Termination has been rolled back.',
                ]);
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'field_assignment.ended',
                'entity_type' => 'field_assignment',
                'entity_id' => $assignmentId,
                'metadata' => [
                    'aspirant_contest_id' => $contestId,
                    'role_application_id' =>
                        $assignment->role_application_id,
                    'applicant_user_id' =>
                        $assignment->applicant_user_id,
                    'requested_role' => $assignment->requested_role,
                    'geographic_level' =>
                        $assignment->geographic_level,
                    'electoral_dataset_id' =>
                        $assignment->electoral_dataset_id,
                    'reason' => $reason,
                ],
            ]);
        }, 3);
    }

    public function assign(
        User $assigner,
        int $contestId,
        int $applicationId
    ): int {
        if (! Gate::forUser($assigner)->allows(
            'campaign.assign-agents',
            $contestId
        )) {
            abort(403, 'Not authorized to assign campaign personnel.');
        }

        return DB::transaction(function () use (
            $assigner,
            $contestId,
            $applicationId
        ): int {
            $application = DB::table('role_applications')
                ->where('id', $applicationId)
                ->where('aspirant_contest_id', $contestId)
                ->lockForUpdate()
                ->first();

            if (! $application) {
                abort(404, 'Application not found in this campaign.');
            }

            if ($application->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => 'Only approved applications can be assigned.',
                ]);
            }

            $role = ElectoralFieldRole::tryFrom(
                $application->requested_role
            );

            if ($role === null) {
                throw ValidationException::withMessages([
                    'requested_role' => 'Unsupported field role.',
                ]);
            }

            $geographicColumn = match (
                $application->geographic_level
            ) {
                'national' => null,
                'county' => 'county_id',
                'constituency' => 'constituency_id',
                'ward' => 'ward_id',
                'polling_centre' => 'polling_centre_id',
                'polling_station' => 'polling_station_id',
                default => throw ValidationException::withMessages([
                    'geographic_level' => 'Unsupported geographic level.',
                ]),
            };

            $geographicId = $geographicColumn === null
                ? null
                : (
                    $application->{$geographicColumn} === null
                        ? null
                        : (int) $application->{$geographicColumn}
                );

            $validated = $this->validator->validate(
                $contestId,
                $role,
                $application->geographic_level,
                $geographicId,
                (int) $application->electoral_dataset_id
            );

            foreach ([
                'electoral_dataset_id',
                'requested_role',
                'geographic_level',
                'county_id',
                'constituency_id',
                'ward_id',
                'polling_centre_id',
                'polling_station_id',
            ] as $field) {
                $actual = $application->{$field};
                $expected = $validated[$field];

                if ($actual !== null && str_ends_with($field, '_id')) {
                    $actual = (int) $actual;
                }

                if ($actual !== $expected) {
                    throw ValidationException::withMessages([
                        $field => 'Application geography or dataset is inconsistent.',
                    ]);
                }
            }

            if (
                DB::table('field_assignments')
                    ->where('role_application_id', $applicationId)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'role_application_id' =>
                        'This application already has a field assignment.',
                ]);
            }

            $conflictingAssignment = DB::table(
                'field_assignments as fa'
            )
                ->join(
                    'role_applications as ra',
                    'ra.id',
                    '=',
                    'fa.role_application_id'
                )
                ->where('ra.aspirant_contest_id', $contestId)
                ->where(
                    'ra.applicant_user_id',
                    $application->applicant_user_id
                )
                ->where(
                    'ra.electoral_dataset_id',
                    $application->electoral_dataset_id
                )
                ->where(
                    'ra.requested_role',
                    $application->requested_role
                )
                ->where(
                    'ra.geographic_level',
                    $application->geographic_level
                )
                ->where('fa.status', 'assigned')
                ->whereNull('fa.ended_at');

            foreach ([
                'county_id',
                'constituency_id',
                'ward_id',
                'polling_centre_id',
                'polling_station_id',
            ] as $field) {
                if ($application->{$field} === null) {
                    $conflictingAssignment->whereNull('ra.'.$field);
                } else {
                    $conflictingAssignment->where(
                        'ra.'.$field,
                        $application->{$field}
                    );
                }
            }

            if ($conflictingAssignment->exists()) {
                throw ValidationException::withMessages([
                    'applicant_user_id' =>
                        'An active appointment already exists for this applicant '
                        . 'in the same campaign, role and location.',
                ]);
            }

            $assignmentId = DB::table('field_assignments')
                ->insertGetId([
                    'role_application_id' => $applicationId,
                    'assigned_by' => $assigner->id,
                    'status' => 'assigned',
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            try {
                DB::table('active_field_assignment_slots')
                    ->insert([
                        'field_assignment_id' => $assignmentId,
                        'aspirant_contest_id' => $contestId,
                        'applicant_user_id' =>
                            $application->applicant_user_id,
                        'electoral_dataset_id' =>
                            $application->electoral_dataset_id,
                        'requested_role' => $application->requested_role,
                        'geographic_level' =>
                            $application->geographic_level,
                        'geographic_id' => $geographicId ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $exception) {
                throw ValidationException::withMessages([
                    'applicant_user_id' =>
                        'An active appointment already exists for this '
                        . 'applicant in the same campaign, role and location.',
                ]);
            }

            AuditLog::create([
                'user_id' => $assigner->id,
                'action' => 'field_assignment.created',
                'entity_type' => 'field_assignment',
                'entity_id' => $assignmentId,
                'metadata' => [
                    'aspirant_contest_id' => $contestId,
                    'role_application_id' => $applicationId,
                    'applicant_user_id' => $application->applicant_user_id,
                    'requested_role' => $role->value,
                    'geographic_level' => $application->geographic_level,
                    'geographic_id' => $geographicId,
                    'electoral_dataset_id' =>
                        $application->electoral_dataset_id,
                ],
            ]);

            return $assignmentId;
        }, 3);
    }
}
