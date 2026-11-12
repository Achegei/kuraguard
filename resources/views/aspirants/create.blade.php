<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Aspirant | KuraGuard</title>
    <style>
        :root {
            color-scheme: light;
            --navy: #10243a;
            --green: #16805d;
            --border: #dce5ec;
            --muted: #64748b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f4f7fa;
            color: var(--navy);
            font-family: system-ui, -apple-system, sans-serif;
        }
        header {
            background: var(--navy);
            color: white;
            padding: 22px max(20px, calc((100vw - 900px)/2));
        }
        header strong { font-size: 23px; }
        header p { color: #cbd5e1; margin: 5px 0 0; }
        main { max-width: 900px; margin: 32px auto; padding: 0 20px; }
        .card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 30px #10243a0a;
        }
        h1 { font-size: 26px; margin: 0 0 8px; }
        .intro { color: var(--muted); margin-bottom: 26px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }
        .field { margin-bottom: 20px; }
        label {
            display: block;
            font-weight: 650;
            margin-bottom: 8px;
        }
        input, select {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #bdcbd7;
            border-radius: 9px;
            background: white;
            color: var(--navy);
            font: inherit;
        }
        input:focus, select:focus {
            outline: 2px solid #86cbb3;
            border-color: var(--green);
        }
        .error { color: #b42318; font-size: 13px; margin-top: 6px; }
        .alert {
            background: #fff2f0;
            border: 1px solid #ffc7c0;
            padding: 14px;
            border-radius: 9px;
            margin-bottom: 20px;
        }
        .note { font-size: 13px; color: var(--muted); }
        .hidden { display: none; }
        button {
            background: var(--green);
            border: 0;
            color: white;
            font: inherit;
            font-weight: 700;
            padding: 14px 24px;
            border-radius: 9px;
            cursor: pointer;
        }
        button:disabled { opacity: .55; cursor: not-allowed; }
        @media(max-width: 650px) {
            .grid { grid-template-columns: 1fr; gap: 0; }
            .card { padding: 20px; }
        }
    </style>
</head>
<body>
<header>
    <strong>KuraGuard</strong>
    <p>Aspirant Campaign Registration</p>
</header>
<main>
    <section class="card">
        <h1>Create your campaign</h1>
        <p class="intro">
            Declare your intended electoral office and jurisdiction.
            Registration does not constitute official candidate verification.
        </p>

        @if ($errors->any())
            <div class="alert" role="alert">
                Please correct the highlighted registration details.
            </div>
        @endif

        <form method="POST"
              action="{{ route('aspirants.store') }}"
              id="registration-form">
            @csrf

            <div class="grid">
                <div class="field">
                    <label for="display_name">Aspirant display name</label>
                    <input id="display_name" name="display_name"
                           maxlength="150" required
                           value="{{ old('display_name', auth()->user()->name) }}">
                    @error('display_name')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="election_year">Election year</label>
                    <input id="election_year" name="election_year"
                           type="number" min="2022" max="2100" required
                           value="{{ old('election_year', 2027) }}">
                    @error('election_year')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="field">
                <label for="office">Electoral office</label>
                <select id="office" name="office" required>
                    <option value="">Select electoral office</option>
                    @foreach ($offices as $office)
                        <option value="{{ $office['value'] }}"
                                data-scope="{{ $office['scope_level'] }}"
                                @selected(old('office') === $office['value'])>
                            {{ $office['label'] }}
                        </option>
                    @endforeach
                </select>
                @error('office')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field hidden" id="county-field">
                <label for="county_id">County</label>
                <select id="county_id" name="county_id"
                        data-old="{{ old('county_id') }}">
                    <option value="">Select county</option>
                </select>
                @error('county_id')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field hidden" id="constituency-field">
                <label for="constituency_id">Constituency</label>
                <select id="constituency_id" name="constituency_id"
                        data-old="{{ old('constituency_id') }}">
                    <option value="">Select constituency</option>
                </select>
                @error('constituency_id')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field hidden" id="ward-field">
                <label for="ward_id">Ward</label>
                <select id="ward_id" name="ward_id"
                        data-old="{{ old('ward_id') }}">
                    <option value="">Select ward</option>
                </select>
                @error('ward_id')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <p class="note">
                Electoral geography is based on the historical IEBC 2022
                reference dataset. It is not a certification of the
                electoral boundaries or register for the selected election year.
            </p>

            <div class="error" id="lookup-error" role="alert"></div>
            <button type="submit" id="submit-button">
                Create campaign
            </button>
        </form>
    </section>
</main>

<script>
(() => {
    'use strict';

    const endpoints = {
        counties: @json(route('explorer.api.counties')),
        constituencies: @json(route('explorer.api.constituencies')),
        wards: @json(route('explorer.api.wards')),
    };

    const office = document.getElementById('office');
    const county = document.getElementById('county_id');
    const constituency = document.getElementById('constituency_id');
    const ward = document.getElementById('ward_id');
    const submit = document.getElementById('submit-button');
    const error = document.getElementById('lookup-error');

    let requestVersion = 0;

    function reset(select, label) {
        select.replaceChildren(new Option(label, ''));
        select.value = '';
    }

    async function load(select, url, label, version) {
        reset(select, label);
        select.disabled = true;

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Lookup unavailable');

            const payload = await response.json();
            if (version !== requestVersion) return false;

            const rows = Array.isArray(payload.data) ? payload.data : [];

            for (const item of rows) {
                select.add(new Option(item.name, String(item.id)));
            }

            const oldValue = select.dataset.old;
            if (oldValue && [...select.options].some(
                option => option.value === oldValue
            )) {
                select.value = oldValue;
            }

            return true;
        } catch {
            if (version === requestVersion) {
                error.textContent =
                    'Electoral locations could not be loaded. Please retry.';
            }
            return false;
        } finally {
            if (version === requestVersion) select.disabled = false;
        }
    }

    function scope() {
        return office.selectedOptions[0]?.dataset.scope || '';
    }

    function show(field, visible) {
        field.classList.toggle('hidden', !visible);
        field.querySelector('select').required = visible;
    }

    async function refresh() {
        const version = ++requestVersion;
        error.textContent = '';
        submit.disabled = true;

        const level = scope();
        const needsCounty = ['county', 'constituency', 'ward'].includes(level);
        const needsConstituency = ['constituency', 'ward'].includes(level);
        const needsWard = level === 'ward';

        show(document.getElementById('county-field'), needsCounty);
        show(document.getElementById('constituency-field'), needsConstituency);
        show(document.getElementById('ward-field'), needsWard);

        if (!needsCounty) {
            reset(county, 'Select county');
            reset(constituency, 'Select constituency');
            reset(ward, 'Select ward');
            submit.disabled = !level;
            return;
        }

        if (!await load(
            county, endpoints.counties, 'Select county', version
        )) return;

        if (needsConstituency && county.value) {
            await loadConstituencies(version);
        }

        submit.disabled = false;
    }

    async function loadConstituencies(version) {
        reset(constituency, 'Select constituency');
        reset(ward, 'Select ward');

        if (!county.value) return;

        const url = new URL(endpoints.constituencies, location.origin);
        url.searchParams.set('county_id', county.value);

        if (!await load(
            constituency, url, 'Select constituency', version
        )) return;

        if (scope() === 'ward' && constituency.value) {
            await loadWards(version);
        }
    }

    async function loadWards(version) {
        reset(ward, 'Select ward');

        if (!constituency.value) return;

        const url = new URL(endpoints.wards, location.origin);
        url.searchParams.set('constituency_id', constituency.value);

        await load(ward, url, 'Select ward', version);
    }

    office.addEventListener('change', () => {
        county.dataset.old = '';
        constituency.dataset.old = '';
        ward.dataset.old = '';
        refresh();
    });

    county.addEventListener('change', () => {
        constituency.dataset.old = '';
        ward.dataset.old = '';
        const version = ++requestVersion;
        loadConstituencies(version);
    });

    constituency.addEventListener('change', () => {
        ward.dataset.old = '';
        const version = ++requestVersion;
        loadWards(version);
    });

    refresh();
})();
</script>
</body>
</html>
