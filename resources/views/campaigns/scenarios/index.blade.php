@extends('layouts.campaign')

@section('title', 'Scenario Builder | KuraGuard')
@section('breadcrumb', 'Scenario Builder')

@push('styles')
<style>
.kg-scenario {
    color: #e9edff;
    font-family: Inter, -apple-system, BlinkMacSystemFont,
        "Segoe UI", sans-serif;
}
.kg-scenario * { box-sizing: border-box; }
.kg-scenario .scenario-hero {
    background: linear-gradient(125deg, #07132d, #17265d 65%, #49328d);
    border: 1px solid #374477;
    border-radius: 22px;
    padding: 34px;
    margin-bottom: 22px;
}
.kg-scenario .eyebrow {
    color: #a5b4fc;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-size: 11px;
    font-weight: 800;
}
.kg-scenario .scenario-hero h1 {
    color: #ffffff !important;
    font-size: clamp(27px, 4vw, 42px);
    letter-spacing: -1.3px;
    margin: 10px 0;
    color: white;
}
.kg-scenario .subtitle {
    color: #c5d0f3;
    max-width: 750px;
    line-height: 1.7;
}
.kg-scenario .context {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 25px;
}
.kg-scenario .context-item {
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 13px;
    padding: 16px;
}
.kg-scenario .context-item span {
    display: block;
    color: #aebce6;
    font-size: 12px;
    margin-bottom: 7px;
}
.kg-scenario .context-item strong {
    display: block;
    color: white;
    font-size: 16px;
    overflow-wrap: anywhere;
}
.kg-scenario .scenario-panel {
    background: #0c1733;
    border: 1px solid #2b3966;
    border-radius: 19px;
    padding: 25px;
    margin-bottom: 20px;
}
.kg-scenario .scenario-panel h2 {
    color: white;
    font-size: 21px;
    margin: 0 0 8px;
}
.kg-scenario .help {
    color: #aebce6;
    font-size: 13px;
    line-height: 1.65;
}
.kg-scenario .steps {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}
.kg-scenario .step {
    padding: 14px 8px;
    text-align: center;
    border-radius: 11px;
    background: #17244b;
    color: #aebce6;
    font-size: 12px;
    border: 1px solid #303d70;
}
.kg-scenario .step.active {
    background: #4338ca;
    color: white;
    border-color: #818cf8;
}
.kg-scenario .form-grid,
.kg-scenario .results-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.kg-scenario label {
    display: block;
    color: #dbe4ff;
    font-size: 13px;
    font-weight: 650;
    margin-bottom: 8px;
}
.kg-scenario input {
    width: 100%;
    background: #111f43;
    color: white;
    border: 1px solid #445383;
    border-radius: 11px;
    padding: 13px;
    font: inherit;
}
.kg-scenario input:focus {
    outline: 2px solid #818cf8;
    outline-offset: 1px;
}
.kg-scenario input[readonly] {
    background: #172347;
    color: #c8d4f4;
}
.kg-scenario .metric {
    background: #142247;
    border: 1px solid #324272;
    padding: 20px;
    border-radius: 13px;
}
.kg-scenario .metric span {
    display: block;
    color: #acbce4;
    font-size: 12px;
    margin-bottom: 10px;
}
.kg-scenario .metric strong {
    color: white;
    font-size: clamp(21px, 3vw, 31px);
    font-variant-numeric: tabular-nums;
}
.kg-scenario .comparison {
    margin-top: 20px;
    display: grid;
    gap: 15px;
}
.kg-scenario .bar-label {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    margin-bottom: 7px;
}
.kg-scenario .bar-track {
    height: 15px;
    background: #26355c;
    border-radius: 999px;
    overflow: hidden;
}
.kg-scenario .bar-fill {
    height: 100%;
    width: 0;
    background: #818cf8;
    transition: width .2s;
}
.kg-scenario .bar-fill.alternative { background: #c084fc; }
.kg-scenario .notice {
    color: #c4cee9;
    font-size: 12px;
    line-height: 1.7;
    margin-top: 18px;
    border-top: 1px solid #2b3966;
    padding-top: 16px;
}
.kg-scenario .error {
    color: #fecaca;
    font-size: 13px;
    margin-top: 12px;
    min-height: 18px;
}
@media(max-width: 760px) {
    .kg-scenario .context,
    .kg-scenario .form-grid,
    .kg-scenario .results-grid {
        grid-template-columns: 1fr;
    }
    .kg-scenario .steps { grid-template-columns: repeat(5, 1fr); }
    .kg-scenario .step { font-size: 10px; padding: 11px 3px; }
    .kg-scenario .scenario-hero,
    .kg-scenario .scenario-panel { padding: 20px; }
}

/* Compact selectable polling-centre cards */
.kg-scenario .scenario-centre-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 18px;
}
.kg-scenario .scenario-centre-card {
    display: grid;
    grid-template-columns: 20px minmax(0, 1fr);
    grid-template-rows: auto auto;
    column-gap: 11px;
    row-gap: 8px;
    align-content: start;
    min-height: 112px;
    padding: 15px;
    margin: 0;
    border: 1px solid #344372;
    border-radius: 13px;
    background: #142247;
    cursor: pointer;
    transition: border-color .15s, background .15s,
                transform .15s;
}
.kg-scenario .scenario-centre-card:hover {
    border-color: #818cf8;
    transform: translateY(-1px);
}
.kg-scenario .scenario-centre-card.is-selected {
    border-color: #a5b4fc;
    background: #25366e;
    box-shadow: inset 0 0 0 1px #818cf8;
}
.kg-scenario .scenario-centre-checkbox {
    grid-column: 1;
    grid-row: 1;
    width: 17px;
    height: 17px;
    margin: 2px 0 0;
    accent-color: #818cf8;
}
.kg-scenario .scenario-centre-name {
    grid-column: 2;
    grid-row: 1;
    color: #edf2ff;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.45;
    overflow-wrap: anywhere;
}
.kg-scenario .scenario-centre-voters {
    grid-column: 2;
    grid-row: 2;
    color: #b7c6ec;
    font-size: 12px;
    font-weight: 500;
}
@media (max-width: 1050px) {
    .kg-scenario .scenario-centre-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 650px) {
    .kg-scenario .scenario-centre-grid {
        grid-template-columns: 1fr;
    }
}


.kg-scenario [data-scenario-stage][hidden] {
    display: none !important;
}
.kg-scenario .scenario-wizard-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin: 24px 0 35px;
}
.kg-scenario .scenario-wizard-button {
    border: 1px solid #5c6eb0;
    border-radius: 11px;
    padding: 13px 23px;
    font-weight: 750;
    cursor: pointer;
    transition: background .15s, transform .15s;
}
.kg-scenario .scenario-wizard-button:hover:not(:disabled) {
    transform: translateY(-1px);
}
.kg-scenario .scenario-wizard-button.primary {
    color: white;
    background: #4338ca;
    border-color: #818cf8;
}
.kg-scenario .scenario-wizard-button.secondary {
    color: #e0e7ff;
    background: #1b2a54;
}
.kg-scenario .scenario-wizard-button:disabled {
    opacity: .4;
    cursor: not-allowed;
}
.kg-scenario .scenario-stage-status {
    color: #b7c6ec;
    font-size: 12px;
    text-align: center;
}
.kg-scenario .step {
    cursor: default;
}
@media (max-width: 650px) {
    .kg-scenario .scenario-wizard-controls {
        flex-wrap: wrap;
    }
    .kg-scenario .scenario-wizard-button {
        flex: 1;
    }
    .kg-scenario .scenario-stage-status {
        order: -1;
        width: 100%;
    }
}


