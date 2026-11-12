<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select Campaign | KuraGuard</title>
    <style>
        :root {
            --navy: #071b24;
            --green: #087b54;
            --background: #f4f7f8;
            --text: #172b35;
            --muted: #667781;
            --border: #dce5e8;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: Inter, -apple-system,
                BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        header {
            background: var(--navy);
            color: white;
            padding: 24px clamp(20px, 5vw, 70px);
        }

        .brand {
            font-size: 26px;
            font-weight: 850;
            letter-spacing: -1px;
        }

        .brand span { color: #39c68a; }

        .caption {
            color: #a4b7c0;
            font-size: 11px;
            letter-spacing: 2px;
            margin-top: 5px;
        }

        main {
            max-width: 1050px;
            margin: 0 auto;
            padding: 65px 24px;
        }

        .eyebrow {
            color: var(--green);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        h1 {
            font-size: clamp(30px, 4vw, 44px);
            margin: 12px 0;
        }

        .description {
            color: var(--muted);
            line-height: 1.7;
            margin-bottom: 35px;
        }

        .campaign-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(min(100%, 290px), 1fr));
            gap: 20px;
        }

        .campaign-card {
            display: block;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px;
            color: inherit;
            text-decoration: none;
            transition: border-color .2s, transform .2s;
        }

        .campaign-card:hover,
        .campaign-card:focus-visible {
            border-color: var(--green);
            transform: translateY(-3px);
            outline-offset: 3px;
        }

        .campaign-card h2 {
            font-size: 22px;
            margin: 0 0 14px;
        }

        .meta {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.9;
        }

        .open-link {
            display: inline-block;
            margin-top: 24px;
            color: var(--green);
            font-weight: 800;
            font-size: 14px;
        }

        footer {
            margin-top: 55px;
            color: var(--muted);
            font-size: 13px;
        }
    </style>
<link rel="stylesheet" href="{{ asset('css/kuraguard-premium.css') }}">
</head>
<body class="kg-campaign">
    <header>
        <div class="brand">Kura<span>Guard</span></div>
        <div class="caption">ELECTION INTELLIGENCE</div>
    </header>

    <main>
        <div class="eyebrow">Campaign Workspace</div>
        <h1>Select Your Campaign</h1>

        <p class="description">
            @if ($contests->isEmpty())
                Your account does not yet have an assigned campaign.
                Once a campaign is created and access is granted,
                it will appear here.
            @else
                Select the campaign you want to manage or review.
            @endif
        </p>

        @if ($contests->isEmpty())
            <section class="campaign-card" aria-label="No campaigns">
                <div class="eyebrow">Workspace status</div>
                <h2>No campaign assigned</h2>
                <p class="meta">
                    Your account is authenticated, but no campaign
                    workspace is currently available.
                    Contact your campaign administrator for access.
                </p>
            </section>
        @endif

        <section class="campaign-grid" aria-label="Available campaigns">
            @foreach ($contests as $contest)
                <a
                    class="campaign-card"
                    href="{{ route('campaign.dashboard', ['contestId' => $contest->id]) }}"
                >
                    <h2>{{ $contest->display_name }}</h2>

                    <div class="meta">
                        <div>
                            <strong>Office:</strong>
                            {{ ucfirst(str_replace('_', ' ', $contest->office)) }}
                        </div>
                        <div>
                            <strong>Election:</strong>
                            {{ $contest->election_year }}
                        </div>
                        <div>
                            <strong>Status:</strong>
                            {{ ucfirst(str_replace('_', ' ', $contest->declaration_status)) }}
                        </div>
                    </div>

                    <span class="open-link">
                        Open Campaign Dashboard &rarr;
                    </span>
                </a>
            @endforeach
        </section>

        <footer>
            KuraGuard &middot; Authorized campaign access only
        </footer>
    </main>
</body>
</html>
