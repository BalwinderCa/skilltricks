@php
    // The six-rung role ladder, from the service that also validates it — the
    // CSV importer and the dialog's Role select read the same list.
    $rankLabels = \App\Services\OrganizationService::RANK_LABELS;
@endphp

<div class="card">
    <div class="card-body">

        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
            <h5 class="mb-0">{{ localize('Members') }}</h5>

            @if($isOwner)
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" data-member-add>
                        {{ localize('Add member') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bulk-open>
                        {{ localize('Bulk add team') }}
                    </button>
                </div>
            @endif
        </div>

        @if($activeDepartment)
            <div class="alert alert-light d-flex flex-wrap align-items-center gap-2 py-2">
                <span class="tt-dept-swatch" style="background: {{ $activeDepartment->color }}"></span>
                <span>{{ localize('Showing') }} <strong>{{ $activeDepartment->name }}</strong></span>

                @if($isOwner)
                    {{-- The head is drawn at the top of this department's branch
                         on the chart. Left unset, the highest role leads. --}}
                    <form method="POST" action="{{ route('organization.departments.head') }}"
                          class="d-flex align-items-center gap-2 mb-0 ms-3">
                        @csrf
                        <input type="hidden" name="department_id" value="{{ $activeDepartment->id }}">
                        <label class="small text-muted mb-0" for="departmentHead">{{ localize('Head') }}</label>
                        <select class="form-control form-control-sm" name="head_user_id" id="departmentHead"
                                style="min-width: 190px;">
                            <option value="">{{ localize('Highest role (automatic)') }}</option>
                            @foreach($departmentMembers as $candidate)
                                <option value="{{ $candidate->id }}"
                                    {{ (int) $activeDepartment->head_user_id === (int) $candidate->id ? 'selected' : '' }}>
                                    {{ $candidate->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary">{{ localize('Save') }}</button>
                    </form>
                @endif

                <a class="ms-auto small" href="{{ route('organization.index') }}">{{ localize('Show everyone') }}</a>
                @if($isOwner)
                    <form method="POST" action="{{ route('organization.departments.destroy') }}" class="mb-0">
                        @csrf
                        <input type="hidden" name="department_id" value="{{ $activeDepartment->id }}">
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"
                                data-confirm="{{ localize('The department is deleted. Nobody loses their account — they simply stop having a department.') }}"
                                data-confirm-title="{{ localize('Delete this department?') }}"
                                data-confirm-ok="{{ localize('Delete it') }}"
                                data-confirm-variant="danger">
                            {{ localize('Delete department') }}
                        </button>
                    </form>
                @endif
            </div>
        @endif

        <p class="text-muted small">
            @if($isOwner)
                {{ localize('Roles are set here and nowhere else. The highest role sets the active strategic context for everyone.') }}
            @else
                {{ localize('Roles are set by the owner of this organization. The highest role sets the active strategic context for everyone.') }}
            @endif
        </p>

        @if($isOwner)
        {{-- One form serves both deletes. A row's Delete button carries
             name="user_id", the checkboxes carry user_ids[]; the server prefers
             the single id, so a stale tick cannot widen a row delete. --}}
        <form method="POST" action="{{ route('organization.members.remove') }}" id="removeForm">
        @csrf
        {{-- No display utility on this wrapper: Bootstrap's .d-flex is !important
             and is declared after [hidden] in the sheet, so it would win the tie
             and the bar would never hide. The flex row is the child instead. --}}
        <div class="mb-2" id="bulkBar" hidden>
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        data-confirm="{{ localize('Everyone ticked keeps their account and can be added again, but they lose access to this organization straight away.') }}"
                        data-confirm-title="{{ localize('Remove the selected members?') }}"
                        data-confirm-ok="{{ localize('Remove them') }}"
                        data-confirm-variant="danger">
                    {{ localize('Remove selected') }}
                </button>

                {{-- formaction, so both actions read the same checkboxes without
                     a second form wrapping the same table. --}}
                <button type="submit" class="btn btn-sm btn-outline-primary"
                        formaction="{{ route('organization.members.invite') }}"
                        data-confirm="{{ localize('Each one is emailed a fresh temporary password and is asked to choose their own the first time they sign in. Any invitation they already had stops working.') }}"
                        data-confirm-title="{{ localize('Send an invitation to the selected members?') }}"
                        data-confirm-ok="{{ localize('Send invitations') }}">
                    {{ localize('Send invitation') }}
                </button>

                <span class="text-muted small" id="bulkCount"></span>
            </div>
        </div>
        @endif

        <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    @if($isOwner)
                        <th style="width: 1%;">
                            <input type="checkbox" class="form-check-input" id="checkAll"
                                   aria-label="{{ localize('Select all members') }}">
                        </th>
                    @endif
                    <th>{{ localize('Name') }}</th>
                    <th>{{ localize('Email') }}</th>
                    <th>{{ localize('Role') }}</th>
                    <th>{{ localize('Department') }}</th>
                    @if($isOwner)
                        <th class="text-end">{{ localize('Actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @forelse($orgMembers as $member)
                @php $isOrgOwner = $org && (int) $org->owner_user_id === (int) $member->id; @endphp
                <tr>
                    @if($isOwner)
                        <td>
                            @unless($isOrgOwner)
                                <input type="checkbox" class="form-check-input member-check"
                                       name="user_ids[]" value="{{ $member->id }}"
                                       aria-label="{{ localize('Select') }} {{ $member->name }}">
                            @endunless
                        </td>
                    @endif
                    <td>
                        {{ $member->name }}
                        @if((int) $member->id === (int) $user->id)
                            <span class="badge bg-light text-muted">{{ localize('you') }}</span>
                        @endif
                        @if($isOrgOwner)
                            <span class="badge bg-light text-muted">{{ localize('owner') }}</span>
                        @endif
                    </td>
                    <td>{{ $member->email }}</td>
                    <td>
                        {{ $member->hierarchy_rank ? localize($rankLabels[(int) $member->hierarchy_rank] ?? '—') : localize('Role not set') }}
                    </td>
                    <td>
                        @if($member->department)
                            <span class="tt-dept-swatch d-inline-block align-middle me-1"
                                  style="background: {{ $member->department->color }}"></span>
                            {{ $member->department->name }}
                        @else
                            —
                        @endif
                    </td>
                    @if($isOwner)
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-member-edit
                                    data-id="{{ $member->id }}"
                                    data-name="{{ $member->name }}"
                                    data-email="{{ $member->email }}"
                                    data-rank="{{ $member->hierarchy_rank }}"
                                    data-department="{{ $member->department_id }}">
                                {{ localize('Edit') }}
                            </button>

                            {{-- The owner is the one row nobody can evict: the organization
                                 would be left unowned, and attachUser() only ever claims an
                                 unowned org for whoever registers next. --}}
                            @unless($isOrgOwner)
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        name="user_id" value="{{ $member->id }}"
                                        data-confirm="{{ $member->name }} — {{ localize('they keep their account and can be added again, but they lose access to this organization straight away.') }}"
                                        data-confirm-title="{{ localize('Remove this member?') }}"
                                        data-confirm-ok="{{ localize('Remove them') }}"
                                        data-confirm-variant="danger">
                                    {{ localize('Delete') }}
                                </button>
                            @endunless
                        </td>
                    @endif
                </tr>
            @empty
                {{-- An organization of one renders no rows at all, because the
                     owner is kept off the roster. Without this the page looks
                     broken rather than empty. --}}
                <tr>
                    <td colspan="{{ $isOwner ? 6 : 4 }}" class="text-center text-muted py-4">
                        @if($activeDepartment)
                            {{ localize('Nobody is in this department yet.') }}
                        @elseif($isOwner)
                            {{ localize('It is just you so far. Add your team with the buttons above, or upload a CSV.') }}
                        @else
                            {{ localize('Nobody else is in this organization yet.') }}
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        </div>

        {{-- The view is named because the app configures no default and
             Laravel's is Tailwind's, whose classes this theme does not ship. It
             carries its own "Showing x to y of z" line and renders nothing at
             all while everyone fits on one page. --}}
        {{ $orgMembers->appends(request()->except('page'))->links('pagination::bootstrap-5') }}

        @if($isOwner)
        </form>
        @endif
    </div>
</div>

{{-- The add/edit and bulk dialogs; the chart tab includes the same file so a
     card there opens the very same editor. --}}
@include('backend.pages.partials.member-dialog')
