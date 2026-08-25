{{--
    Site dialogs: stAlert() / stConfirm(), plus a [data-confirm] hook.

    Replaces the browser's alert()/confirm() boxes, which ignore the site's
    styling entirely and, on the chat page, freeze the extension-driven flows
    because they block the event loop.

    Deliberately dependency-free -- no Bootstrap JS, no jQuery -- so the same
    partial serves the backend, the frontend theme and the auth layout, which
    do not all load the same bundles.

    Colours come from the theme's own tokens with hardcoded fallbacks, so the
    dialog follows whatever --bs-primary is in scope: the backend orange on
    most pages, the teal the chat page rebinds on body. Either way it matches
    the .btn-primary sitting next to it.
--}}
<style>
    .st-dialog-backdrop {
        position: fixed;
        inset: 0;
        z-index: 20000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(22, 28, 36, .55);
        opacity: 0;
        transition: opacity .15s ease;
    }
    .st-dialog-backdrop.is-open { opacity: 1; }

    .st-dialog {
        width: 100%;
        max-width: 420px;
        background: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212B36);
        border-radius: 14px;
        box-shadow: 0 18px 60px rgba(22, 28, 36, .28);
        padding: 26px 26px 20px;
        text-align: center;
        transform: translateY(8px) scale(.98);
        transition: transform .15s ease;
    }
    .st-dialog-backdrop.is-open .st-dialog { transform: none; }

    .st-dialog-icon {
        width: 54px;
        height: 54px;
        margin: 0 auto 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        line-height: 1;
    }
    /* Tinted from --bs-primary itself rather than rgba(var(--bs-primary-rgb)).
       Both tokens are correctly paired today -- :root carries the orange
       #ff8f3b with 255,143,59, and the chat page rebinds both to the teal
       #36839b with 54,131,155 on body -- but deriving the halo from the one
       colour the button already uses means a future retune cannot desync the
       two. Plain colour first as the fallback for anything without
       color-mix(). */
    .st-dialog[data-variant="primary"] .st-dialog-icon {
        background: #eef4f6;
        background: color-mix(in srgb, var(--bs-primary, #36839b) 14%, #fff);
        color: var(--bs-primary, #36839b);
    }
    .st-dialog[data-variant="danger"] .st-dialog-icon {
        background: #fdecec;
        background: color-mix(in srgb, var(--bs-danger, #EF4444) 14%, #fff);
        color: var(--bs-danger, #EF4444);
    }
    .st-dialog[data-variant="success"] .st-dialog-icon {
        background: #e9f8ee;
        background: color-mix(in srgb, var(--bs-success, #22C55E) 14%, #fff);
        color: var(--bs-success, #22C55E);
    }

    .st-dialog-title {
        font-size: 17px;
        font-weight: 700;
        margin: 0 0 8px;
    }
    .st-dialog-message {
        font-size: 13.5px;
        line-height: 1.6;
        color: var(--bs-secondary-color, #6b7280);
        margin: 0 0 22px;
        white-space: pre-line;
        word-break: break-word;
    }

    .st-dialog-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .st-dialog-actions button {
        flex: 1;
        border: 0;
        border-radius: 9px;
        padding: 11px 16px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: filter .15s ease, background-color .15s ease;
    }
    .st-dialog-actions button:hover { filter: brightness(.94); }
    .st-dialog-actions button:focus-visible {
        outline: 2px solid var(--bs-primary, #36839b);
        outline-offset: 2px;
    }
    .st-dialog-cancel {
        background: var(--bs-secondary, #F4F6F8);
        color: var(--bs-body-color, #212B36);
    }
    .st-dialog-ok { color: #fff; }
    .st-dialog[data-variant="primary"] .st-dialog-ok { background: var(--bs-primary, #36839b); }
    .st-dialog[data-variant="danger"]  .st-dialog-ok { background: var(--bs-danger, #EF4444); }
    .st-dialog[data-variant="success"] .st-dialog-ok { background: var(--bs-success, #22C55E); }

    /* Alerts have one button, which should not stretch the full width. */
    .st-dialog-actions.is-single { justify-content: center; }
    .st-dialog-actions.is-single button { flex: 0 0 auto; min-width: 130px; }

    [data-bs-theme="dark"] .st-dialog {
        background: var(--bs-gray-900, #161C24);
        color: #e6e8ee;
    }
    [data-bs-theme="dark"] .st-dialog-cancel {
        background: #2a323b;
        color: #e6e8ee;
    }

    @media (max-width: 480px) {
        .st-dialog { padding: 22px 18px 16px; }
        .st-dialog-actions { flex-direction: column-reverse; }
        .st-dialog-actions.is-single button { width: 100%; }
    }
</style>

<script>
(function () {
    'use strict';

    var ICONS = { primary: '!', danger: '!', success: '✓' };

    var DEFAULTS = {
        alert:   { title: @json(localize('Notice')),           ok: @json(localize('OK')),      variant: 'primary' },
        confirm: { title: @json(localize('Are you sure?')),    ok: @json(localize('Confirm')), cancel: @json(localize('Cancel')), variant: 'primary' }
    };

    var open = null;

    function build(message, opts, withCancel) {
        var backdrop = document.createElement('div');
        backdrop.className = 'st-dialog-backdrop';

        var dialog = document.createElement('div');
        dialog.className = 'st-dialog';
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.dataset.variant = opts.variant;

        var icon = document.createElement('div');
        icon.className = 'st-dialog-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = ICONS[opts.variant] || ICONS.primary;

        var title = document.createElement('h2');
        title.className = 'st-dialog-title';
        title.textContent = opts.title;

        var body = document.createElement('p');
        body.className = 'st-dialog-message';
        // textContent, never innerHTML: these messages carry server errors and
        // user-entered text.
        body.textContent = message == null ? '' : String(message);

        var actions = document.createElement('div');
        actions.className = 'st-dialog-actions' + (withCancel ? '' : ' is-single');

        var cancelBtn = null;

        if (withCancel) {
            cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'st-dialog-cancel';
            cancelBtn.textContent = opts.cancel;
            actions.appendChild(cancelBtn);
        }

        var okBtn = document.createElement('button');
        okBtn.type = 'button';
        okBtn.className = 'st-dialog-ok';
        okBtn.textContent = opts.ok;
        actions.appendChild(okBtn);

        dialog.appendChild(icon);
        dialog.appendChild(title);
        dialog.appendChild(body);
        dialog.appendChild(actions);
        backdrop.appendChild(dialog);

        title.id = 'st-dialog-title-' + Date.now();
        dialog.setAttribute('aria-labelledby', title.id);

        return { backdrop: backdrop, ok: okBtn, cancel: cancelBtn };
    }

    function show(message, options, withCancel) {
        var base = withCancel ? DEFAULTS.confirm : DEFAULTS.alert;
        var opts = Object.assign({}, base, options || {});
        var parts = build(message, opts, withCancel);
        var previouslyFocused = document.activeElement;

        document.body.appendChild(parts.backdrop);
        // Next frame, so the opening transition actually runs.
        requestAnimationFrame(function () { parts.backdrop.classList.add('is-open'); });
        parts.ok.focus();

        return new Promise(function (resolve) {
            function close(result) {
                if (!open) return;
                open = null;
                document.removeEventListener('keydown', onKey, true);
                parts.backdrop.classList.remove('is-open');
                setTimeout(function () {
                    if (parts.backdrop.parentNode) parts.backdrop.parentNode.removeChild(parts.backdrop);
                }, 150);
                if (previouslyFocused && previouslyFocused.focus) previouslyFocused.focus();
                resolve(result);
            }

            function onKey(e) {
                if (e.key === 'Escape') { e.preventDefault(); close(false); }
            }

            open = close;

            parts.ok.addEventListener('click', function () { close(true); });
            if (parts.cancel) parts.cancel.addEventListener('click', function () { close(false); });
            // Clicking the backdrop dismisses, the card itself does not.
            parts.backdrop.addEventListener('click', function (e) {
                if (e.target === parts.backdrop) close(false);
            });
            document.addEventListener('keydown', onKey, true);
        });
    }

    window.stAlert = function (message, options) { return show(message, options, false); };
    window.stConfirm = function (message, options) { return show(message, options, true); };

    // ---- [data-confirm] ------------------------------------------------
    // Lets markup keep declaring its confirmation the way onclick="return
    // confirm(...)" did, without any inline JS. The element is re-fired once
    // the user agrees, guarded by a flag so we do not loop back into here.
    function optionsFor(el) {
        return {
            title: el.dataset.confirmTitle || undefined,
            ok: el.dataset.confirmOk || undefined,
            cancel: el.dataset.confirmCancel || undefined,
            variant: el.dataset.confirmVariant || 'primary'
        };
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest ? e.target.closest('[data-confirm]') : null;

        if (!el || el.tagName === 'FORM' || el.dataset.stConfirmed === '1') return;

        e.preventDefault();
        e.stopPropagation();

        stConfirm(el.dataset.confirm, optionsFor(el)).then(function (ok) {
            if (!ok) return;
            el.dataset.stConfirmed = '1';
            el.click();
            delete el.dataset.stConfirmed;
        });
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target;

        if (!form.matches || !form.matches('[data-confirm]') || form.dataset.stConfirmed === '1') return;

        e.preventDefault();
        e.stopPropagation();

        stConfirm(form.dataset.confirm, optionsFor(form)).then(function (ok) {
            if (!ok) return;
            form.dataset.stConfirmed = '1';
            // .submit() skips submit listeners, including this one.
            form.submit();
        });
    }, true);
})();
</script>
