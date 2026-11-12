<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobilizer Workspace | KuraGuard</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #080d21;
            color: #e8edff;
        }

        .shell {
            max-width: 1080px;
            margin: 0 auto;
            padding: 48px 24px;
        }

        .eyebrow {
            color: #a5a7ff;
            text-transform: uppercase;
            letter-spacing: .15em;
            font-size: 12px;
            font-weight: 700;
        }

        h1 {
            font-size: clamp(30px, 5vw, 48px);
            margin: 12px 0;
        }

        .intro {
            color: #a9b4d2;
            line-height: 1.7;
            max-width: 700px;
        }

        .panel {
            margin-top: 32px;
            padding: 28px;
            border: 1px solid #29345b;
            border-radius: 20px;
            background: linear-gradient(135deg, #141d40, #11152d);
        }

        .status {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 999px;
            background: #302b65;
            color: #d5d1ff;
            font-size: 13px;
            font-weight: 600;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }

        .label {
            color: #9ca9cb;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .value {
            font-size: 16px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body>
<main class="shell">
    <div class="eyebrow">KuraGuard Field Operations</div>

    <h1>Mobilizer Workspace</h1>

    <p class="intro">
        View your campaign deployment and assigned geographic scope.
        Your workspace is separate from campaign administration
        and election-results operations.
    </p>

    @if ($awaitingDeployment)
        <section class="panel">
            <span class="status">Awaiting deployment</span>

            <h2>Your account is activated</h2>

            <p class="intro">
                Your mobilizer application has been approved.
                A campaign administrator must assign your deployment
                before operational access becomes available.
            </p>
        </section>
    @else
        @foreach ($assignments as $assignment)
            <section class="panel">
                <span class="status">Active deployment</span>

                <h2>{{ $assignment->display_name }}</h2>

                <div class="details">
                    <div>
                        <div class="label">Campaign office</div>
                        <div class="value">
                            {{ ucwords(str_replace('_', ' ', $assignment->office)) }}
                        </div>
                    </div>

                    <div>
                        <div class="label">Election year</div>
                        <div class="value">{{ $assignment->election_year }}</div>
                    </div>

                    <div>
                        <div class="label">Geographic level</div>
                        <div class="value">
                            {{ ucwords(str_replace('_', ' ', $assignment->geographic_level)) }}
                        </div>
                    </div>

                    <div>
                        <div class="label">Geographic reference</div>
                        <div class="value">#{{ $assignment->geographic_id }}</div>
                    </div>
                </div>
            </section>
        @endforeach
    @endif
</main>
</body>
</html>
