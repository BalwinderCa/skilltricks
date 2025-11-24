<fieldset class="tt-single-fieldset h-100">
    <form action="#" class="d-flex flex-column stepKeywordForm">
        <input class="form-control ai_blog_wizard_id" type="hidden" id="ai_blog_wizard_id" name="ai_blog_wizard_id"
            value="">
        <div class="mb-3">
            <label for="topicStepKeyword" class="form-label">{{ localize('Topic') }} <span
                    class="text-danger">*</span></label>
            <input class="form-control" type="text" id="topicStepKeyword" name="topic" value="{{isset($aiBlogWizard) ? $aiBlogWizard->aiBlogWizardKeyword->topic : old('name')}}"
                placeholder="{{ localize('Type your topic') }}" required>
        </div>

        <div class="mb-3">
            <label for="number_of_results" class="form-label">{{ localize('Number of Keywords') }} <span
                    class="text-danger">*</span></label>
            <input class="form-control" type="number" id="number_of_results" name="number_of_results"
                placeholder="{{ localize('Type the number of keywords you want to generate') }}" required min="1"
                value="15" max="50" required>
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

        <div class="mb-3">
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


        <div class="d-flex align-items-center  mb-4 gap-2">
            <button class="btn btn-primary btn-create-content" data-text="{{ localize('Generate Keywords') }}">
                <span class="me-2 btn-create-text keyword-text">{{ localize('Generate Keywords') }}</span>
                <!-- text preloader start -->
                <span class="tt-text-preloader d-none">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <!-- text preloader end -->
            </button>
        </div>
    </form>

    <div class="position-relative d-flex align-items-center justify-content-center py-4">
        <span class="divider-bar"></span>
        <h6 class="position-absolute text-center divider-text bg-light-subtle px-3 mb-0">{{ localize('Or') }}</h6>


    </div>
    <div class="text-center mb-4">
        {{ localize('Already have keywords? Enter your keywords and skip this step.') }}
    </div>

    <form action="#" class="d-flex flex-column">

        <div class="mb-3">
            <label for="keywordsStep" class="form-label">{{ localize('Keywords') }}</label>
            <input class="form-control" type="text" id="keywordsStep" name="keywords"
                placeholder="{{ localize('Type your keywords') }}">
        </div>

        <div class="d-flex align-items-center mb-4">
            <button type="button" class="btn btn-primary px-5 btn-next-step">{{ localize('Next Step') }}</button>
        </div>
    </form>
</fieldset>
