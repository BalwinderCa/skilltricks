<input type="hidden" name="id" value="{{ $prompt->id }}">

<div class="modal-header">
    <h1 class="modal-title fs-5 text-capitalize" id="promptModalLabel">{{ $language->name }}</h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">
    <div class="my-3">
        <label for="symbol" class="form-label mb-2">{{ $string_key }}:</label>
        <input type="text" class="form-control value w-100" name="prompt_value"
            placeholder="{{ localize('Type localization here') }}" value="{{ $prompt->t_value }}">
    </div>
</div>

<div class="modal-footer">
    <button type="submit" class="btn btn-primary update-prompt-submit-btn"><i data-feather="save"
            class="icon-14 me-1"></i>{{ localize('Save Changes') }}</button>
</div>
