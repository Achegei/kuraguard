@extends('layouts.campaign')

@section('title', 'Recruitment Management | KuraGuard')
@section('breadcrumb', 'Recruitment Management')

@push('styles')
<style>
        :root {
            --navy: #071b24;
            --green: #087b54;
            --bg: #f4f7f8;
            --border: #e1e8ec;
            --muted: #697d88;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: #172b35;
            font-family: Inter, -apple-system,
                BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .layout { display: flex; min-height: 100vh; }
        main {
            flex: 1;
            min-width: 0;
            padding: 36px;
        }
        .heading { margin-bottom: 28px; }
        .heading h1 {
            font-size: 32px;
            margin: 8px 0;
        }
        .heading p { color: var(--muted); }
        .panel {
            max-width: 900px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 22px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            max-width: 900px;
            margin-bottom: 24px;
        }
        .stat {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .stat strong {
            display: block;
            font-size: 30px;
            margin-top: 8px;
        }
        .muted { color: var(--muted); }
        .setting {
            display: flex;
            gap: 16px;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--border);
        }
        .setting strong { display: block; }
        .setting small {
            display: block;
            margin-top: 7px;
            color: var(--muted);
            line-height: 1.6;
        }
        select, input[type="text"] {
            padding: 12px;
            border: 1px solid #cbd5df;
            border-radius: 8px;
            font: inherit;
        }
        .btn {
            background: var(--green);
            border: 0;
            border-radius: 9px;
            padding: 13px 22px;
            color: white;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        .success {
            max-width: 900px;
            padding: 15px;
            margin-bottom: 18px;
            background: #ecfdf5;
            color: #065f46;
            border-radius: 9px;
        }
        .link {
            color: var(--green);
            font-weight: 700;
            word-break: break-all;
        }
        @media(max-width: 850px) {
            main { padding: 22px; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            .setting {
                align-items: flex-start;
                flex-direction: column;
            }
        }
        @media(max-width: 500px) {
            .stats { grid-template-columns: 1fr 1fr; }
            .panel { padding: 20px; }
        }
    </style>
@endpush

@section('content')
<div class="heading">
            <div class="muted">CAMPAIGN MANAGEMENT</div>
            <h1>Recruitment Management</h1>
            <p>
                {{ $contest->display_name }}
                · {{ $contest->election_year }}
            </p>
        </div>

        @if(session('status'))
            <div class="success" role="status">
                {{ session('status') }}
            </div>
        @endif

        <div class="stats">
            @foreach($statistics as $label => $count)
                <div class="stat">
                    <span class="muted">{{ ucfirst($label) }}</span>
                    <strong>{{ number_format($count) }}</strong>
                </div>
            @endforeach
        </div>

        <section class="panel">
            <h2>Public recruitment controls</h2>
            <p class="muted">
                Only authorized campaign management can change
                these settings.
            </p>

            <form method="POST"
                  action="{{ route('campaign.recruitment.update', $contest->id) }}">
                @csrf
                @method('PATCH')

                <div class="setting">
                    <div>
                        <strong>Public campaign visibility</strong>
                        <small>
                            Allow this campaign to appear in the
                            public recruitment portal.
                        </small>
                    </div>
                    <select name="public_recruitment_enabled">
                        <option value="0"
                            @selected(!$contest->public_recruitment_enabled)>
                            Disabled
                        </option>
                        <option value="1"
                            @selected($contest->public_recruitment_enabled)>
                            Enabled
                        </option>
                    </select>
                </div>

                <div class="setting">
                    <div>
                        <strong>Accept new applications</strong>
                        <small>
                            Open or close submissions from the
                            public application form.
                        </small>
                    </div>
                    <select name="public_recruitment_open">
                        <option value="0"
                            @selected(!$contest->public_recruitment_open)>
                            Closed
                        </option>
                        <option value="1"
                            @selected($contest->public_recruitment_open)>
                            Open
                        </option>
                    </select>
                </div>

                <p style="margin-top:24px">
                    <button type="submit" class="btn">
                        Save recruitment settings
                    </button>
                </p>
            </form>
        </section>

        <section class="panel">
            <h2>Public application links</h2>
            <p class="muted">
                These links accept applications only when both
                recruitment controls are enabled.
            </p>

            <p>
                <a class="link" target="_blank" rel="noopener"
                   href="{{ route('join.agent') }}">
                    {{ route('join.agent') }}
                </a>
            </p>
            <p>
                <a class="link" target="_blank" rel="noopener"
                   href="{{ route('join.mobilizer') }}">
                    {{ route('join.mobilizer') }}
                </a>
            </p>
        </section>

        <section class="panel">
            <h2>Application review</h2>

            <p class="muted">
                Review public applicants before account onboarding
                and polling-station deployment.
            </p>

            @if($errors->any())
                <div class="success"
                     style="background:#fff1f2;color:#9f1239">
                    {{ $errors->first() }}
                </div>
            @endif

            @if($applications->count())
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr>
                                <th style="padding:12px;text-align:left">Applicant</th>
                                <th style="padding:12px;text-align:left">Role</th>
                                <th style="padding:12px;text-align:left">Status</th>
                                <th style="padding:12px;text-align:left">Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $application)
                                <tr>
                                    <td style="padding:12px;border-top:1px solid #e1e8ec">
                                        <strong>{{ $application->full_name }}</strong>
                                        <div class="muted">{{ $application->email }}</div>
                                        <div class="muted">{{ $application->phone }}</div>
                                    </td>

                                    <td style="padding:12px;border-top:1px solid #e1e8ec">
                                        {{ ucfirst(str_replace('_', ' ', $application->requested_role)) }}
                                    </td>

                                    <td style="padding:12px;border-top:1px solid #e1e8ec">
                                        {{ ucfirst($application->status) }}
                                    </td>

                                    <td style="padding:12px;border-top:1px solid #e1e8ec">
                                        @if($application->status === 'pending')
                                            @can('campaign.review-applications', $contest->id)
                                                <form method="POST"
                                                      action="{{ route('campaign.public-applications.review', ['contestId' => $contest->id, 'applicationId' => $application->id]) }}">
                                                    @csrf

                                                    <input type="text"
                                                           name="review_notes"
                                                           maxlength="5000"
                                                           placeholder="Review notes (optional)"
                                                           style="width:100%;margin-bottom:8px">

                                                    <button type="submit"
                                                            class="btn"
                                                            name="decision"
                                                            value="approved">
                                                        Approve
                                                    </button>

                                                    <button type="submit"
                                                            class="btn"
                                                            name="decision"
                                                            value="rejected"
                                                            style="background:#b91c1c">
                                                        Reject
                                                    </button>
                                                </form>



                                            @endcan
                                        @else
                                            @if ($application->status === 'approved'
    && $application->linked_user_id === null)
    <form method="POST"
          action="{{ route('campaign.agent-invitations.send', [
              'contestId' => $contest->id,
              'applicationId' => $application->id
          ]) }}"
          style="margin-top:8px">
        @csrf

        <button type="submit"
                class="btn"
                onclick="return confirm('Send an activation invitation to this applicant?')"
                style="background:#087f68">
            Send Activation Invitation
        </button>
    </form>
@else
    <span class="muted">Reviewed</span>
@endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:20px">
                    {{ $applications->links() }}
                </div>
            @else
                <p class="muted">
                    No public applications have been received yet.
                </p>
            @endif
        </section>
@endsection
