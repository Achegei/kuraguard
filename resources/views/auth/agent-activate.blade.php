@extends('layouts.auth')

@section('title', 'Activate Agent Account | KuraGuard')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/kuraguard-auth-experience.css') }}">
@endpush

@section('content')
<div class="kg-auth-experience">

    <section class="kg-auth-story">
        <div class="kg-auth-story-content">
            <div class="kg-auth-eyebrow">
                <span class="kg-auth-signal"></span>
                KURAGUARD FIELD OPERATIONS
            </div>

            <h1>
                Your station.<br>
                Your responsibility.<br>
                <span>Your command.</span>
            </h1>

            <p class="kg-auth-story-description">
                Join your campaign's authorized field network.
                Stay connected to your assigned electoral location
                and support coordinated election operations.
            </p>

            <div class="kg-auth-feature-list">
                <div class="kg-auth-feature">
                    <span class="kg-auth-feature-icon">01</span>
                    <div>
                        <strong>Activate your account</strong>
                        <p>Secure access to your field identity.</p>
                    </div>
                </div>

                <div class="kg-auth-feature">
                    <span class="kg-auth-feature-icon">02</span>
                    <div>
                        <strong>Receive your deployment</strong>
                        <p>Access your authorized assignment when available.</p>
                    </div>
                </div>

                <div class="kg-auth-feature">
                    <span class="kg-auth-feature-icon">03</span>
                    <div>
                        <strong>Support election operations</strong>
                        <p>Coordinate with your campaign team.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="kg-auth-story-footer">
            ELECTION INTELLIGENCE · FIELD OPERATIONS · ACCOUNTABILITY
        </div>
    </section>

    <section class="kg-auth-form-panel">
        <div class="kg-auth-form-content">

            <div class="kg-auth-form-eyebrow">
                APPROVED CAMPAIGN APPLICATION
            </div>

            <div class="kg-auth-form-heading">
                <h2>Welcome to the team.</h2>
                <p>
                    Hello, {{ $application->full_name }}.
                    Your application has been approved.
                    Complete your account activation below.
                </p>
            </div>

            <div style="margin-bottom:26px;padding:16px 18px;
                        border:1px solid #d9e2f3;border-radius:12px;
                        background:#fff">
                <strong style="display:block;margin-bottom:6px">
                    Approved role
                </strong>
                <span>
                    {{ ucwords(str_replace('_', ' ', $application->requested_role)) }}
                </span>
            </div>

            @if ($errors->any())
                <div role="alert"
                     style="padding:15px;margin-bottom:22px;
                            border-radius:10px;background:#fff1f2;
                            color:#9f1239">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST"
                  action="{{ route('agent.activation.store', ['token' => $token]) }}">
                @csrf

                @if (!$existingAccount)
                    <p style="margin-bottom:22px;color:#657795">
                        Create a secure password to activate
                        your KuraGuard account.
                    </p>

                    <div class="kg-auth-field">
                        <label for="password">Password</label>
                        <input id="password"
                               name="password"
                               type="password"
                               minlength="12"
                               required
                               autocomplete="new-password"
                               placeholder="Create a secure password">
                    </div>

                    <div class="kg-auth-field">
                        <label for="password_confirmation">
                            Confirm password
                        </label>
                        <input id="password_confirmation"
                               name="password_confirmation"
                               type="password"
                               minlength="12"
                               required
                               autocomplete="new-password"
                               placeholder="Repeat your password">
                    </div>

                    <p style="font-size:12px;color:#7889a5;
                              margin-bottom:22px">
                        At least 12 characters, including uppercase,
                        lowercase and numbers.
                    </p>
                @else
                    <p style="margin-bottom:26px;color:#657795">
                        Your existing KuraGuard account will be linked
                        to this approved campaign application.
                    </p>
                @endif

                <button type="submit" class="kg-auth-submit">
                    Activate campaign account &rarr;
                </button>
            </form>

            <p style="margin-top:26px;text-align:center;
                      font-size:13px;color:#7889a5">
                Authorized campaign participation only.
            </p>

        </div>
    </section>

</div>
@endsection
