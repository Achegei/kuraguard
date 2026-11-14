<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Join a Campaign | KuraGuard</title>
    <style>
:root {
    --kg-navy: #101c32;
    --kg-navy-deep: #0b1527;
    --kg-emerald: #087b54;
    --kg-emerald-soft: #e8f5ee;
    --kg-canvas: #f5f7f9;
    --kg-surface: #ffffff;
    --kg-ink: #172338;
    --kg-muted: #687789;
    --kg-border: #e4e9ef;
    --kg-radius: 12px;
    --kg-shadow: 0 8px 28px rgba(16,28,50,.035);
    --navy: var(--kg-navy);
    --blue: var(--kg-emerald);
    --light: var(--kg-canvas);
    --border: var(--kg-border);
    --muted: var(--kg-muted);
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
    margin: 0;
    background: var(--kg-canvas);
    color: var(--kg-ink);
    font-family: Inter, -apple-system, BlinkMacSystemFont,
        "Segoe UI", sans-serif;
    -webkit-font-smoothing: antialiased;
}
header {
    background: #fff;
    border-bottom: 1px solid var(--kg-border);
}
.nav {
    max-width: 1120px;
    margin: auto;
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.brand {
    font-weight: 850;
    font-size: 24px;
    letter-spacing: -.9px;
    color: var(--kg-navy);
}
.brand span { color: var(--kg-emerald); }
a { color: inherit; text-decoration: none; }
.nav-link {
    color: var(--kg-muted);
    font-size: 14px;
    font-weight: 600;
}
.nav-link:hover { color: var(--kg-emerald); }
.hero {
    background: linear-gradient(125deg, #0b1527, #17344a);
    color: white;
    padding: 70px 24px 90px;
    text-align: center;
}
.eyebrow {
    color: #6ee7b7;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-size: 12px;
    font-weight: 800;
}
h1 {
    font-size: clamp(32px, 5vw, 52px);
    margin: 14px 0;
    letter-spacing: -1.8px;
    color: white;
}
.hero p {
    max-width: 620px;
    margin: auto;
    line-height: 1.7;
    color: #d5e4e9;
}
main {
    max-width: 920px;
    margin: -42px auto 60px;
    padding: 0 20px;
    position: relative;
}
.panel {
    background: var(--kg-surface);
    border: 1px solid var(--kg-border);
    border-radius: 16px;
    padding: clamp(22px, 4vw, 40px);
    box-shadow: 0 14px 40px rgba(16,28,50,.07);
}
.section-title {
    margin: 0 0 8px;
    font-size: 24px;
    font-weight: 750;
    letter-spacing: -.5px;
}
.sub {
    margin: 0 0 26px;
    color: var(--kg-muted);
    line-height: 1.65;
    font-size: 14px;
}
.roles {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 30px;
}
.role {
    text-align: left;
    padding: 24px;
    border: 1.5px solid var(--kg-border);
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    font: inherit;
    transition: border-color .18s, background .18s,
        box-shadow .18s, transform .18s;
}
.role:hover {
    border-color: #a7d9c2;
    box-shadow: 0 8px 24px rgba(8,123,84,.07);
    transform: translateY(-2px);
}
.role.active {
    border-color: var(--kg-emerald);
    background: var(--kg-emerald-soft);
    box-shadow: 0 0 0 1px var(--kg-emerald);
}
.role strong {
    display: block;
    margin-bottom: 9px;
    font-size: 17px;
    color: var(--kg-ink);
}
.role small {
    color: var(--kg-muted);
    line-height: 1.6;
    font-size: 13px;
}
.grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}
.full { grid-column: 1 / -1; }
label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 8px;
}
input, select, textarea {
    width: 100%;
    min-height: 48px;
    padding: 13px 14px;
    border: 1px solid #cbd5df;
    border-radius: 9px;
    background: white;
    color: var(--kg-ink);
    font: inherit;
    font-size: 14px;
}
input:focus, select:focus, textarea:focus {
    outline: 3px solid rgba(8,123,84,.15);
    border-color: var(--kg-emerald);
}
select:disabled { background: #f1f4f6; color: #94a3b8; }
textarea { min-height: 112px; resize: vertical; }
.divider {
    border: 0;
    border-top: 1px solid var(--kg-border);
    margin: 32px 0;
}
h3 { font-size: 19px; letter-spacing: -.3px; }
.btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    padding: 15px 28px;
    border: 0;
    border-radius: 9px;
    background: var(--kg-emerald);
    color: white;
    font: inherit;
    font-weight: 750;
    cursor: pointer;
    transition: background .18s, transform .18s;
}
.btn:hover { background: #066844; transform: translateY(-1px); }
.btn:disabled { opacity: .5; cursor: not-allowed; }
.notice {
    font-size: 13px;
    color: var(--kg-muted);
    line-height: 1.7;
    margin-top: 18px;
}
.message {
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: none;
    line-height: 1.6;
}
.message.error {
    display: block;
    background: #fef2f2;
    color: #991b1b;
}
.message.success {
    display: block;
    background: #ecfdf5;
    color: #065f46;
}
footer {
    text-align: center;
    color: var(--kg-muted);
    padding: 24px;
    font-size: 13px;
}
:is(a, button, input, select, textarea):focus-visible {
    outline: 3px solid rgba(8,123,84,.3);
    outline-offset: 3px;
}
@media(max-width: 640px) {
    .roles, .grid { grid-template-columns: 1fr; }
    .full { grid-column: auto; }
    .hero { padding: 48px 20px 72px; }
    .panel { padding: 22px; }
    .nav { padding: 17px 20px; }
    main { padding: 0 14px; }
}
@media(prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        transition-duration: .01ms !important;
        scroll-behavior: auto !important;
    }
}
</style>
<link rel="stylesheet" href="{{ asset('css/kuraguard-join-premium.css') }}">
<link rel="stylesheet" href="{{ asset('css/kuraguard-join-155c.css') }}?v=20261114a">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-civic-ticker.css') }}?v=20261114a">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-join-155d.css') }}?v=20261114a">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-brand-157c.css') }}?v=20261114c">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=20261114c">
</head>
<body>
<x-civic-ticker />

