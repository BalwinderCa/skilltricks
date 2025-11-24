@isset($editApiKey)
    <form action="{{ route('admin.multiOpenAi.update') }}" method="POST" class="pb-650">
        <input type="hidden" name="id" value="{{ $editApiKey->id }}">
    @else
        <form action="{{ route('admin.multiOpenAi.store') }}" method="POST" class="pb-650">
        @endisset

        @csrf
        <!--basic information start-->
        <div class="card mb-4" id="section-1">
            <div class="card-body">


                <div class="mb-4">
                    <label for="engine" class="form-label">{{ localize('AI Engine') }} <span
                            class="text-danger ms-1">*</span></label>
                    <select class="form-control select2" name="engine" data-toggle="select2" required>
                        <option value="1"
                            {{ isset($editApiKey) ? ($editApiKey->engine == 1 ? 'selected' : '') : '' }}>
                            Open AI</option>
                        <option value="2"
                            {{ isset($editApiKey) ? ($editApiKey->engine == 2 ? 'selected' : '') : '' }}>
                            Stable Diffusion</option>

                    </select>
                </div>

                <div class="mb-4">
                    <label for="name" class="form-label">{{ localize('Api Key') }} <span
                            class="text-danger ms-1">*</span></label>
                    <input type="text" name="api_key" id="key"
                        value="{{ isset($editApiKey) ? $editApiKey->key : '' }}"
                        placeholder="****************************************" class="form-control" required>
                </div>

                <div class="mb-4">
                    <label for="status" class="form-label">{{ localize('Status') }} <span
                            class="text-danger ms-1">*</span></label>
                    <select class="form-control select2" name="status" data-toggle="select2" required>
                        <option value="1"
                            {{ isset($editApiKey) ? ($editApiKey->is_active == 1 ? 'selected' : '') : '' }}>
                            {{ localize('Active') }}</option>
                        <option value="0"
                            {{ isset($editApiKey) ? ($editApiKey->is_active == 0 ? 'selected' : '') : '' }}>
                            {{ localize('DeActive') }}</option>

                    </select>
                </div>


            </div>
        </div>
        <!--basic information end-->
        <!-- submit button -->
        <div class="row">
            <div class="col-12">
                <div class="mb-4">
                    <button class="btn btn-primary" type="submit">
                        <i data-feather="save" class="me-1"></i>
                        {{ isset($editApiKey) ? localize('Update') : localize('Save') }}
                    </button>
                </div>
            </div>
        </div>
        <!-- submit button end -->

    </form>
