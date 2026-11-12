<?php

namespace App\Http\Controllers;

use App\Enums\ElectoralFieldRole;
use App\Models\AuditLog;
use App\Services\RoleApplicationValidator;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AgentActivationController extends Controller
{
    private function invitation(string $token): object
    {
        abort_unless(
            preg_match('/^[A-Za-z0-9]{64}$/', $token) === 1,
            404
        );

        $invitation = DB::table('agent_activation_invitations')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        abort_if($invitation === null, 404);

        return $invitation;
    }

    public function create(
        Request $request,
        string $token
    ): View|RedirectResponse {
        $invitation = $this->invitation($token);

        $application = DB::table('public_role_applications')
            ->where('id', $invitation->public_role_application_id)
            ->where('aspirant_contest_id', $invitation->aspirant_contest_id)
            ->where('status', 'approved')
            ->whereNull('linked_user_id')
            ->whereNull('linked_role_application_id')
            ->first();

        abort_if($application === null, 404);

        $existingUser = User::query()
            ->where('email', $invitation->email)
            ->first();

        if ($existingUser !== null) {
            if (
                Auth::check() &&
                (int) Auth::id() === (int) $existingUser->id
            ) {
                return view('auth.agent-activate', [
                    'token' => $token,
                    'application' => $application,
                    'existingAccount' => true,
                ]);
            }

            $request->session()->put(
                'url.intended',
                route('agent.activation.create', ['token' => $token])
            );

            return redirect()
                ->route('login')
                ->with(
                    'status',
                    'Sign in using the email address associated with your invitation.'
                );
        }

        abort_if(Auth::check(), 403);

        return view('auth.agent-activate', [
            'token' => $token,
            'application' => $application,
            'existingAccount' => false,
        ]);
    }

    public function store(
        Request $request,
        string $token
    ): RedirectResponse {
        $invitation = $this->invitation($token);

        $existingUser = User::query()
            ->where('email', $invitation->email)
            ->first();

        if ($existingUser !== null) {
            abort_unless(
                Auth::check() &&
                (int) Auth::id() === (int) $existingUser->id,
                403
            );

            $data = [];
        } else {
            abort_if(Auth::check(), 403);

            $data = $request->validate([
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(12)->mixedCase()->numbers(),
                ],
            ]);
        }

        $activation = DB::transaction(function () use (
            $invitation,
            $existingUser,
            $data
        ) {
            $lockedInvitation = DB::table(
                'agent_activation_invitations'
            )
                ->where('id', $invitation->id)
                ->lockForUpdate()
                ->first();

            abort_if(
                $lockedInvitation === null ||
                $lockedInvitation->used_at !== null ||
                $lockedInvitation->revoked_at !== null ||
                now()->greaterThan($lockedInvitation->expires_at),
                404
            );

            $application = DB::table('public_role_applications')
                ->where(
                    'id',
                    $lockedInvitation->public_role_application_id
                )
                ->where(
                    'aspirant_contest_id',
                    $lockedInvitation->aspirant_contest_id
                )
                ->lockForUpdate()
                ->first();

            abort_if(
                $application === null ||
                $application->status !== 'approved' ||
                $application->linked_user_id !== null ||
                $application->linked_role_application_id !== null ||
                mb_strtolower(trim($application->email)) !==
                    $lockedInvitation->email,
                403
            );

            if ($existingUser !== null) {
                $user = $existingUser;
            } else {
                if (
                    User::query()
                        ->where('email', $lockedInvitation->email)
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'email' => 'An account already exists for this email. Sign in first.',
                    ]);
                }

                $user = new User();
                $user->name = $application->full_name;
                $user->email = $lockedInvitation->email;
                $user->password = Hash::make($data['password']);
                $user->save();
            }

            $role = ElectoralFieldRole::tryFrom(
                $application->requested_role
            );

            if ($role === null) {
                throw ValidationException::withMessages([
                    'role' => 'This role is not supported for field activation.',
                ]);
            }

            $geographicId = match ($application->geographic_level) {
                'national' => null,
                'county' => $application->county_id,
                'constituency' => $application->constituency_id,
                'ward' => $application->ward_id,
                'polling_centre' => $application->polling_centre_id,
                'polling_station' => $application->polling_station_id,
                default => throw ValidationException::withMessages([
                    'geography' => 'Unsupported geographic level.',
                ]),
            };

            app(RoleApplicationValidator::class)->validate(
                (int) $application->aspirant_contest_id,
                $role,
                $application->geographic_level,
                $geographicId === null ? null : (int) $geographicId,
                (int) $application->electoral_dataset_id
            );

            $roleApplicationId = DB::table('role_applications')
                ->insertGetId([
                    'aspirant_contest_id' => $application->aspirant_contest_id,
                    'applicant_user_id' => $user->id,
                    'electoral_dataset_id' => $application->electoral_dataset_id,
                    'requested_role' => $application->requested_role,
                    'geographic_level' => $application->geographic_level,
                    'county_id' => $application->county_id,
                    'constituency_id' => $application->constituency_id,
                    'ward_id' => $application->ward_id,
                    'polling_centre_id' => $application->polling_centre_id,
                    'polling_station_id' => $application->polling_station_id,
                    'status' => 'approved',
                    'reviewed_by' => $application->reviewed_by,
                    'reviewed_at' => $application->reviewed_at,
                    'review_notes' => $application->review_notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('public_role_applications')
                ->where('id', $application->id)
                ->update([
                    'linked_user_id' => $user->id,
                    'linked_role_application_id' => $roleApplicationId,
                    'updated_at' => now(),
                ]);

            DB::table('agent_activation_invitations')
                ->where('id', $lockedInvitation->id)
                ->update([
                    'used_at' => now(),
                    'updated_at' => now(),
                ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'agent_activation.activated',
                'entity_type' => 'public_role_application',
                'entity_id' => $application->id,
                'metadata' => [
                    'aspirant_contest_id' => $application->aspirant_contest_id,
                    'role_application_id' => $roleApplicationId,
                    'requested_role' => $application->requested_role,
                ],
            ]);

            return [
                'user_id' => (int) $user->id,
                'requested_role' => $application->requested_role,
            ];
        }, 3);

        if (! Auth::check()) {
            Auth::loginUsingId($activation['user_id']);
            $request->session()->regenerate();
        }

        $destination = $activation['requested_role'] === 'mobilizer'
            ? 'mobilizer.workspace'
            : 'campaign.landing';

        return redirect()
            ->route($destination)
            ->with(
                'status',
                'Your campaign account has been activated. '
                . 'You are awaiting field deployment.'
            );
    }
}
