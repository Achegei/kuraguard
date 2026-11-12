<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Polling Stations | KuraGuard</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif;
            background: #f3f6f8;
            color: #172c37;
        }
        .layout { display: flex; min-height: 100vh; }
        .sidebar {
            width: 262px; flex-shrink: 0;
            background: #061d26; color: #fff;
            padding: 34px 20px;
        }
        .brand {
            font-size: 29px; font-weight: 850;
            letter-spacing: -1.4px;
        }
        .brand span { color: #16b884; }
        .brand-sub {
            font-size: 11px; letter-spacing: 2px;
            color: #94aeb9; margin-top: 5px;
        }
        .nav-label {
            color: #8fa8b5; font-size: 11px;
            letter-spacing: 2px;
            margin: 48px 12px 18px;
        }
        .nav a {
            display: block; padding: 15px 16px;
            border-radius: 10px; margin-bottom: 7px;
            color: #cbd7dd; font-weight: 700;
            text-decoration: none;
        }
        .nav a:hover { background: #10333d; }
        .nav a.active { background: #07815c; color: #fff; }
        main {
            flex: 1; min-width: 0;
            padding: 40px 36px 60px;
        }
        .eyebrow {
            color: #007f5c; font-size: 12px;
            letter-spacing: 2.2px;
            font-weight: 850;
        }
        .header {
            display: flex; justify-content: space-between;
            align-items: flex-start; gap: 16px;
            margin-bottom: 30px;
        }
        h1 {
            font-size: 39px; margin: 12px 0 6px;
            letter-spacing: -1.6px;
        }
        .subtitle { color: #68808e; font-size: 16px; }
        .demo {
            border: 1px solid #f0d79a;
            background: #fff9eb; color: #9b6200;
            border-radius: 10px; padding: 14px 16px;
            font-size: 12px; font-weight: 850;
            white-space: nowrap;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px; margin-bottom: 26px;
        }
        .card {
            background: white;
            border: 1px solid #dce6eb;
            border-radius: 15px;
        }
        .metric { padding: 25px; }
        .metric-label { color: #657d8b; font-size: 14px; }
        .metric-value {
            font-size: 37px; font-weight: 850;
            letter-spacing: -1px; margin-top: 9px;
        }
        .panel { padding: 26px; margin-bottom: 26px; }
        h2 { font-size: 22px; margin: 0 0 8px; }
        .hint { color: #6b8290; margin: 0 0 22px; }
        .coverage {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }
        .coverage-item {
            border: 1px solid #e1e9ed;
            border-radius: 12px;
            padding: 18px;
        }
        .coverage-item strong {
            display: block; font-size: 28px;
            margin-top: 8px;
        }
        .table-card { overflow-x: auto; }
        .table-heading { padding: 26px 26px 12px; }
        table {
            width: 100%; border-collapse: collapse;
            text-align: left; font-size: 14px;
        }
        th {
            background: #f6f9fa; color: #657d8b;
            font-size: 11px; letter-spacing: 1px;
            text-transform: uppercase;
            padding: 16px 18px;
        }
        td {
            padding: 17px 18px;
            border-top: 1px solid #e3ebef;
            vertical-align: middle;
        }
        td strong { font-size: 14px; }
        .muted { color: #708695; font-size: 12px; margin-top: 4px; }
        .pill {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 12px; font-weight: 800;
            white-space: nowrap;
        }
        .verified { background: #d9fae6; color: #166b3c; }
        .pending { background: #fff0c5; color: #995000; }
        .rejected { background: #ffe0df; color: #a92323; }
        .unreported { background: #eaf0f3; color: #506774; }
        .link {
            color: #087f5c; font-weight: 800;
            text-decoration: none;
        }
        .link:hover { text-decoration: underline; }
        .footer {
            color: #728995; font-size: 12px;
            margin-top: 28px;
        }
        @media (max-width: 1100px) {
            .metrics, .coverage {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 760px) {
            .layout { display: block; }
            .sidebar { width: 100%; padding: 20px; }
            .nav-label { margin-top: 20px; }
            main { padding: 24px 16px; }
            .header { display: block; }
            .demo { display: inline-block; margin-top: 16px; }
            h1 { font-size: 30px; }
        }

        /* KuraGuard account controls */
        .kg-account-panel {
            margin-top: 42px;
            padding: 20px 14px;
            border-top: 1px solid rgba(255,255,255,.18);
        }
        .kg-account-label {
            color: #8fa8b5;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            margin-bottom: 9px;
        }
        .kg-account-name {
            color: #ffffff;
            font-size: 15px;
            font-weight: 750;
            overflow-wrap: anywhere;
            margin-bottom: 18px;
        }
        .kg-logout-button {
            display: block;
            width: 100%;
            min-height: 46px;
            border: 1px solid #36c99c;
            border-radius: 9px;
            background: #087f5c;
            color: #ffffff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
        }
        .kg-logout-button:hover {
            background: #059669;
        }
        .kg-logout-button:focus-visible {
            outline: 3px solid #ffffff;
            outline-offset: 3px;
        }

    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">Kura<span>Guard</span></div>
        <div class="brand-sub">ELECTION INTELLIGENCE</div>

        <div class="nav-label">OPERATIONS</div>
        <nav class="nav">
            <a href="{{ route('command-centre.index') }}">
                Command Centre
            </a>
            <a class="active" href="{{ url('/polling-stations') }}">
                Polling Stations
            </a>
            <a href="{{ route('agents.index') }}">
                Agent Network
            </a>
            <a href="{{ url('/command-centre#verification') }}">
                Result Verification
            </a>
            <a href="{{ url('/command-centre#incidents') }}">
                Incident Monitoring
            </a>
        </nav>

        {{-- Authenticated account controls --}}
        <div class="kg-account-panel">
            <div class="kg-account-label">SIGNED IN AS</div>
            <div class="kg-account-name">
                {{ auth()->user()?->name ?? 'Administrator' }}
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="kg-logout-button">
                    Log Out
                </button>
            </form>
        </div>

    </aside>

    <main>
        <div class="header">
            <div>
                <div class="eyebrow">
                    ELECTION OPERATIONS / POLLING STATIONS
                </div>
                <h1>Polling Stations</h1>
                <div class="subtitle">
                    {{ $election->name }} · Station coverage and reporting
                </div>
            </div>
            <div class="demo">SIMULATED DEMO DATA</div>
        </div>

        <section class="metrics">
            <div class="card metric">
                <div class="metric-label">Total Polling Stations</div>
                <div class="metric-value">{{ $metrics['total'] }}</div>
            </div>
            <div class="card metric">
                <div class="metric-label">Stations With Agents</div>
                <div class="metric-value">{{ $metrics['assigned'] }}</div>
            </div>
            <div class="card metric">
                <div class="metric-label">Agents Checked In</div>
                <div class="metric-value">{{ $metrics['checked_in'] }}</div>
            </div>
            <div class="card metric">
                <div class="metric-label">Verified Stations</div>
                <div class="metric-value">{{ $metrics['verified'] }}</div>
            </div>
        </section>

        <section class="card panel">
            <h2>Reporting Coverage</h2>
            <p class="hint">
                Status is derived from result submissions,
                not the station's operational status.
            </p>
            <div class="coverage">
                <div class="coverage-item">
                    Verified
                    <strong>{{ $metrics['verified'] }}</strong>
                </div>
                <div class="coverage-item">
                    Pending Verification
                    <strong>{{ $metrics['pending'] }}</strong>
                </div>
                <div class="coverage-item">
                    Rejected / Resubmit
                    <strong>{{ $metrics['rejected'] }}</strong>
                </div>
                <div class="coverage-item">
                    Not Reported
                    <strong>{{ $metrics['unreported'] }}</strong>
                </div>
            </div>
        </section>

        <section class="card table-card">
            <div class="table-heading">
                <h2>Polling Station Monitoring</h2>
                <p class="hint">
                    Station-level deployment, attendance and results
                </p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Polling Station</th>
                        <th>Ward</th>
                        <th>Assigned Agent</th>
                        <th>Attendance</th>
                        <th>Reporting</th>
                        <th>Open Incidents</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stations as $station)
                        @php
                            $status = $station->reporting_status;
                            $agent = $station->assigned_agent;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $station->name }}</strong>
                                <div class="muted">
                                    {{ $station->station_code }}
                                </div>
                            </td>
                            <td>{{ $station->ward }}</td>
                            <td>{{ $agent?->name ?? 'Unassigned' }}</td>
                            <td>
                                @if($station->agent_checked_in)
                                    <span class="pill verified">
                                        Checked In
                                    </span>
                                @else
                                    <span class="pill pending">
                                        Not Checked In
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="pill {{ $status }}">
                                    {{ match($status) {
                                        'verified' => 'Verified',
                                        'pending' => 'Pending Review',
                                        'rejected' => 'Rejected / Resubmit',
                                        default => 'Not Reported'
                                    } }}
                                </span>
                            </td>
                            <td>{{ $station->open_incidents }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                No polling stations registered.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <p class="footer">
            KuraGuard · Election Intelligence Platform ·
            All displayed election data is fictional and intended
            solely for product demonstration.
        </p>
    </main>
</div>
</body>
</html>
