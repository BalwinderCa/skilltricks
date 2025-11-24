<fieldset class="tt-single-fieldset">

    <div class="d-flex flex-column h-100">
        @if (isCustomer())
            @php
                $user = auth()->user();
                $latestPackage = activePackageHistory(auth()->user()->id);
            @endphp
        @if($latestPackage->new_image_balance != -1)
            <div class="card card-body py-2 mb-3">
                <div class="d-flex align-items-center flex-column used-images-percentage">
                    <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                        @include('backend.pages.templates.inc.used-images-percentage')
                    </div>
                </div>
            </div>
        @endif  
             
        @endif
        <form action="#" class="d-flex flex-column stepImageForm">
            <input class="form-control ai_blog_wizard_id" type="hidden" id="ai_blog_wizard_id" name="ai_blog_wizard_id"
                value="">

            <div class="mb-3">
                <label for="imageStepTitle" class="form-label">{{ localize('Title') }} <span
                        class="text-danger">*</span></label>
                <input class="form-control" type="text" id="imageStepTitle" name="title"
                    placeholder="{{ localize('Type your title') }}" required>
            </div>
            @if(getSetting('generate_image_option') == 'dall_e_2')
                @include('backend.pages.common.dall-e-2')
            @elseif(getSetting('generate_image_option') == 'dall_e_3')
                @include('backend.pages.common.dall-e-3')
            @elseif(getSetting('generate_image_option') == 'stable_diffusion')
                @include('backend.pages.common.stability-ai')
            @else
                @include('backend.pages.common.dall-e-2')
            @endif

            <div class="form-input"
                @if (env('DEMO_MODE') == 'On') data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-title="{{ localize('Disabled in demo') }}" @endif>
                <label for="num_of_results" class="form-label">{{ localize('Number of Results') }}
                    <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="{{ localize('Select how many variations of result you want') }}"><i
                            data-feather="help-circle" class="icon-14"></i></span>
                </label>
                <select class="form-select" id="num_of_results" name="num_of_results" required
                    @if (env('DEMO_MODE') == 'On') disabled @endif>
                    @if(getSetting('generate_image_option') == 'dall_e_3')
                        <option value="1"  selected >1</option>
                    @else
                    <option value="1" @if (getSetting('default_number_of_results') == '1') selected @endif>
                        1
                    </option>
                    <option value="2" @if (getSetting('default_number_of_results') == '2') selected @endif>
                        2
                    </option>
                    <option value="3" @if (getSetting('default_number_of_results') == '3') selected @endif>
                        3
                    </option>
                    <option value="4" @if (getSetting('default_number_of_results') == '4') selected @endif>
                        4
                    </option>
                    <option value="5" @if (getSetting('default_number_of_results') == '5') selected @endif>
                        5
                    </option>
                    @endif
                </select>
            </div>


            <div class="d-flex align-items-center flex-wrap my-4 gap-2">
                <button class="btn btn-primary btn-create-content" data-text="{{ localize('Generate Images') }}">
                    <span class="me-2 btn-create-text image-text">{{ localize('Generate Images') }}</span>
                    <!-- text preloader start -->
                    <span class="tt-text-preloader d-none">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                    <!-- text preloader end -->
                </button>

                <a href="javascript:void(0);" class="text-decoration-underline text-muted btn-next-step">
                    {{ localize('Skip this step') }}
                </a>

                <div class="flex-grow-1"></div>

                <button type="button" class="btn btn-soft-primary rounded-circle btn-icon btn-prev-step">
                    <i data-feather="arrow-left"></i>
                </button>

                <button type="button" class="btn btn-soft-primary rounded-circle btn-icon btn-next-step">
                    <i data-feather="arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</fieldset>
