/* KURAGUARD SCENARIO SIMULATOR V1 */

(() => {
    'use strict';

    const el = id => document.getElementById(id);
    const fmt = value =>
        new Intl.NumberFormat('en-KE').format(value);

    const colors = [
        '#9285ff',
        '#568df4',
        '#d49bff',
        '#f3a46c',
        '#7bc4de',
        '#b5a2ec'
    ];

    let registered = 0;
    let candidates = [];

    const safeNumber = value => {
        const n = Number(value);
        return Number.isFinite(n) ? n : 0;
    };

    const clampShare = value =>
        Math.max(0, Math.min(100, safeNumber(value)));

    function addCandidate(name, share) {
        if (candidates.length >= 6) return;

        candidates.push({
            name,
            share
        });

        renderInputs();
        calculate();
    }

    function renderInputs() {
        const container = el('kg-sim-candidates');
        container.replaceChildren();

        candidates.forEach((candidate, index) => {
            const row = document.createElement('div');
            row.className = 'kg-sim-candidate';

            const name = document.createElement('input');
            name.type = 'text';
            name.maxLength = 80;
            name.value = candidate.name;
            name.setAttribute(
                'aria-label',
                'Candidate ' + (index + 1) + ' name'
            );

            name.addEventListener('input', () => {
                candidate.name = name.value;
                calculate();
            });

            const share = document.createElement('input');
            share.type = 'number';
            share.min = '0';
            share.max = '100';
            share.step = '0.1';
            share.value = candidate.share;
            share.setAttribute(
                'aria-label',
                'Candidate ' + (index + 1) + ' vote share percent'
            );

            share.addEventListener('input', () => {
                candidate.share = clampShare(share.value);
                calculate();
            });

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = '×';
            remove.title = 'Remove candidate';
            remove.setAttribute(
                'aria-label',
                'Remove candidate ' + (index + 1)
            );

            remove.addEventListener('click', () => {
                if (candidates.length <= 2) return;
                candidates.splice(index, 1);
                renderInputs();
                calculate();
            });

            remove.disabled = candidates.length <= 2;

            row.append(name, share, remove);
            container.appendChild(row);
        });

        el('kg-sim-add').disabled = candidates.length >= 6;
    }

    function calculate() {
        const turnout = safeNumber(el('kg-sim-turnout').value);
        const cast = Math.round(registered * turnout / 100);

        el('kg-sim-turnout-value').textContent = turnout + '%';
        el('kg-sim-votes-cast').textContent = fmt(cast);

        const totalShare = candidates.reduce(
            (sum, c) => sum + clampShare(c.share),
            0
        );

        const valid = totalShare <= 100.000001;

        const chart = el('kg-sim-chart');
        chart.replaceChildren();

        if (!valid) {
            el('kg-sim-allocated').textContent = '—';
            el('kg-sim-unallocated').textContent = '—';
            el('kg-sim-message').textContent =
                'Combined vote shares exceed 100%. ' +
                'Reduce the values to calculate this scenario.';
            return;
        }

        let allocated = 0;

        candidates.forEach((candidate, index) => {
            const votes = Math.round(
                cast * clampShare(candidate.share) / 100
            );

            allocated += votes;

            const item = document.createElement('div');
            item.className = 'kg-sim-bar-item';

            const header = document.createElement('div');
            header.className = 'kg-sim-bar-header';

            const label = document.createElement('span');
            label.textContent =
                candidate.name.trim() ||
                'Candidate ' + (index + 1);

            const amount = document.createElement('strong');
            amount.textContent =
                fmt(votes) + ' · ' +
                clampShare(candidate.share) + '%';

            header.append(label, amount);

            const track = document.createElement('div');
            track.className = 'kg-sim-bar-track';

            const fill = document.createElement('div');
            fill.className = 'kg-sim-bar-fill';
            fill.style.width = clampShare(candidate.share) + '%';
            fill.style.background = colors[index];

            track.appendChild(fill);
            item.append(header, track);
            chart.appendChild(item);
        });

        // Candidate totals are individually rounded.
        // Clamp the residual to avoid a negative display
        // caused by independent rounding.
        const unallocated = Math.max(0, cast - allocated);

        el('kg-sim-allocated').textContent = fmt(allocated);
        el('kg-sim-unallocated').textContent = fmt(unallocated);

        const ranked = candidates.map((candidate, index) => ({
            name: candidate.name.trim() ||
                'Candidate ' + (index + 1),
            share: clampShare(candidate.share)
        })).sort((a, b) => b.share - a.share);

        if (!registered) {
            el('kg-sim-message').textContent =
                'No historical registered-voter total is available ' +
                'for this selection.';
            return;
        }

        if (ranked[0].share === ranked[1].share) {
            el('kg-sim-message').textContent =
                'The highest hypothetical vote shares are tied.';
            return;
        }

        const margin = Math.round(
            cast * (ranked[0].share - ranked[1].share) / 100
        );

        el('kg-sim-message').textContent =
            'Highest hypothetical share: ' +
            ranked[0].name +
            ' (' + ranked[0].share + '%). ' +
            'Difference from second-highest share: ' +
            fmt(margin) + ' hypothetical votes.';
    }

    function syncExplorer() {
        const results = el('results-panel');
        const simulator = el('kg-simulator');

        if (results.classList.contains('hidden')) {
            simulator.classList.add('hidden');
            return;
        }

        const raw = el('registered-voters')
            .textContent.replace(/[^\d]/g, '');

        if (!raw) {
            simulator.classList.add('hidden');
            return;
        }

        registered = safeNumber(raw);

        const scope = el('breadcrumb').textContent.trim();

        el('kg-sim-scope').textContent =
            scope || 'Selected electoral jurisdiction';

        el('kg-sim-register').textContent = fmt(registered);

        simulator.classList.remove('hidden');
        calculate();
    }

    function reset() {
        candidates = [
            { name: 'Candidate A', share: 45 },
            { name: 'Candidate B', share: 40 },
            { name: 'Candidate C', share: 15 }
        ];

        el('kg-sim-turnout').value = '65';
        renderInputs();
        calculate();
    }

    el('kg-sim-turnout').addEventListener('input', calculate);

    el('kg-sim-add').addEventListener('click', () => {
        addCandidate(
            'Candidate ' + (candidates.length + 1),
            0
        );
    });

    el('kg-sim-reset').addEventListener('click', reset);

    reset();

    const observer = new MutationObserver(syncExplorer);

    observer.observe(el('results-panel'), {
        attributes: true,
        attributeFilter: ['class'],
        childList: true,
        characterData: true,
        subtree: true
    });

    syncExplorer();
})();
