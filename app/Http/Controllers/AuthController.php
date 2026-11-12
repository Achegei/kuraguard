<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors([
                    'email' => 'Invalid email address or password.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $userId = (int) Auth::id();

        $hasCampaignAccess = app(
            \App\Services\CampaignLandingService::class
        )->authorizedContests($userId)->isNotEmpty();

        $hasApprovedMobilizerApplication = \Illuminate\Support\Facades\DB::table(
            'role_applications'
        )
            ->where('applicant_user_id', $userId)
            ->where('requested_role', 'mobilizer')
            ->where('status', 'approved')
            ->exists();

        $destination = ! $hasCampaignAccess
            && $hasApprovedMobilizerApplication
                ? 'mobilizer.workspace'
                : 'campaign.landing';

        return redirect()->intended(route($destination));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
