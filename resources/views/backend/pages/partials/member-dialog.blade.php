@php
    // Its own copy now that two tabs include this file — the Members roster is
    // no longer the only place it is rendered from.
    $rankLabels = \App\Services\OrganizationService::RANK_LABELS;
@endphp

@if($isOwner)
    {{-- Dialogs.

         Native <dialog>s: showModal() brings the backdrop, the focus trap and
         Escape-to-close with no library at all, and the site's own dialog tokens
         (.st-dialog-*, loaded by inc/ui-dialog.blade.php in the layout) dress
         them so they match stConfirm() standing next to them. Delete needs no
         markup here — it is a [data-confirm] form, which that same partial
         already handles. --}}

    <dialog id="memberDialog" class="st-modal" aria-labelledby="memberDialogTitle"
            data-store-url="{{ route('organization.members.store') }}"
            data-update-url="{{ route('organization.members.update') }}">
        <form method="POST" id="memberForm">
            @csrf
            <input type="hidden" name="user_id" id="memberId">

            <h2 class="st-dialog-title" id="memberDialogTitle">{{ localize('Add member') }}</h2>

            <div class="mb-3">
                <label class="form-label" for="memberName">{{ localize('Name') }}<span class="text-danger">*</span></label>
                <input class="form-control" type="text" id="memberName" name="name" required maxlength="255">
            </div>

            <div class="mb-3">
                <label class="form-label" for="memberEmail">{{ localize('Email') }}<span class="text-danger">*</span></label>
                <input class="form-control" type="email" id="memberEmail" name="email" required maxlength="255">
                <small class="text-muted" id="memberEmailNote"></small>
            </div>

            <div class="mb-3">
                <label class="form-label" for="memberRank">{{ localize('Role') }}<span class="text-danger">*</span></label>
                <select class="form-control" id="memberRank" name="rank" required>
                    @foreach($rankLabels as $value => $label)
                        <option value="{{ $value }}">{{ localize($label) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label" for="memberDepartment">{{ localize('Department') }}</label>
                <select class="form-control" id="memberDepartment" name="department_id">
                    <option value="">{{ localize('None') }}</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
                @if($departments->isEmpty())
                    <small class="text-muted">{{ localize('Add one with the + beside Department in the sidebar.') }}</small>
                @endif
            </div>

            {{-- Only meaningful when adding: an existing member's way in is the
                 roster's Send invitation button. --}}
            <div class="form-check mb-3" id="memberInviteRow">
                <input class="form-check-input" type="checkbox" value="1" name="send_invite" id="sendInvite" checked>
                <label class="form-check-label" for="sendInvite">
                    {{ localize('Email them an invitation with a temporary password') }}
                </label>
            </div>

            <div class="st-dialog-actions">
                <button type="button" class="st-dialog-cancel" data-member-cancel>{{ localize('Cancel') }}</button>
                <button type="submit" class="st-dialog-ok">{{ localize('Save') }}</button>
            </div>
        </form>
    </dialog>

    <dialog id="bulkDialog" class="st-modal" aria-labelledby="bulkDialogTitle">
        <form method="POST" action="{{ route('organization.members.import') }}" enctype="multipart/form-data">
            @csrf

            <h2 class="st-dialog-title" id="bulkDialogTitle">{{ localize('Bulk add team') }}</h2>

            <p class="text-muted small mb-2">
                {{ localize('Upload a CSV with one person per row and these four columns') }}:
                <code>name,email,role,department</code>.
                {{ localize('Role must be one of') }}
                {{ implode(', ', array_map('localize', array_values($rankLabels))) }}.
                {{ localize('Anything else counts as Individual Contributor. A department name that does not exist yet is created, and it may be left blank.') }}
            </p>

            <p class="text-muted small mb-2">
                {{ localize('Everyone in the file joins this organization and is emailed a link to set their password. Rows with no name or an invalid email, and addresses that already have an account, are skipped.') }}
            </p>

            <p class="small mb-3">
                <a href="{{ route('organization.sample-csv') }}">{{ localize('Download a sample CSV') }}</a>
            </p>

            <div class="mb-3">
                <label class="form-label" for="membersFile">{{ localize('CSV file') }}<span class="text-danger">*</span></label>
                <input type="file" name="members" id="membersFile" accept=".csv,text/csv" class="form-control" required>
                @error('members')
                    <p class="text-danger small mt-2 mb-0">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" name="send_invites" id="sendInvites" checked>
                <label class="form-check-label" for="sendInvites">
                    {{ localize('Email everyone an invitation with a temporary password') }}
                </label>
                <div class="text-muted small">
                    {{ localize('They sign in with it and are asked to choose their own password. Leave it off to add the rows quietly — you can invite them later from the roster.') }}
                </div>
            </div>

            <div class="st-dialog-actions">
                <button type="button" class="st-dialog-cancel" data-bulk-cancel>{{ localize('Cancel') }}</button>
                <button type="submit" class="st-dialog-ok">{{ localize('Upload') }}</button>
            </div>
        </form>
    </dialog>

    <script>
    (function () {
        var dialog = document.getElementById('memberDialog');
        var bulk = document.getElementById('bulkDialog');

        // No <dialog> support: every button here would open nothing, so say why
        // rather than dying silently on click.
        if (!dialog || typeof dialog.showModal !== 'function') {
            document.addEventListener('click', function (e) {
                if (e.target.closest('[data-member-add], [data-member-edit], [data-bulk-open]')) {
                    if (window.stAlert) stAlert(@json(localize('This browser cannot open dialogs. Please update it to add team members.')));
                }
            });
            return;
        }

        var form = document.getElementById('memberForm');
        var title = document.getElementById('memberDialogTitle');
        var emailInput = document.getElementById('memberEmail');
        var emailNote = document.getElementById('memberEmailNote');

        // URLs come off the element rather than @json(route(...)): the same
        // string then appears in the HTML unescaped, which is what the page
        // reads like to anything inspecting it.
        var STORE = dialog.dataset.storeUrl;
        var UPDATE = dialog.dataset.updateUrl;
        var ADD_TITLE = @json(localize('Add member'));
        var EDIT_TITLE = @json(localize('Edit member'));
        var EMAIL_NOTE = @json(localize('Changing this address means it has to be verified again before they can sign in.'));

        function open(member) {
            var editing = !!member;

            form.action = editing ? UPDATE : STORE;
            title.textContent = editing ? EDIT_TITLE : ADD_TITLE;

            document.getElementById('memberId').value = editing ? member.id : '';
            document.getElementById('memberName').value = editing ? member.name : '';
            document.getElementById('memberDepartment').value = (editing && member.department) ? member.department : '';
            document.getElementById('memberRank').value = (editing && member.rank) ? member.rank : '10';

            emailInput.value = editing ? member.email : '';
            emailNote.textContent = editing ? EMAIL_NOTE : '';

            // Hidden while editing: storeMember() reads it, updateMember() does not.
            var inviteRow = document.getElementById('memberInviteRow');
            inviteRow.hidden = editing;
            document.getElementById('sendInvite').checked = !editing;

            dialog.showModal();
            document.getElementById('memberName').focus();
        }

        document.addEventListener('click', function (e) {
            var add = e.target.closest('[data-member-add]');
            if (add) { e.preventDefault(); open(null); return; }

            var edit = e.target.closest('[data-member-edit]');
            if (edit) {
                e.preventDefault();
                open({
                    id: edit.dataset.id,
                    name: edit.dataset.name,
                    email: edit.dataset.email,
                    rank: edit.dataset.rank,
                    department: edit.dataset.department
                });
                return;
            }

            if (e.target.closest('[data-bulk-open]')) { e.preventDefault(); bulk.showModal(); return; }
            if (e.target.closest('[data-bulk-cancel]')) { e.preventDefault(); bulk.close(); return; }
            if (e.target.closest('[data-member-cancel]')) { e.preventDefault(); dialog.close(); }
        });

        // A rejected upload redirects back with its error rendered inside the
        // dialog, which would otherwise be closed and the message unreachable.
        @error('members')
            bulk.showModal();
        @enderror
    })();

    (function () {
        // ---- checkbox selection --------------------------------------------
        var checkAll = document.getElementById('checkAll');
        var bulkBar = document.getElementById('bulkBar');
        var bulkCount = document.getElementById('bulkCount');
        var boxes = Array.prototype.slice.call(document.querySelectorAll('.member-check'));
        var SELECTED = @json(localize('selected'));

        function sync() {
            var n = boxes.filter(function (b) { return b.checked; }).length;
            // Hidden rather than disabled: until something is ticked there is
            // nothing for it to act on, so it has no reason to be on screen.
            if (bulkBar) bulkBar.hidden = n === 0;
            // Per page: the checkboxes only exist for the rows on screen.
            if (bulkCount) bulkCount.textContent = n ? n + ' ' + SELECTED : '';
            if (checkAll) {
                checkAll.checked = n > 0 && n === boxes.length;
                checkAll.indeterminate = n > 0 && n < boxes.length;
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                boxes.forEach(function (b) { b.checked = checkAll.checked; });
                sync();
            });
        }

        boxes.forEach(function (b) { b.addEventListener('change', sync); });
        sync();
    })();
    </script>
@endif
