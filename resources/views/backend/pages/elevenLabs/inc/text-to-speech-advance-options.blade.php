<div class="card mb-4">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-lg-6">
                <div class="form-input">
                    <label for="models" class="form-label">{{ localize('Model') }} <x-required-star /> </label>
                    <select class="form-select select2" id="models" name="model" required>

                        @isset($ttsModels)
                            @foreach ($ttsModels as $model)
                                <option value='{{ $model->model_id }}'>{{ $model->name }}</option>
                            @endforeach
                        @endisset

                    </select>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="form-input">
                    <label for="voice" class="form-label">{{ localize('Voice') }} <x-required-star /></label>
                    <select class="form-select select2 voiceSelect" id="voiceSelect" name="voice" required>
                        @isset($languages_voices)
                            @foreach ($languages_voices as $voice)
                                <option value='{{ $voice->voice_id }}'>{{ $voice->name }} [{{ $voice->accent }} ]
                                    [{{ $voice->description }} ] [{{ $voice->use_case }} ]</option>
                            @endforeach
                        @endisset

                    </select>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="form-input">
                    <label for="stability" class="form-label d-block">{{ localize('Stability') }}</label>
                    <div class="d-flex justify-content-between">
                        <span class="cursor-pointer me-2" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Increasing variability can make speech more expressive with output varying between re-generations.It can also lead to instabilities.') }}">
                            {{ localize('More variable') }}<i data-feather="help-circle" class="icon-14"></i></span>
                        <span class="cursor-pointer me-2" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Increasing stability will make the voice more consistent between re-generations, but it can also make it sounds a bit monotone.On longer text fragments we recommend lowering this value.') }}">
                            {{ localize('More stable') }}<i data-feather="help-circle" class="icon-14"></i></span>

                    </div>
                    <input class="range-slider__range" name="stability" id="stability" type="range"
                        value="{{ isset($defaultVoiceSetting) && $defaultVoiceSetting->stability * 100 }}"
                        value="0" min="0" max="100">
                    <span id="stability__value"
                        class="range-slider__value">{{ isset($defaultVoiceSetting) && $defaultVoiceSetting->stability * 100 }}</span>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="form-input">
                    <label for="similarity_boost"
                        class="form-label d-block">{{ localize('Clarity + Similarity Enhancement') }}
                    </label>
                    <div class="d-flex justify-content-between">
                        <span class="cursor-pointer me-2" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Low  Values are recommended if background artifacts are present in generated speech.') }}">
                            {{ localize('Low') }}<i data-feather="help-circle" class="icon-14"></i></span>
                        <span class="cursor-pointer me-2" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('High enhancement boosts overall voice clarity and target speaker similarity.Very high values can cause artifacts, so adjusting this setting to find the optimal value is encouraged.') }}">
                            {{ localize('High') }}<i data-feather="help-circle" class="icon-14"></i></span>

                    </div>
                    <input class="range-slider__range" id="similarity_boost" name="similarity_boost" type="range"
                        value="{{ isset($defaultVoiceSetting) && $defaultVoiceSetting->similarity_boost * 100 }}"
                        min="0" max="100">

                    <span id="similarity_boost__value"
                        class="range-slider__value">{{ isset($defaultVoiceSetting) && $defaultVoiceSetting->similarity_boost * 100 }}</span>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="form-input">
                    <label for="style" class="form-label d-block">{{ localize('Style Exaggeration') }}
                    </label>
                    <div class="d-flex justify-content-between">

                        <span class="cursor-pointer me-2">
                            {{ localize('None') }}</span>

                        <span class="cursor-pointer me-2" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Hight Values are recommended if the style of the speech should be exaggerated compared to the uploaded audio.Higher values can lead to more instability in the generated speech.Setting this to 0.0 will greatly increase generation speed and is the deafult setting.') }}">
                            {{ localize('Exaggerated') }}<i data-feather="help-circle" class="icon-14"></i></span>

                    </div>
                    <input class="range-slider__range" name="style" id="style" type="range"
                        value="{{ isset($defaultVoiceSetting) && $defaultVoiceSetting->style * 100 }}" min="0"
                        max="100">

                    <span id="style__value"
                        class="range-slider__value">{{ isset($defaultVoiceSetting) && $defaultVoiceSetting->style * 100 }}</span>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" name="use_speaker_boost"
                        {{ isset($defaultVoiceSetting) && $defaultVoiceSetting->use_speaker_boost == true ? 'checked' : '' }}
                        type="checkbox" id="use_speaker_boost" checked>
                    <label class="form-check-label" for="use_speaker_boost">{{ localize('Speaker Boost') }} <span
                            class="cursor-pointer me-2" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Boost the similarity of the synthesized speech and the voice at the cost of some generation speed') }}"><i
                                data-feather="help-circle" class="icon-14"></i></span></label>
                </div>
            </div>
        </div>
    </div>
</div>