<header class="kg-join-header kg-join-header-155d">
    <nav class="nav" aria-label="Registration page navigation">
        <a href="{{ url('/') }}" class="brand"
           aria-label="KuraGuard homepage">
            <span class="kg-brand-157c">
    <img
        class="kg-brand-image-157c"
        src="{{ asset('images/brand/kuraguard-logo-transparent.png') }}"
        alt="KuraGuard"
    >
    <span class="kg-brand-tagline-157c">
        Every Polling Station. Every Vote. Accounted For.
    </span>
</span>
        </a>
        <a href="{{ url('/') }}" class="kg-join-home-155d">
            <span aria-hidden="true">←</span> Back to Home
        </a>
    </nav>
</header>

<section class="hero kg-join-photo-hero kg-join-hero-155d">
    <div class="kg-join-hero-inner">
        <div class="eyebrow">KuraGuard · Campaign Network</div>
        <h1>Be where every vote matters.</h1>
        <p>
            Join the people supporting organized, accountable
            election operations. Choose your role and apply
            to participate in an authorized campaign.
        </p>
        <div class="kg-join-hero-caption-155d">
            <span aria-hidden="true">◆</span>
            Every Polling Station. Every Vote. Accounted For.
        </div>
    </div>
</section>

<main>
    <div class="panel">
        <h2 class="section-title">Choose your role. Make your impact.</h2>
        <p class="sub">Select one of the two opportunities below, then complete your application.</p>

        <div id="message" class="message" role="status" aria-live="polite"></div>

        <div class="roles">
            <button type="button" class="role" id="agentRole">
                <strong>Polling Station Agent</strong>
                <small>Support election-day operations at an assigned polling station.</small>
            </button>
            <button type="button" class="role" id="mobilizerRole">
                <strong>Campaign Mobilizer</strong>
                <small>Support campaign organization and outreach in your area.</small>
            </button>
        </div>

        <form id="applicationForm">
            <input type="hidden" id="requested_role" name="requested_role">

            <div class="grid">
                <div class="full">
                    <label for="aspirant_contest_id">Choose the aspirant you want to support *</label>
                    <input
                        id="aspirant_search"
                        type="search"
                        placeholder="Search aspirant name or electoral position"
                        autocomplete="off"
                        aria-label="Search available aspirants"
                        style="margin-bottom:12px"
                    >
                    <select id="aspirant_contest_id" name="aspirant_contest_id" required>
                        <option value="">Loading available campaigns...</option>
                    </select>
                </div>

                <div>
                    <label for="full_name">Full name *</label>
                    <input id="full_name" name="full_name" autocomplete="name" required maxlength="255">
                </div>

                <div>
                    <label for="phone">Phone number *</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" required maxlength="30">
                </div>

                <div class="full">
                    <label for="email">Email address *</label>
                    <input id="email" name="email" type="email" autocomplete="email" required maxlength="255">
                </div>
            </div>

            <hr class="divider">

            <h3>Electoral location</h3>
            <p class="sub">Select your location using the electoral reference records.</p>

            <div class="grid">
                <div class="full">
                    <label for="geographic_level">Application area *</label>
                    <select id="geographic_level" name="geographic_level" required>
                        <option value="">Select a participation role first</option>
                    </select>
                </div>

                <div>
                    <label for="county_id">County</label>
                    <select id="county_id" disabled>
                        <option value="">Select county</option>
                    </select>
                </div>

                <div>
                    <label for="constituency_id">Constituency</label>
                    <select id="constituency_id" disabled>
                        <option value="">Select constituency</option>
                    </select>
                </div>

                <div>
                    <label for="ward_id">Ward</label>
                    <select id="ward_id" disabled>
                        <option value="">Select ward</option>
                    </select>
                </div>

                <div>
                    <label for="polling_centre_id">Polling centre</label>
                    <select id="polling_centre_id" disabled>
                        <option value="">Select polling centre</option>
                    </select>
                </div>

                <div class="full">
                    <label for="polling_station_id">Polling station</label>
                    <select id="polling_station_id" disabled>
                        <option value="">Select polling station</option>
                    </select>
                </div>

                <div class="full">
                    <label for="experience">Relevant experience (optional)</label>
                    <textarea id="experience" name="experience"
                        placeholder="Briefly describe your relevant experience"></textarea>
                </div>
            </div>

            <hr class="divider">

            <button class="btn" id="submitBtn" type="submit">
                Submit application
            </button>

            <p class="notice">
                Submitting an application does not automatically create an account,
                grant campaign access, or confirm an appointment.
                The campaign team will review your information.
                Electoral locations are based on historical reference data.
            </p>
        </form>
    </div>
