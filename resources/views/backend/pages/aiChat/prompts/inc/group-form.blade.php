<div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">
        {{ isset($existingGroup) ? localize('Update Prompt Group') : localize('Add Prompt Group') }}</h5>
    <span class="btn btn-outline-danger rounded-circle btn-icon d-inline-flex align-items-center justify-content-center"
        data-bs-dismiss="offcanvas">
        <i data-feather="x"></i>
    </span>
</div>
<div class="offcanvas-body" data-simplebar>
    <form action="{{ isset($existingGroup) ? route('chat.updatePromptGroup') : route('chat.storePromptGroup') }}"
        method="POST" enctype="multipart/form-data">
        @csrf

        @isset($existingGroup)
            <input type="hidden" name="id" value="{{ $existingGroup->id }}">
        @endisset

        <div class="mb-3">
            <label for="name" class="form-label">{{ localize('Group Name') }}</label>
            <input type="text" id="name" name="name" class="form-control"
                placeholder="{{ localize('Type group name') }}"
                value="{{ isset($existingGroup) ? $existingGroup->name : '' }}" required>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="mb-4">
                    <button class="btn btn-primary" type="submit">
                        <i data-feather="save" class="me-1"></i>
                        {{ isset($existingGroup) ? localize('Update Group') : localize('Save Group') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
