@extends('layouts.campaign')

@section('title', 'Election Operations | KuraGuard')
@section('breadcrumb', 'Election Operations')

@push('styles')
<style>
        :root {
            --navy: #101c32;
            --green: #16a34a;
            --background: #f3f6fa;
            --border: #e2e8f0;
            --muted: #64748b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--background);
            color: #172338;
            font-family: Inter, system-ui, -apple-system, sans-serif;
        }

        .layout { display: flex; min-height: 100vh; }

        aside {
            width: 250px;
            background: var(--navy);
            color: white;
            padding: 30px 22px;
            flex-shrink: 0;
        }

        .brand { font-size: 26px; font-weight: 850; }
        .brand span { color: #4ade80; }

        .caption {
            margin-top: 7px;
            font-size: 10px;
            letter-spacing: 2px;
            color: #94a3b8;
        }

        .sidebar-link {
            display: block;
            margin-top: 30px;
            padding: 13px 15px;
            border-radius: 9px;
            color: #e2e8f0;
            text-decoration: none;
            font-size: 14px;
        }

        .sidebar-link:hover { background: #263750; }

        main {
            flex: 1;
            min-width: 0;
            padding: 35px;
            max-width: 1650px;
        }

        .eyebrow {
            color: var(--green);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        h1 { margin: 8px 0; font-size: 31px; }

        .subtitle {
            color: var(--muted);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat {
            background: white;
            padding: 22px;
            border: 1px solid var(--border);
            border-radius: 13px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.5;
        }

        .stat-value {
            font-size: 30px;
            font-weight: 850;
            margin-top: 12px;
        }

        .stat.warning { border-left: 4px solid #f59e0b; }
        .stat.success { border-left: 4px solid var(--green); }

        .panel {
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .panel-heading {
            padding: 23px;
            border-bottom: 1px solid var(--border);
        }

        .panel-heading h2 { margin: 0; font-size: 19px; }

        .panel-heading p {
            color: var(--muted);
            margin: 7px 0 0;
            font-size: 13px;
        }

        .table-scroll { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        th, td {
            padding: 15px 20px;
            border-bottom: 1px solid #edf2f7;
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .pill {
            display: inline-block;
            border-radius: 30px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 750;
            background: #e2e8f0;
            color: #334155;
        }

        .pill.checked_in,
        .pill.verified,
        .pill.resolved {
            background: #dcfce7;
            color: #166534;
        }

        .pill.pending,
        .pill.medium {
            background: #fef3c7;
            color: #92400e;
        }

        .pill.critical,
        .pill.high,
        .pill.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            padding: 30px 23px;
            color: var(--muted);
            font-size: 14px;
        }

        .note {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 1250px) {
            .stats { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 900px) {
            .layout { display: block; }
            aside { width: 100%; padding: 22px; }
            main { padding: 20px; }
            .stats { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 550px) {
            .stats { grid-template-columns: 1fr; }
            h1 { font-size: 25px; }
        }
    </style>
@endpush

@section('content')
@if (session('status'))
            <div
                role="status"
                style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 9px; margin-bottom: 20px;"
            >
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                role="alert"
                style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 9px; margin-bottom: 20px;"
            >
                <strong>Review could not be completed:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="eyebrow">Election-Day Operations</div>
        <h1>Campaign Command Centre</h1>

        <p class="subtitle">
            {{ $contest->display_name }} ·
            {{ strtoupper(str_replace('_', ' ', $contest->office)) }} ·
            {{ $contest->election_year }}
        </p>

        <section class="stats">
            @foreach ([
                ['label' => 'Stations Reporting',
                 'key' => 'station_reports', 'style' => ''],
                ['label' => 'Stations Checked In',
                 'key' => 'checked_in_stations', 'style' => 'success'],
                ['label' => 'Unresolved Incidents',
                 'key' => 'open_incidents', 'style' => 'warning'],
                ['label' => 'Pending Results',
                 'key' => 'pending_results', 'style' => 'warning'],
                ['label' => 'Verified Results',
                 'key' => 'verified_results', 'style' => 'success'],
            ] as $metric)
                <div class="stat {{ $metric['style'] }}">
                    <div class="stat-label">{{ $metric['label'] }}</div>
                    <div class="stat-value">
                        {{ number_format($statistics[$metric['key']]) }}
                    </div>
                </div>
            @endforeach
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Polling Station Attendance</h2>
                <p>Latest 100 station reports from campaign agents.</p>
            </div>

            @if ($stationReports->isEmpty())
                <div class="empty">No polling station reports received yet.</div>
            @else
                <div class="table-scroll">
                    <table>
                        <thead>
                        <tr>
                            <th>Station ID</th>
                            <th>Attendance</th>
                            <th>Reported Turnout</th>
                            <th>Updated</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($stationReports as $report)
                            <tr>
                                <td>{{ $report->electoral_polling_station_id }}</td>
                                <td>
                                    <span class="pill {{ $report->attendance_status }}">
                                        {{ ucwords(str_replace('_', ' ', $report->attendance_status)) }}
                                    </span>
                                </td>
                                <td>{{ $report->reported_turnout ?? '—' }}</td>
                                <td>{{ $report->updated_at }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Incident Monitoring</h2>
                <p>Latest 100 campaign incident reports, lifecycle actions and status history.</p>
            </div>

            @if ($incidents->isEmpty())
                <div class="empty">No incidents have been reported.</div>
            @else
                <div class="table-scroll">
                    <table>
                        <thead>
                        <tr>
                            <th>Station ID</th>
                            <th>Category</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Lifecycle History</th>
                            @if ($canManageIncidents)
                                <th>Management Actions</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($incidents as $incident)
                            <tr>
                                <td>{{ $incident->electoral_polling_station_id }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $incident->category)) }}</td>
                                <td>
                                    <span class="pill {{ $incident->severity }}">
                                        {{ ucfirst($incident->severity) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="pill {{ $incident->status }}">
                                        {{ ucfirst($incident->status) }}
                                    </span>
                                </td>
                                <td>{{ $incident->description }}</td>

                                <td>
                                    @forelse ($incidentHistory->get($incident->id, collect()) as $change)
                                        <div style="margin-bottom: 12px; border-left: 3px solid #cbd5e1; padding-left: 10px;">
                                            <strong>
                                                {{ ucfirst($change->from_status) }}
                                                →
                                                {{ ucfirst($change->to_status) }}
                                            </strong>

                                            <div class="note">
                                                By {{ $change->changed_by_name }}
                                            </div>

                                            <div class="note">
                                                {{ $change->created_at }}
                                            </div>

                                            @if ($change->notes)
                                                <div style="margin-top: 5px; white-space: pre-wrap;">
                                                    {{ $change->notes }}
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="note">No status changes yet.</span>
                                    @endforelse
                                </td>

                                @if ($canManageIncidents)
                                    <td>
                                        @php
                                            $allowedTransitions = match ($incident->status) {
                                                'open' => ['acknowledged', 'escalated'],
                                                'acknowledged' => ['investigating', 'escalated'],
                                                'investigating' => ['escalated', 'resolved'],
                                                'escalated' => ['investigating', 'resolved'],
                                                default => [],
                                            };
                                        @endphp

                                        @if (count($allowedTransitions) > 0)
                                            <form
                                                method="POST"
                                                action="{{ route('campaign.operations.incidents.transition', [
                                                    'contestId' => $contest->id,
                                                    'incidentId' => $incident->id,
                                                ]) }}"
                                                style="min-width: 220px;"
                                            >
                                                @csrf

                                                <label
                                                    for="incident-status-{{ $incident->id }}"
                                                    class="note"
                                                >
                                                    Change Status
                                                </label>

                                                <select
                                                    id="incident-status-{{ $incident->id }}"
                                                    name="status"
                                                    required
                                                    style="display: block; width: 100%; padding: 9px; margin: 7px 0 10px; border: 1px solid #cbd5e1; border-radius: 7px;"
                                                >
                                                    @foreach ($allowedTransitions as $nextStatus)
                                                        <option value="{{ $nextStatus }}">
                                                            {{ ucfirst($nextStatus) }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <label
                                                    for="incident-notes-{{ $incident->id }}"
                                                    class="note"
                                                >
                                                    Notes (required for resolution)
                                                </label>

                                                <textarea
                                                    id="incident-notes-{{ $incident->id }}"
                                                    name="notes"
                                                    maxlength="5000"
                                                    rows="3"
                                                    style="display: block; width: 100%; padding: 9px; margin: 7px 0 10px; border: 1px solid #cbd5e1; border-radius: 7px;"
                                                ></textarea>

                                                <button
                                                    type="submit"
                                                    style="background: #1d4ed8; color: white; border: 0; border-radius: 7px; padding: 9px 13px; cursor: pointer; font-weight: 700;"
                                                >
                                                    Update Incident
                                                </button>
                                            </form>
                                        @else
                                            <span class="pill resolved">
                                                No Further Actions
                                            </span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Result Submission Queue</h2>
                <p>
                    Recent station results and supporting evidence.
                    Authorized reviewers can verify or reject pending results after examining supporting evidence.
                </p>
            </div>

            @if ($resultSubmissions->isEmpty())
                <div class="empty">No station results have been submitted.</div>
            @else
                <div class="table-scroll">
                    <table>
                        <thead>
                        <tr>
                            <th>Submission</th>
                            <th>Station ID</th>
                            <th>Valid Votes</th>
                            <th>Rejected Votes</th>
                            <th>Evidence Files</th>
                            <th>Status</th>
                            @if ($canReviewResults)
                                <th>Review Actions</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($resultSubmissions as $submission)
                            <tr>
                                <td>#{{ $submission->id }}</td>
                                <td>{{ $submission->electoral_polling_station_id }}</td>
                                <td>{{ number_format($submission->valid_votes) }}</td>
                                <td>{{ number_format($submission->rejected_votes) }}</td>
                                <td>
                                    <div>{{ $submission->evidence_count }} file(s)</div>

                                    @if ($canReviewResults)
                                        @foreach (
                                            $evidenceBySubmission->get($submission->id, collect())
                                            as $evidence
                                        )
                                            <div style="margin-top: 7px;">
                                                <a
                                                    href="{{ route('campaign.results.evidence.download', [
                                                        'contestId' => $contest->id,
                                                        'submissionId' => $submission->id,
                                                        'evidenceId' => $evidence->id,
                                                    ]) }}"
                                                    style="color: #166534; font-weight: 700;"
                                                >
                                                    Download Evidence #{{ $evidence->id }}
                                                </a>
                                                @if ($evidence->form_type)
                                                    <span class="note">
                                                        ({{ ucwords(str_replace('_', ' ', $evidence->form_type)) }})
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                                <td>
                                    <span class="pill {{ $submission->status }}">
                                        {{ ucfirst($submission->status) }}
                                    </span>
                                </td>

                                @if ($canReviewResults)
                                    <td>
                                        @if ($submission->status === 'pending')
                                            <form
                                                method="POST"
                                                action="{{ route('campaign.results.web-review', [
                                                    'contestId' => $contest->id,
                                                    'submissionId' => $submission->id,
                                                ]) }}"
                                                style="margin-bottom: 12px;"
                                                onsubmit="return confirm('Verify this result submission?');"
                                            >
                                                @csrf
                                                <input type="hidden" name="decision" value="verified">

                                                <button
                                                    type="submit"
                                                    style="background: #166534; color: white; border: 0; border-radius: 7px; padding: 9px 13px; cursor: pointer; font-weight: 700;"
                                                >
                                                    Verify
                                                </button>
                                            </form>

                                            <form
                                                method="POST"
                                                action="{{ route('campaign.results.web-review', [
                                                    'contestId' => $contest->id,
                                                    'submissionId' => $submission->id,
                                                ]) }}"
                                                onsubmit="return confirm('Reject this result submission?');"
                                            >
                                                @csrf
                                                <input type="hidden" name="decision" value="rejected">

                                                <label
                                                    for="reason-{{ $submission->id }}"
                                                    style="display: block; margin-bottom: 6px;"
                                                >
                                                    Rejection reason
                                                </label>

                                                <textarea
                                                    id="reason-{{ $submission->id }}"
                                                    name="reason"
                                                    required
                                                    minlength="10"
                                                    maxlength="2000"
                                                    rows="3"
                                                    style="width: 220px; max-width: 100%; border: 1px solid #cbd5e1; border-radius: 7px; padding: 8px;"
                                                ></textarea>

                                                <button
                                                    type="submit"
                                                    style="background: #991b1b; color: white; border: 0; border-radius: 7px; padding: 9px 13px; margin-top: 7px; cursor: pointer; font-weight: 700;"
                                                >
                                                    Reject
                                                </button>
                                            </form>
                                        @else
                                            <span class="note">Review completed</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <p class="note">
            This monitoring interface displays campaign-specific records.
            Only authorized reviewers can approve results.
            Vote totals remain provisional campaign records and are not official IEBC results.
        </p>
@endsection
