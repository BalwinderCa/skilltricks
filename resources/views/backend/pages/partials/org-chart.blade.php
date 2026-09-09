
<div class="card">
    <div class="card-body">

        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
            <div>
                <h5 class="mb-1">{{ localize('Organization chart') }}</h5>
                <p class="text-muted small mb-0">
                    {{ localize('Drawn from the roles and departments on the Members tab: the owner leads, and inside each department the highest role does.') }}
                </p>
                @if($isOwner)
                    <p class="text-muted small mb-0 mt-1" id="chartEditHint" hidden>
                        {{ localize('Drop a card on a person to make them report to them, in the gap between two cards to place it there, on a department band to lift it back to the top, or on the red strip to remove them.') }}
                    </p>
                @endif
            </div>

            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-zoom="out"
                        aria-label="{{ localize('Zoom out') }}">&minus;</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-zoom="in"
                        aria-label="{{ localize('Zoom in') }}">+</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-fit>
                    {{ localize('Fit') }}
                </button>

                <input type="search" class="form-control form-control-sm ms-2" id="chartSearch"
                       style="max-width: 190px;" placeholder="{{ localize('Find someone') }}"
                       aria-label="{{ localize('Find someone') }}">

                @if($isOwner)
                    <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="chartEditToggle"
                            data-label-edit="{{ localize('Edit organization') }}"
                            data-label-done="{{ localize('Done editing') }}">
                        {{ localize('Edit organization') }}
                    </button>
                @endif
            </div>
        </div>

        @if(! $chart['root'])
            <p class="text-muted mb-0">{{ localize('Nobody is in this organization yet.') }}</p>
        @else
            <div class="tt-chart-scroll">
                <div class="tt-chart" id="orgChart"
                     data-move-url="{{ route('organization.chart.move') }}"
                     data-remove-url="{{ route('organization.members.remove') }}">

                    @include('backend.pages.partials.org-chart-body')
                </div>
            </div>

            @if($isOwner)
                <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                    <div class="tt-chart-remove" data-drop-remove hidden>
                        {{ localize('Drag here to remove from the organization') }}
                    </div>
                    <span class="text-muted small" id="chartStatus" role="status" aria-live="polite"></span>
                </div>
            @endif
        @endif
    </div>
</div>

{{-- The same editor the Members tab uses: a card here opens it prefilled. --}}
@include('backend.pages.partials.member-dialog')

