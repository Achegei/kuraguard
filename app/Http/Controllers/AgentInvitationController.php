<?php

namespace App\Http\Controllers;

use App\Enums\ElectoralFieldRole;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgentInvitationController extends Controller
{
    public function send(
        Request $request,
        int $contestId,
        int $applicationId
    ): RedirectResponse {
        abort_unless(
            Gate::forUser($request->user())
                ->allows('campaign.review-applications', $contestId),
            403
        );

        $token = Str::random(64);

        $application = DB::transaction(function () use (
            $request,
            $contestId,
            $applicationId,
            $token
        ) {
            $application = DB::table('public_role_applications')
                ->where('id', $applicationId)
                ->where('aspirant_contest_id', $contestId)
                ->lockForUpdate()
                ->first();

            abort_if($application === null, 404);

            if ($application->status !== 'approved') {
                throw ValidationException::withMessages([
                    'application' => 'Only approved applicants can be invited.',
                ]);
            }

            if (
                ElectoralFieldRole::tryFrom(
                    $application->requested_role
                ) === null
            ) {
                throw ValidationException::withMessages([
                    'application' =>
                        'This role is not yet supported for account activation.',
                ]);
            }

            if (
                $application->linked_user_id !== null ||
                $application->linked_role_application_id !== null
            ) {
                throw ValidationException::withMessages([
                    'application' => 'This applicant has already been linked.',
                ]);
            }

            $activeInvitation = DB::table('agent_activation_invitations')
                ->where('public_role_application_id', $applicationId)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->exists();

            if ($activeInvitation) {
                throw ValidationException::withMessages([
                    'application' => 'An active invitation already exists.',
                ]);
            }

            DB::table('agent_activation_invitations')->insert([
                'public_role_application_id' => $applicationId,
                'aspirant_contest_id' => $contestId,
                'email' => mb_strtolower(trim($application->email)),
                'token_hash' => hash('sha256', $token),
                'invited_by' => $request->user()->id,
                'expires_at' => now()->addHours(24),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'agent_activation.invited',
                'entity_type' => 'public_role_application',
                'entity_id' => $applicationId,
                'metadata' => [
                    'aspirant_contest_id' => $contestId,
                    'requested_role' => $application->requested_role,
                ],
            ]);

            return $application;
        }, 3);

        $url = url('/agent/activate/' . $token);

        Mail::raw(
            "Hello {$application->full_name},\n\n"
            . "Your KuraGuard campaign application has been approved.\n\n"
            . "Activate your account using this secure link:\n"
            . $url . "\n\n"
            . "The invitation expires in 24 hours and can only be used once.\n\n"
            . "If you did not apply, please ignore this message.\n\n"
            . "KuraGuard",
            function ($message) use ($application) {
                $message
                    ->to($application->email)
                    ->subject('KuraGuard — Activate Your Campaign Account');
            }
        );

        return redirect()
            ->route('campaign.recruitment.index', $contestId)
            ->with(
                'status',
                'Activation invitation generated for the approved applicant.'
            );
    }
}
