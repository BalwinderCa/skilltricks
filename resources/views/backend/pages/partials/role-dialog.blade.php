{{-- Add/edit one role.

     Native <dialog> with the site's own .st-dialog-* tokens, same as the member
     dialog next door. Delete needs no markup here: it is a [data-confirm]
     submit, which inc/ui-dialog.blade.php already handles. --}}

<dialog id="roleDialog" class="st-modal" aria-labelledby="roleDialogTitle"
        data-store-url="{{ route('organization.roles.store') }}"
        data-update-url="{{ route('organization.roles.update') }}">
    <form method="POST" id="roleForm">
        @csrf
        <input type="hidden" name="role_id" id="roleId">

        <h2 class="st-dialog-title" id="roleDialogTitle">{{ localize('Add role') }}</h2>

        <div class="mb-3">
            <label class="form-label" for="roleName">{{ localize('Role') }}<span class="text-danger">*</span></label>
            <input class="form-control" type="text" id="roleName" name="name" required maxlength="255"
                   placeholder="{{ localize('e.g. Editor') }}">
        </div>

        {{-- Only on add. An existing role's permissions are the live checkboxes
             in its row, so repeating them here would give the same value two
             places to disagree. --}}
        <div class="mb-3" id="rolePermissionRow">
            <label class="form-label d-block">{{ localize('Permission') }}</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="1" name="can_read" id="roleCanRead" checked>
                <label class="form-check-label" for="roleCanRead">{{ localize('Read') }}</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="1" name="can_write" id="roleCanWrite">
                <label class="form-check-label" for="roleCanWrite">{{ localize('Write') }}</label>
            </div>
        </div>

        <div class="st-dialog-actions">
            <button type="button" class="st-dialog-cancel" data-role-cancel>{{ localize('Cancel') }}</button>
            <button type="submit" class="st-dialog-ok">{{ localize('Save') }}</button>
        </div>
    </form>
</dialog>

<script>
(function () {
    var dialog = document.getElementById('roleDialog');
    if (!dialog) return;

    var form = document.getElementById('roleForm');
    var title = document.getElementById('roleDialogTitle');
    var permissionRow = document.getElementById('rolePermissionRow');

    var STORE = dialog.dataset.storeUrl;
    var UPDATE = dialog.dataset.updateUrl;
    var ADD_TITLE = @json(localize('Add role'));
    var EDIT_TITLE = @json(localize('Edit role'));

    function open(role) {
        var editing = !!role;

        form.action = editing ? UPDATE : STORE;
        title.textContent = editing ? EDIT_TITLE : ADD_TITLE;

        document.getElementById('roleId').value = editing ? role.id : '';
        document.getElementById('roleName').value = editing ? role.name : '';

        permissionRow.hidden = editing;
        document.getElementById('roleCanRead').checked = !editing;
        document.getElementById('roleCanWrite').checked = false;

        dialog.showModal();
        document.getElementById('roleName').focus();
    }

    document.addEventListener('click', function (e) {
        var add = e.target.closest('[data-role-add]');
        if (add) { open(null); return; }

        var edit = e.target.closest('[data-role-edit]');
        if (edit) {
            open({ id: edit.dataset.id, name: edit.dataset.name });

            return;
        }

        if (e.target.closest('[data-role-cancel]')) dialog.close();
    });
})();
</script>
