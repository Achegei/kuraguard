@extends('layouts.campaign')

@section('title', 'Campaign Reports | KuraGuard')
@section('breadcrumb', 'Campaign Reports')

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
        .layout { display: flex; min-height: 100vh; }
        main { flex: 1; min-width: 0; padding: 38px; }
        .eyebrow {
            color: var(--green);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }
        h1 { margin: 12px 0; font-size: clamp(26px, 3vw, 38px); }
        .subtitle { color: var(--muted); line-height: 1.6; }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin: 30px 0;
        }
        .stat, .panel {
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
        }
        .stat span { display: block; color: var(--muted); }
        .stat strong {
            display: block;
            margin-top: 12px;
            font-size: 34px;
        }
        .panel { margin-top: 26px; overflow-x: auto; }
        .panel h2 { margin-top: 0; font-size: 21px; }
        .empty {
            padding: 48px 16px;
            text-align: center;
            color: var(--muted);
            line-height: 1.7;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        th { color: var(--muted); font-size: 13px; }
        .pagination { margin-top: 22px; }
        .pagination a { color: var(--green); margin-right: 15px; }
        @media(max-width: 850px) {
            main { padding: 24px; }
            .stats { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
<div class="eyebrow">CAMPAIGN INTELLIGENCE</div>

        <h1>
            {{ $type === 'tally'
                ? 'Result Verification'
                : 'Application Audit History' }}
        </h1>

        <p class="subtitle">
            {{ $contest->display_name }} · {{ $contest->election_year }}
        </p>

        @if($type === 'tally')
            <div class="stats">
                <div class="stat">
                    <span>Verified submissions</span>
                    <strong>{{ number_format($verifiedSubmissions) }}</strong>
                </div>
                <div class="stat">
                    <span>Valid votes</span>
                    <strong>{{ number_format($validVotes) }}</strong>
                </div>
                <div class="stat">
                    <span>Rejected votes</span>
                    <strong>{{ number_format($rejectedVotes) }}</strong>
                </div>
            </div>

            <section class="panel">
                <h2>Candidate vote totals</h2>

                @forelse($candidateTotals as $candidate => $votes)
                    <div style="display:flex;justify-content:space-between;
                        gap:16px;padding:15px 0;border-bottom:1px solid #e1e8ec">
                        <strong>{{ $candidate }}</strong>
                        <strong>{{ number_format($votes) }}</strong>
                    </div>
                @empty
                    <div class="empty">
                        No verified election results are available yet.
                        <p>Verified submissions will appear here once reviewed.</p>
                    </div>
                @endforelse
            </section>
        @else
            <section class="panel">
                <h2>Application review decisions</h2>

                @if($auditRecords->count())
                    <table>
                        <thead>
                        <tr>
                            <th>Record</th>
                            <th>Decision</th>
                            <th>Reviewer ID</th>
                            <th>Recorded at</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($auditRecords as $record)
                            <tr>
                                <td>#{{ $record->id }}</td>
                                <td>{{ str_replace('_', ' ', $record->action) }}</td>
                                <td>{{ $record->user_id }}</td>
                                <td>{{ $record->created_at }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div class="pagination">
                        @if($auditRecords->previousPageUrl())
                            <a href="{{ $auditRecords->previousPageUrl() }}">Previous</a>
                        @endif

                        Page {{ $auditRecords->currentPage() }}
                        of {{ $auditRecords->lastPage() }}

                        @if($auditRecords->nextPageUrl())
                            <a href="{{ $auditRecords->nextPageUrl() }}">Next</a>
                        @endif
                    </div>
                @else
                    <div class="empty">
                        No application review decisions have been recorded yet.
                        <p>Approval and rejection activity will appear here.</p>
                    </div>
                @endif
            </section>
        @endif
@endsection
