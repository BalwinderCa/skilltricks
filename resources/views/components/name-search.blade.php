<div class="card-header border-bottom-0">
    <div class="row justify-content-between g-3">
        <div class="col-auto flex-grow-1">
            <div class="tt-search-box">
                <div class="input-group">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-2">
                        <i data-feather="search"></i></span>
                    <input class="form-control rounded-start w-100" type="text" id="search" name="search"
                        placeholder="{{ localize('Search') }}..."
                        @isset($searchKey)
            value="{{ $searchKey }}"
        @endisset>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">
                <i data-feather="search" width="18"></i>
                {{ localize('Search') }}
            </button>
        </div>
    </div>
</div>
