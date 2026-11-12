<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AI Result Extraction | KuraGuard</title>
<style>
:root {
    --navy:#0b1730;
    --green:#087f5b;
    --bg:#f3f7f6;
    --border:#dce7e3;
    --text:#18302a;
    --muted:#647b74;
}
* { box-sizing:border-box; }
body {
    margin:0;
    background:var(--bg);
    color:var(--text);
    font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}
header {
    background:var(--navy);
    color:white;
    padding:24px 5%;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    flex-wrap:wrap;
}
.brand { font-size:27px;font-weight:850;letter-spacing:-1px; }
.brand span { color:#36d399; }
.back { color:#d4e6e0;text-decoration:none;font-weight:650; }
main { max-width:1250px;margin:auto;padding:42px 24px 80px; }
.eyebrow {
    color:var(--green);
    text-transform:uppercase;
    font-size:13px;
    font-weight:850;
    letter-spacing:1.6px;
}
h1 { font-size:clamp(32px,4vw,48px);margin:12px 0;letter-spacing:-1.5px; }
.subtitle { font-size:17px;color:var(--muted);line-height:1.6; }
.demo {
    display:inline-block;
    background:#fff1f2;
    color:#b42335;
    border:1px solid #fecdd3;
    border-radius:9px;
    padding:10px 14px;
    font-size:12px;
    font-weight:850;
}
.grid {
    display:grid;
    grid-template-columns:1.4fr .6fr;
    gap:22px;
    margin-top:30px;
}
.card {
    background:white;
    border:1px solid var(--border);
    border-radius:18px;
    padding:28px;
    box-shadow:0 5px 24px rgba(12,45,35,.04);
}
h2 { font-size:23px;margin:0 0 12px; }
p { line-height:1.6; }
table { width:100%;border-collapse:collapse;margin-top:22px; }
th {
    text-align:left;
    background:#f3f8f6;
    color:#56736a;
    padding:17px;
    font-size:13px;
}
td { padding:19px 17px;border-bottom:1px solid var(--border); }
td strong { font-size:20px; }
.ok { color:#087f5b;font-weight:850; }
.bad { color:#b42335;font-weight:850; }
.warn { color:#9a6700;font-weight:850; }
.notice {
    border-radius:12px;
    padding:18px;
    margin-top:20px;
    font-size:15px;
    line-height:1.7;
}
.notice.blue { background:#eff6ff;color:#1e40af; }
.notice.amber { background:#fffbeb;color:#92400e; }
.notice.red { background:#fff1f2;color:#9f1239; }
.metric {
    border-bottom:1px solid var(--border);
    padding:19px 0;
}
.metric span { display:block;color:var(--muted);font-size:14px; }
.metric strong { display:block;font-size:29px;margin-top:6px; }
.button {
    display:inline-block;
    margin-top:24px;
    background:var(--green);
    color:white;
    padding:15px 22px;
    border-radius:11px;
    text-decoration:none;
    font-weight:800;
}
@media(max-width:900px) {
    .grid { grid-template-columns:1fr; }
    main { padding:25px 15px; }
    .card { padding:20px;overflow-x:auto; }
}
</style>
</head>
<body>

<header>
    <div class="brand">Kura<span>Guard</span></div>
    <a class="back"
       href="{{ route('verification.show', $submission->id) }}">
       ← Back to Verification
    </a>
</header>

<main>
    <div class="eyebrow">Election Operations / AI-Assisted Verification</div>

    <h1>AI Result Extraction</h1>

    <p class="subtitle">
        Submission #{{ $submission->id }}
        · {{ $submission->pollingStation->name }}
        · {{ $submission->pollingStation->station_code }}
    </p>

    <div class="demo">SIMULATED DEMO DATA — NOT OFFICIAL RESULTS</div>

    @php
        $rows = is_array($extracted['candidates'] ?? null)
            ? $extracted['candidates']
            : [];

        $warnings = is_array($extracted['warnings'] ?? null)
            ? $extracted['warnings']
            : [];

        $expected = [];

        foreach ($expectedCandidates as $entry) {
            $name = trim((string) ($entry->candidate?->name ?? ''));

            if ($name !== '') {
                $expected[mb_strtolower($name)] = (int) $entry->votes;
            }
        }

        $seen = [];
        $allMatch = count($rows) === count($expected)
            && count($rows) > 0;

        $sum = 0;
        $sumComplete = true;
    @endphp

    <div class="grid">
        <section class="card">
            <h2>Candidate Vote Comparison</h2>

            <p style="color:var(--muted)">
                AI-extracted figures are compared with the agent's
                submitted values. Matching figures do not establish
                the authenticity of the document.
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Submitted</th>
                        <th>AI Extracted</th>
                        <th>Comparison</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($rows as $row)
                    @php
                        $name = trim((string) ($row['name'] ?? ''));
                        $key = mb_strtolower($name);
                        $votes = $row['votes'] ?? null;
                        $uncertain = ($row['uncertain'] ?? true) !== false;

                        $validInteger = is_int($votes) && $votes >= 0;
                        $known = $key !== ''
                            && array_key_exists($key, $expected);
                        $duplicate = isset($seen[$key]);
                        $seen[$key] = true;

                        $match = $known
                            && !$duplicate
                            && !$uncertain
                            && $validInteger
                            && $votes === $expected[$key];

                        if (!$match) {
                            $allMatch = false;
                        }

                        if ($validInteger) {
                            $sum += $votes;
                        } else {
                            $sumComplete = false;
                        }
                    @endphp
                    <tr>
                        <td><strong>{{ $name ?: 'Unreadable' }}</strong></td>
                        <td>
                            {{ $known ? number_format($expected[$key]) : '—' }}
                        </td>
                        <td>
                            <strong>
                                {{ $validInteger ? number_format($votes) : 'Unreadable' }}
                            </strong>
                        </td>
                        <td>
                            @if ($match)
                                <span class="ok">MATCH</span>
                            @elseif ($uncertain || !$validInteger)
                                <span class="warn">REVIEW</span>
                            @else
                                <span class="bad">MISMATCH</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @php
                $validVotes = $extracted['valid_votes'] ?? null;
                $rejectedVotes = $extracted['rejected_votes'] ?? null;

                $validMatch = is_int($validVotes)
                    && $validVotes === (int) $submission->valid_votes;

                $rejectedMatch = is_int($rejectedVotes)
                    && $rejectedVotes === (int) $submission->rejected_votes;

                $sumMatch = $sumComplete
                    && is_int($validVotes)
                    && $sum === $validVotes;

                $allMatch = $allMatch
                    && $validMatch
                    && $rejectedMatch
                    && $sumMatch
                    && empty($warnings);
            @endphp

            <div class="notice {{ $allMatch ? 'blue' : 'red' }}">
                <strong>
                    {{ $allMatch ? 'AI comparison passed' : 'Manual review required' }}
                </strong>

                <p>
                    {{ $allMatch
                        ? 'All extracted figures match the submitted values. A human reviewer must still inspect the original document before making any verification decision.'
                        : 'One or more figures are missing, uncertain, inconsistent, or different from the submitted values. Do not approve based on this extraction.'
                    }}
                </p>
            </div>

            @if (!empty($warnings))
                <div class="notice amber">
                    <strong>AI Extraction Warnings</strong>
                    <ul>
                        @foreach ($warnings as $warning)
                            <li>{{ is_scalar($warning) ? $warning : 'Unstructured warning returned' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <aside class="card">
            <h2>Extracted Totals</h2>

            <div class="metric">
                <span>AI-Extracted Valid Votes</span>
                <strong>
                    {{ is_int($validVotes) ? number_format($validVotes) : 'Unreadable' }}
                </strong>
                <span class="{{ $validMatch ? 'ok' : 'bad' }}">
                    {{ $validMatch ? 'Matches submission' : 'Review required' }}
                </span>
            </div>

            <div class="metric">
                <span>AI-Extracted Rejected Ballots</span>
                <strong>
                    {{ is_int($rejectedVotes) ? number_format($rejectedVotes) : 'Unreadable' }}
                </strong>
                <span class="{{ $rejectedMatch ? 'ok' : 'bad' }}">
                    {{ $rejectedMatch ? 'Matches submission' : 'Review required' }}
                </span>
            </div>

            <div class="metric">
                <span>Candidate Vote Sum</span>
                <strong>
                    {{ $sumComplete ? number_format($sum) : 'Incomplete' }}
                </strong>
                <span class="{{ $sumMatch ? 'ok' : 'bad' }}">
                    {{ $sumMatch ? 'Arithmetic consistent' : 'Review required' }}
                </span>
            </div>

            <div class="notice amber">
                <strong>Human Verification Required</strong>
                <p>
                    AI recognition is an assistance tool, not an
                    independent certification of election results.
                    This page does not change submission status
                    or verified vote totals.
                </p>
            </div>

            <a class="button"
               href="{{ route('verification.show', $submission->id) }}">
                Return to Verification
            </a>
        </aside>
    </div>
</main>

</body>
</html>
