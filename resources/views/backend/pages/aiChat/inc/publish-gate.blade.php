{{-- Publish gate (Features spec, phase 1): Commit Resources & Publish card,
     mounted under the Leadership Alignment Brief once it exists. --}}
<style>
    .pg-card { border: 1px solid #36839b; border-radius: 10px; padding: 16px; background: #e7f3f7; }
    .pg-card h5 { color: #2c6d82; margin-bottom: 4px; }
    .pg-card .pg-sub { font-size: 13px; color: #555; margin-bottom: 12px; }
    .pg-card table input, .pg-card table textarea, .pg-card table select { min-width: 90px; font-size: 13px; }
    .pg-card .pg-hint { font-size: 12px; color: #6c757d; }
    .pg-btn-primary { background: #36839b; border-color: #36839b; color: #fff; }
    .pg-btn-primary:hover { background: #2c6d82; border-color: #2c6d82; color: #fff; }
    .pg-btn-confirm { background: #ec883f; border-color: #ec883f; color: #fff; }
    .pg-published { background: #fbf2ea; border-left: 4px solid #ec883f; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; }
    .pg-error { color: #b42318; font-size: 13px; margin: 8px 0; }
    .pg-history { font-size: 12px; margin-top: 12px; }
</style>
<script>
(function () {
    const urls = {
        show: '{{ route('users-new-chat-resources.show', ['chat' => $id]) }}',
        suggest: '{{ route('users-new-chat-resources-suggest.index') }}',
        save: '{{ route('users-new-chat-resources-save.index') }}',
        publish: '{{ route('users-new-chat-publish.index') }}',
    };
    const chatId = {{ (int) $id }};
    const csrf = '{{ csrf_token() }}';
    let state = null;
    let busy = false;
    let error = '';
    let confirmPublish = false;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const money = v => (v === null || v === undefined || v === '') ? '—' : state.currency + Number(v).toLocaleString();

    async function call(url, body) {
        const res = await fetch(url, {
            method: body ? 'POST' : 'GET',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: body ? JSON.stringify(body) : undefined,
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
            const msg = json.error || (json.errors ? Object.values(json.errors)[0][0] : '') || json.message || 'Something went wrong.';
            throw Object.assign(new Error(msg), { status: res.status });
        }
        return json;
    }

    function card() { return document.getElementById('publish-gate-card'); }

    // The brief is rendered from the DB on load, or injected after Finish, and the
    // wizard sometimes rebuilds its DOM; re-mount whenever the card goes missing.
    function mount() {
        const briefs = document.querySelectorAll('.leadership-alignment-brief');
        if (!briefs.length || card()) return;
        const el = document.createElement('div');
        el.id = 'publish-gate-card';
        el.className = 'pg-card mt-3';
        briefs[briefs.length - 1].insertAdjacentElement('afterend', el);
        if (state) { render(); } else { load(); }
    }

    async function load() {
        try {
            state = await call(urls.show);
            error = '';
            if (state.status === 'draft' && state.rows.length === 0 && state.ready) {
                return suggest();
            }
        } catch (e) { error = e.message; }
        render();
    }

    async function suggest() {
        busy = true; error = ''; render();
        try {
            state = await call(urls.suggest, { chat_id: chatId });
        } catch (e) {
            error = e.message;
            if (state && state.rows.length === 0) {
                // Let the author type figures in by hand while the AI is unavailable.
                state.rows = (state.departments.length ? state.departments : [{ id: null, name: 'Whole organization' }])
                    .map(d => ({ id: null, department_id: d.id, department_name: d.name, budget: null, fte: null, tools: '', notes: '' }));
            }
        }
        busy = false; render();
    }

    function readRows() {
        return Array.from(card().querySelectorAll('tr[data-row]')).map(tr => {
            const v = name => { const f = tr.querySelector(`[name="${name}"]`); return f ? f.value : null; };
            const num = name => { const x = v(name); return x === '' || x === null ? null : Number(x); };
            const dept = v('department_id');
            return {
                id: tr.dataset.id ? Number(tr.dataset.id) : null,
                department_id: dept === '' || dept === null ? null : Number(dept),
                department_name: tr.dataset.name,
                budget: num('budget'), fte: num('fte'), tools: v('tools'), notes: v('notes'),
            };
        });
    }

    async function save() {
        busy = true; error = ''; render();
        try { state = await call(urls.save, { chat_id: chatId, rows: readRows() }); }
        catch (e) { error = e.message; }
        busy = false; render();
        return !error;
    }

    async function publish() {
        if (!confirmPublish) { confirmPublish = true; render(); return; }
        confirmPublish = false;
        if (!(await save())) return;
        busy = true; render();
        try { state = await call(urls.publish, { chat_id: chatId }); }
        catch (e) { error = e.message; }
        busy = false; render();
    }

    function addRow() {
        const used = new Set(readRows().map(r => r.department_id));
        const pick = card().querySelector('#pg-add-dept').value;
        const d = pick === '' ? { id: null, name: 'Whole organization' } : state.departments.find(x => String(x.id) === pick);
        if (!d || used.has(d.id)) return;
        state.rows = readRows().concat([{ id: null, department_id: d.id, department_name: d.name, budget: null, fte: null, tools: '', notes: '' }]);
        render();
    }

    function removeRow(i) { state.rows = readRows().filter((_, j) => j !== i); render(); }

    function rowHtml(r, i, editable, fixedSet) {
        const ai = r.ai_suggestion || null;
        const aiHint = ai ? `<div class="pg-hint">AI: ${esc(money(ai.budget))} · ${esc(ai.fte ?? '—')} FTE${ai.rationale ? ' — ' + esc(ai.rationale) : ''}</div>` : '';
        const cell = (name, value, type) => editable
            ? (type === 'textarea'
                ? `<textarea class="form-control form-control-sm" name="${name}" rows="1">${esc(value)}</textarea>`
                : `<input class="form-control form-control-sm" type="number" min="0" step="any" name="${name}" value="${esc(value)}">`)
            : esc(name === 'budget' ? money(value) : (value ?? '—'));
        return `<tr data-row data-id="${r.id ?? ''}" data-name="${esc(r.department_name)}">
            <td><strong>${esc(r.department_name)}</strong><input type="hidden" name="department_id" value="${r.department_id ?? ''}">${aiHint}</td>
            <td>${cell('budget', r.budget)}</td>
            <td>${cell('fte', r.fte)}</td>
            <td>${cell('tools', r.tools, 'textarea')}</td>
            <td>${cell('notes', r.notes, 'textarea')}</td>
            <td>${editable && !fixedSet ? `<button type="button" class="btn btn-sm btn-link text-danger" data-remove="${i}" title="Remove">✕</button>` : ''}</td>
        </tr>`;
    }

    function render() {
        const el = card();
        if (!el) return;
        if (!state) {
            el.innerHTML = `<h5>Commit Resources &amp; Publish</h5>${error ? `<div class="pg-error">${esc(error)}</div>` : '<div class="pg-sub">Loading…</div>'}`;
            return;
        }

        const published = state.status === 'published';
        const editable = !published || state.is_publisher;
        const header = published
            ? `<div class="pg-published">Published by <strong>${esc(state.published_by)}</strong> on ${esc(new Date(state.published_at).toLocaleString())}. ${state.is_publisher ? 'You can still change amounts; every change is logged.' : ''}</div>`
            : `<div class="pg-sub">Review the resources each department needs. This strategy stays private until it is published.</div>`;

        const used = new Set(state.rows.map(r => r.department_id));
        const options = [{ id: '', name: 'Whole organization', key: null }]
            .concat(state.departments.map(d => ({ id: d.id, name: d.name, key: d.id })))
            .filter(o => !used.has(o.key))
            .map(o => `<option value="${o.id}">${esc(o.name)}</option>`).join('');

        const history = state.changes.length
            ? `<div class="pg-history"><strong>Change history</strong><ul class="mb-0">${state.changes.map(c =>
                `<li>${esc(new Date(c.at).toLocaleString())} — ${esc(c.user)} changed ${esc(c.department_name)} ${esc(c.field)} from ${esc(c.old_value ?? '—')} to ${esc(c.new_value ?? '—')}</li>`).join('')}</ul></div>`
            : '';

        const publishBtn = published ? '' : (state.can_publish
            ? `<button type="button" class="btn btn-sm ${confirmPublish ? 'pg-btn-confirm' : 'pg-btn-primary'}" data-act="publish" ${busy ? 'disabled' : ''}>${confirmPublish ? 'Click again to publish to your organization' : '🔒 Commit Resources &amp; Publish'}</button>`
            : `<button type="button" class="btn btn-sm pg-btn-primary" disabled>🔒 Commit Resources &amp; Publish</button>
               <div class="pg-hint mt-1">Only department heads, managers and the organization owner can publish. This strategy stays private to you.</div>`);

        el.innerHTML = `
            <h5>Commit Resources &amp; Publish</h5>
            ${header}
            ${error ? `<div class="pg-error">${esc(error)}</div>` : ''}
            ${busy ? '<div class="pg-sub">Working…</div>' : ''}
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-2">
                    <thead><tr><th>Department</th><th>Budget (${esc(state.currency)})</th><th>People (FTE)</th><th>Tools</th><th>Notes</th><th></th></tr></thead>
                    <tbody>${state.rows.map((r, i) => rowHtml(r, i, editable, published)).join('') || '<tr><td colspan="6" class="pg-hint">No resources yet.</td></tr>'}</tbody>
                </table>
            </div>
            ${!published ? `<div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                ${options ? `<select id="pg-add-dept" class="form-select form-select-sm" style="width:auto">${options}</select>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-act="add">Add row</button>` : ''}
                <button type="button" class="btn btn-sm btn-outline-secondary" data-act="suggest" ${busy || !state.ready ? 'disabled' : ''}>Regenerate suggestions</button>
            </div>` : ''}
            <div class="d-flex flex-wrap gap-2 align-items-start">
                ${editable ? `<button type="button" class="btn btn-sm btn-outline-secondary" data-act="save" ${busy ? 'disabled' : ''}>${published ? 'Save changes' : 'Save draft'}</button>` : ''}
                <div>${publishBtn}</div>
            </div>
            ${history}`;
    }

    document.addEventListener('click', e => {
        const el = card();
        if (!el || !el.contains(e.target)) return;
        const act = e.target.closest('[data-act]');
        const rm = e.target.closest('[data-remove]');
        if (rm) return removeRow(Number(rm.dataset.remove));
        if (!act || busy) return;
        if (act.dataset.act !== 'publish') confirmPublish = false;
        ({ suggest, save, publish, add: addRow })[act.dataset.act]();
    });

    new MutationObserver(mount).observe(document.body, { childList: true, subtree: true });
    mount();
})();
</script>
