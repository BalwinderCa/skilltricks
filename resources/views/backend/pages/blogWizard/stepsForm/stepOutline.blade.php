<fieldset class="tt-single-fieldset">

    <div class="d-flex flex-column h-100">
        <form action="#" class="d-flex flex-column stepOutlineForm">
            <input class="form-control ai_blog_wizard_id" type="hidden" id="ai_blog_wizard_id" name="ai_blog_wizard_id"
                value="">

            <div class="mb-3">
                <label for="outlineStepTitle" class="form-label">{{ localize('Title') }} <span
                        class="text-danger">*</span></label>
                <input class="form-control" type="text" id="outlineStepTitle" name="title"
                    placeholder="{{ localize('Type your title') }}" required>
            </div>

            <div class="mb-3">
                <label for="outlineStepKeywords" class="form-label">{{ localize('Keywords') }}</label>
                <input class="form-control" type="text" id="outlineStepKeywords" name="keywords"
                    placeholder="{{ localize('Type your keywords') }}">
            </div>

            <div class="form-input mb-3">
                <label for="tone" class="form-label">{{ localize('Choose a Tone') }}
                    <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="{{ localize('Choose the tone of the result text as you need') }}"><i
                            data-feather="help-circle" class="icon-14"></i></span>
                </label>
                <select class="form-select" id="tone" name="tone" required>
                    <option value="{{ localize('Friendly') }}" @if (getSetting('default_tone') == 'Friendly') selected @endif>
                        {{ localize('Friendly') }}</option>
                    <option value="{{ localize('Luxury') }}" @if (getSetting('default_tone') == 'Luxury') selected @endif>
                        {{ localize('Luxury') }}
                    </option>
                    <option value="{{ localize('Relaxed') }}" @if (getSetting('default_tone') == 'Relaxed') selected @endif>
                        {{ localize('Relaxed') }}
                    </option>
                    <option value="{{ localize('Professional') }}" @if (getSetting('default_tone') == 'Professional') selected @endif>
                        {{ localize('Professional') }}</option>
                    <option value="{{ localize('Casual') }}" @if (getSetting('default_tone') == 'Casual') selected @endif>
                        {{ localize('Casual') }}
                    </option>
                    <option value="{{ localize('Excited') }}" @if (getSetting('default_tone') == 'Excited') selected @endif>
                        {{ localize('Excited') }}
                    </option>
                    <option value="{{ localize('Bold') }}" @if (getSetting('default_tone') == 'Bold') selected @endif>
                        {{ localize('Bold') }}
                    </option>
                    <option value="{{ localize('Masculine') }}" @if (getSetting('default_tone') == 'Masculine') selected @endif>
                        {{ localize('Masculine') }}</option>
                    <option value="{{ localize('Dramatic') }}" @if (getSetting('default_tone') == 'Dramatic') selected @endif>
                        {{ localize('Dramatic') }}</option>
                </select>
            </div>

            @php
                if (Session::has('locale')) {
                    $locale = Session::get('locale', Config::get('app.locale'));
                } else {
                    $locale = env('DEFAULT_LANGUAGE');
                }
                $currentLanguage = \App\Models\Language::where('code', $locale)->first();
                
                if (is_null($currentLanguage)) {
                    $currentLanguage = \App\Models\Language::where('code', 'en')->first();
                }
            @endphp

            <div>
                <div class="d-flex flex-column">
                    <label for="language" class="form-label">{{ localize('Choose a language') }}</label>
                    <select id="language" class="w-100 form-select text-capitalize" name="lang">
                        @foreach ($languages as $key => $language)
                            <option value="{{ $language->name }}" @if ($currentLanguage->code == $language->code) selected @endif
                                data-flag="{{ staticAsset('backend/assets/img/flags/' . $language->flag . '.png') }}">
                                {{ $language->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center flex-wrap my-4 gap-2">
                <div class="form-input">
                    <select class="form-select" id="num_of_results" name="num_of_results" required>
                        <option value="1">
                            1
                        </option>
                        <option value="2">
                            2
                        </option>
                        <option value="3" selected>
                            3
                        </option>
                        <option value="4">
                            4
                        </option>
                        <option value="5">
                            5
                        </option>
                    </select>
                </div>

                <button class="btn btn-primary btn-create-content" data-text="{{ localize('Generate Outlines') }}">
                    <span class="me-2 btn-create-text outline-text">{{ localize('Generate Outlines') }}</span>
                    <!-- text preloader start -->
                    <span class="tt-text-preloader d-none">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                    <!-- text preloader end -->
                </button>
                <div class="flex-grow-1"></div>

                <button type="button" class="btn btn-soft-primary rounded-circle btn-icon btn-prev-step">
                    <i data-feather="arrow-left"></i>
                </button>

                <button type="button" class="btn btn-soft-primary rounded-circle btn-icon" disabled>
                    <i data-feather="arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</fieldset>
