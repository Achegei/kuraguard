<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KuraGuard | Election Intelligence')</title>
    <link rel="stylesheet" href="{{ asset('css/kuraguard-premium.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-layouts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-public-nav.css') }}?v=20261113a">
@stack('styles')
    <link rel="stylesheet" href="{{ asset('css/kuraguard-civic-ticker.css') }}?v=20261114a">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-brand-157c.css') }}?v=20261114c">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=20261114c">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-mobile-158b.css') }}?v=20261114b">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-mobile-160b.css') }}?v=20261114d">
</head>
<body class="kg-public">
        <x-civic-ticker />

    <header class="kg-public-header">
        <nav class="kg-public-nav" aria-label="Main navigation">
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

            <button
                id="kg-mobile-menu-toggle"
                class="kg-mobile-menu-toggle"
                type="button"
                aria-label="Open navigation menu"
                aria-controls="kg-public-links"
                aria-expanded="false"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="kg-public-links" id="kg-public-links">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ url('/explorer') }}">Explore Elections</a>
                <a class="kg-nav-register" href="{{ route('register') }}">Register as Aspirant <span aria-hidden="true">↗</span></a>
                <a href="{{ route('join.agent') }}">Become an Agent</a>
                <a href="{{ route('join.mobilizer') }}">Become a Mobilizer</a>
                @auth
                    <a href="{{ url('/campaigns') }}">My Campaigns</a>
                @else
                    <a href="{{ url('/login') }}">Sign In</a>
                @endauth
            </div>
        </nav>
    </header>

    <main id="main-content">
        @yield('content')
    </main>

    <footer class="kg-public-footer">
        <span>KuraGuard · Election Intelligence & Operations</span>
        <span>Independent campaign management platform</span>
    </footer>

        <script src="{{ asset('js/kuraguard-public-nav.js') }}?v=20261113a" defer></script>
    @stack('scripts')
</body>
</html>
