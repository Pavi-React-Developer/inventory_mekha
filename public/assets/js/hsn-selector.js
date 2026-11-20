(function () {
    const endpoint = window.hsnSearchEndpoint;
    if (!endpoint) return;
    const requestIds = new WeakMap();

    function getDropdown(input) {
        let results = input.parentElement.querySelector('.hsn-autocomplete-results');
        if (results) return results;

        if (getComputedStyle(input.parentElement).position === 'static') {
            input.parentElement.style.position = 'relative';
        }
        results = document.createElement('div');
        results.className = 'hsn-autocomplete-results list-group shadow-sm';
        results.style.cssText = 'display:none;position:absolute;top:100%;left:0;right:0;z-index:1080;max-height:260px;overflow-y:auto;';
        input.parentElement.appendChild(results);
        return results;
    }

    function applySelection(input, row) {
        input.value = row.hsn_code;
        input.dataset.hsnGstRate = row.gst_rate ?? '';
        input.dataset.hsnRateNote = row.rate_note || '';

        const valueTarget = input.dataset.hsnValueTarget;
        if (valueTarget) {
            const target = document.querySelector(valueTarget);
            if (target) target.value = row.hsn_code;
        }

        const gstTarget = input.dataset.hsnGstTarget;
        if (gstTarget) {
            const target = document.querySelector(gstTarget);
            if (target) target.value = row.gst_rate === null ? '' : Number(row.gst_rate).toFixed(2);
        }

        const noteTarget = input.dataset.hsnNoteTarget;
        if (noteTarget) {
            const target = document.querySelector(noteTarget);
            if (target) {
                target.textContent = row.rate_note || (row.gst_rate === null ? 'Verify the applicable GST rate for this classification.' : '');
            }
        }

        input.dispatchEvent(new Event('change', { bubbles: true }));
        const results = getDropdown(input);
        results.style.display = 'none';
    }

    async function search(input) {
        const results = getDropdown(input);
        const query = input.value.trim();
        const type = input.dataset.hsnType || 'HSN';
        const requestId = (requestIds.get(input) || 0) + 1;
        requestIds.set(input, requestId);
        results.replaceChildren();

        try {
            const response = await fetch(`${endpoint}?q=${encodeURIComponent(query)}&type=${encodeURIComponent(type)}`, {
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) throw new Error('Unable to search the HSN/SAC master.');
            const payload = await response.json();
            if (!payload.success) throw new Error(payload.message || 'Unable to search the HSN/SAC master.');
            if (requestIds.get(input) !== requestId) return;

            payload.data.forEach(row => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action py-2';
                const heading = document.createElement('div');
                heading.className = 'd-flex justify-content-between gap-2';
                const code = document.createElement('strong');
                code.className = 'font-monospace';
                code.textContent = `${row.code_type} ${row.hsn_code}`;
                const rate = document.createElement('span');
                rate.className = 'small text-muted';
                rate.textContent = row.gst_rate === null ? 'Verify rate' : `${Number(row.gst_rate).toFixed(2)}%`;
                heading.append(code, rate);
                button.append(heading);

                if (row.description) {
                    const description = document.createElement('div');
                    description.className = 'small text-muted';
                    description.textContent = row.description;
                    button.append(description);
                }
                if (row.rate_note) {
                    const note = document.createElement('div');
                    note.className = 'small text-warning-emphasis';
                    note.textContent = row.rate_note;
                    button.append(note);
                }

                button.addEventListener('click', () => applySelection(input, row));
                results.append(button);
            });

            if (payload.data.length === 0 && query) {
                const empty = document.createElement('div');
                empty.className = 'list-group-item small text-muted';
                empty.textContent = 'No matching HSN/SAC codes found.';
                results.append(empty);
            }
            results.style.display = payload.data.length || query ? 'block' : 'none';
        } catch (error) {
            if (requestIds.get(input) !== requestId) return;
            results.replaceChildren();
            const message = document.createElement('div');
            message.className = 'list-group-item small text-danger';
            message.textContent = error.message || 'Unable to search the HSN/SAC master.';
            results.append(message);
            results.style.display = 'block';
        }
    }

    document.addEventListener('focusin', event => {
        const input = event.target.closest('[data-hsn-autocomplete]');
        if (input) search(input);
    });

    document.addEventListener('input', event => {
        const input = event.target.closest('[data-hsn-autocomplete]');
        if (!input) return;
        const valueTarget = input.dataset.hsnValueTarget;
        if (valueTarget) {
            const target = document.querySelector(valueTarget);
            if (target) target.value = input.value;
        }
        const gstTarget = input.dataset.hsnGstTarget;
        if (gstTarget) {
            const target = document.querySelector(gstTarget);
            if (target) target.value = '';
        }
        input.dataset.hsnGstRate = '';
        input.dataset.hsnRateNote = '';
        const noteTarget = input.dataset.hsnNoteTarget;
        if (noteTarget) {
            const target = document.querySelector(noteTarget);
            if (target) target.textContent = '';
        }
        search(input);
    });

    document.addEventListener('click', event => {
        if (event.target.closest('[data-hsn-autocomplete], .hsn-autocomplete-results')) return;
        document.querySelectorAll('.hsn-autocomplete-results').forEach(results => {
            results.style.display = 'none';
        });
    });
})();