/* Step 10.21M: reversible assignments */
.kg-scenario .scenario-centre-card.is-other-assigned { border-color:#76608e; }
.kg-scenario .scenario-centre-card.is-other-assigned .kg-multi-owner { color:#e9c6ff; }
.kg-scenario .kg-multi-result-fill.is-unassigned { background:#94a3b8; }
/* Step 10.21L: electorate share presentation */
/* Multi-candidate comparison — scoped styles */
.kg-scenario .kg-multi-list{display:grid;gap:12px;margin:18px 0}
.kg-scenario .kg-multi-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;background:#142247;border:1px solid #344372;border-radius:13px;padding:14px}
.kg-scenario .kg-multi-row input{min-width:0}
.kg-scenario .kg-multi-row button{background:#25366e;border:1px solid #56679e;color:#e9edff;border-radius:9px;padding:11px 14px;cursor:pointer}
.kg-scenario .kg-multi-row strong{color:#fff;font-size:19px}
.kg-scenario .kg-multi-row small{display:block;color:#aebce6;margin-top:5px}
.kg-scenario .kg-multi-picker{max-width:440px}
.kg-scenario .kg-multi-picker select{width:100%;padding:12px;background:#142247;color:white;border:1px solid #56679e;border-radius:11px;font:inherit}
.kg-scenario .scenario-centre-card.is-selected{border-color:#a5b4fc;background:#25366e}
.kg-scenario .kg-multi-owner{grid-column:2;grid-row:3;color:#c4b5fd;font-size:11px}
.kg-scenario .kg-multi-result-bar{height:9px;background:#25365b;border-radius:999px;overflow:hidden;margin-top:10px}
.kg-scenario .kg-multi-result-fill{height:100%;background:#818cf8}
.kg-scenario .kg-multi-inline{display:flex;align-items:center;justify-content:space-between;gap:12px}
.kg-scenario .kg-multi-inline span{color:#aebce6;font-size:12px}
@media(max-width:650px){.kg-scenario .kg-multi-row{grid-template-columns:1fr}}

</style>
@endpush

@section('content')
<div class="kg-scenario">
    <section class="scenario-hero">
        <div class="eyebrow">KuraGuard / Election Intelligence</div>
        <h1>Scenario Builder</h1>
        <p class="subtitle">
            Explore transparent, hypothetical election calculations
            within your declared electoral jurisdiction.
            All figures are illustrative, not election predictions.
        </p>

        <div class="context">
            <div class="context-item">
                <span>Campaign</span>
                <strong>{{ $contest->display_name }}</strong>
            </div>
            <div class="context-item">
                <span>Political office</span>
                <strong>{{ $scope['office_label'] }}</strong>
            </div>
            <div class="context-item">
                <span>Electoral jurisdiction</span>
                <strong>{{ $scope['geographic_name'] }}</strong>
            </div>
        </div>
    </section>

    <div class="steps" aria-label="Scenario stages">
        <div class="step active">1. Seat</div>
        <div class="step">2. Candidates</div>
        <div class="step">3. Areas</div>
        <div class="step">4. Review</div>
        <div class="step">5. Result</div>
    </div>

    <section class="scenario-panel" data-scenario-stage="0">
        <h2>Electoral seat and historical reference</h2>
        <p class="help">{{ $scope['dataset']['name'] }} ({{ $scope['dataset']['reference_year'] }}) — {{ $scope['geographic_name'] }}.</p>
        <div class="results-grid">
            <div class="metric"><span>Historical registered voters</span><strong id="kg-scope-voters">{{ number_format($scope['statistics']['registered_voters']) }}</strong></div>
            <div class="metric"><span>Polling stations in reference</span><strong>{{ number_format($scope['statistics']['polling_stations']) }}</strong></div>
        </div>
        <p class="notice">Historical voter-registration figures are not actual votes, candidate support, or a prediction. No election records are modified.</p>
    </section>

    <section class="scenario-panel" data-scenario-stage="1">
        <h2>Comparison candidates</h2>
        <p class="help">Registered aspirants for this same seat are listed automatically when discovery is enabled. Add other competitors manually if needed. Geographic assignments remain hypothetical.</p>
        <div id="kg-multi-candidate-list" class="kg-multi-list"></div>
        <button type="button" id="kg-add-candidate" class="scenario-wizard-button secondary">+ Add unregistered aspirant</button>
        <p class="notice">This comparison is not saved. “Registered on KuraGuard” means a platform declaration, not official IEBC clearance. Only explicitly published aspirant names should be discoverable.</p>
    </section>

    <section class="scenario-panel" id="scenario-geography" data-scenario-stage="2">
        <h2>Assign geographic areas</h2>
        <p class="help">Choose a candidate and tick centres to assign them. Untick any of their centres to return them to Unassigned. To transfer a centre from another candidate, tick it and confirm the reassignment. Every centre can belong to only one group.</p>
        <div class="kg-multi-picker"><label for="kg-active-candidate">Assign areas to</label><select id="kg-active-candidate"></select></div>
        <div class="results-grid" style="margin:18px 0">
            <div class="metric"><span>Centres assigned to selected candidate</span><strong id="kg-selected-count">0</strong></div>
            <div class="metric"><span>Registered voters in selected candidate's areas</span><strong id="kg-selected-voters">0</strong></div>
        </div>
        <div id="scenario-geography-status" class="help">Loading electoral geography...</div>
        <div id="scenario-geography-list" class="scenario-centre-grid"></div>
        <p class="notice">Assigning a polling centre does not mean its voters support the named candidate. All figures are historical registered-voter counts.</p>
    </section>

    <section class="scenario-panel" data-scenario-stage="3">
        <h2>Review geographic coverage</h2>
        <p class="help">Review the distribution of historical registered voters across mutually exclusive candidate-assigned areas.</p>
        <div class="results-grid">
            <div class="metric"><span>Assigned registered voters</span><strong id="kg-review-assigned">0</strong></div>
            <div class="metric"><span>Unassigned registered voters</span><strong id="kg-review-unassigned">—</strong></div>
        </div>
        <div id="kg-review-list" class="kg-multi-list" style="margin-top:18px"></div>
        <p class="notice" id="kg-coverage-notice">Unassigned voters include all voters outside selected centres and any difference between the source reference totals and centre-level data.</p>
    </section>

    <section class="scenario-panel" data-scenario-stage="4">
        <h2>Geographic electorate comparison</h2>
        <p class="help">Each percentage is the candidate-assigned area's share of the entire historical registered electorate, not a vote share or prediction of support.</p>
        <div id="kg-result-list" class="kg-multi-list"></div>
        <div class="results-grid" style="margin-top:18px">
            <div class="metric"><span>Unassigned registered electorate</span><strong id="kg-result-unassigned">—</strong></div>
            <div class="metric"><span>Unassigned share of total</span><strong id="kg-result-unassigned-share">—</strong></div>
        </div>
        <p class="notice">No turnout, vote share, winning probability or political support is inferred. These assignments are hypothetical and are not saved.</p>
    </section>

    <div id="scenario-wizard-controls" class="scenario-wizard-controls">
        <button type="button" id="scenario-back"
                class="scenario-wizard-button secondary">
            Back
        </button>

        <span id="scenario-stage-status"
              class="scenario-stage-status"
              aria-live="polite"></span>

        <button type="button" id="scenario-next"
                class="scenario-wizard-button primary">
            Continue
        </button>
    </div>

</div>
@endsection

@push('scripts')
<script>
(() => {
    'use strict';
    const office = @json($contest->office);
    const wardId = @json($contest->ward_id);
    const campaignName = @json($contest->display_name);
    const scopeVoters = Number(@json($scope['statistics']['registered_voters']));
    const list = document.getElementById('kg-multi-candidate-list');
    const picker = document.getElementById('kg-active-candidate');
    const geographyList = document.getElementById('scenario-geography-list');
    const geographyStatus = document.getElementById('scenario-geography-status');
    const indicators = [...document.querySelectorAll('.kg-scenario .steps .step')];
    const stages = [...document.querySelectorAll('[data-scenario-stage]')];
    const back = document.getElementById('scenario-back');
    const next = document.getElementById('scenario-next');
    const status = document.getElementById('scenario-stage-status');
    const format = n => Math.round(n).toLocaleString('en-KE');
    const set = (id, value) => { document.getElementById(id).textContent = value; };
    // Step 10.21N: same-seat registered aspirants (public-safe fields only).
    const registeredCompetitors = @json($registeredCompetitors);
    const candidates = [{id:1, name:campaignName || 'Campaign aspirant', source:'active', contestId:@json($contest->id)}];
    for (const competitor of registeredCompetitors) {
        if (!competitor || !Number.isSafeInteger(Number(competitor.contest_id))) continue;
        if (!String(competitor.name || '').trim()) continue;
        candidates.push({id:candidates.length + 1, name:String(competitor.name), source:'registered', contestId:Number(competitor.contest_id)});
    }
    const centres = new Map();
    const assignments = new Map();
    let candidateSequence = candidates.length;
    let activeCandidate = 1;
    let activeStage = 0;
    let geographyReady = false;

    function candidateName(id) {
        const c = candidates.find(item => item.id === id);
        return c ? (c.name.trim() || ('Candidate ' + id)) : 'Unassigned';
    }
    function totals() {
        const result = new Map(candidates.map(c => [c.id, {count:0, voters:0}]));
        for (const [centreId, candidateId] of assignments) {
            const centre = centres.get(centreId);
            const total = result.get(candidateId);
            if (centre && total) { total.count++; total.voters += centre.voters; }
        }
        return result;
    }
    function renderCandidates() {
        list.replaceChildren();
        picker.replaceChildren();
        for (const candidate of candidates) {
            const row = document.createElement('div');
            row.className = 'kg-multi-row';
            const input = document.createElement('input');
            input.type = 'text'; input.maxLength = 120; input.value = candidate.name;
            input.readOnly = candidate.source !== 'manual';
            input.setAttribute('aria-label', 'Candidate name');
            input.addEventListener('input', () => {
                candidate.name = input.value;
                renderPickerLabels();
            });
            row.append(input);
            const sourceNote = document.createElement('span');
            sourceNote.className = 'help';
            sourceNote.textContent = candidate.source === 'active' ? 'Active campaign' :
                candidate.source === 'registered' ? 'Registered on KuraGuard · platform declaration' : 'Manually added · unregistered';
            row.append(sourceNote);
            if (candidate.id === 1) {
                const note = document.createElement('span');
                note.textContent = 'Campaign aspirant'; note.className = 'help';
                row.append(note);
            } else {
                const remove = document.createElement('button');
                remove.type = 'button'; remove.textContent = 'Remove';
                remove.addEventListener('click', () => {
                    for (const [id, owner] of assignments) {
                        if (owner === candidate.id) assignments.delete(id);
                    }
                    candidates.splice(candidates.findIndex(c => c.id === candidate.id), 1);
                    if (activeCandidate === candidate.id) activeCandidate = 1;
                    renderCandidates(); renderGeography(); renderSummaries();
                });
                row.append(remove);
            }
            list.append(row);
            const option = document.createElement('option');
            option.value = String(candidate.id); option.textContent = candidateName(candidate.id);
            picker.append(option);
        }
        picker.value = String(activeCandidate);
    }
    function renderPickerLabels() {
        for (const option of picker.options) option.textContent = candidateName(Number(option.value));
    }
    // Display labels use the lowest available manual placeholder number.
    // Candidate IDs remain monotonic and are NEVER reused for assignments.
    function nextManualLabelNumber() {
        const used = new Set();
        for (const candidate of candidates) {
            const match = /^Unregistered aspirant ([1-9]\d*)$/.exec(candidate.name.trim());
            if (match) used.add(Number(match[1]));
        }
        let number = candidates.filter(candidate => candidate.source !== 'manual').length + 1;
        while (used.has(number)) number++;
        return number;
    }

    document.getElementById('kg-add-candidate').addEventListener('click', () => {
        const labelNumber = nextManualLabelNumber();
        candidateSequence++;
        candidates.push({id:candidateSequence, name:'Unregistered aspirant ' + labelNumber, source:'manual', contestId:null});
        renderCandidates();
    });
    picker.addEventListener('change', () => {
        activeCandidate = Number(picker.value);
        renderGeography();
    });
    function renderGeography() {
        geographyList.replaceChildren();
        const candidateTotal = totals().get(activeCandidate) || {count:0,voters:0};
        set('kg-selected-count', format(candidateTotal.count));
        set('kg-selected-voters', format(candidateTotal.voters));
        for (const centre of centres.values()) {
            const owner = assignments.get(centre.id);
            const label = document.createElement('label');
            label.className = 'scenario-centre-card';
            if (owner === activeCandidate) label.classList.add('is-selected');
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox'; checkbox.className = 'scenario-centre-checkbox';
            checkbox.checked = owner === activeCandidate;
            checkbox.disabled = false;
            if (owner !== undefined && owner !== activeCandidate) label.classList.add('is-other-assigned');
            const name = document.createElement('span');
            name.className = 'scenario-centre-name'; name.textContent = centre.name;
            const voters = document.createElement('span');
            voters.className = 'scenario-centre-voters'; voters.textContent = format(centre.voters) + ' registered voters';
            label.append(checkbox, name, voters);
            if (checkbox.disabled) {
                const ownerLabel = document.createElement('span');
                ownerLabel.className = 'kg-multi-owner';
                ownerLabel.textContent = 'Assigned to ' + candidateName(owner);
                label.append(ownerLabel);
                label.title = 'Click to transfer from ' + candidateName(owner) + ' to ' + candidateName(activeCandidate);
            }
            checkbox.addEventListener('change', () => {
                const previousOwner = assignments.get(centre.id);
                if (checkbox.checked && previousOwner !== undefined && previousOwner !== activeCandidate) {
                    const message = 'Move "' + centre.name + '" from ' + candidateName(previousOwner) + ' to ' + candidateName(activeCandidate) + '? This changes only the hypothetical geographic assignment.';
                    if (!window.confirm(message)) {
                        renderGeography();
                        return;
                    }
                }
                if (checkbox.checked) assignments.set(centre.id, activeCandidate);
                else if (previousOwner === activeCandidate) assignments.delete(centre.id);
                renderGeography(); renderSummaries();
            });
            geographyList.append(label);
        }
    }
    function makeSummaryRow(candidate, total, includeBar) {
        const row = document.createElement('div'); row.className = 'kg-multi-row';
        const content = document.createElement('div');
        const heading = document.createElement('strong'); heading.textContent = candidateName(candidate.id);
        const detail = document.createElement('small');
        const percentage = scopeVoters > 0 ? (total.voters / scopeVoters * 100) : 0;
        detail.textContent = format(total.count) + ' centres assigned · ' + percentage.toFixed(2) + '% of registered electorate';
        content.append(heading, detail);
        if (includeBar) {
            const track = document.createElement('div'); track.className = 'kg-multi-result-bar';
            const fill = document.createElement('div'); fill.className = 'kg-multi-result-fill';
            fill.style.width = (scopeVoters > 0 ? Math.min(100, total.voters / scopeVoters * 100) : 0) + '%';
            track.append(fill); content.append(track);
        }
        const value = document.createElement('strong'); value.textContent = format(total.voters);
        row.append(content, value); return row;
    }
    function renderSummaries() {
        const perCandidate = totals();
        const assigned = [...perCandidate.values()].reduce((sum, t) => sum + t.voters, 0);
        set('kg-review-assigned', format(assigned));
        const unassigned = Math.max(0, scopeVoters - assigned);
        set('kg-review-unassigned', format(unassigned));
        set('kg-result-unassigned', format(unassigned));
        set('kg-result-unassigned-share', scopeVoters > 0 ? (unassigned / scopeVoters * 100).toFixed(2) + '%' : 'N/A');
        for (const [target, includeBar] of [['kg-review-list', false], ['kg-result-list', true]]) {
            const el = document.getElementById(target); el.replaceChildren();
            for (const candidate of candidates) {
                el.append(makeSummaryRow(candidate, perCandidate.get(candidate.id), includeBar));
            }
        }
        const remainder = Math.max(0, scopeVoters - assigned);
        for (const target of ['kg-review-list', 'kg-result-list']) {
            const container = document.getElementById(target);
            const row = document.createElement('div');
            row.className = 'kg-multi-row';
            const content = document.createElement('div');
            const heading = document.createElement('strong');
            heading.textContent = 'Unassigned electorate';
            const detail = document.createElement('small');
            detail.textContent = (scopeVoters > 0 ? (remainder / scopeVoters * 100).toFixed(2) : '0.00') + '% of registered electorate';
            content.append(heading, detail);
            if (target === 'kg-result-list') {
                const track = document.createElement('div');
                track.className = 'kg-multi-result-bar';
                const fill = document.createElement('div');
                fill.className = 'kg-multi-result-fill is-unassigned';
                fill.style.width = (scopeVoters > 0 ? Math.min(100, remainder / scopeVoters * 100) : 0) + '%';
                track.append(fill);
                content.append(track);
            }
            const value = document.createElement('strong');
            value.textContent = format(remainder);
            row.append(content, value);
            container.append(row);
        }
        const note = document.getElementById('kg-coverage-notice');
        if (assigned > scopeVoters) {
            note.textContent = 'Warning: centre-level total exceeds the reference scope. Check the underlying electoral dataset before interpreting these figures.';
        } else {
            note.textContent = 'Unassigned registered voters: ' + format(scopeVoters - assigned) + '. This is a geographic coverage remainder, not a voting preference.';
        }
    }
    async function loadGeography() {
        if (office !== 'mca' || !wardId) {
            geographyStatus.textContent = 'Geographic assignments are currently supported for MCA ward campaigns only. Other offices require scoped geographic data integration.';
            return;
        }
        try {
            const response = await fetch('/explorer/api/polling-centres?ward_id=' + encodeURIComponent(wardId), {credentials:'same-origin'});
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            const records = Array.isArray(payload) ? payload : (payload.data || payload.polling_centres || []);
            if (!Array.isArray(records)) throw new Error('Unexpected electoral API response');
            for (const item of records) {
                const id = Number(item.id), voters = Number(item.registered_voters);
                if (!Number.isSafeInteger(id) || id <= 0 || !Number.isSafeInteger(voters) || voters < 0 || centres.has(id)) {
                    throw new Error('Invalid or duplicate centre reference');
                }
                centres.set(id, {id, name:String(item.name || ('Centre #' + id)), voters});
            }
            geographyReady = true;
            geographyStatus.textContent = format(centres.size) + ' polling centres available.';
            renderGeography(); renderSummaries();
        } catch (err) {
            geographyStatus.textContent = 'Unable to load polling centres: ' + err.message;
        }
    }
    function showStage(stage) {
        activeStage = Math.max(0, Math.min(4, stage));
        stages.forEach(section => { section.hidden = Number(section.dataset.scenarioStage) !== activeStage; });
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === activeStage);
            indicator.setAttribute('aria-current', index === activeStage ? 'step' : 'false');
        });
        back.disabled = activeStage === 0;
        next.textContent = activeStage === 3 ? 'See Result' : activeStage === 4 ? 'Start Again' : 'Continue';
        status.textContent = 'Step ' + (activeStage + 1) + ' of 5';
        if (activeStage === 2) renderGeography();
        if (activeStage >= 3) renderSummaries();
        document.querySelector('.kg-scenario .steps').scrollIntoView({behavior:'smooth',block:'start'});
    }
    back.addEventListener('click', () => showStage(activeStage - 1));
    next.addEventListener('click', () => {
        if (activeStage === 4) { showStage(0); return; }
        if (activeStage === 2 && !geographyReady) {
            geographyStatus.textContent = 'Cannot proceed until geographic data loads successfully.';
            return;
        }
        showStage(activeStage + 1);
    });
    renderCandidates(); renderSummaries(); loadGeography(); showStage(0);
})();
</script>
@endpush