<style>
    .tt-chart-scroll { overflow: auto; padding: 8px 4px 16px; }
    .tt-chart {
        min-width: min-content;
        transform-origin: top left;
        /* The zoom buttons drive this; declared here so the first paint has it. */
        transform: scale(var(--tt-chart-zoom, 1));
    }

    .tt-chart-root { display: flex; justify-content: center; }

    .tt-chart-card {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #e5e7eb);
        border-radius: 12px;
        padding: 10px 14px;
        min-width: 230px;
        box-shadow: 0 1px 2px rgba(22, 28, 36, .06);
    }
    .tt-chart-who { display: flex; flex-direction: column; line-height: 1.3; }
    .tt-chart-who small { font-size: 12px; }

    .tt-chart-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        flex: 0 0 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        background: var(--bs-secondary-color, #6b7280);
    }

    /* The trunk: a stub down from the root, then one horizontal rail the
       branches hang from. Borders rather than SVG — the shape is a comb, and a
       comb is what borders draw well. */
    .tt-chart-branches {
        display: flex;
        align-items: flex-start;
        gap: 26px;
        margin-top: 26px;
        padding-top: 26px;
        border-top: 2px solid var(--bs-border-color, #e5e7eb);
        position: relative;
    }
    .tt-chart-branches::before {
        content: "";
        position: absolute;
        top: -26px;
        left: 50%;
        width: 2px;
        height: 26px;
        background: var(--bs-border-color, #e5e7eb);
    }

    .tt-chart-branch { position: relative; display: flex; flex-direction: column; gap: 14px; }
    /* Each branch's own stub up to the rail. */
    .tt-chart-branch::before {
        content: "";
        position: absolute;
        top: -26px;
        left: 50%;
        width: 2px;
        height: 26px;
        background: var(--bs-border-color, #e5e7eb);
    }

    .tt-chart-empty {
        border: 1px dashed var(--bs-border-color, #e5e7eb);
        border-radius: 12px;
        padding: 14px;
        min-width: 230px;
        text-align: center;
        font-size: 12px;
        color: var(--bs-secondary-color, #6b7280);
    }

    .tt-chart-band {
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        border-radius: 999px;
        padding: 7px 14px;
        border: 0;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .tt-chart-band > span:nth-child(2) { flex: 1 1 auto; text-align: center; }
    .tt-chart-band-count {
        background: rgba(255, 255, 255, .25);
        border-radius: 999px;
        padding: 1px 8px;
        font-size: 11px;
    }
    .tt-chart-caret { transition: transform .15s ease; }
    .tt-chart-branch.is-collapsed .tt-chart-caret { transform: rotate(-90deg); }
    /* Collapsed hides the people, never the band — it stays a drop target. */
    .tt-chart-branch.is-collapsed > *:not(.tt-chart-band) { display: none; }

    /* Search dims rather than removes: the shape of the organization stays
       readable while you pick someone out of it. */
    .tt-chart.is-searching .tt-chart-card { opacity: .25; }
    .tt-chart.is-searching .tt-chart-card.is-match { opacity: 1; outline: 2px solid var(--bs-primary, #36839b); outline-offset: 3px; }

    /* Reports hang off a rail down the left, each with a short elbow. */
    .tt-chart-reports {
        list-style: none;
        margin: 0;
        padding: 0 0 0 26px;
        position: relative;
    }
    /* The column's own top level hangs off the band, not off a card, so it
       carries neither the rail nor the elbows. */
    .tt-chart-reports.is-roots { padding-left: 0; }
    .tt-chart-reports.is-roots::before { display: none; }
    .tt-chart-reports.is-roots > li::before { display: none; }
    .tt-chart-reports li > .tt-chart-card { margin-bottom: 12px; }

    /* Insertion points. They hold their space at all times rather than
       appearing on dragstart: a gap that opens as you pick a card up shifts
       every card below it out from under the cursor, exactly when you are
       aiming. Invisible until something is over them. */
    .tt-chart-gap {
        height: 16px;
        margin: -5px 0;
        border-radius: 8px;
        border: 2px dashed transparent;
        list-style: none;
    }
    .tt-chart-gap::before { display: none !important; }
    .tt-chart-gap.is-over {
        border-color: var(--bs-primary, #36839b);
        background: color-mix(in srgb, var(--bs-primary, #36839b) 8%, transparent);
    }
    .tt-chart-reports::before {
        content: "";
        position: absolute;
        left: 12px;
        top: -14px;
        bottom: 24px;
        width: 2px;
        background: var(--bs-border-color, #e5e7eb);
    }
    .tt-chart-reports li { position: relative; margin-bottom: 12px; }
    .tt-chart-reports li:last-child { margin-bottom: 0; }
    .tt-chart-reports li::before {
        content: "";
        position: absolute;
        left: -14px;
        top: 24px;
        width: 14px;
        height: 2px;
        background: var(--bs-border-color, #e5e7eb);
    }

    /* A chosen head is stated; an inferred one says so, so nobody reads a
       tie-break as somebody's decision. */
    .tt-chart-head.is-chosen { border-color: var(--bs-primary, #36839b); }
    .tt-chart-auto {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .06em;
        opacity: .65;
        margin-left: 4px;
    }

    /* Edit mode. The dotted canvas is the mode's tell: while you can rearrange
       people, the surface looks like something you rearrange things on. */
    .tt-chart.is-editing {
        background-image: radial-gradient(var(--bs-border-color, #d7dbe0) 1.4px, transparent 1.4px);
        background-size: 18px 18px;
        background-position: -9px -9px;
        border-radius: 12px;
        padding: 18px;
    }
    .tt-chart.is-editing .tt-chart-card[data-member-id] { cursor: grab; }
    .tt-chart.is-editing .tt-chart-card[data-member-id]:active { cursor: grabbing; }
    .tt-chart-card.is-dragging { opacity: .45; }

    /* Only lit while something is actually over them, so the chart does not
       read as a grid of buttons the rest of the time. */
    .tt-chart-branch.is-over { outline: 2px dashed var(--bs-primary, #36839b); outline-offset: 6px; border-radius: 12px; }
    .tt-chart-card.is-over { outline: 2px dashed var(--bs-primary, #36839b); outline-offset: 3px; }

    /* The one destructive target, so it is drawn as one: dashed, red, and
       nowhere near the branches you drop onto by accident. */
    .tt-chart-remove {
        border: 2px dashed var(--bs-danger, #EF4444);
        color: var(--bs-danger, #EF4444);
        border-radius: 12px;
        padding: 12px 18px;
        font-size: 13px;
        font-weight: 600;
    }
    .tt-chart-remove.is-over { background: color-mix(in srgb, var(--bs-danger, #EF4444) 10%, transparent); }

    /* While a card is in the air the zone follows the viewport: the chart is
       wider and taller than the screen, and a target you have to scroll to is
       a target you cannot drop on. */
    body.tt-dragging .tt-chart-remove {
        position: fixed;
        right: 26px;
        bottom: 26px;
        z-index: 1030;
        background: var(--bs-body-bg, #fff);
        box-shadow: 0 10px 30px rgba(22, 28, 36, .2);
    }

    [data-bs-theme="dark"] .tt-chart-card { background: var(--bs-gray-900, #161C24); }
    [data-bs-theme="dark"] .tt-chart.is-editing {
        background-image: radial-gradient(#39424c 1.4px, transparent 1.4px);
    }
</style>

<script>
(function () {
    var chart = document.getElementById('orgChart');

    if (!chart) return;

    var scroller = chart.closest('.tt-chart-scroll');
    var zoom = 1;

    function setZoom(next) {
        // Bounded: past these the cards are either unreadable or pointless.
        zoom = Math.min(1.4, Math.max(0.5, next));
        chart.style.setProperty('--tt-chart-zoom', zoom);
    }

    /**
     * Shrink until the whole organization is on screen.
     *
     * A chart wider than its container is the thing that makes drag and drop
     * feel broken: the department you are aiming for is off the right-hand edge,
     * and you are dragging against an auto-scroll to reach it. Fitting first
     * means every target is simply there.
     */
    function fit() {
        if (!scroller) return;

        var natural = chart.scrollWidth / zoom;

        setZoom(Math.min(1, (scroller.clientWidth - 8) / natural));
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-chart-fit]')) {
            e.preventDefault();
            fit();

            return;
        }

        var button = e.target.closest('[data-chart-zoom]');

        if (!button) return;

        e.preventDefault();
        setZoom(zoom + (button.dataset.chartZoom === 'in' ? 0.1 : -0.1));
    });

    // On load, and again whenever the drawing is replaced or the window
    // changes shape.
    fit();
    window.addEventListener('resize', fit);
    new MutationObserver(fit).observe(chart, { childList: true });

    // ---- collapse a branch ----------------------------------------------
    // Delegated on the chart: the branches are replaced wholesale after every
    // drop, so a listener bound to each band would not survive one.
    chart.addEventListener('click', function (e) {
        var band = e.target.closest('[data-branch-toggle]');

        if (!band) return;

        var branch = band.closest('.tt-chart-branch');
        var collapsed = branch.classList.toggle('is-collapsed');
        band.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    });

    // ---- find someone ----------------------------------------------------
    var search = document.getElementById('chartSearch');

    if (search) {
        search.addEventListener('input', function () {
            var term = search.value.trim().toLowerCase();

            chart.classList.toggle('is-searching', term !== '');

            chart.querySelectorAll('.tt-chart-card').forEach(function (card) {
                card.classList.toggle('is-match', term !== '' && card.textContent.toLowerCase().indexOf(term) !== -1);
            });
        });
    }

})();
@if($isOwner)
{{-- Edit mode ships only to the owner: nobody else has an endpoint to call. --}}
(function () {
    var chart = document.getElementById('orgChart');
    var toggle = document.getElementById('chartEditToggle');

    if (!chart || !toggle) return;

    var dragging = null;
    var hint = document.getElementById('chartEditHint');
    var removeZone = document.querySelector('[data-drop-remove]');
    var status = document.getElementById('chartStatus');
    var editing = false;

    // Queried fresh every time: the chart's contents are replaced after each
    // move, so a list captured once would point at cards that are gone.
    function cards() {
        return Array.prototype.slice.call(chart.querySelectorAll('[data-member-id]'));
    }

    function applyDraggable() {
        cards().forEach(function (card) { card.draggable = editing; });
    }

    function say(message) {
        if (!status) return;

        status.textContent = message || '';
        clearTimeout(say.timer);
        say.timer = setTimeout(function () { status.textContent = ''; }, 4000);
    }

    toggle.addEventListener('click', function () {
        editing = !editing;
        chart.classList.toggle('is-editing', editing);
        toggle.textContent = editing ? toggle.dataset.labelDone : toggle.dataset.labelEdit;
        toggle.classList.toggle('btn-primary', editing);
        toggle.classList.toggle('btn-outline-primary', !editing);

        if (hint) hint.hidden = !editing;
        if (removeZone) removeZone.hidden = !editing;
        if (!editing) say('');

        // draggable is an attribute, not a style: it has to come off again or
        // the cards stay draggable after leaving edit mode.
        applyDraggable();
    });

    var scroller = chart.closest('.tt-chart-scroll');

    /**
     * Scroll the chart while a card is being dragged over its edges.
     *
     * The Fit button normally means nothing is off-screen, but zooming in past
     * it brings the edges back.
     *
     * HTML5 drag does not auto-scroll an overflow container, and the chart is
     * wider than the screen as soon as there are a few departments — so without
     * this the branches off the right-hand edge are simply unreachable, which
     * is exactly how "drag and drop does not work" looks from the outside.
     */
    function autoScroll(e) {
        if (!dragging || !scroller) return;

        var box = scroller.getBoundingClientRect();
        var edge = 90;
        var step = 22;

        if (e.clientX > box.right - edge) scroller.scrollLeft += step;
        else if (e.clientX < box.left + edge) scroller.scrollLeft -= step;

        if (e.clientY > window.innerHeight - edge) window.scrollBy(0, step);
        else if (e.clientY < edge + 60) window.scrollBy(0, -step);
    }

    // On document, not the chart: dragover only reaches elements the pointer is
    // actually over, and the edge you are heading for is often past them.
    document.addEventListener('dragover', autoScroll);

    chart.addEventListener('dragstart', function (e) {
        var card = e.target.closest('[data-member-id]');

        if (!editing || !card) return;

        dragging = card;
        card.classList.add('is-dragging');
        // Lifts the remove zone out of the flow so it cannot scroll away.
        document.body.classList.add('tt-dragging');
        e.dataTransfer.effectAllowed = 'move';
        // Firefox ignores a drag that carries no payload.
        e.dataTransfer.setData('text/plain', card.dataset.memberId);
    });

    chart.addEventListener('dragend', function () {
        if (dragging) dragging.classList.remove('is-dragging');
        dragging = null;
        document.body.classList.remove('tt-dragging');
        chart.querySelectorAll('.is-over').forEach(function (el) { el.classList.remove('is-over'); });
    });

    function targetFor(node) {
        if (!node || !node.closest) return null;

        // A gap wins over everything: it is a thin strip you have to aim at, so
        // hitting one is never accidental.
        var gap = node.closest('[data-drop-before], [data-drop-after]');

        if (gap) {
            return {
                el: gap,
                before: gap.dataset.dropBefore || '',
                after: gap.dataset.dropAfter || '',
                asHead: false
            };
        }

        // The head card wins over the branch it sits in: dropping there means
        // "and lead it", which is the more specific of the two intentions.
        var head = node.closest('[data-drop-head]');

        if (head && head !== dragging) {
            return { el: head, department: head.dataset.dropHead, asHead: true };
        }

        // Any other card: dropping on a person makes the dragged one their
        // report, which is the whole point of the nesting.
        var person = node.closest('[data-drop-manager]');

        if (person && person !== dragging && !dragging.contains(person)) {
            return { el: person, manager: person.dataset.dropManager, asHead: false };
        }

        // The band, or the column's empty space: back to the top level.
        var branch = node.closest('[data-drop-department]');

        return branch ? { el: branch, department: branch.dataset.dropDepartment, asHead: false } : null;
    }

    chart.addEventListener('dragover', function (e) {
        if (!editing || !dragging) return;

        var target = targetFor(e.target);

        if (!target) return;

        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        chart.querySelectorAll('.is-over').forEach(function (el) {
            if (el !== target.el) el.classList.remove('is-over');
        });
        target.el.classList.add('is-over');
    });

    /**
     * Post one change and redraw.
     *
     * The server sends back the chart re-derived — one move can change a head,
     * the ordering and the seniority fallback at once — so the answer replaces
     * the drawing rather than this script trying to replay the rules. No page
     * reload, and edit mode survives it.
     */
    function send(url, body, failure) {
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: body
        }).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) throw new Error(data.message || 'HTTP ' + response.status);

                return data;
            });
        }).then(function (data) {
            if (data.html) chart.innerHTML = data.html;
            applyDraggable();
            say(data.message);
        }).catch(function (error) {
            // A refusal carries its own reason — a loop, or somebody else's
            // organization — and that is more use than a generic apology.
            if (window.stAlert) stAlert(error.message || failure, { variant: 'danger' });
        });
    }

    chart.addEventListener('drop', function (e) {
        if (!editing || !dragging) return;

        var target = targetFor(e.target);

        if (!target) return;

        e.preventDefault();

        var body = new FormData();
        body.append('user_id', dragging.dataset.memberId);
        body.append('department_id', target.department || '');
        body.append('manager_id', target.manager || '');
        body.append('before_id', target.before || '');
        body.append('after_id', target.after || '');
        body.append('as_head', target.asHead ? '1' : '0');

        send(chart.dataset.moveUrl, body, @json(localize('That move could not be saved. Please try again.')));

        // The chart is about to be replaced, so dragend may never reach the node
        // it started on: clear the drag state here rather than trusting it.
        dragging = null;
        document.body.classList.remove('tt-dragging');
    });

    // ---- the remove zone ------------------------------------------------
    if (removeZone) {
        removeZone.addEventListener('dragover', function (e) {
            if (!editing || !dragging) return;

            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            removeZone.classList.add('is-over');
        });

        removeZone.addEventListener('dragleave', function () {
            removeZone.classList.remove('is-over');
        });

        removeZone.addEventListener('drop', function (e) {
            if (!editing || !dragging) return;

            e.preventDefault();
            removeZone.classList.remove('is-over');

            var id = dragging.dataset.memberId;
            var name = (dragging.querySelector('strong') || {}).textContent || '';

            // Destructive, and a drag is easy to mean loosely — same confirm the
            // roster's Delete uses.
            stConfirm(name + ' — ' + @json(localize('they keep their account and can be added again, but they lose access to this organization straight away.')), {
                title: @json(localize('Remove this member?')),
                ok: @json(localize('Remove them')),
                variant: 'danger'
            }).then(function (ok) {
                if (!ok) return;

                var body = new FormData();
                body.append('user_id', id);

                send(chart.dataset.removeUrl, body, @json(localize('That removal could not be saved. Please try again.')));
            });
        });
    }
})();
@endif
</script>
