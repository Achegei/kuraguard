<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KuraGuard | Election Intelligence')</title>
    <link rel="stylesheet" href="{{ asset('css/kuraguard-premium.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-layouts.css') }}">
@stack('styles')
</head>
<body class="kg-public">
    <header class="kg-public-header">
        <nav class="kg-public-nav" aria-label="Main navigation">
            <a class="kg-public-brand" href="{{ url('/') }}">
                Kura<span>Guard</span>
            </a>

            <div class="kg-public-links">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ url('/explorer') }}">Explore Elections</a>
                <a href="{{ route('register') }}">Register as Aspirant</a>
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

    @stack('scripts')
</body>
</html>
