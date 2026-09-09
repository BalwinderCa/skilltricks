@php
    $deptOrg = auth()->user()->organization;
    $deptIsOwner = $deptOrg && (int) $deptOrg->owner_user_id === (int) auth()->id();
@endphp

@if($deptOrg)
    {{-- Styles for the sidebar section itself: included once from the layout,
         because the menu partial is rendered twice (rail and mobile offcanvas)
         and a <dialog> cannot be. --}}
    <style>
        .tt-dept-section { margin-top: 22px; }
        .tt-dept-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px 6px;
        }
        .tt-dept-head .tt-nav-title-text {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .tt-dept-add {
            border: 0;
            background: transparent;
            color: var(--bs-secondary-color, #6b7280);
            font-size: 20px;
            line-height: 1;
            padding: 0 2px;
            cursor: pointer;
            border-radius: 6px;
        }
        .tt-dept-add:hover { color: var(--bs-primary, #36839b); }
        .tt-dept-list .side-nav-link { display: flex; align-items: center; gap: 10px; }
        .tt-dept-swatch {
            width: 12px;
            height: 12px;
            border-radius: 4px;
            flex: 0 0 12px;
        }
        .tt-dept-list .tt-nav-link-text { flex: 1 1 auto; }
        .tt-dept-count { font-size: 12px; }
        .tt-dept-empty { padding: 4px 20px; font-size: 12px; }

        /* The swatch picker in the dialog: a radio per colour, the dot itself
           is the control. */
        .st-swatches { display: flex; flex-wrap: wrap; gap: 10px; }
        .st-swatches input { position: absolute; opacity: 0; width: 0; height: 0; }
        .st-swatches label {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            cursor: pointer;
            display: block;
            box-shadow: 0 0 0 2px transparent;
        }
        .st-swatches input:checked + label { box-shadow: 0 0 0 2px var(--bs-body-bg, #fff), 0 0 0 4px currentColor; }
        .st-swatches input:focus-visible + label { outline: 2px solid var(--bs-primary, #36839b); outline-offset: 2px; }
    </style>

    @if($deptIsOwner)
        <dialog id="departmentDialog" class="st-modal" aria-labelledby="departmentDialogTitle">
            <form method="POST" action="{{ route('organization.departments.store') }}">
                @csrf

                <h2 class="st-dialog-title" id="departmentDialogTitle">{{ localize('Add department') }}</h2>

                <div class="mb-3">
                    <label class="form-label" for="departmentName">{{ localize('Name') }}<span class="text-danger">*</span></label>
                    <input class="form-control" type="text" id="departmentName" name="name" required maxlength="255"
                           placeholder="{{ localize('e.g. Design') }}">
                </div>

                <div class="mb-3">
                    <span class="form-label d-block">{{ localize('Label colour') }}</span>
                    <div class="st-swatches">
                        @foreach(\App\Models\Department::PALETTE as $index => $color)
                            <input type="radio" name="color" id="deptColor{{ $index }}" value="{{ $color }}"
                                   {{ $index === 0 ? 'checked' : '' }}>
                            <label for="deptColor{{ $index }}" style="background: {{ $color }}; color: {{ $color }}"
                                   title="{{ $color }}"></label>
                        @endforeach
                    </div>
                </div>

                <div class="st-dialog-actions">
                    <button type="button" class="st-dialog-cancel" data-department-cancel>{{ localize('Cancel') }}</button>
                    <button type="submit" class="st-dialog-ok">{{ localize('Add department') }}</button>
                </div>
            </form>
        </dialog>

        <script>
        (function () {
            var dialog = document.getElementById('departmentDialog');

            if (!dialog || typeof dialog.showModal !== 'function') return;

            // Delegated: the + exists twice, once in the rail and once in the
            // mobile offcanvas, and only one of them is on screen at a time.
            document.addEventListener('click', function (e) {
                if (e.target.closest('[data-department-add]')) {
                    e.preventDefault();
                    dialog.showModal();
                    document.getElementById('departmentName').focus();
                    return;
                }

                if (e.target.closest('[data-department-cancel]')) {
                    e.preventDefault();
                    dialog.close();
                }
            });
        })();
        </script>
    @endif
@endif
