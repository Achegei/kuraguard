<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Electoral Explorer | KuraGuard</title>

    <style>
        :root {
            color-scheme: light;
            --navy: #10243a;
            --green: #087f63;
            --border: #dce5eb;
            --muted: #64748b;
            --background: #f4f7fa;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--background);
            color: var(--navy);
            font-family: system-ui, -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif;
        }

        header {
            background: var(--navy);
            color: white;
            padding: 20px max(20px, calc((100vw - 1100px) / 2));
        }

        header a {
            color: white;
            text-decoration: none;
            font-weight: 800;
            font-size: 22px;
        }

        main {
            max-width: 1100px;
            margin: 36px auto;
            padding: 0 20px 50px;
        }

        h1 {
            font-size: clamp(28px, 4vw, 42px);
            margin-bottom: 8px;
        }

        .intro {
            color: var(--muted);
            line-height: 1.7;
            max-width: 750px;
        }

        .panel {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 25px;
            margin-top: 25px;
            box-shadow: 0 6px 22px rgba(16, 36, 58, .04);
        }

        h2 { margin-top: 0; font-size: 21px; }

        .office-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .office-button {
            border: 1px solid var(--border);
            background: white;
            border-radius: 10px;
            padding: 20px 12px;
            font-weight: 700;
            color: var(--navy);
            cursor: pointer;
            min-height: 75px;
        }

        .office-button:hover,
        .office-button.active {
            border-color: var(--green);
            background: #e8f7f1;
        }

        .office-button.active {
            box-shadow: inset 0 0 0 1px var(--green);
        }

        .selectors {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        label {
            display: block;
            font-weight: 650;
            margin-bottom: 8px;
        }

        select {
            width: 100%;
            padding: 13px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: white;
            color: var(--navy);
        }

        .hidden { display: none !important; }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .stat {
            padding: 20px;
            background: #f5f9fb;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .stat strong {
            display: block;
            font-size: clamp(22px, 3vw, 30px);
            margin-bottom: 6px;
        }

        .stat span {
            color: var(--muted);
            font-size: 13px;
        }

        .notice {
            margin-top: 18px;
            padding: 14px;
            background: #fff8e8;
            border-radius: 9px;
            color: #76551b;
            font-size: 13px;
            line-height: 1.6;
        }

        .status {
            margin-top: 15px;
            color: var(--muted);
        }

        .error { color: #b42318; }

        .breadcrumb {
            color: var(--green);
            font-weight: 700;
            margin-bottom: 12px;
        }

        .levels {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .level {
            background: #e8f7f1;
            color: #075c49;
            padding: 8px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
        }


        .polling-centre-toolbar {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            gap: 12px;
            margin-top: 16px;
        }

        .polling-centre-toolbar label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .polling-centre-toolbar input,
        .polling-centre-toolbar select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: white;
            color: var(--navy);
            font: inherit;
        }

        .polling-centre-results-count {
            color: var(--muted);
            font-size: 13px;
            margin: 12px 0 0;
        }

        @media (max-width: 700px) {
            .polling-centre-toolbar {
                grid-template-columns: 1fr;
            }
        }

        .polling-centre-row {
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #f5f9fb;
            min-height: 105px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 12px;
        }

        .polling-centre-row[hidden] {
            display: none !important;
        }

        .polling-centre-row:hover {
            border-color: var(--green);
            background: #eaf7f2;
        }

        .polling-centre-row {
            cursor: pointer;
        }

        .polling-centre-row:focus-visible {
            outline: 3px solid var(--green);
            outline-offset: 2px;
        }

        .polling-centre-row.selected {
            border: 2px solid var(--green);
            background: #dff5eb;
        }

        .polling-selection-summary {
            margin-top: 14px;
            padding: 12px 15px;
            border-radius: 10px;
            background: #edf7f3;
            color: var(--navy);
            font-size: 14px;
            font-weight: 600;
        }

        .view-stations-button {
            align-self: flex-start;
            border: 1px solid var(--green);
            background: white;
            color: var(--green);
            border-radius: 8px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .view-stations-button:hover {
            background: #eaf7f2;
        }

        .view-stations-button:focus-visible {
            outline: 3px solid var(--green);
            outline-offset: 2px;
        }

        .polling-station-row {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 15px;
            margin-top: 8px;
            background: #f5f9fb;
        }

        .polling-station-row strong {
            display: block;
            margin-bottom: 5px;
        }

        .polling-station-row span {
            color: var(--muted);
            font-size: 13px;
        }

        .polling-centre-name {
            color: var(--navy);
            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
        }

        .polling-centre-details {
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }

        #polling-centre-list {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        @media (max-width: 1000px) {
            #polling-centre-list {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            #polling-centre-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 430px) {
            #polling-centre-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .office-grid { grid-template-columns: repeat(2, 1fr); }
            .selectors, .stats { grid-template-columns: 1fr; }
            .panel { padding: 18px; }
            main { margin-top: 22px; }
        }

        /* Reusable geographic card explorer */
        .geographic-explorer-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: end;
            justify-content: space-between;
            margin: 16px 0;
        }

        .geographic-explorer-toolbar input {
            width: min(100%, 340px);
            padding: 11px 13px;
            border: 1px solid var(--border);
            border-radius: 9px;
            font: inherit;
        }

        .geographic-card-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .geographic-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
            width: 100%;
            padding: 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: white;
            color: var(--navy);
            text-align: left;
            cursor: pointer;
            transition: border-color .15s, background .15s;
        }

        .geographic-card:hover,
        .geographic-card:focus-visible {
            border-color: var(--green);
            background: #edf7f3;
        }

        .geographic-card-name {
            font-size: 15px;
            font-weight: 750;
            line-height: 1.4;
        }

        .geographic-card-voters {
            font-size: 12px;
            color: var(--muted);
        }

        .geographic-breadcrumbs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin: 10px 0 16px;
            font-size: 13px;
        }

        .geographic-breadcrumbs button {
            border: 0;
            background: transparent;
            color: var(--green);
            cursor: pointer;
            font: inherit;
            font-weight: 700;
        }

        @media (max-width: 1000px) {
            .geographic-card-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .geographic-card-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 430px) {
            .geographic-card-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>
    <link rel="stylesheet" href="{{ asset('css/kuraguard-explorer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-explorer-photo.css') }}?v=20261114a">
    <link rel="stylesheet"
          href="{{ asset('css/kuraguard-simulator.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-civic-ticker.css') }}?v=20261114a">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-navigation-153.css') }}?v=20261114a">
    <link rel="stylesheet" href="{{ asset('css/kuraguard-brand-157c.css') }}?v=20261114c">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=20261114c">
</head>
<body>

<x-civic-ticker />

<header>
    <a href="{{ url('/') }}" class="kg-explorer-brand-157c" aria-label="KuraGuard homepage"><span class="kg-brand-157c">
    <img
        class="kg-brand-image-157c"
        src="{{ asset('images/brand/kuraguard-logo-transparent.png') }}"
        alt="KuraGuard"
    >
    <span class="kg-brand-tagline-157c">
        Every Polling Station. Every Vote. Accounted For.
    </span>
</span></a>
    <nav class="kg-explorer-nav" aria-label="Explorer navigation">
        <a href="{{ url('/') }}">← Home</a>
        <a href="{{ route('register') }}">Register as Aspirant ↗</a>
    </nav>
</header>

<main>
    <h1>Electoral Explorer</h1>

    <p class="intro">
        Explore Kenya's electoral geography using historical
        IEBC 2022 registered-voter reference data.
        Start by choosing the political office.
    </p>

    <section class="panel">
        <h2>1. Select political office</h2>
        <div id="office-grid" class="office-grid"></div>
    </section>

    <section id="geography-panel" class="panel hidden">
        <h2>2. Select electoral jurisdiction</h2>

        <div class="selectors">
            <div id="county-field" class="hidden">
                <label for="county">County</label>
                <select id="county">
                    <option value="">Select county</option>
                </select>
            </div>

            <div id="constituency-field" class="hidden">
                <label for="constituency">Constituency</label>
                <select id="constituency" disabled>
                    <option value="">Select constituency</option>
                </select>
            </div>

            <div id="ward-field" class="hidden">
                <label for="ward">Ward</label>
                <select id="ward" disabled>
                    <option value="">Select ward</option>
                </select>
            </div>
        </div>
    </section>

    <section id="results-panel" class="panel hidden" aria-live="polite">
        <div id="breadcrumb" class="breadcrumb"></div>
        <h2 id="result-title">Electoral summary</h2>

        <div class="stats">
            <div class="stat">
                <strong id="registered-voters">—</strong>
                <span>Registered voters</span>
            </div>

            <div class="stat">
                <strong id="polling-centres">—</strong>
                <span>Polling centres</span>
            </div>

            <div class="stat">
                <strong id="polling-stations">—</strong>
                <span>Polling stations</span>
            </div>
        </div>

        <div id="navigation-levels" class="levels"></div>

        <div class="notice">
            Historical IEBC 2022 reference only.
            These figures are not the certified 2027 voter register,
            election results or forecasts.
            <span id="national-notice"></span>
        </div>
    </section>


<section id="kg-simulator" class="kg-simulator hidden"
         aria-labelledby="kg-simulator-heading">

    <div class="kg-sim-heading">
        <div>
            <span class="kg-sim-eyebrow">
                INTERACTIVE ELECTION SCENARIO
            </span>
            <h2 id="kg-simulator-heading">
                Hypothetical Vote Simulator
            </h2>
            <p>
                Compare user-defined voting scenarios using the
                selected jurisdiction's historical registered-voter
                total as a reference.
            </p>
        </div>
        <span class="kg-sim-tag">SIMULATION ONLY</span>
    </div>

    <div class="kg-sim-steps">
        <span>01 Seat</span>
        <span>02 Voters</span>
        <span>03 Candidates</span>
        <span>04 Votes</span>
        <span>05 Results</span>
    </div>

    <div class="kg-sim-context">
        <div>
            <small>SELECTED ELECTORAL JURISDICTION</small>
            <strong id="kg-sim-scope">—</strong>
        </div>
        <div>
            <small>HISTORICAL REGISTERED VOTERS · 2022</small>
            <strong id="kg-sim-register">—</strong>
        </div>
    </div>

    <div class="kg-sim-layout">
        <div class="kg-sim-controls">
            <h3>Scenario assumptions</h3>

            <label for="kg-sim-turnout">
                Hypothetical turnout (%)
            </label>
            <div class="kg-sim-range-row">
                <input type="range" id="kg-sim-turnout"
                       min="0" max="100" step="1" value="65">
                <output id="kg-sim-turnout-value">65%</output>
            </div>

            <div class="kg-sim-projected">
                <small>Hypothetical votes cast</small>
                <strong id="kg-sim-votes-cast">—</strong>
            </div>

            <h3>Candidate vote shares</h3>
            <p class="kg-sim-help">
                Enter candidate labels and hypothetical shares.
                Combined shares cannot exceed 100%.
            </p>

            <div id="kg-sim-candidates"></div>

            <button type="button" id="kg-sim-add"
                    class="kg-sim-add">
                + Add candidate
            </button>

            <button type="button" id="kg-sim-reset"
                    class="kg-sim-reset">
                Reset assumptions
            </button>
        </div>

        <div class="kg-sim-results">
            <h3>Scenario comparison</h3>

            <div class="kg-sim-metrics">
                <div>
                    <small>Votes allocated</small>
                    <strong id="kg-sim-allocated">—</strong>
                </div>
                <div>
                    <small>Unallocated votes</small>
                    <strong id="kg-sim-unallocated">—</strong>
                </div>
            </div>

            <div id="kg-sim-chart"
                 class="kg-sim-chart"
                 aria-live="polite"></div>

            <div id="kg-sim-message"
                 class="kg-sim-message"
                 role="status"></div>

            <div class="kg-sim-disclaimer">
                <strong>Important:</strong>
                This is a mathematical illustration, not an
                election prediction, official result or voter
                intention estimate. The 2022 registered-voter
                figures are historical reference data. Turnout
                and candidate shares are user-defined assumptions.
                Unallocated votes are not assigned to candidates.
            </div>
        </div>
    </div>
</section>

<section id="drilldown-panel"
             class="panel hidden"
             aria-label="Polling station explorer">

        <h2>3. Explore polling stations</h2>

        <p class="intro">
            Navigate from the selected electoral jurisdiction
            to individual polling centres and stations.
            All voter figures are historical IEBC 2022 data.
        </p>


        <div id="geographic-card-explorer" class="hidden">
            <div id="geographic-card-breadcrumbs"
                 class="geographic-breadcrumbs"></div>

            <h3 id="geographic-card-title">
                Explore electoral geography
            </h3>

            <div class="geographic-explorer-toolbar">
                <input id="geographic-card-search"
                       type="search"
                       placeholder="Search geographic areas"
                       autocomplete="off">

                <span id="geographic-card-count"
                      aria-live="polite"></span>
            </div>

            <div id="geographic-card-grid"
                 class="geographic-card-grid"></div>
        </div>

        <div id="drilldown-navigation" class="selectors">

            <div id="drilldown-county-field" class="hidden">
                <label for="drilldown-county">Explore county</label>
                <select id="drilldown-county" disabled>
                    <option value="">Select county</option>
                </select>
            </div>

            <div id="drilldown-constituency-field" class="hidden">
                <label for="drilldown-constituency">
                    Explore constituency
                </label>
                <select id="drilldown-constituency" disabled>
                    <option value="">Select constituency</option>
                </select>
            </div>

            <div id="drilldown-ward-field" class="hidden">
                <label for="drilldown-ward">Explore ward</label>
                <select id="drilldown-ward" disabled>
                    <option value="">Select ward</option>
                </select>
            </div>

        </div>

        <div id="drilldown-centres" class="hidden">
            <h3>Polling centres</h3>

            <div class="polling-centre-toolbar">
                <div>
                    <label for="polling-centre-search">
                        Search polling centres
                    </label>
                    <input
                        type="search"
                        id="polling-centre-search"
                        placeholder="Search by polling centre name"
                        autocomplete="off">
                </div>

                <div>
                    <label for="polling-centre-sort">
                        Sort polling centres
                    </label>
                    <select id="polling-centre-sort">
                        <option value="name">
                            Name: A to Z
                        </option>
                        <option value="voters-desc">
                            Most registered voters
                        </option>
                        <option value="voters-asc">
                            Fewest registered voters
                        </option>
                    </select>
                </div>
            </div>

            <p id="polling-centre-results-count"
               class="polling-centre-results-count"
               aria-live="polite"></p>

            <div id="polling-centre-list"></div>
            <div id="polling-selection-summary"
                 class="polling-selection-summary"
                 aria-live="polite">
                0 polling centres selected
            </div>
        </div>

        <div id="drilldown-stations" class="hidden">
            <h3>Polling stations</h3>
            <div id="polling-station-list"></div>
        </div>

        <p id="drilldown-status"
           class="status"
           role="status"
           aria-live="polite"></p>
    </section>

    <p id="status" class="status" role="status" aria-live="polite"></p>
</main>

<script>
(() => {
    'use strict';

    const api = '/explorer/api';

    const state = {
        office: null,
        county: null,
        constituency: null,
        ward: null,
        generation: 0,
        stationRequestId: 0
    };

    const el = id => document.getElementById(id);
    const show = (id, visible) =>
        el(id).classList.toggle('hidden', !visible);

    const status = (message, error = false) => {
        el('status').textContent = message;
        el('status').classList.toggle('error', error);
    };

    const format = number =>
        new Intl.NumberFormat('en-KE').format(number);

    async function get(path) {
        const response = await fetch(api + path, {
            headers: { Accept: 'application/json' }
        });

        if (!response.ok) {
            throw new Error('Request failed (HTTP ' + response.status + ')');
        }

        return (await response.json()).data;
    }

    function populate(id, records, placeholder) {
        const select = el(id);
        select.replaceChildren(new Option(placeholder, ''));

        for (const record of records) {
            select.add(new Option(record.name, record.id));
        }

        select.disabled = false;
    }

    function reset(id, placeholder) {
        el(id).replaceChildren(new Option(placeholder, ''));
        el(id).disabled = true;
    }

    function clearResults() {
        show('results-panel', false);
        resetDrilldown();
    }

    function resetDrilldown() {
        state.stationRequestId++;
        show('drilldown-panel', false);
        show('drilldown-navigation', true);
        show('geographic-card-explorer', false);

        el('geographic-card-grid').replaceChildren();
        el('geographic-card-breadcrumbs').replaceChildren();
        el('geographic-card-count').textContent = '';
        el('geographic-card-search').value = '';

        reset('drilldown-county', 'Select county');
        reset('drilldown-constituency', 'Select constituency');
        reset('drilldown-ward', 'Select ward');

        show('drilldown-county-field', false);
        show('drilldown-constituency-field', false);
        show('drilldown-ward-field', false);

        show('drilldown-centres', false);
        show('drilldown-stations', false);

        el('polling-centre-list').replaceChildren();
        el('polling-station-list').replaceChildren();
        el('polling-centre-search').value = '';
        el('polling-centre-sort').value = 'name';
        el('polling-centre-results-count').textContent = '';
        el('polling-selection-summary').textContent =
            '0 polling centres selected';
        el('drilldown-status').textContent = '';
    }

    async function displayPollingStations(centre, token) {
        const requestId = ++state.stationRequestId;
        const container = el('polling-station-list');

        container.replaceChildren();
        show('drilldown-stations', true);

        const heading = el('drilldown-stations')
            .querySelector('h3');

        heading.textContent =
            'Polling stations — ' + centre.name;

        el('drilldown-status').textContent =
            'Loading polling stations...';

        try {
            const records = await get(
                '/polling-stations?polling_centre_id=' +
                encodeURIComponent(centre.id)
            );

            if (token !== state.generation ||
                requestId !== state.stationRequestId) return;

            container.replaceChildren();

            for (const station of records) {
                const row = document.createElement('div');
                row.className = 'polling-station-row';

                const title = document.createElement('strong');
                title.textContent =
                    'Stream ' + (station.stream_number || '—');

                const details = document.createElement('span');
                details.textContent =
                    'Station code: ' + station.code +
                    ' · ' +
                    format(station.registered_voters) +
                    ' historical registered voters';

                row.append(title, details);
                container.appendChild(row);
            }

            el('drilldown-status').textContent =
                records.length
                    ? format(records.length) +
                      ' polling stations loaded.'
                    : 'No polling stations found.';

            el('drilldown-stations').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

        } catch (error) {
            if (token === state.generation &&
                requestId === state.stationRequestId) {
                el('drilldown-status').textContent =
                    error.message;
            }
        }
    }


    function renderGeographicCards(records, level, onSelect) {
        const grid = el('geographic-card-grid');
        const search = el('geographic-card-search');
        const count = el('geographic-card-count');

        grid.replaceChildren();
        search.value = '';

        const cards = records.map(record => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'geographic-card';

            const name = document.createElement('span');
            name.className = 'geographic-card-name';
            name.textContent = record.name;

            const voters = document.createElement('span');
            voters.className = 'geographic-card-voters';
            voters.textContent =
                format(record.registered_voters || 0) +
                ' historical registered voters';

            button.append(name, voters);
            button.addEventListener('click', () => onSelect(record));

            return { record, button };
        });

        const update = () => {
            const query = search.value.trim().toLocaleLowerCase();
            grid.replaceChildren();

            const visible = cards.filter(({ record }) =>
                record.name.toLocaleLowerCase().includes(query) ||
                String(record.code || '')
                    .toLocaleLowerCase().includes(query)
            );

            for (const { button } of visible) {
                grid.appendChild(button);
            }

            count.textContent =
                format(visible.length) + ' of ' +
                format(cards.length) + ' ' + level + ' shown';
        };

        search.oninput = update;
        update();

        show('geographic-card-explorer', true);
    }

    function clearCardSummary() {
        show('results-panel', false);
    }

    async function loadSummaryForCardSelection(token) {
        const office = state.office;
        if (!office) return;

        const scope = office.scope_level;
        const geographicId =
            scope === 'county' ? state.county :
            scope === 'constituency' ? state.constituency :
            scope === 'ward' ? state.ward : null;

        if (!geographicId) return;

        const query = new URLSearchParams({
            office: office.code,
            geographic_id: geographicId
        });

        try {
            const data = await get(
                '/summary?' + query.toString()
            );

            if (token !== state.generation ||
                state.office !== office) return;

            const currentId =
                scope === 'county' ? state.county :
                scope === 'constituency' ? state.constituency :
                state.ward;

            if (String(currentId) !== String(geographicId)) {
                return;
            }

            el('breadcrumb').textContent =
                data.office_label + ' / ' + data.geographic_name;

            el('result-title').textContent =
                data.geographic_name + ' — Electoral Summary';

            el('registered-voters').textContent =
                format(data.statistics.registered_voters);

            el('polling-centres').textContent =
                format(data.statistics.polling_centres);

            el('polling-stations').textContent =
                format(data.statistics.polling_stations);

            el('national-notice').textContent =
                data.national_total_notice
                    ? ' ' + data.national_total_notice
                    : '';

            el('navigation-levels').replaceChildren();

            for (const level of data.navigation_levels) {
                const span = document.createElement('span');
                span.className = 'level';
                span.textContent = level.replaceAll('_', ' ');
                el('navigation-levels').appendChild(span);
            }

            show('results-panel', true);
        } catch (error) {
            if (token === state.generation) {
                status(error.message, true);
            }
        }
    }

    async function navigateGeographicCards(
        level,
        parentId,
        breadcrumbs,
        token
    ) {
        const requestId = ++drilldownRequestId;

        const paths = {
            county: '/counties',
            constituency:
                '/constituencies?county_id=' +
                encodeURIComponent(parentId),
            ward:
                '/wards?constituency_id=' +
                encodeURIComponent(parentId)
        };

        if (!Object.hasOwn(paths, level)) return;

        el('geographic-card-title').textContent =
            'Explore ' + level.charAt(0).toUpperCase() +
            level.slice(1) + 's';

        const breadcrumbContainer =
            el('geographic-card-breadcrumbs');

        breadcrumbContainer.replaceChildren();

        for (const crumb of breadcrumbs) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = crumb.name;

            button.addEventListener('click', () => {
                navigateGeographicCards(
                    crumb.level,
                    crumb.parentId,
                    breadcrumbs.slice(0, breadcrumbs.indexOf(crumb)),
                    state.generation
                ).catch(error => {
                    el('drilldown-status').textContent = error.message;
                });
            });

            breadcrumbContainer.appendChild(button);
        }

        clearDrilldownDetails();

        el('geographic-card-grid').replaceChildren();
        el('geographic-card-count').textContent = '';
        el('drilldown-status').textContent =
            'Loading ' + level + 's...';

        const records = await get(paths[level]);

        if (token !== state.generation ||
            requestId !== drilldownRequestId) return;

        renderGeographicCards(records, level + 's', record => {
            const nextBreadcrumbs = [
                ...breadcrumbs,
                {
                    name: record.name,
                    level,
                    parentId
                }
            ];

            if (level === 'county') {
                state.county = String(record.id);
                state.constituency = null;
                state.ward = null;

                if (state.office.scope_level === 'county') {
                    loadSummaryForCardSelection(
                        state.generation
                    );
                } else {
                    clearCardSummary();
                }

                navigateGeographicCards(
                    'constituency',
                    record.id,
                    nextBreadcrumbs,
                    state.generation
                ).catch(error => {
                    el('drilldown-status').textContent = error.message;
                });
            } else if (level === 'constituency') {
                state.constituency = String(record.id);
                state.ward = null;

                if (state.office.scope_level === 'constituency') {
                    loadSummaryForCardSelection(
                        state.generation
                    );
                } else if (state.office.scope_level === 'ward') {
                    clearCardSummary();
                }

                navigateGeographicCards(
                    'ward',
                    record.id,
                    nextBreadcrumbs,
                    state.generation
                ).catch(error => {
                    el('drilldown-status').textContent = error.message;
                });
            } else if (level === 'ward') {
                state.ward = String(record.id);

                if (state.office.scope_level === 'ward') {
                    loadSummaryForCardSelection(
                        state.generation
                    );
                }
                const wardRequestId = ++drilldownRequestId;
                const wardToken = state.generation;

                clearDrilldownDetails();

                el('drilldown-status').textContent =
                    'Loading polling centres for ' + record.name + '...';

                loadDrilldownCentres(record.id, wardToken)
                    .then(() => {
                        if (wardToken !== state.generation ||
                            wardRequestId !== drilldownRequestId) return;

                        el('drilldown-status').textContent =
                            'Polling centres — ' + record.name;

                        el('drilldown-centres').scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    })
                    .catch(error => {
                        if (wardToken === state.generation &&
                            wardRequestId === drilldownRequestId) {
                            el('drilldown-status').textContent =
                                error.message;
                        }
                    });
            }
        });

        el('drilldown-status').textContent = '';
    }

    async function initializeDrilldown(token) {
        resetDrilldown();

        const office = state.office;
        if (!office) return;

        const level = office.scope_level;

        if (level !== 'national' &&
            level !== 'county' &&
            level !== 'constituency' &&
            level !== 'ward') {
            return;
        }

        if (level === 'national') {
            show('drilldown-navigation', false);
            show('drilldown-panel', true);

            await navigateGeographicCards(
                'county',
                null,
                [],
                token
            );

            return;
        }

        if (level === 'county' && state.county) {
            show('drilldown-navigation', false);
            show('drilldown-panel', true);

            await navigateGeographicCards(
                'constituency',
                state.county,
                [],
                token
            );

            return;
        }

        if (level === 'constituency' && state.constituency) {
            show('drilldown-navigation', false);
            show('drilldown-panel', true);

            await navigateGeographicCards(
                'ward',
                state.constituency,
                [],
                token
            );

            return;
        }

        if (level === 'ward' && state.ward) {
            show('drilldown-navigation', false);
            show('geographic-card-explorer', false);
            show('drilldown-panel', true);

            await loadDrilldownCentres(state.ward, token);

            return;
        }

        if (token !== state.generation) return;
        show('drilldown-panel', true);
    }

    async function loadDrilldownCentres(wardId, token) {
            const records = await get(
                '/polling-centres?ward_id=' +
                encodeURIComponent(wardId)
            );

            if (token !== state.generation) return;

            const list = el('polling-centre-list');
            list.replaceChildren();

            const selectedPollingCentres = new Map();
            const centreCards = [];

            const updateSelectionSummary = () => {
                const count = selectedPollingCentres.size;
                const voters = Array.from(
                    selectedPollingCentres.values()
                ).reduce(
                    (total, centre) =>
                        total + Number(centre.registered_voters || 0),
                    0
                );

                el('polling-selection-summary').textContent =
                    format(count) +
                    ' polling centres selected · ' +
                    format(voters) +
                    ' historical registered voters';
            };

            for (const centre of records) {
                const item = document.createElement('div');
                item.className = 'polling-centre-row';
                item.setAttribute('role', 'button');
                item.setAttribute('tabindex', '0');
                item.setAttribute('aria-pressed', 'false');

                const toggleSelection = () => {
                    const key = String(centre.id);
                    const selected = selectedPollingCentres.has(key);

                    if (selected) {
                        selectedPollingCentres.delete(key);
                    } else {
                        selectedPollingCentres.set(key, centre);
                    }

                    item.classList.toggle('selected', !selected);
                    item.setAttribute(
                        'aria-pressed',
                        String(!selected)
                    );

                    updateSelectionSummary();
                };

                item.addEventListener('click', toggleSelection);

                item.addEventListener('keydown', event => {
                    if (event.key === 'Enter' ||
                        event.key === ' ') {
                        event.preventDefault();
                        toggleSelection();
                    }
                });

                const name = document.createElement('div');
                name.className = 'polling-centre-name';
                name.textContent = centre.name;

                const details = document.createElement('div');
                details.className = 'polling-centre-details';
                details.textContent =
                    format(centre.polling_stations) +
                    ' stations · ' +
                    format(centre.registered_voters) +
                    ' registered voters';

                const viewButton =
                    document.createElement('button');

                viewButton.type = 'button';
                viewButton.className = 'view-stations-button';
                viewButton.textContent = 'View stations';

                viewButton.addEventListener('click', event => {
                    event.stopPropagation();

                    displayPollingStations(
                        centre,
                        state.generation
                    );
                });

                viewButton.addEventListener('keydown', event => {
                    event.stopPropagation();
                });

                item.append(name, details, viewButton);
                list.appendChild(item);
                centreCards.push({ centre, item });
            }

            const searchInput = el('polling-centre-search');
            const sortSelect = el('polling-centre-sort');
            const resultsCount = el('polling-centre-results-count');

            const updateCentreDisplay = () => {
                const query = searchInput.value
                    .trim()
                    .toLocaleLowerCase();

                const sortMode = sortSelect.value;

                const matching = centreCards.filter(({ centre }) =>
                    centre.name.toLocaleLowerCase().includes(query) ||
                    String(centre.code || '')
                        .toLocaleLowerCase().includes(query)
                );

                matching.sort((a, b) => {
                    if (sortMode === 'voters-desc') {
                        return Number(b.centre.registered_voters) -
                            Number(a.centre.registered_voters);
                    }

                    if (sortMode === 'voters-asc') {
                        return Number(a.centre.registered_voters) -
                            Number(b.centre.registered_voters);
                    }

                    return a.centre.name.localeCompare(b.centre.name);
                });

                const visibleIds = new Set(
                    matching.map(({ centre }) => String(centre.id))
                );

                for (const { centre, item } of centreCards) {
                    item.hidden = !visibleIds.has(String(centre.id));
                }

                for (const { item } of matching) {
                    list.appendChild(item);
                }

                resultsCount.textContent =
                    format(matching.length) +
                    ' of ' +
                    format(centreCards.length) +
                    ' polling centres shown';
            };

            searchInput.oninput = updateCentreDisplay;
            sortSelect.onchange = updateCentreDisplay;
            updateCentreDisplay();

            show('drilldown-centres', true);

            if (records.length === 0) {
                el('drilldown-status').textContent =
                    'No polling centres found for this ward.';
            }
    }

    async function loadCounties(token) {
        const records = await get('/counties');
        if (token !== state.generation) return;

        populate('county', records, 'Select county');
    }

    async function selectOffice(office) {
        const token = ++state.generation;

        state.office = office;
        state.county = null;
        state.constituency = null;
        state.ward = null;

        clearResults();
        status('');

        document.querySelectorAll('.office-button')
            .forEach(button => {
                const active = button.dataset.office === office.code;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', String(active));
            });

        reset('county', 'Select county');
        reset('constituency', 'Select constituency');
        reset('ward', 'Select ward');

        const level = office.scope_level;

        show('geography-panel', false);
        show('county-field', false);
        show('constituency-field', false);
        show('ward-field', false);

        try {
            if (level === 'national') {
                await loadSummary(token);
            } else {
                show('drilldown-panel', true);
                show('drilldown-navigation', false);

                await navigateGeographicCards(
                    'county',
                    null,
                    [],
                    token
                );
            }
        } catch (error) {
            if (token === state.generation) {
                status(error.message, true);
            }
        }
    }

    async function loadSummary(token) {
        const office = state.office;
        if (!office) return;

        let geographicId = null;

        if (office.scope_level === 'county') {
            geographicId = state.county;
        } else if (office.scope_level === 'constituency') {
            geographicId = state.constituency;
        } else if (office.scope_level === 'ward') {
            geographicId = state.ward;
        }

        if (office.scope_level !== 'national' && !geographicId) {
            clearResults();
            return;
        }

        status('Loading electoral summary...');

        const query = new URLSearchParams({ office: office.code });
        if (geographicId) {
            query.set('geographic_id', geographicId);
        }

        const data = await get('/summary?' + query.toString());
        if (token !== state.generation) return;

        el('breadcrumb').textContent =
            data.office_label + ' / ' + data.geographic_name;

        el('result-title').textContent =
            data.geographic_name + ' — Electoral Summary';

        el('registered-voters').textContent =
            format(data.statistics.registered_voters);

        el('polling-centres').textContent =
            format(data.statistics.polling_centres);

        el('polling-stations').textContent =
            format(data.statistics.polling_stations);

        el('national-notice').textContent =
            data.national_total_notice
                ? ' ' + data.national_total_notice
                : '';

        el('navigation-levels').replaceChildren();

        for (const level of data.navigation_levels) {
            const span = document.createElement('span');
            span.className = 'level';
            span.textContent = level.replaceAll('_', ' ');
            el('navigation-levels').appendChild(span);
        }

        show('results-panel', true);
        status('');

        try {
            await initializeDrilldown(token);
        } catch (error) {
            if (token === state.generation) {
                el('drilldown-status').textContent =
                    'Unable to load geographic navigation: ' +
                    error.message;
                show('drilldown-panel', true);
            }
        }
    }

    let drilldownRequestId = 0;

    function clearDrilldownDetails() {
        state.stationRequestId++;
        show('drilldown-centres', false);
        show('drilldown-stations', false);
        el('polling-centre-list').replaceChildren();
        el('polling-station-list').replaceChildren();
        el('polling-selection-summary').textContent =
            '0 polling centres selected';
        el('polling-centre-results-count').textContent = '';
        el('polling-centre-search').value = '';
        el('polling-centre-sort').value = 'name';
        el('drilldown-status').textContent = '';
    }

    el('drilldown-county').addEventListener('change', async event => {
        const requestId = ++drilldownRequestId;
        const token = state.generation;
        const countyId = event.target.value;

        reset('drilldown-constituency', 'Select constituency');
        reset('drilldown-ward', 'Select ward');
        show('drilldown-constituency-field', false);
        show('drilldown-ward-field', false);
        clearDrilldownDetails();

        if (!countyId) return;

        try {
            const records = await get(
                '/constituencies?county_id=' +
                encodeURIComponent(countyId)
            );

            if (token !== state.generation ||
                requestId !== drilldownRequestId) return;

            show('drilldown-constituency-field', true);
            populate(
                'drilldown-constituency',
                records,
                'Select constituency'
            );
        } catch (error) {
            if (token === state.generation &&
                requestId === drilldownRequestId) {
                el('drilldown-status').textContent = error.message;
            }
        }
    });

    el('drilldown-constituency').addEventListener('change', async event => {
        const requestId = ++drilldownRequestId;
        const token = state.generation;
        const constituencyId = event.target.value;

        reset('drilldown-ward', 'Select ward');
        show('drilldown-ward-field', false);
        clearDrilldownDetails();

        if (!constituencyId) return;

        try {
            const records = await get(
                '/wards?constituency_id=' +
                encodeURIComponent(constituencyId)
            );

            if (token !== state.generation ||
                requestId !== drilldownRequestId) return;

            show('drilldown-ward-field', true);
            populate('drilldown-ward', records, 'Select ward');
        } catch (error) {
            if (token === state.generation &&
                requestId === drilldownRequestId) {
                el('drilldown-status').textContent = error.message;
            }
        }
    });

    el('drilldown-ward').addEventListener('change', async event => {
        const requestId = ++drilldownRequestId;
        const token = state.generation;
        const wardId = event.target.value;

        clearDrilldownDetails();

        if (!wardId) return;

        try {
            await loadDrilldownCentres(wardId, token);
        } catch (error) {
            if (token === state.generation &&
                requestId === drilldownRequestId) {
                el('drilldown-status').textContent = error.message;
            }
        }
    });

    el('county').addEventListener('change', async event => {
        const token = ++state.generation;

        state.county = event.target.value || null;
        state.constituency = null;
        state.ward = null;

        reset('constituency', 'Select constituency');
        reset('ward', 'Select ward');
        clearResults();

        if (!state.county) return;

        try {
            if (state.office.scope_level === 'county') {
                await loadSummary(token);
            } else {
                status('Loading constituencies...');

                const records = await get(
                    '/constituencies?county_id=' +
                    encodeURIComponent(state.county)
                );

                if (token !== state.generation) return;

                populate(
                    'constituency',
                    records,
                    'Select constituency'
                );
                status('');
            }
        } catch (error) {
            if (token === state.generation) {
                status(error.message, true);
            }
        }
    });

    el('constituency').addEventListener('change', async event => {
        const token = ++state.generation;

        state.constituency = event.target.value || null;
        state.ward = null;

        reset('ward', 'Select ward');
        clearResults();

        if (!state.constituency) return;

        try {
            if (state.office.scope_level === 'constituency') {
                await loadSummary(token);
            } else {
                status('Loading wards...');

                const records = await get(
                    '/wards?constituency_id=' +
                    encodeURIComponent(state.constituency)
                );

                if (token !== state.generation) return;

                populate('ward', records, 'Select ward');
                status('');
            }
        } catch (error) {
            if (token === state.generation) {
                status(error.message, true);
            }
        }
    });

    el('ward').addEventListener('change', async event => {
        const token = ++state.generation;

        state.ward = event.target.value || null;
        clearResults();

        if (!state.ward) return;

        try {
            await loadSummary(token);
        } catch (error) {
            if (token === state.generation) {
                status(error.message, true);
            }
        }
    });

    async function initialize() {
        try {
            status('Loading political offices...');

            const offices = await get('/offices');

            for (const office of offices) {
                const button = document.createElement('button');

                button.type = 'button';
                button.className = 'office-button';
                button.dataset.office = office.code;
                button.textContent = office.name;
                button.setAttribute('aria-pressed', 'false');

                button.addEventListener('click', () => {
                    selectOffice(office);
                });

                el('office-grid').appendChild(button);
            }

            status('');
        } catch (error) {
            status(error.message, true);
        }
    }

    initialize();
})();
</script>

<script src="{{ asset('js/kuraguard-simulator.js') }}"
        defer></script>
</body>
</html>
