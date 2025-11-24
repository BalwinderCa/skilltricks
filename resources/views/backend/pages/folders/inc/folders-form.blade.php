<form action="{{ route('folders.store') }}" class="pb-650" method="POST">
    @csrf
    <!-- tag info start-->
    <div class="card mb-4" id="section-2">
        <div class="card-body">
            <h5 class="mb-4">{{ localize('Add New Folder') }}</h5>

            <div class="mb-4">
                <label for="name" class="form-label">{{ localize('Folder Name') }} <x-required-star/></label>
                <input class="form-control" type="text" id="name" name="name"
                    placeholder="{{ localize('Type folder name') }}" value="{{ old('name') }}" required>
                <x-error :name="'name'"/>
            </div>
        </div>
    </div>
    <!-- tag info end-->

    <div class="row">
        <div class="col-12">
            <div class="mb-4">
                <button class="btn btn-primary" type="submit">
                    <i data-feather="save" class="me-1"></i> {{ localize('Save Folder') }}
                </button>
            </div>
        </div>
    </div>
</form>
