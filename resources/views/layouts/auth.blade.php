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
</head>
<body class="kg-auth">
    <header class="kg-auth-header">
        <a class="kg-public-brand" href="{{ url('/') }}">
            Kura<span>Guard</span>
        </a>
        <a href="{{ url('/') }}">Back to website</a>
    </header>

    <main class="kg-auth-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
