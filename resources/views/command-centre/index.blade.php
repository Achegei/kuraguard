<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KuraGuard | Election Command Centre</title>
    <style>
        :root {
            --navy: #0b1730;
            --navy-light: #172a49;
            --blue: #2563eb;
            --red: #dc3545;
            --bg: #f3f6fb;
            --border: #e1e7f0;
            --text: #18263d;
            --muted: #68778c;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .shell { display: flex; min-height: 100vh; }
        .sidebar { width: 258px; flex-shrink: 0; background: var(--navy); color: white; padding: 32px 20px; }
        .brand { font-size: 28px; font-weight: 850; letter-spacing: -1px; padding: 0 14px; }
        .brand span { color: #ff5263; }
        .brand-caption { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #9bb0d0; margin: 8px 14px 40px; }
        .nav-label { margin: 26px 14px 12px; font-size: 11px; letter-spacing: 1.8px; color: #8299bc; text-transform: uppercase; }
        .nav-item { display: block; padding: 15px; border-radius: 10px; font-size: 15px; color: #c6d3e8; text-decoration: none; margin-bottom: 5px; }
        .nav-item.active { color: white; background: #233c62; font-weight: 700; }
        .main { flex: 1; min-width: 0; padding: 36px 42px 70px; }
        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap; }
        .eyebrow { font-size: 12px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--blue); }
        h1 { font-size: clamp(28px, 3vw, 43px); letter-spacing: -1.4px; margin: 10px 0 8px; }
        .subtitle { color: var(--muted); font-size: 16px; margin: 0; }
        .demo { display: inline-block; background: #fff1f2; border: 1px solid #fecdd3; color: #b42335; border-radius: 8px; padding: 12px 16px; font-weight: 800; font-size: 12px; letter-spacing: 1px; }
        .section-title { font-size: 21px; font-weight: 800; letter-spacing: -.5px; margin: 35px 0 17px; }
        .metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-top: 32px; }
        .card { background: white; border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 4px 20px rgba(15,32,60,.035); }
        .metric { padding: 23px; }
        .metric-label { font-size: 13px; color: var(--muted); font-weight: 650; }
        .metric-number { font-size: 39px; font-weight: 850; letter-spacing: -1.5px; margin-top: 12px; }
        .metric-note { font-size: 12px; color: var(--muted); margin-top: 6px; }
        .two-col { display: grid; grid-template-columns: 1.15fr .85fr; gap: 20px; }
        .panel { padding: 26px; }
        .panel h2 { margin: 0 0 7px; font-size: 20px; letter-spacing: -.4px; }
        .panel-sub { margin: 0 0 24px; font-size: 13px; color: var(--muted); }
        .candidate { margin: 0 0 24px; }
        .candidate-head { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 10px; align-items: center; }
        .candidate-name { font-size: 16px; font-weight: 750; }
        .candidate-party { font-size: 12px; color: var(--muted); margin-top: 3px; }
        .candidate-votes { font-size: 22px; font-weight: 850; font-variant-numeric: tabular-nums; }
        .track { height: 13px; background: #edf1f7; border-radius: 20px; overflow: hidden; }
        .fill { height: 100%; background: var(--blue); border-radius: 20px; }
        .candidate:nth-of-type(3) .fill { background: #577da9; }
        .candidate:nth-of-type(4) .fill { background: #94a7c0; }
        .coverage-row { display: flex; align-items: center; justify-content: space-between; padding: 17px 0; border-bottom: 1px solid var(--border); font-size: 15px; }
        .coverage-row:last-child { border-bottom: 0; }
        .coverage-row strong { font-size: 22px; }
        .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 10px; background: #a0aec0; }
        .dot.green { background: #16a34a; }
        .dot.amber { background: #f59e0b; }
        .dot.red { background: #e11d48; }
        .table-wrap { overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; text-align: left; }
        th { padding: 15px; background: #f7f9fc; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); white-space: nowrap; }
        td { padding: 16px 15px; font-size: 14px; border-bottom: 1px solid var(--border); }
        tr:last-child td { border-bottom: none; }
        .pill { display: inline-block; border-radius: 30px; padding: 6px 10px; font-size: 11px; font-weight: 800; }
        .pill.verified { background: #dcfce7; color: #166534; }
        .pill.pending { background: #fef3c7; color: #92400e; }
        .pill.unreported { background: #fee2e2; color: #991b1b; }
        .footer { color: var(--muted); font-size: 12px; margin-top: 35px; }
        @media (max-width: 1200px) { .metrics { grid-template-columns: repeat(2, minmax(0,1fr)); } .two-col { grid-template-columns: 1fr; } }
        @media (max-width: 750px) { .sidebar { display: none; } .main { padding: 25px 16px; } .metrics { gap: 10px; } .metric { padding: 17px; } .metric-number { font-size: 31px; } }

/* presentation-dashboard-v1 */
:root {
    --navy: #071b24;
    --navy-light: #12333a;
    --blue: #087b54;
    --red: #e5484d;
    --bg: #f5f7f8;
    --border: #e5e9ed;
    --text: #172b35;
    --muted: #6d7d86;
}

body {
    background: var(--bg);
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.sidebar {
    width: 245px;
    background: linear-gradient(180deg, #071b24 0%, #102b34 100%);
    padding: 28px 17px;
}

.brand {
    font-size: 26px;
    letter-spacing: -1.2px;
}

.brand span {
    color: #3cc78b;
}

.brand-caption {
    color: #91b0b2;
}

.nav-item {
    border-radius: 9px;
    padding: 13px 15px;
}

.nav-item.active {
    background: #087b54;
}

.nav-item:hover {
    background: #17434a;
    color: white;
}

.main {
    padding: 28px 34px 60px;
}

.eyebrow {
    color: #087b54;
}

h1 {
    font-size: clamp(29px, 2.7vw, 39px);
    letter-spacing: -1.2px;
}

.metrics {
    gap: 13px;
    margin-top: 24px;
}

.card {
    border-radius: 12px;
    box-shadow: 0 3px 14px rgba(18, 43, 52, .035);
}

.metric {
    padding: 20px;
}

.metric-number {
    font-size: 36px;
    margin-top: 9px;
}

.section-title {
    margin: 27px 0 14px;
}

.panel {
    padding: 23px;
}

.panel h2 {
    font-size: 18px;
}

.fill {
    background: #087b54;
}

.candidate:nth-of-type(3) .fill {
    background: #1789c8;
}

.candidate:nth-of-type(4) .fill {
    background: #e9a23b;
}

.track {
    height: 10px;
}

.demo {
    background: #fff8e9;
    border-color: #f1dca7;
    color: #946200;
}

a:focus-visible,
button:focus-visible {
    outline: 3px solid #37c98d;
    outline-offset: 3px;
}

@media (max-width: 750px) {
    .main {
        padding: 22px 16px;
    }
}


/* operations-overview-v2 */
.overview-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(300px, 1fr);
    gap: 16px;
    margin-top: 24px;
}
.overview-panel { padding: 22px; }
.overview-heading {
    font-size: 18px;
    font-weight: 800;
    margin: 0 0 6px;
}
.overview-caption {
    color: var(--muted);
    font-size: 12px;
    margin: 0 0 18px;
}
.station-visual {
    background: #f5faf7;
    border: 1px solid #e1eee7;
    border-radius: 12px;
    padding: 14px;
}
.station-visual svg {
    display: block;
    width: 100%;
    height: 220px;
}
.station-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 13px;
    font-size: 12px;
    color: var(--muted);
}
.activity-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
}
.activity-row:last-child { border-bottom: 0; }
.activity-row a {
    color: #087b54;
    font-weight: 750;
    text-decoration: none;
}
.activity-row small {
    display: block;
    color: var(--muted);
    margin-top: 5px;
}
.overview-results {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 16px;
}
.overview-result { padding: 17px; }
.overview-result strong {
    display: block;
    font-size: 24px;
    margin-top: 8px;
}
.overview-result span {
    font-size: 12px;
    color: var(--muted);
}
@media (max-width: 1100px) {
    .overview-grid { grid-template-columns: 1fr; }
}
@media (max-width: 700px) {
    .overview-results { grid-template-columns: 1fr; }
}


/* navigation-enhancement-v1 */
html {
    scroll-behavior: smooth;
    scroll-padding-top: 28px;
}

.section-title[id],
.metric[id] {
    scroll-margin-top: 28px;
}

.nav-item {
    transition: background .18s ease, color .18s ease;
}

.nav-item:focus-visible {
    outline: 2px solid #40d9a2;
    outline-offset: 3px;
}

@media (prefers-reduced-motion: reduce) {
    html { scroll-behavior: auto; }
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
<div class="shell">
    <aside class="sidebar">
        <div class="brand">Kura<span>Guard</span></div>
        <div class="brand-caption">Election Intelligence</div>
        <div class="nav-label">Operations</div>
        <a class="nav-item active" href="{{ url('/command-centre') }}">Command Centre</a>
        <a class="nav-item" href="{{ route('polling-stations.index') }}">Polling Stations</a>
        <a class="nav-item" href="{{ route('agents.index') }}">Agent Network</a>
        <a class="nav-item" href="#verification">Result Verification</a>
        <a class="nav-item" href="#incidents">Incident Monitoring</a>
        <div class="nav-label">Environment</div>
        <div class="nav-item">Simulated Election</div>

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

    <main class="main">
        <div class="top">
            <div>
                <div class="eyebrow">Election Operations / Overview</div>
                <h1>Election Command Centre</h1>
                <p class="subtitle">{{ $election->name }} · {{ $election->office }}</p>
            </div>
            <span class="demo">SIMULATED DEMO DATA</span>
        </div>

        <section class="metrics">
            <div class="card metric">
                <div class="metric-label">Polling Stations</div>
                <div class="metric-number">{{ number_format($metrics['stations']) }}</div>
                <div class="metric-note">Total registered stations</div>
            </div>
            <div class="card metric">
                <div class="metric-label">Verified Stations</div>
                <div class="metric-number">{{ number_format($metrics['verified']) }}</div>
                <div class="metric-note">Approved results only</div>
            </div>
            <div class="card metric">
                <div class="metric-label">Verified Votes</div>
                <div class="metric-number">{{ number_format($metrics['verified_votes']) }}</div>
                <div class="metric-note">Excludes pending submissions</div>
            </div>
            <div class="card metric" id="agents">
                <div class="metric-label">Agents Checked In</div>
                <div class="metric-number">{{ $metrics['checked_in'] }}<span style="font-size:19px;color:#9aa7b9"> / {{ $metrics['agents'] }}</span></div>
                <div class="metric-note">Polling-station attendance</div>
            </div>
        </section>


        {{-- Compact presentation overview: all counts come from demo data. --}}
        <section class="overview-grid">
            <div class="card overview-panel">
                <h2 class="overview-heading">Polling Station Coverage</h2>
                <p class="overview-caption">
                    {{ $metrics['stations'] }} simulated stations ·
                    {{ $metrics['verified'] }} verified ·
                    {{ $metrics['pending'] }} pending ·
                    {{ $metrics['unreported'] }} not yet reported
                </p>

                <div class="station-visual">
                    <svg viewBox="0 0 560 220"
                         role="img"
                         aria-label="Illustrative polling station coverage diagram">
                        <path d="M75 45 L155 17 L250 35 L325 12
                                 L465 54 L515 113 L460 177
                                 L335 195 L235 175 L145 204
                                 L55 150 L38 91 Z"
                              fill="#e4f1e9"
                              stroke="#aacbb8"
                              stroke-width="2"/>

                        @foreach ($stations as $index => $station)
                            @php
                                $verified = $station->submissions
                                    ->contains('status', 'verified');

                                $pending = $station->submissions
                                    ->contains('status', 'pending');

                                $colour = $verified
                                    ? '#16a34a'
                                    : ($pending ? '#f59e0b' : '#e11d48');

                                $positions = [
                                    [105,85], [165,55], [225,95],
                                    [285,50], [365,80], [440,110],
                                    [115,145], [195,155], [275,125],
                                    [350,155], [415,145], [295,175]
                                ];

                                [$x, $y] = $positions[$index % count($positions)];
                            @endphp

                            <circle cx="{{ $x }}"
                                    cy="{{ $y }}"
                                    r="11"
                                    fill="{{ $colour }}"
                                    stroke="#ffffff"
                                    stroke-width="3">
                                <title>{{ $station->name }}</title>
                            </circle>
                        @endforeach
                    </svg>
                </div>

                <div class="station-legend">
                    <span><span class="dot green"></span>Verified</span>
                    <span><span class="dot amber"></span>Pending</span>
                    <span><span class="dot red"></span>Not yet reported / other</span>
                </div>

                <p class="overview-caption" style="margin-top:13px;margin-bottom:0">
                    Illustrative layout only. Marker positions are not geographical coordinates.
                </p>
            </div>

            <div class="card overview-panel">
                <h2 class="overview-heading">Recent Submissions</h2>
                <p class="overview-caption">Pending result reviews requiring action</p>

                @forelse ($pendingSubmissions->take(5) as $submission)
                    <div class="activity-row">
                        <div>
                            <a href="{{ route('verification.show', $submission->id) }}">
                                {{ $submission->station_name }}
                            </a>
                            <small>
                                Submission #{{ $submission->id }} ·
                                {{ number_format($submission->valid_votes) }} valid votes
                            </small>
                        </div>
                        <span class="pill pending">Pending</span>
                    </div>
                @empty
                    <p class="overview-caption">
                        No pending submissions.
                    </p>
                @endforelse

                <a href="#verification"
                   style="display:inline-block;margin-top:20px;
                          color:#087b54;font-weight:800;text-decoration:none">
                    View verification queue →
                </a>
            </div>
        </section>

        <section class="overview-results">
            <div class="card overview-result">
                <span>Verified Votes</span>
                <strong>{{ number_format($metrics['verified_votes']) }}</strong>
            </div>
            <div class="card overview-result">
                <span>Agents Checked In</span>
                <strong>{{ $metrics['checked_in'] }} / {{ $metrics['agents'] }}</strong>
            </div>
            <div class="card overview-result">
                <span>Open Incidents</span>
                <strong>{{ $metrics['incidents'] }}</strong>
            </div>
        </section>

        <div class="section-title">Live Operations Snapshot</div>
        <section class="two-col">
            <div class="card panel">
                <h2>Verified Candidate Tallies</h2>
                <p class="panel-sub">Simulated votes from verified submissions only</p>
                @php $maximum = max(1, $candidateTotals->max('votes') ?? 1); @endphp
                @foreach ($candidateTotals as $candidate)
                    <div class="candidate">
                        <div class="candidate-head">
                            <div>
                                <div class="candidate-name">{{ $candidate['name'] }}</div>
                                <div class="candidate-party">{{ $candidate['party'] }}</div>
                            </div>
                            <div class="candidate-votes">{{ number_format($candidate['votes']) }}</div>
                        </div>
                        <div class="track">
                            <div class="fill" style="width: {{ ($candidate['votes'] / $maximum) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card panel">
                <h2>Station Reporting Coverage</h2>
                <p class="panel-sub">Reporting status across the constituency</p>
                <div class="coverage-row"><span><span class="dot green"></span>Verified</span><strong>{{ $metrics['verified'] }}</strong></div>
                <div class="coverage-row"><span><span class="dot amber"></span>Pending verification</span><strong>{{ $metrics['pending'] }}</strong></div>
                <div class="coverage-row"><span><span class="dot red"></span>Not yet reported</span><strong>{{ $metrics['unreported'] }}</strong></div>
                <div class="coverage-row"><span><span class="dot red"></span>Rejected / resubmit</span><strong>{{ $metrics['rejected'] }}</strong></div>
                <div class="coverage-row"><span>Open incidents</span><strong>{{ $metrics['incidents'] }}</strong></div>
            </div>
        </section>

        <div class="section-title" id="stations">Polling Station Monitoring</div>
        <section class="card table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Station</th>
                        <th>Ward</th>
                        <th>Assigned Agent</th>
                        <th>Reporting Status</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($stations as $station)
                    @php
                        $isVerified = $station->submissions->contains('status', 'verified');
                        $isPending = $station->submissions->contains('status', 'pending');
                        $isRejected = $station->submissions->contains('status', 'rejected');
                        $status = $isVerified
                            ? 'verified'
                            : ($isPending
                                ? 'pending'
                                : ($isRejected ? 'rejected' : 'unreported'));
                        $agent = $station->assignments->first()?->agent;
                    @endphp
                    <tr>
                        <td><strong>{{ $station->name }}</strong><div class="candidate-party">{{ $station->station_code }}</div></td>
                        <td>{{ $station->ward }}</td>
                        <td>{{ $agent?->name ?? 'Unassigned' }}</td>
                        <td><span class="pill {{ $status }}">{{ ucfirst($status) }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>

        <div class="section-title" id="verification">Verification Queue</div>
        <section class="card table-wrap">
            <table>
                <thead><tr><th>Submission</th><th>Polling Station</th><th>Valid Votes</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($pendingSubmissions as $submission)
                    <tr>
                        <td>
                            <a href="{{ route('verification.show', $submission->id) }}"
                               style="color:#2563eb;font-weight:800;text-decoration:none">
                                Review #{{ $submission->id }} →
                            </a>
                        </td>
                        <td>{{ $submission->station_name }} ({{ $submission->station_code }})</td>
                        <td>{{ number_format($submission->valid_votes) }}</td>
                        <td><span class="pill pending">Awaiting review</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4">No pending submissions.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <div class="section-title" id="incidents">Incident Monitoring</div>
        <section class="card table-wrap">
            <table>
                <thead><tr><th>Incident</th><th>Station</th><th>Severity</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($incidents as $incident)
                    <tr>
                        <td>{{ $incident->category }}</td>
                        <td>{{ $incident->station_name }}</td>
                        <td>{{ ucfirst($incident->severity) }}</td>
                        <td>{{ ucfirst($incident->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No incidents reported.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <p class="footer">KuraGuard · Election Intelligence Platform · All displayed election data is fictional and intended solely for product demonstration.</p>
    </main>
</div>
</body>
</html>
