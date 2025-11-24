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

<div class="mb-4">
    <div class="d-flex flex-column">
        <label for="language" class="form-label"><span
                class="fw-bold tt-promot-number fw-bold fs-4 me-2">1.</span>{{ localize('Select input & output language') }}<span
                class="text-danger ms-1">*</span></label>
        <select id="language" class="w-100 form-control text-capitalize country-flag-select" data-toggle="select2"
            name="lang">
            @foreach ($languages as $key => $language)
                <option value="{{ $language->name }}" @if ($currentLanguage->code == $language->code) selected @endif
                    data-flag="{{ staticAsset('backend/assets/img/flags/' . $language->flag . '.png') }}">
                    {{ $language->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>
