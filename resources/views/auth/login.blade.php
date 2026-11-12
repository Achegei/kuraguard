<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | KuraGuard</title>
    <style>
        :root {
            --navy: #0b1730;
            --blue: #2563eb;
            --red: #f34d61;
            --muted: #6c7a90;
            --border: #dfe6f0;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Inter, -apple-system, BlinkMacSystemFont,
                         "Segoe UI", sans-serif;
            background: #f5f7fb;
            color: #18263d;
        }

        .layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        .intro {
            background: var(--navy);
            color: white;
            padding: 65px clamp(35px, 6vw, 110px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand {
            font-size: 34px;
            font-weight: 850;
            letter-spacing: -1.5px;
        }

        .brand span { color: var(--red); }

        .brand-subtitle {
            margin-top: 8px;
            color: #9aacc8;
            font-size: 12px;
            letter-spacing: 2.4px;
            text-transform: uppercase;
        }

        .intro h1 {
            font-size: clamp(40px, 4.5vw, 68px);
            line-height: 1.08;
            letter-spacing: -2.5px;
            margin: 0 0 24px;
        }

        .intro p {
            color: #b8c6dd;
            font-size: 18px;
            line-height: 1.8;
            max-width: 520px;
        }

        .intro-footer {
            font-size: 13px;
            color: #879ab9;
        }

        .form-area {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 45px 30px;
        }

        .form-card {
            width: 100%;
            max-width: 430px;
        }

        .eyebrow {
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--blue);
            font-weight: 800;
        }

        h2 {
            font-size: 37px;
            letter-spacing: -1.2px;
            margin: 13px 0 10px;
        }

        .description {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 35px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 750;
            margin-bottom: 10px;
        }

        input {
            width: 100%;
            height: 54px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 16px;
            font: inherit;
            font-size: 16px;
            background: white;
            outline: none;
        }

        input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        .field { margin-bottom: 24px; }

        button {
            width: 100%;
            height: 56px;
            border: 0;
            border-radius: 10px;
            background: var(--blue);
            color: white;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            margin-top: 6px;
        }

        button:hover { background: #1d4ed8; }

        .error {
            background: #fff0f1;
            color: #b42335;
            border: 1px solid #fecdd3;
            padding: 14px;
            border-radius: 9px;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .notice {
            background: #f0f5ff;
            border: 1px solid #d9e5ff;
            color: #35527d;
            padding: 15px;
            border-radius: 10px;
            margin-top: 30px;
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 850px) {
            .layout { grid-template-columns: 1fr; }
            .intro { min-height: 280px; padding: 35px; }
            .intro h1 { font-size: 36px; margin-top: 35px; }
            .intro p { font-size: 15px; }
            .intro-footer { display: none; }
            .form-area { padding: 50px 25px; }
        }
    </style>
</head>
<body>
<div class="layout">
    <section class="intro">
        <div>
            <div class="brand">Kura<span>Guard</span></div>
            <div class="brand-subtitle">Election Intelligence Platform</div>
        </div>

        <div>
            <h1>Every station.<br>Every report.<br>Accounted for.</h1>
            <p>
                A unified command centre for polling-station monitoring,
                agent coordination, result verification and election
                incident reporting.
            </p>
        </div>

        <div class="intro-footer">
            KuraGuard · Secure Election Operations
        </div>
    </section>

    <section class="form-area">
        <div class="form-card">
            <div class="eyebrow">Authorized Access</div>
            <h2>Welcome back.</h2>

            <p class="description">
                Sign in with your authorized KuraGuard account
                to access your authorized KuraGuard workspace.
            </p>

            @if ($errors->any())
                <div class="error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="name@organization.com"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit">Sign in to KuraGuard</button>
            </form>

            <div class="notice">
                <strong>Demonstration environment</strong><br>
                Election data shown in this application is simulated.
                Access is intended for authorized demonstration users only.
            </div>
        </div>
    </section>
</div>
</body>
</html>
