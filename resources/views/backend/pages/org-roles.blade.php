@extends('backend.layouts.master')

@section('title')
    {{ localize('Roles') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="mb-1">{{ localize('Roles') }}</h4>
                    <p class="text-muted small mb-0">
                        {{ localize('The roles people in your organization can hold') }}:
                        <code>{{ $org->domain }}</code>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="mb-1">{{ localize('Roles') }}</h5>
                            <p class="text-muted small mb-0">
                                {{ localize('Permissions are recorded here but are not enforced yet.') }}
                            </p>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" data-role-add>
                            {{ localize('Add role') }}
                        </button>
                    </div>

                    <form method="POST" action="{{ route('organization.roles.destroy') }}">
                        @csrf
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ localize('Role') }}</th>
                                        <th>{{ localize('Permission') }}</th>
                                        <th class="text-end">{{ localize('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($roles as $role)
                                    <tr>
                                        <td>{{ $role->name }}</td>
                                        <td class="text-nowrap">
                                            @foreach(\App\Models\OrgRole::PERMISSIONS as $permission => $column)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="perm-{{ $role->id }}-{{ $permission }}"
                                                           data-role-permission
                                                           data-role-id="{{ $role->id }}"
                                                           data-permission="{{ $permission }}"
                                                           {{ $role->$column ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="perm-{{ $role->id }}-{{ $permission }}">
                                                        {{ localize(ucfirst($permission)) }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-role-edit
                                                    data-id="{{ $role->id }}"
                                                    data-name="{{ $role->name }}"
                >
                                                {{ localize('Edit') }}
                                            </button>
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    name="role_id" value="{{ $role->id }}"
                                                    data-confirm="{{ $role->name }} — {{ localize('a role that is still assigned to someone cannot be deleted; move those members first.') }}"
                                                    data-confirm-title="{{ localize('Delete this role?') }}"
                                                    data-confirm-ok="{{ localize('Delete it') }}"
                                                    data-confirm-variant="danger">
                                                {{ localize('Delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            {{ localize('No roles yet. Add one with the button above.') }}
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>

                </div>
            </div>

            @include('backend.pages.partials.role-dialog')

        </div>
    </section>
@endsection

@section('scripts')
<script>
(function () {
    // Same shape as the org chart's send(): the toggle is the save, so a failure
    // has to put the box back rather than leave the page claiming something the
    // server never stored.
    document.querySelectorAll('[data-role-permission]').forEach(function (box) {
        box.addEventListener('change', function () {
            var body = new FormData();
            body.append('role_id', box.dataset.roleId);
            body.append('permission', box.dataset.permission);
            body.append('value', box.checked ? 1 : 0);

            fetch("{{ route('organization.roles.permission') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: body
            }).then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
            }).catch(function () {
                box.checked = !box.checked;
            });
        });
    });
})();
</script>
@endsection
