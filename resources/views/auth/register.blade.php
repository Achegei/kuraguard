@extends('layouts.auth')

@section('title', 'Create Aspirant Account | KuraGuard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/kuraguard-auth-experience.css') }}">
<link rel="stylesheet" href="{{ asset('css/kuraguard-register-photo.css') }}?v=20261113a">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-auth-immersive-156.css') }}?v=20261114b">
@endpush

@section('content')
<div class="kg-auth-experience">
    <section class="kg-auth-story" aria-label="KuraGuard introduction">
        <div class="kg-auth-story-content">
            <div class="kg-auth-eyebrow">
                <span class="kg-auth-signal"></span>
                KURAGUARD CAMPAIGN INTELLIGENCE
            </div>

            <h1>
                Your campaign.<br>
                Your command.<br>
                <span>Your advantage.</span>
            </h1>

            <p class="kg-auth-story-description">
                Build your election command centre, organize your field
                network, and coordinate your campaign from one secure workspace.
            </p>

            <div class="kg-auth-feature-list">
                <div class="kg-auth-feature">
                    <span class="kg-auth-feature-icon">01</span>
                    <div>
                        <strong>Establish your campaign</strong>
                        <small>Configure your electoral position and jurisdiction.</small>
                    </div>
                </div>

                <div class="kg-auth-feature">
                    <span class="kg-auth-feature-icon">02</span>
                    <div>
                        <strong>Build your field network</strong>
                        <small>Recruit and deploy your campaign representatives.</small>
                    </div>
                </div>

                <div class="kg-auth-feature">
                    <span class="kg-auth-feature-icon">03</span>
                    <div>
                        <strong>Stay informed</strong>
                        <small>Monitor station activity, incidents and results.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="kg-auth-story-footer">
            ELECTION INTELLIGENCE · FIELD OPERATIONS · RESULT VERIFICATION
        </div>
    </section>

    <section class="kg-auth-form-panel" aria-labelledby="kg-register-title">
        <div class="kg-auth-form-shell">
            <div class="kg-auth-form-topline">
                <span>ASPIRANT REGISTRATION</span>
                <span>01 / 02</span>
            </div>

            <div class="kg-auth-form-heading">
                <span class="kg-auth-form-kicker">WELCOME TO KURAGUARD</span>
                <h2 id="kg-register-title">Create your account</h2>
                <p>
                    Start with your details. Next, we'll help you establish
                    your campaign and select your electoral jurisdiction.
                </p>
            </div>

            @if ($errors->any())
                <div class="kg-auth-error" role="alert">
                    <strong>Please review your details.</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}">
                @csrf

                <div class="kg-auth-field">
                    <label for="name">Full name</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        maxlength="150"
                        autocomplete="name"
                        value="{{ old('name') }}"
                        placeholder="Enter your full name"
                    >
                </div>

                <div class="kg-auth-field">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        maxlength="190"
                        autocomplete="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                    >
                </div>

                <div class="kg-auth-field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        minlength="12"
                        autocomplete="new-password"
                        placeholder="Create a secure password"
                    >
                    <small>
                        At least 12 characters, including uppercase,
                        lowercase and numbers.
                    </small>
                </div>

                <div class="kg-auth-field">
                    <label for="password_confirmation">Confirm password</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        minlength="12"
                        autocomplete="new-password"
                        placeholder="Repeat your password"
                    >
                </div>

                <button type="submit" class="kg-auth-primary-button">
                    Create account and continue
                    <span aria-hidden="true">→</span>
                </button>
            </form>

            <p class="kg-auth-bottom-link">
                Already have an account?
                <a href="{{ route('login') }}">Sign in</a>
            </p>

            <div class="kg-auth-security-note">
                <span aria-hidden="true">◆</span>
                Your account is protected by KuraGuard's authentication controls.
            </div>
        </div>
    </section>
</div>
@endsection
