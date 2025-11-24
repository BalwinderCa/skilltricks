<div class="modal-header">
    <h1 class="modal-title fs-5" id="saveToFolderLabel">{{ localize('New Package') }}</h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body pb-5">

    <div class="d-flex justify-content-center pb-3 mt-3">
        <form class="copy-package-form">
            @csrf
            <button type="submit"
                class="btn btn-success px-4 ms-2 pkg-submit-btn">{{ localize('Create New Package') }}</button>
        </form>
    </div>
    <div class="text-center text-muted mb-3">{{ localize('Or') }}</div>
    <form class="copy-package-form">
        @csrf
        <div class="form-input d-flex justify-content-center">
            <select class="form-select select2 w-50" id="package_id" name="package_id" required>
                <option value="">{{ localize('Copy From Existing') }}
                </option>

                <optgroup label="{{ localize('Monthly Packages') }}">
                    @foreach ($packages as $package)
                        @if ($package->package_type == 'monthly' || $package->package_type == 'starter')
                            <option value="{{ $package->id }}">
                                {!! html_entity_decode($package->title) !!}</option>
                        @endif
                    @endforeach
                </optgroup>

                <optgroup label="{{ localize('Yearly Packages') }}">
                    @foreach ($packages as $package)
                        @if ($package->package_type == 'yearly')
                            <option value="{{ $package->id }}">
                                {!! html_entity_decode($package->title) !!}</option>
                        @endif
                    @endforeach
                </optgroup>

                <optgroup label="{{ localize('Lifetime Packages') }}">
                    @foreach ($packages as $package)
                        @if ($package->package_type == 'lifetime')
                            <option value="{{ $package->id }}">
                                {!! html_entity_decode($package->title) !!}</option>
                        @endif
                    @endforeach
                </optgroup>

                <optgroup label="{{ localize('Prepaid Packages') }}">
                    @foreach ($packages as $package)
                        @if ($package->package_type == 'prepaid')
                            <option value="{{ $package->id }}">
                                {!! html_entity_decode($package->title) !!}</option>
                        @endif
                    @endforeach
                </optgroup>

            </select>
            <div class="d-flex justify-content-center ms-2">
                <button type="submit" class="btn btn-primary pkg-submit-btn">{{ localize('Copy') }}</button>
            </div>
        </div>
    </form>
</div>
