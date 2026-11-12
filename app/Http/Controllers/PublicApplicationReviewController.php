<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicApplicationReviewController extends Controller
{
    public function review(
        Request $request,
        int $contestId,
        int $applicationId
    ): RedirectResponse {
        abort_unless(
            Gate::forUser($request->user())
                ->allows('campaign.review-applications', $contestId),
            403
        );

        $data = $request->validate([
            'decision' => [
                'required',
                Rule::in(['approved', 'rejected']),
            ],
            'review_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        DB::transaction(function () use (
            $request,
            $contestId,
            $applicationId,
            $data
        ): void {
            $application = DB::table('public_role_applications')
                ->where('id', $applicationId)
                ->where('aspirant_contest_id', $contestId)
                ->lockForUpdate()
                ->first();

            abort_if($application === null, 404);

            if ($application->status !== 'pending') {
                throw ValidationException::withMessages([
                    'decision' => 'This application has already been reviewed.',
                ]);
            }

            $updated = DB::table('public_role_applications')
                ->where('id', $applicationId)
                ->where('aspirant_contest_id', $contestId)
                ->where('status', 'pending')
                ->update([
                    'status' => $data['decision'],
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'review_notes' => $data['review_notes'] ?? null,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'decision' => 'Application review could not be completed.',
                ]);
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'public_role_application.' . $data['decision'],
                'entity_type' => 'public_role_application',
                'entity_id' => $applicationId,
                'metadata' => [
                    'aspirant_contest_id' => $contestId,
                    'previous_status' => 'pending',
                    'new_status' => $data['decision'],
                    'review_notes' => $data['review_notes'] ?? null,
                ],
            ]);
        }, 3);

        return redirect()
            ->route('campaign.recruitment.index', $contestId)
            ->with('status', 'Application reviewed successfully.');
    }
}
