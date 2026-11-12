@extends('layouts.campaign')

@section('title', 'Campaign Network | KuraGuard')

@section('breadcrumb')
    {{ $page === 'stations' ? 'Polling Stations' : 'Agent Network' }}
@endsection

@push('styles')
<style>
.kg-network {
    max-width: 1440px;
    margin: 0 auto;
    color: #17243d;
}
.kg-network .network-heading {
    margin-bottom: 30px;
}
.kg-network .network-eyebrow {
    color: #5276ad;
    text-transform: uppercase;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
}
.kg-network h1 {
    margin: 10px 0;
    font-size: clamp(28px, 3vw, 40px);
    letter-spacing: -1.5px;
    color: #0b1730;
}
.kg-network .network-subtitle {
    color: #697a94;
    line-height: 1.7;
}
.kg-network .network-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 16px;
    margin: 28px 0;
}
.kg-network .network-metric {
    padding: 23px;
    background: white;
    border: 1px solid #e5ebf4;
    border-radius: 17px;
    box-shadow: 0 8px 28px rgba(15, 34, 68, .035);
}
.kg-network .network-metric span {
    display: block;
    color: #70819b;
    font-size: 12px;
    font-weight: 600;
}
.kg-network .network-metric strong {
    display: block;
    margin-top: 12px;
    font-size: 32px;
    color: #102b54;
}
.kg-network .network-panel {
    background: white;
    border: 1px solid #e5ebf4;
    border-radius: 19px;
    padding: 26px;
    box-shadow: 0 10px 35px rgba(15, 34, 68, .035);
}
.kg-network .network-panel h2 {
    margin: 0 0 20px;
    font-size: 19px;
    color: #0b1730;
}
.kg-network .network-table-wrap {
    overflow-x: auto;
}
.kg-network table {
    width: 100%;
    border-collapse: collapse;
}
.kg-network th,
.kg-network td {
    padding: 15px 12px;
    border-bottom: 1px solid #edf1f7;
    text-align: left;
}
.kg-network th {
    color: #64748b;
    font-size: 12px;
}
.kg-network .network-empty {
    padding: 40px 16px;
    text-align: center;
    color: #71829a;
    line-height: 1.8;
}
.kg-network .network-action {
    display: inline-block;
    padding: 10px 16px;
    background: #102b54;
    color: white !important;
    text-decoration: none;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
}
.kg-network .network-pagination {
    margin-top: 22px;
}
</style>
@endpush

@section('content')
<div class="kg-network">
    <div class="network-heading">
        <div class="network-eyebrow">
            {{ $contest->display_name }} · {{ $contest->election_year }}
        </div>

        <h1>
            {{ $page === 'stations' ? 'Polling Stations' : 'Agent Network' }}
        </h1>

        <p class="network-subtitle">
            @if($page === 'stations')
                Campaign-specific station reporting, attendance,
                incidents and results. Historical electoral reference
                records are not counted as active campaign reports.
            @else
                Campaign applications and field deployment records.
                Legacy demonstration agents are excluded.
            @endif
        </p>
    </div>

    <div class="network-metrics">
        @foreach($statistics as $label => $value)
            <div class="network-metric">
                <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
                <strong>{{ number_format($value) }}</strong>
            </div>
        @endforeach
    </div>

    <section class="network-panel">
        @if($page === 'stations')
            <h2>Station Activity</h2>

            @if($reports->count())
                <div class="network-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Electoral Station ID</th>
                                <th>Attendance</th>
                                <th>Reported Turnout</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $report)
                                <tr>
                                    <td>{{ $report->electoral_polling_station_id }}</td>
                                    <td>{{ str_replace('_', ' ', $report->attendance_status ?? 'Not recorded') }}</td>
                                    <td>{{ $report->reported_turnout ?? '—' }}</td>
                                    <td>{{ $report->updated_at ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="network-empty">
                    <h2>No station reports yet</h2>
                    <p>
                        Station attendance and reporting activity will
                        appear here when authorized campaign agents submit
                        their reports.
                    </p>
                </div>
            @endif

            <div class="network-pagination">
                {{ $reports->links() }}
            </div>
        @else
            <h2>Campaign Role Applications</h2>

            @if($applications->count())
                <div class="network-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Applicant User ID</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Polling Station</th>
                                <th>Deployment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $application)
                                <tr>
                                    <td>#{{ $application->id }}</td>
                                    <td>{{ $application->applicant_user_id }}</td>
                                    <td>{{ $application->status ?? '—' }}</td>
                                    <td>{{ str_replace('_', ' ', $application->requested_role) }}</td>
                                    <td>{{ $application->polling_station_id ?? '—' }}</td>
                                    <td>
                                        @if($application->active_assignment_id !== null)
                                            <span class="network-subtitle">
                                                Deployed — Assignment
                                                #{{ $application->active_assignment_id }}
                                            </span>
                                        @elseif($application->status === 'approved')
                                            <button
                                                type="button"
                                                class="network-action kg-deploy-agent"
                                                data-url="{{ route('campaign.applications.assign', [
                                                    'contestId' => $contest->id,
                                                    'applicationId' => $application->id
                                                ]) }}"
                                                data-application="{{ $application->id }}"
                                                style="border:0;cursor:pointer">
                                                Deploy Agent
                                            </button>
                                            <div class="kg-deployment-feedback"
                                                 role="status"
                                                 aria-live="polite"
                                                 style="margin-top:8px;font-size:12px"></div>
                                        @else
                                            <span class="network-subtitle">Not eligible</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="network-empty">
                    <h2>No campaign role applications yet</h2>
                    <p>
                        Approved applicants and field assignments
                        will be managed within this campaign.
                    </p>
                    <a class="network-action"
                       href="{{ route('campaign.recruitment.index', ['contestId' => $contest->id]) }}">
                        Recruitment Management
                    </a>
                </div>
            @endif

            <div class="network-pagination">
                {{ $applications->links() }}
            </div>
        @endif
    </section>
</div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.kg-deploy-agent').forEach(button => {
        button.addEventListener('click', async () => {
            const feedback = button.parentElement.querySelector(
                '.kg-deployment-feedback'
            );

            if (!confirm(
                'Deploy this approved agent to their assigned electoral location?'
            )) {
                return;
            }

            button.disabled = true;
            feedback.textContent = 'Processing deployment...';

            try {
                const response = await fetch(button.dataset.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.content || ''
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(
                        result.message ||
                        Object.values(result.errors || {})
                            .flat()
                            .join(' ') ||
                        'Deployment failed.'
                    );
                }

                feedback.textContent =
                    'Agent deployed successfully. Assignment #' +
                    result.assignment_id;

                button.textContent = 'Deployed';
                button.disabled = true;

            } catch (error) {
                feedback.textContent = error.message;
                button.disabled = false;
            }
        });
    });
});
</script>
@endpush
