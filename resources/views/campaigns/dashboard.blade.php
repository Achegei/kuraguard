@extends('layouts.campaign')

@section('title', 'Campaign Dashboard | KuraGuard')
@section('breadcrumb', 'Campaign Overview')

@push('styles')
<style>
        :root {
            --navy: #071b24;
            --green: #087b54;
            --bg: #f4f7f8;
            --text: #172b35;
            --muted: #697d88;
            --border: #e1e8ec;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        aside {
            width: 245px;
            flex-shrink: 0;
            background: var(--navy);
            padding: 32px 18px;
            color: white;
        }

        .brand {
            font-size: 27px;
            font-weight: 850;
            letter-spacing: -1px;
        }

        .brand span { color: #3cc78b; }

        .caption {
            margin-top: 5px;
            color: #92aeb5;
            font-size: 11px;
            letter-spacing: 2px;
        }

        .nav-label {
            margin: 46px 12px 15px;
            font-size: 11px;
            letter-spacing: 2px;
            color: #91a8b7;
        }

        .nav-item {
            display: block;
            padding: 14px 15px;
            border-radius: 9px;
            background: var(--green);
            color: white;
            font-weight: 700;
        }

        main {
            flex: 1;
            min-width: 0;
            padding: 34px;
        }

        .eyebrow {
            color: var(--green);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        h1 {
            margin: 12px 0 8px;
            font-size: 36px;
            letter-spacing: -1px;
        }

        .subtitle {
            margin: 0;
            color: var(--muted);
        }

        .contest-info {
            margin-top: 24px;
            padding: 22px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
        }

        .contest-info strong {
            display: block;
            margin-bottom: 7px;
            font-size: 19px;
        }

        .contest-info span {
            color: var(--muted);
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin: 26px 0;
        }

        .stat {
            padding: 23px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 14px;
        }

        .stat-number {
            margin-top: 9px;
            font-size: 35px;
            font-weight: 850;
        }

        .panel {
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
        }

        .panel-heading { padding: 24px; }

        .panel-heading h2 {
            margin: 0 0 6px;
            font-size: 21px;
        }

        .panel-heading p {
            margin: 0;
            color: var(--muted);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 15px 20px;
            background: #f7f9fa;
            text-align: left;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
        }

        td {
            padding: 17px 20px;
            border-top: 1px solid var(--border);
            font-size: 14px;
        }

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            background: #e2e8f0;
            font-size: 12px;
            font-weight: 750;
        }

        .status.approved {
            background: #dcfce7;
            color: #166534;
        }

        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            padding: 30px;
            color: var(--muted);
            text-align: center;
        }

        footer {
            margin-top: 25px;
            color: var(--muted);
            font-size: 12px;
        }

        .deployment-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 26px;
        }

        .deployment-list {
            padding: 0 24px 20px;
        }

        .deployment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 13px 0;
            border-top: 1px solid var(--border);
            font-size: 14px;
        }

        .deployment-item strong {
            font-size: 18px;
            color: var(--green);
        }

        .personnel-panel {
            margin-bottom: 26px;
        }


        .operations-heading {
            margin: 35px 0 17px;
        }

        .operations-heading h2 {
            margin: 0 0 7px;
            font-size: 25px;
        }

        .operations-heading p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .operations-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 26px;
        }

        .operations-card {
            padding: 22px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
        }

        .operations-card-label {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .operations-card-value {
            margin-top: 11px;
            color: var(--navy);
            font-size: 32px;
            font-weight: 850;
        }

        .operations-card.critical {
            border-left: 4px solid #dc2626;
        }

        .operations-card.verified {
            border-left: 4px solid var(--green);
        }

        .operations-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 28px;
        }

        .operations-link {
            display: inline-block;
            padding: 12px 17px;
            border-radius: 9px;
            background: var(--navy);
            color: white;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .operations-link.secondary {
            background: var(--green);
        }

        @media (max-width: 1100px) {
            .operations-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .layout { display: block; }
            aside { width: 100%; }
            main { padding: 20px; }
            .stats { grid-template-columns: 1fr; }
            .operations-grid { grid-template-columns: 1fr; }
            .deployment-grid { grid-template-columns: 1fr; }
            .panel { overflow-x: auto; }
        }
    </style>
@endpush

@section('content')
<div class="eyebrow">Aspirant Campaign Management</div>

        <h1>Campaign Dashboard</h1>

        <p class="subtitle">
            Secure, contest-specific overview of applications
            and field appointments.
        </p>

        <section class="contest-info">
            <strong>{{ $contest->display_name }}</strong>
            <span>
                {{ str_replace('_', ' ', $contest->office) }}
                &middot;
                {{ $contest->election_year }}
                &middot;
                {{ str_replace('_', ' ', $contest->declaration_status) }}
            </span>
        </section>

        <p style="margin: 14px 0 20px;">
            <a class="operations-link secondary" href="{{ route('campaign.discovery-settings.show', ['contestId' => $contest->id]) }}">
                Aspirant discovery &amp; privacy settings
            </a>
        </p>

        <section class="stats">
            @foreach ([
                'Total Applications' => 'total_applications',
                'Pending Review' => 'pending_applications',
                'Approved Applications' => 'approved_applications',
                'Rejected Applications' => 'rejected_applications',
                'Active Assignments' => 'active_assignments',
            ] as $label => $key)
                <div class="stat">
                    <div class="stat-label">{{ $label }}</div>
                    <div class="stat-number">
                        {{ number_format($statistics[$key]) }}
                    </div>
                </div>
            @endforeach
        </section>


        {{-- Campaign-specific election-day monitoring. --}}
        <div class="operations-heading">
            <h2>Election-Day Operations</h2>
            <p>
                Live database-backed operational statistics for this
                campaign. Only verified submissions contribute to
                verified vote totals.
            </p>
        </div>

        <section class="operations-grid">
            @foreach ([
                ['label' => 'Stations Reporting',
                 'key' => 'station_reports',
                 'style' => ''],

                ['label' => 'Stations Checked In',
                 'key' => 'checked_in_stations',
                 'style' => ''],

                ['label' => 'Unresolved Incidents',
                 'key' => 'open_incidents',
                 'style' => ''],

                ['label' => 'Unresolved Critical Incidents',
                 'key' => 'critical_incidents',
                 'style' => 'critical'],

                ['label' => 'Pending Results',
                 'key' => 'pending_results',
                 'style' => ''],

                ['label' => 'Verified Results',
                 'key' => 'verified_results',
                 'style' => 'verified'],

                ['label' => 'Verified Valid Votes',
                 'key' => 'verified_valid_votes',
                 'style' => 'verified'],

                ['label' => 'Verified Rejected Votes',
                 'key' => 'verified_rejected_votes',
                 'style' => ''],
            ] as $metric)
                <div class="operations-card {{ $metric['style'] }}">
                    <div class="operations-card-label">
                        {{ $metric['label'] }}
                    </div>
                    <div class="operations-card-value">
                        {{ number_format($operationsStatistics[$metric['key']]) }}
                    </div>
                </div>
            @endforeach
        </section>

        <div class="operations-actions">
            <a
                class="operations-link"
                href="{{ route('campaign.operations.monitor', ['contestId' => $contest->id]) }}"
            >
                Open Election-Day Command Centre
            </a>

            <a
                class="operations-link secondary"
                href="{{ route('campaign.results.tally', ['contestId' => $contest->id]) }}"
            >
                View Verified Vote Tally
            </a>
        </div>

        <section class="deployment-grid">
            <div class="panel">
                <div class="panel-heading">
                    <h2>Personnel by Role</h2>
                    <p>Current appointments in this campaign.</p>
                </div>

                <div class="deployment-list">
                    @foreach ($personnelByRole as $item)
                        <div class="deployment-item">
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ number_format($item['count']) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="panel">
                <div class="panel-heading">
                    <h2>Deployment by Geography</h2>
                    <p>Active appointments by geographic level.</p>
                </div>

                <div class="deployment-list">
                    @foreach ($deploymentsByLevel as $item)
                        <div class="deployment-item">
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ number_format($item['count']) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="panel personnel-panel">
            <div class="panel-heading">
                <h2>Recent Active Appointments</h2>
                <p>Latest 20 active personnel assignments.</p>
            </div>

            @if ($recentAssignments->isEmpty())
                <div class="empty">
                    No active personnel assignments yet.
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Personnel</th>
                            <th>Role</th>
                            <th>Geographic Level</th>
                            <th>Location</th>
                            <th>Assigned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentAssignments as $assignment)
                            <tr>
                                <td>{{ $assignment->personnel_name }}</td>
                                <td>
                                    {{ str_replace(
                                        '_',
                                        ' ',
                                        $assignment->requested_role
                                    ) }}
                                </td>
                                <td>
                                    {{ str_replace(
                                        '_',
                                        ' ',
                                        $assignment->geographic_level
                                    ) }}
                                </td>
                                <td>
                                    @if ($assignment->geographic_level === 'national')
                                        National
                                    @else
                                        Location #{{ $assignment->geographic_id }}
                                    @endif
                                </td>
                                <td>{{ $assignment->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Recent Role Applications</h2>
                <p>Latest 20 applications for this contest.</p>
            </div>

            @if ($recentApplications->isEmpty())
                <div class="empty">
                    No role applications have been submitted yet.
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Requested Role</th>
                            <th>Geographic Level</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentApplications as $application)
                            <tr>
                                <td>{{ $application->applicant_name }}</td>
                                <td>
                                    {{ str_replace(
                                        '_',
                                        ' ',
                                        $application->requested_role
                                    ) }}
                                </td>
                                <td>
                                    {{ ucfirst(str_replace(
                                        '_',
                                        ' ',
                                        $application->geographic_level
                                    )) }}
                                </td>
                                <td>
                                    <span class="status {{ in_array(
                                        $application->status,
                                        ['pending', 'approved', 'rejected'],
                                        true
                                    ) ? $application->status : '' }}">
                                        {{ ucfirst($application->status) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $application->created_at }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <footer>
            KuraGuard &middot; Campaign-specific workspace
            &middot; Read-only overview
        </footer>
@endsection
