<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RoleApplicationReviewService
{
    public function approve(
        User $reviewer,
        int $contestId,
        int $applicationId,
        ?string $notes = null
    ): void {
        $this->review(
            $reviewer,
            $contestId,
            $applicationId,
            'approved',
            $notes
        );
    }

    public function reject(
        User $reviewer,
        int $contestId,
        int $applicationId,
        ?string $notes = null
    ): void {
        $this->review(
            $reviewer,
            $contestId,
            $applicationId,
            'rejected',
            $notes
        );
    }

    private function review(
        User $reviewer,
        int $contestId,
        int $applicationId,
        string $decision,
        ?string $notes
    ): void {
        if (! Gate::forUser($reviewer)->allows(
            'campaign.review-applications',
            $contestId
        )) {
            abort(403, 'Not authorized to review applications.');
        }

        if ($notes !== null && mb_strlen($notes) > 5000) {
            throw ValidationException::withMessages([
                'review_notes' => 'Review notes cannot exceed 5000 characters.',
            ]);
        }

        DB::transaction(function () use (
            $reviewer,
            $contestId,
            $applicationId,
            $decision,
            $notes
        ): void {
            $application = DB::table('role_applications')
                ->where('id', $applicationId)
                ->where('aspirant_contest_id', $contestId)
                ->lockForUpdate()
                ->first();

            if (! $application) {
                abort(404, 'Application not found.');
            }

            if ($application->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending applications can be reviewed.',
                ]);
            }

            $updated = DB::table('role_applications')
                ->where('id', $applicationId)
                ->where('aspirant_contest_id', $contestId)
                ->where('status', 'pending')
                ->update([
                    'status' => $decision,
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                    'review_notes' => $notes,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'status' => 'The application was already reviewed.',
                ]);
            }

            AuditLog::create([
                'user_id' => $reviewer->id,
                'action' => 'role_application.' . $decision,
                'entity_type' => 'role_application',
                'entity_id' => $applicationId,
                'metadata' => [
                    'aspirant_contest_id' => $contestId,
                    'previous_status' => 'pending',
                    'new_status' => $decision,
                    'review_notes' => $notes,
                ],
            ]);
        });
    }
}
