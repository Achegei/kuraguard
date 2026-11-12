<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Review Submission #{{ $submission->id }} | KuraGuard</title>
    <style>
        :root {
            --navy: #0b1730;
            --blue: #2563eb;
            --bg: #f3f6fb;
            --border: #e1e7f0;
            --text: #18263d;
            --muted: #68778c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .header {
            background: var(--navy);
            color: white;
            padding: 22px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .brand { font-size: 26px; font-weight: 850; letter-spacing: -1px; }
        .brand span { color: #ff5263; }
        .back { color: #c7d7f0; text-decoration: none; font-size: 14px; }
        .container { max-width: 1180px; margin: 0 auto; padding: 40px 24px 80px; }
        .eyebrow {
            color: var(--blue);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }
        h1 { font-size: clamp(30px, 3vw, 43px); margin: 12px 0; letter-spacing: -1px; }
        .subtitle { color: var(--muted); font-size: 16px; }
        .demo {
            display: inline-block;
            margin-top: 14px;
            padding: 9px 13px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #b42335;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
        }
        .grid { display: grid; grid-template-columns: 1.3fr .7fr; gap: 20px; margin-top: 30px; }
        .card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(15,32,60,.035);
        }
        h2 { font-size: 21px; margin: 0 0 22px; }
        .detail {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--border);
            font-size: 15px;
        }
        .detail:last-child { border-bottom: 0; }
        .label { color: var(--muted); }
        .value { font-weight: 750; text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #f7f9fc;
            color: var(--muted);
            font-size: 12px;
            text-align: left;
            padding: 15px;
        }
        td { padding: 17px 15px; border-bottom: 1px solid var(--border); }
        th:last-child, td:last-child { text-align: right; }
        .votes { font-size: 22px; font-weight: 850; }
        .total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 15px;
            font-size: 17px;
            font-weight: 800;
        }
        .total strong { font-size: 28px; }
        .check {
            border-radius: 12px;
            padding: 19px;
            margin-top: 24px;
            font-weight: 750;
            font-size: 14px;
        }
        .check.pass { background: #dcfce7; color: #166534; }
        .check.fail { background: #fee2e2; color: #991b1b; }
        .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            background: #fef3c7;
            color: #92400e;
            font-weight: 800;
            font-size: 12px;
        }
        .notice {
            margin-top: 20px;
            background: #eff6ff;
            color: #1e40af;
            padding: 18px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.7;
        }
        @media(max-width: 850px) {
            .grid { grid-template-columns: 1fr; }
            .container { padding: 25px 15px; }
            .card { padding: 20px; }
        }
    </style>
</head>
<body>
<header class="header">
    <div class="brand">Kura<span>Guard</span></div>
    <a class="back" href="{{ route('command-centre.index') }}">← Back to Command Centre</a>
</header>

<main class="container">
    <div class="eyebrow">Election Operations / Result Verification</div>
    <h1>Review Submission #{{ $submission->id }}</h1>
    <p class="subtitle">
        {{ $submission->pollingStation->name }}
        · {{ $submission->pollingStation->station_code }}
    </p>
    <div class="demo">SIMULATED DEMO DATA</div>

    <section class="grid">
        <div class="card">
            <h2>Candidate Vote Breakdown</h2>

            <table>
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Political Party</th>
                        <th>Votes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($candidateResults as $result)
                        <tr>
                            <td><strong>{{ $result->candidate?->name ?? 'Unknown candidate' }}</strong></td>
                            <td>{{ $result->candidate?->party ?? '—' }}</td>
                            <td class="votes">{{ number_format($result->votes) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total">
                <span>Calculated valid votes</span>
                <strong>{{ number_format($calculatedVotes) }}</strong>
            </div>

            <div class="check {{ $totalsMatch ? 'pass' : 'fail' }}">
                @if ($totalsMatch)
                    Vote consistency check passed. Candidate votes match the submitted valid-vote total.
                @else
                    Vote consistency check failed. Candidate votes do not match the submitted valid-vote total.
                @endif
            </div>

            @if (session('success'))
                <div style="margin-top:22px;padding:18px;border-radius:12px;background:#dcfce7;color:#166534;font-weight:750">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div style="margin-top:22px;padding:18px;border-radius:12px;background:#fee2e2;color:#991b1b">
                    <strong>Decision could not be completed</strong>
                    <ul style="margin-bottom:0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($submission->status === 'pending')
                <div style="margin-top:28px;border-top:1px solid #e1e7f0;padding-top:25px">
                    <h2>Verification Decision</h2>

                    <p style="color:#68778c;line-height:1.6">
                        Review the candidate totals and supporting evidence before making a decision.
                        Decisions are recorded in the audit log.
                    </p>

                    <div style="background:#f8fafc;border:1px solid #e1e7f0;
                                border-radius:12px;padding:22px;margin:22px 0">

                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;
                                border-radius:14px;padding:24px;margin:22px 0">

                        <div style="color:#087f5b;font-size:12px;font-weight:850;
                                    letter-spacing:1.5px;text-transform:uppercase">
                            AI-Assisted Verification
                        </div>

                        <h2 style="margin:12px 0;font-size:25px">
                            Extract Handwritten Results
                        </h2>

                        <p style="font-size:15px;color:#45645a;line-height:1.7">
                            Upload a simulated polling-station result sheet.
                            KuraGuard AI will read the candidate names and vote
                            figures, then compare them with the agent's submission.
                            All extracted figures require human review.
                        </p>

                        <form method="POST"
                              action="{{ route('verification.ocr.extract', $submission->id) }}"
                              enctype="multipart/form-data">
                            @csrf

                            <label for="result_image"
                                   style="display:block;font-size:15px;font-weight:800;
                                          margin:18px 0 10px">
                                Select simulated result image
                            </label>

                            <input type="file"
                                   id="result_image"
                                   name="result_image"
                                   accept=".jpg,.jpeg,.png"
                                   required
                                   style="display:block;width:100%;padding:15px;
                                          background:white;border:1px solid #b8d9c7;
                                          border-radius:10px;font-size:15px">

                            <p style="font-size:13px;color:#527166;margin:12px 0">
                                JPEG or PNG, maximum 5 MB.
                                Use simulated documents only.
                                Images are sent to OpenAI for extraction.
                            </p>

                            <button type="submit"
                                    style="background:#087f5b;color:white;border:0;
                                           border-radius:11px;padding:16px 24px;
                                           font-size:16px;font-weight:850;cursor:pointer;
                                           margin-top:10px">
                                Extract Results with AI
                            </button>
                        </form>

                        <p style="font-size:13px;color:#527166;line-height:1.6;
                                  margin-bottom:0;margin-top:18px">
                            AI extraction does not approve results, alter recorded
                            votes or update verified election tallies.
                        </p>
                    </div>

<h2 style="margin-bottom:10px">Supporting Result Evidence</h2>

                        <p style="font-size:14px;color:#68778c;line-height:1.6">
                            Upload a clearly labeled simulated polling-station result
                            document for this demonstration. Accepted formats:
                            PDF, JPG and PNG. Maximum size: 5 MB.
                        </p>

                        <p style="font-size:14px;font-weight:750">
                            Attached evidence records:
                            {{ $submission->evidence->count() }}
                        </p>

                        <form method="POST"
                              action="{{ route('verification.evidence.store', $submission->id) }}"
                              enctype="multipart/form-data">
                            @csrf

                            <input type="hidden"
                                   name="form_type"
                                   value="simulated_result_form">

                            <label for="evidence"
                                   style="display:block;font-weight:750;font-size:14px;
                                          margin-bottom:10px">
                                Select simulated result document
                            </label>

                            <input type="file"
                                   id="evidence"
                                   name="evidence"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   required
                                   style="display:block;width:100%;margin-bottom:16px;
                                          font-size:14px">

                            <button type="submit"
                                    style="background:#2563eb;color:white;border:0;
                                           border-radius:10px;padding:14px 20px;
                                           font-weight:800;font-size:14px;cursor:pointer">
                                Upload Evidence
                            </button>
                        </form>
                    </div>

                    @php
                        $canApprove = $totalsMatch
                            && $candidateResults->isNotEmpty()
                            && $submission->evidence->isNotEmpty()
                            && auth()->id() === 1;
                    @endphp

                    @if ($submission->evidence->isEmpty())
                        <div style="background:#fff7ed;color:#9a3412;padding:16px;border-radius:10px;margin:18px 0;font-size:14px">
                            <strong>Approval unavailable:</strong>
                            This submission has no supporting evidence record.
                            A matching vote total alone does not establish authenticity.
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ route('verification.approve', $submission->id) }}"
                          style="margin:18px 0">
                        @csrf

                        <button type="submit"
                                @disabled(!$canApprove)
                                style="border:0;border-radius:10px;padding:15px 22px;font-weight:800;font-size:15px;
                                background:{{ $canApprove ? '#166534' : '#cbd5e1' }};
                                color:{{ $canApprove ? 'white' : '#475569' }};
                                cursor:{{ $canApprove ? 'pointer' : 'not-allowed' }}">
                            Approve Submission
                        </button>
                    </form>

                    <div style="border-top:1px solid #e1e7f0;margin:28px 0"></div>

                    <h2>Reject Submission</h2>

                    <form method="POST"
                          action="{{ route('verification.reject', $submission->id) }}">
                        @csrf

                        <label for="reason"
                               style="display:block;font-size:14px;font-weight:750;margin-bottom:10px">
                            Reason for rejection
                        </label>

                        <textarea id="reason"
                                  name="reason"
                                  required
                                  minlength="10"
                                  maxlength="2000"
                                  rows="4"
                                  placeholder="Explain why this submission cannot be accepted..."
                                  style="width:100%;border:1px solid #cbd5e1;border-radius:10px;
                                  padding:14px;font:inherit;resize:vertical">{{ old('reason') }}</textarea>

                        <button type="submit"
                                style="display:block;margin-top:14px;border:0;border-radius:10px;
                                padding:15px 22px;background:#b42335;color:white;
                                font-weight:800;font-size:15px;cursor:pointer">
                            Reject Submission
                        </button>
                    </form>
                </div>
            @else
                <div class="notice">
                    <strong>Decision recorded</strong><br>
                    This submission is {{ $submission->status }} and can no longer be
                    approved or rejected through this workflow.
                </div>
            @endif
        </div>

        <div>
            <div class="card">
                <h2>Submission Details</h2>

                <div class="detail">
                    <span class="label">Submission ID</span>
                    <span class="value">#{{ $submission->id }}</span>
                </div>

                <div class="detail">
                    <span class="label">Status</span>
                    <span class="status">{{ ucfirst($submission->status) }}</span>
                </div>

                <div class="detail">
                    <span class="label">Polling Station</span>
                    <span class="value">{{ $submission->pollingStation->name }}</span>
                </div>

                <div class="detail">
                    <span class="label">Ward</span>
                    <span class="value">{{ $submission->pollingStation->ward }}</span>
                </div>

                <div class="detail">
                    <span class="label">Agent</span>
                    <span class="value">{{ $submission->agent?->name ?? 'Not assigned' }}</span>
                </div>

                <div class="detail">
                    <span class="label">Recorded Valid Votes</span>
                    <span class="value">{{ number_format($submission->valid_votes) }}</span>
                </div>

                <div class="detail">
                    <span class="label">Rejected Ballots</span>
                    <span class="value">{{ number_format($submission->rejected_votes) }}</span>
                </div>

                <div class="detail">
                    <span class="label">Evidence Records</span>
                    <span class="value">{{ $submission->evidence->count() }}</span>
                </div>

                <div class="detail">
                    <span class="label">Verified By</span>
                    <span class="value">{{ $submission->verifier?->name ?? 'Not yet verified' }}</span>
                </div>
            </div>
        </div>
    </section>
</main>
</body>
</html>
