<form action="{{ route('admin.plagiarism.settings.store') }}" class="pb-650" method="POST">
    @csrf
    <!-- tag info start-->
    <div class="card mb-4" id="section-2">
        <div class="card-body">
            <h5 class="mb-4">{{ localize('Add New Plagiarism Info') }}</h5>

            <div class="mb-4">
                <label for="key" class="form-label">{{ localize('Plagiarism key') }} <x-required-star/></label>
                <span class="float-end text-info checkMaxToken cursor-pointer" id="checkMaxToken">
                 <a href="https://dev.gowinston.ai/user/api-tokens" target="_blank" rel="noopener noreferrer">{{ localize('Collect API') }}</a>  </span>
                <input class="form-control" type="text" id="key" name="key"
                    placeholder="{{ localize('Type folder key') }}" value="{{ old('key') }}" required>
                <x-error :name="'key'"/>
            </div>
        </div>
    </div>
    <!-- tag info end-->

    <div class="row">
        <div class="col-12">
            <div class="mb-4">
                <button class="btn btn-primary" type="submit">
                    <i data-feather="save" class="me-1"></i> {{ localize('Save Plagiarism') }}
                </button>
            </div>
        </div>
    </div>
</form>
