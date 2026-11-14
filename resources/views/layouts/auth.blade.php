<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="referrer" content="no-referrer">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign In | KuraGuard')</title>
    <link rel="stylesheet" href="{{ asset('css/kuraguard-premium.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-layouts.css') }}">
@stack('styles')
    <link rel="stylesheet" href="{{ asset('css/kuraguard-navigation-153.css') }}?v=20261114a">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-civic-ticker.css') }}?v=20261114a">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-brand-157c.css') }}?v=20261114c">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=20261114c">
</head>
<body class="kg-auth">
@if (request()->routeIs("register"))
    <div class="kg-register-immersive-ticker"><x-civic-ticker /></div>
@endif
    <header class="kg-auth-header">
        <a class="kg-public-brand" href="{{ url('/') }}">
            <span class="kg-brand-157c">
    <img
        class="kg-brand-image-157c"
        src="{{ asset('images/brand/kuraguard-logo-transparent.png') }}"
        alt="KuraGuard"
    >
    <span class="kg-brand-tagline-157c">
        Every Polling Station. Every Vote. Accounted For.
    </span>
</span>
        </a>
        <div class="kg-auth-header-actions">
            <a href="{{ url('/') }}">← Home</a>
            <a href="{{ route('login') }}">Sign In</a>
        </div>
    </header>

    <main class="kg-auth-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
