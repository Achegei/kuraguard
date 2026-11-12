<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Campaign Workspace | KuraGuard')</title>
    <link rel="stylesheet" href="{{ asset('css/kuraguard-campaign.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-premium.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-layouts.css') }}">
@stack('styles')
</head>
<body class="kg-campaign">
    <div class="kg-campaign-shell">
        @include('campaigns.partials.unified-sidebar')

        <div class="kg-campaign-content">
            <header class="kg-workspace-topbar">
                <div class="kg-workspace-topbar-label">
                    KuraGuard / @yield('breadcrumb', 'Campaign Workspace')
                </div>

                <div class="kg-workspace-topbar-user">
                    @auth
                        {{ auth()->user()->name }}
                    @endauth
                </div>
            </header>

            <main class="kg-workspace-main">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