</main>

<footer>© {{ date('Y') }} KuraGuard. Election operations and accountability.</footer>

<script>
(() => {
    'use strict';

    const form = document.getElementById('applicationForm');
    const message = document.getElementById('message');
    const submitBtn = document.getElementById('submitBtn');
    const roleInput = document.getElementById('requested_role');

    const levels = [
        'county',
        'constituency',
        'ward',
        'polling_centre',
        'polling_station'
    ];

    const selectors = Object.fromEntries(
        levels.map(level => [
            level,
            document.getElementById(level + '_id')
        ])
    );

    const levelInput = document.getElementById('geographic_level');
    const campaignInput = document.getElementById('aspirant_contest_id');
    const aspirantSearch = document.getElementById('aspirant_search');

    window.kuraGuardAvailableCampaigns = [];

    let campaignsLoaded = false;

    function filterAspirants() {
        const query = aspirantSearch.value
            .trim()
            .toLocaleLowerCase();

        if (!campaignsLoaded) {
            resetSelect(campaignInput, 'Loading aspirants...');
            return;
        }

        const matching = window.kuraGuardAvailableCampaigns.filter(
            campaign => campaign.name
                .toLocaleLowerCase()
                .includes(query)
        );

        const selected = campaignInput.value;

        populate(
            campaignInput,
            matching,
            matching.length
                ? 'Select aspirant'
                : 'No matching aspirants'
        );

        if (matching.some(
            campaign => String(campaign.id) === selected
        )) {
            campaignInput.value = selected;
        }

        campaignInput.disabled = matching.length === 0;
    }

    aspirantSearch.addEventListener('input', filterAspirants);

    function showMessage(text, type = 'error') {
        message.className = 'message ' + type;
        message.textContent = text;
        message.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    function resetSelect(select, label) {
        select.replaceChildren(new Option(label, ''));
        select.disabled = true;
    }

    function populate(select, records, label) {
        resetSelect(select, label);

        for (const record of records) {
            select.add(new Option(
                record.name + (record.code ? ' (' + record.code + ')' : ''),
                record.id
            ));
        }

        select.disabled = false;
    }

    async function loadGeography(level, parentId = null) {
        const query = new URLSearchParams({ level });

        if (parentId) query.set('parent_id', parentId);

        const response = await fetch(
            '/join/api/geography?' + query.toString(),
            { headers: { Accept: 'application/json' } }
        );

        if (!response.ok) {
            throw new Error('Unable to load electoral locations.');
        }

        const result = await response.json();

        return Array.isArray(result)
            ? result
            : (result.data || []);
    }

    async function loadCampaigns() {
        try {
            const response = await fetch('/join/api/campaigns', {
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Campaign API HTTP ' + response.status);
            }

            const result = await response.json();
            const campaigns = Array.isArray(result)
                ? result
                : (result.data || []);

            window.kuraGuardAvailableCampaigns = campaigns.map(campaign => ({
                id: campaign.id,
                name: [
                    campaign.display_name,
                    campaign.office
                        ? String(campaign.office)
                            .replaceAll('_', ' ')
                            .toUpperCase()
                        : '',
                    campaign.election_year || ''
                ].filter(Boolean).join(' — ')
            }));

            campaignsLoaded = true;
            filterAspirants();

        } catch (error) {
            console.error('Campaign loading failed:', error);

            resetSelect(campaignInput, 'Unable to load aspirants');
        }
    }

    function selectRole(role) {
        roleInput.value = role;

        document.getElementById('agentRole')
            .classList.toggle('active', role === 'polling_station_agent');

        document.getElementById('mobilizerRole')
            .classList.toggle('active', role === 'mobilizer');

        levelInput.replaceChildren();

        if (role === 'polling_station_agent') {
            levelInput.add(new Option('Polling station', 'polling_station'));
            levelInput.value = 'polling_station';
            levelInput.disabled = true;
        } else {
            for (const level of ['county', 'constituency', 'ward']) {
                levelInput.add(new Option(
                    level.replaceAll('_', ' '),
                    level
                ));
            }
            levelInput.disabled = false;
        }
    }

    async function loadChildren(level, parentId) {
        const select = selectors[level];
        const index = levels.indexOf(level);

        for (let i = index; i < levels.length; i++) {
            resetSelect(
                selectors[levels[i]],
                'Select ' + levels[i].replaceAll('_', ' ')
            );
        }

        if (!parentId && level !== 'county') return;

        try {
            const records = await loadGeography(level, parentId);

            populate(
                select,
                records,
                'Select ' + level.replaceAll('_', ' ')
            );
        } catch (error) {
            showMessage(error.message);
        }
    }

    levels.forEach((level, index) => {
        selectors[level].addEventListener('change', () => {
            const next = levels[index + 1];

            if (next) {
                loadChildren(next, selectors[level].value);
            }
        });
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        if (!roleInput.value) {
            showMessage('Please choose a participation role.');
            return;
        }

        const level = levelInput.value;
        const geographicId = selectors[level]?.value;

        if (!geographicId) {
            showMessage('Please select your electoral location.');
            return;
        }

        const payload = {
            aspirant_contest_id: Number(campaignInput.value),
            requested_role: roleInput.value,
            full_name: document.getElementById('full_name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            geographic_level: level,
            geographic_id: Number(geographicId),
            experience: document.getElementById('experience').value
        };

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        try {
            const response = await fetch('/join/api/applications', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok) {
                const errors = result.errors
                    ? Object.values(result.errors).flat().join(' ')
                    : (result.message || 'Application could not be submitted.');

                throw new Error(errors);
            }

            showMessage(
                'Your application has been received for review. '
                + 'No campaign access has been granted.',
                'success'
            );

            form.reset();
            roleInput.value = '';
            document.getElementById('agentRole')
                .classList.remove('active');
            document.getElementById('mobilizerRole')
                .classList.remove('active');

        } catch (error) {
            showMessage(error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit application';
        }
    });

    document.getElementById('agentRole').addEventListener(
        'click',
        () => selectRole('polling_station_agent')
    );

    document.getElementById('mobilizerRole').addEventListener(
        'click',
        () => selectRole('mobilizer')
    );

    levelInput.addEventListener('change', () => {
        for (const level of levels) {
            resetSelect(
                selectors[level],
                'Select ' + level.replaceAll('_', ' ')
            );
        }
        loadChildren('county');
    });

    const requestedPath = window.location.pathname;

    selectRole(
        requestedPath.endsWith('/mobilizer')
            ? 'mobilizer'
            : 'polling_station_agent'
    );

    loadCampaigns();
    loadChildren('county');
})();
</script>
</body>
</html>
