<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agent Network | KuraGuard</title>
    <style>
        :root {
            --navy:#071b24;
            --green:#087b54;
            --bg:#f4f7f8;
            --text:#172b35;
            --muted:#697d88;
            --border:#e1e8ec;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            font-family:Inter,-apple-system,BlinkMacSystemFont,
                "Segoe UI",sans-serif;
            background:var(--bg);
            color:var(--text);
        }
        .layout { display:flex; min-height:100vh; }
        .sidebar {
            width:245px;
            flex-shrink:0;
            background:var(--navy);
            padding:32px 18px;
            color:white;
        }
        .brand {
            font-size:27px;
            font-weight:850;
            letter-spacing:-1px;
            margin-bottom:5px;
        }
        .brand span { color:#3cc78b; }
        .caption {
            color:#92aeb5;
            font-size:11px;
            letter-spacing:2px;
        }
        .nav-label {
            margin:46px 12px 15px;
            font-size:11px;
            letter-spacing:2px;
            color:#91a8b7;
        }
        .nav a {
            display:block;
            color:#c7d6dd;
            text-decoration:none;
            padding:14px 15px;
            border-radius:9px;
            margin-bottom:5px;
            font-weight:600;
        }
        .nav a.active { background:var(--green); color:white; }
        .nav a:hover { background:#17434a; color:white; }
        main { flex:1; min-width:0; padding:34px; }
        .eyebrow {
            color:var(--green);
            font-size:12px;
            font-weight:800;
            letter-spacing:2px;
        }
        h1 {
            font-size:38px;
            letter-spacing:-1.5px;
            margin:12px 0 8px;
        }
        .subtitle { color:var(--muted); margin:0; }
        .header {
            display:flex;
            justify-content:space-between;
            align-items:start;
            gap:20px;
        }
        .demo {
            border:1px solid #f1dca7;
            background:#fff8e9;
            color:#946200;
            padding:13px 16px;
            border-radius:9px;
            font-size:12px;
            font-weight:800;
            white-space:nowrap;
        }
        .stats {
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:15px;
            margin:30px 0;
        }
        .card {
            background:white;
            border:1px solid var(--border);
            border-radius:13px;
            padding:23px;
        }
        .stat-label { color:var(--muted); font-size:14px; }
        .stat-number {
            font-size:38px;
            font-weight:850;
            margin-top:9px;
        }
        .panel {
            background:white;
            border:1px solid var(--border);
            border-radius:13px;
            overflow:hidden;
        }
        .panel-heading { padding:24px; }
        .panel-heading h2 { margin:0 0 6px; font-size:21px; }
        .panel-heading p { margin:0; color:var(--muted); }
        table { width:100%; border-collapse:collapse; }
        th {
            background:#f7f9fa;
            text-align:left;
            padding:15px 20px;
            font-size:11px;
            letter-spacing:1px;
            color:var(--muted);
            text-transform:uppercase;
        }
        td {
            padding:18px 20px;
            border-top:1px solid var(--border);
            font-size:14px;
        }
        td strong { display:block; }
        td small { display:block; color:var(--muted); margin-top:4px; }
        .pill {
            display:inline-block;
            padding:7px 11px;
            border-radius:30px;
            background:#dcfce7;
            color:#166534;
            font-size:12px;
            font-weight:750;
        }
        .pill.waiting { background:#fef3c7; color:#92400e; }
        footer { margin-top:25px; color:var(--muted); font-size:12px; }
        @media(max-width:900px) {
            .layout { display:block; }
            .sidebar { width:100%; }
            main { padding:20px; }
            .stats { grid-template-columns:1fr; }
            .header { flex-direction:column; }
            .panel { overflow-x:auto; }
        }

        /* agent-registration-v1 */
        .registration-panel {
            margin-bottom:24px;
            padding:26px;
        }
        .registration-panel h2 {
            margin:0 0 8px;
            font-size:23px;
        }
        .registration-panel p {
            margin:0 0 22px;
            color:var(--muted);
        }
        .form-grid {
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:17px;
        }
        .field label {
            display:block;
            margin-bottom:9px;
            font-size:13px;
            font-weight:750;
        }
        .field input,.field select {
            width:100%;
            padding:14px;
            border:1px solid #ccd8df;
            border-radius:9px;
            background:white;
            color:var(--text);
            font:inherit;
        }
        .field input:focus,.field select:focus {
            outline:2px solid #65cba4;
            border-color:var(--green);
        }
        .form-actions { margin-top:22px; }
        .submit-button {
            border:0;
            background:var(--green);
            color:white;
            padding:15px 23px;
            border-radius:9px;
            font-size:14px;
            font-weight:800;
            cursor:pointer;
        }
        .submit-button:hover { background:#066747; }
        .alert {
            padding:15px 18px;
            border-radius:9px;
            margin-bottom:20px;
            font-weight:650;
        }
        .alert-success {
            background:#dcfce7;
            color:#166534;
        }
        .alert-error {
            background:#fee2e2;
            color:#991b1b;
        }
        .field-error {
            color:#b42335;
            font-size:12px;
            margin-top:7px;
        }
        @media(max-width:900px) {
            .form-grid { grid-template-columns:1fr; }
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
        <div class="caption">ELECTION INTELLIGENCE</div>
        <div class="nav-label">OPERATIONS</div>
        <nav class="nav">
            <a href="{{ route('command-centre.index') }}">Command Centre</a>
            <a href="{{ route('polling-stations.index') }}">Polling Stations</a>
            <a class="active" href="{{ route('agents.index') }}">Agent Network</a>
            <a href="{{ route('command-centre.index') }}#verification">Result Verification</a>
            <a href="{{ route('command-centre.index') }}#incidents">Incident Monitoring</a>
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
                <div class="eyebrow">ELECTION OPERATIONS / AGENTS</div>
                <h1>Agent Network</h1>
                <p class="subtitle">
                    {{ $election->name }} · Agent assignments and attendance
                </p>
            </div>
            <div class="demo">SIMULATED DEMO DATA</div>
        </div>

        <section class="stats">
            <div class="card">
                <div class="stat-label">Registered Agents</div>
                <div class="stat-number">{{ $totalAgents }}</div>
            </div>
            <div class="card">
                <div class="stat-label">Assigned Agents</div>
                <div class="stat-number">{{ $assigned }}</div>
            </div>
            <div class="card">
                <div class="stat-label">Agents Checked In</div>
                <div class="stat-number">{{ $checkedIn }}</div>
            </div>
        </section>


        <section class="card registration-panel" id="agent-registration">
            <h2>Register New Agent</h2>
            <p>
                Add an election agent to the network. Polling-station
                assignment is optional and can be completed later.
            </p>

            @if(session('success'))
                <div class="alert alert-success" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error" role="alert">
                    Please correct the highlighted fields.
                </div>
            @endif

            <form method="POST" action="{{ route('agents.store') }}">
                @csrf

                <div class="form-grid">
                    <div class="field">
                        <label for="agent-name">Full Name *</label>
                        <input
                            id="agent-name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            maxlength="150"
                            autocomplete="name"
                            required
                        >
                        @error('name')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="agent-phone">Phone Number</label>
                        <input
                            id="agent-phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone') }}"
                            maxlength="30"
                            autocomplete="tel"
                            placeholder="+254..."
                        >
                        @error('phone')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="station-id">Polling Station</label>
                        <select id="station-id" name="polling_station_id">
                            <option value="">Assign later</option>
                            @foreach(
                                \App\Models\PollingStation::query()
                                    ->where('election_id', $election->id)
                                    ->whereDoesntHave('assignments')
                                    ->orderBy('name')
                                    ->get()
                                as $station
                            )
                                <option
                                    value="{{ $station->id }}"
                                    @selected(
                                        old('polling_station_id') == $station->id
                                    )
                                >
                                    {{ $station->name }}
                                    ({{ $station->station_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('polling_station_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button class="submit-button" type="submit">
                        Register Agent
                    </button>
                </div>
            </form>
        </section>



        {{-- Simulated polling station registration. --}}
        <section
            class="card registration-panel"
            id="polling-station-registration"
        >
            <h2>Register Polling Station</h2>
            <p>
                Add a simulated polling station to the election network.
                New stations become available for agent assignment.
            </p>

            <form
                method="POST"
                action="{{ route('polling-stations.store') }}"
            >
                @csrf

                <div class="form-grid">
                    <div class="field">
                        <label for="station-county">County</label>
                        <input
                            id="station-county"
                            name="county"
                            type="text"
                            maxlength="120"
                            value="{{ old('county') }}"
                            placeholder="e.g. Nairobi"
                            required
                        >
                        @error('county')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="station-constituency">
                            Constituency
                        </label>
                        <input
                            id="station-constituency"
                            name="constituency"
                            type="text"
                            maxlength="120"
                            value="{{ old('constituency') }}"
                            placeholder="e.g. Westlands"
                            required
                        >
                        @error('constituency')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="station-ward">Ward</label>
                        <input
                            id="station-ward"
                            name="ward"
                            type="text"
                            maxlength="120"
                            value="{{ old('ward') }}"
                            placeholder="e.g. Parklands"
                            required
                        >
                        @error('ward')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="station-name">
                            Polling Station Name
                        </label>
                        <input
                            id="station-name"
                            name="name"
                            type="text"
                            maxlength="180"
                            value="{{ old('name') }}"
                            placeholder="e.g. Simulated Polling Station 13"
                            required
                        >
                        @error('name')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="station-code">Station Code</label>
                        <input
                            id="station-code"
                            name="station_code"
                            type="text"
                            maxlength="50"
                            pattern="[A-Za-z0-9_-]+"
                            value="{{ old('station_code', 'DEMO-013') }}"
                            placeholder="DEMO-013"
                            required
                        >
                        @error('station_code')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="station-voters">
                            Registered Voters
                        </label>
                        <input
                            id="station-voters"
                            name="registered_voters"
                            type="number"
                            min="0"
                            max="100000"
                            value="{{ old('registered_voters', 500) }}"
                            required
                        >
                        @error('registered_voters')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button class="submit-button" type="submit">
                        Register Polling Station
                    </button>
                </div>
            </form>
        </section>

        {{-- Assignment Management: all data is from the simulated election. --}}
        @php
            $unassignedAgents = $agents->filter(
                fn ($agent) => $agent->assignments->isEmpty()
            );

            $vacantStations = \App\Models\PollingStation::query()
                ->where('election_id', $election->id)
                ->whereDoesntHave('assignments')
                ->orderBy('name')
                ->get();

            $canAssign = $unassignedAgents->isNotEmpty()
                && $vacantStations->isNotEmpty();
        @endphp

        <section class="card registration-panel" id="agent-assignment">
            <h2>Agent Assignment Management</h2>
            <p>
                Assign registered agents to available polling stations.
                Existing assignments are protected against duplication.
            </p>

            <div class="stats" style="margin:0 0 24px">
                <div class="card">
                    <div class="stat-label">Unassigned Agents</div>
                    <div class="stat-number">
                        {{ $unassignedAgents->count() }}
                    </div>
                </div>
                <div class="card">
                    <div class="stat-label">Vacant Polling Stations</div>
                    <div class="stat-number">
                        {{ $vacantStations->count() }}
                    </div>
                </div>
                <div class="card">
                    <div class="stat-label">Current Assignments</div>
                    <div class="stat-number">{{ $assigned }}</div>
                </div>
            </div>

            @if(!$canAssign)
                <div class="alert alert-error" role="status">
                    @if($vacantStations->isEmpty())
                        No vacant polling stations available.
                        Existing station assignments remain protected.
                    @else
                        No unassigned agents are currently available.
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('agents.assign') }}">
                @csrf

                <div class="form-grid">
                    <div class="field">
                        <label for="assign-agent">Select Agent</label>
                        <select
                            id="assign-agent"
                            name="agent_id"
                            required
                            @disabled(!$canAssign)
                        >
                            <option value="">Choose an agent</option>
                            @foreach($unassignedAgents as $agent)
                                <option
                                    value="{{ $agent->id }}"
                                    @selected(old('agent_id') == $agent->id)
                                >
                                    {{ $agent->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('agent_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="assign-station">Polling Station</label>
                        <select
                            id="assign-station"
                            name="polling_station_id"
                            required
                            @disabled(!$canAssign)
                        >
                            <option value="">Choose a vacant station</option>
                            @foreach($vacantStations as $station)
                                <option
                                    value="{{ $station->id }}"
                                    @selected(
                                        old('polling_station_id') == $station->id
                                    )
                                >
                                    {{ $station->name }}
                                    ({{ $station->station_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('polling_station_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button
                        class="submit-button"
                        type="submit"
                        @disabled(!$canAssign)
                        style="{{ !$canAssign ? 'opacity:.5;cursor:not-allowed' : '' }}"
                    >
                        Assign Agent
                    </button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Polling Station Agent Directory</h2>
                <p>Agent deployment and election-day attendance</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Contact</th>
                        <th>Polling Station</th>
                        <th>Ward</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($agents as $agent)
                    @php
                        $assignment = $agent->assignments->first();
                        $station = $assignment?->pollingStation;
                        $present = $agent->assignments->contains(
                            fn ($a) => $a->status === 'checked_in'
                                || $a->checked_in_at !== null
                        );
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $agent->name }}</strong>
                            <small>Agent #{{ $agent->id }}</small>
                        </td>
                        <td>{{ $agent->phone ?: 'Not provided' }}</td>
                        <td>
                            <strong>{{ $station?->name ?? 'Unassigned' }}</strong>
                            <small>{{ $station?->station_code ?? '—' }}</small>
                        </td>
                        <td>{{ $station?->ward ?? '—' }}</td>
                        <td>
                            <span class="pill {{ $present ? '' : 'waiting' }}">
                                {{ $present ? 'Checked In' : 'Not Checked In' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No agents registered.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <footer>
            KuraGuard · Simulated election data for product demonstration only.
        </footer>
    </main>
</div>
</body>
</html>
