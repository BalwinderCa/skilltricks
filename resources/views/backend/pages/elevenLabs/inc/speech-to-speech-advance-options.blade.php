<div class="row justify-content-between align-items-center pb-3">
    <div class="col-auto flex-grow-1">
        <div class="tt-promt-fild">
            <div class="d-flex align-items-center tt-advance-options cursor-pointer">
                <label for="tt-advance-options"
                    class="form-label cursor-pointer mb-0 btn btn-outline-secondary btn-sm rounded-pill"><span
                        class="fw-bold tt-promot-number fw-bold me-1"><span class="me-1 cursor-pointer"
                            data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Choose style, mood, resolution, number of results') }}"><i
                                data-feather="help-circle"
                                class="icon-14"></i></span></span>{{ localize('Advance Options') }}
                    <span><i data-feather="plus" class="icon-16 text-primary ms-2"></i></span></label>
            </div>
        </div>
    </div>
    <div class="col-auto">
        @if (isCustomer())
            @php
                $user = auth()->user();
                $latestPackage = activePackageHistory(auth()->user()->id);
            @endphp
            @if ($latestPackage->new_image_balance != -1)
                <div class="d-flex align-items-center flex-column used-words-percentage">
                    @include('backend.pages.templates.inc.used-images-percentage')
                </div>
            @endif
        @endif
    </div>
</div>

<!-- advance options -->
<div class="card mb-3 tt-advance-options-wrapper" id="tt-advance-options">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-lg-6">
                <div class="form-input">
                    <label for="sts_models" class="form-label">{{ localize('Model') }}
                        <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Choose a model to use') }}"><i data-feather="help-circle"
                                class="icon-14"></i></span>
                    </label>
                    <select class="form-select select2" id="sts_models" name="model">
                        <option value=""> {{localize('Select Model')}}</option>
                        @isset($stsModels)
                            
                        @foreach ($stsModels as $model)
                            <option value='{{ $model->model_id }}'>{{ $model->name }}</option>
                        @endforeach
                        @endisset

                    </select>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="form-input">
                    <label for="sts_voice" class="form-label">{{ localize('Voice') }}

                    </label>
                    <select class="form-select select2 voiceSelect" id="sts_voice" name="voice">
                       

                    </select>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="form-input">
                    <label for="sts_stability" class="form-label d-block">{{ localize('Stability') }}</label>
                    <input class="range-slider__range" id="sts_stability" type="range" name="stability"
                        value="{{isset($defaultVoiceSetting) && $defaultVoiceSetting->stability *100}}" min="0" max="100">
                    <span id="sts_stability__value"
                        class="range-slider__value">{{isset($defaultVoiceSetting) && $defaultVoiceSetting->stability *100}}</span>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="form-input">
                    <label for="sts_similarity_boost"
                        class="form-label d-block">{{ localize('Clarity + Similarity Enhancement') }}
                    </label>
                    <input class="range-slider__range" id="sts_similarity_boost" name="similarity_boost" type="range"
                        value="{{isset($defaultVoiceSetting) && $defaultVoiceSetting->similarity_boost *100}}" min="0" max="100">

                    <span id="sts_similarity_boost__value"
                        class="range-slider__value">{{isset($defaultVoiceSetting) && $defaultVoiceSetting->similarity_boost *100 }}</span>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="form-input">
                    <label for="sts_style" class="form-label d-block">{{ localize('Style Exaggeration') }}
                    </label>
                    <input class="range-slider__range" name="style" id="sts_style" type="range" value="{{isset($defaultVoiceSetting) && $defaultVoiceSetting->style *100}}"
                        min="0" max="100">

                    <span id="sts_style__value" class="range-slider__value">{{isset($defaultVoiceSetting) && $defaultVoiceSetting->style *100}}</span>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="form-input">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="use_speaker_boost"
                            {{isset($defaultVoiceSetting) && $defaultVoiceSetting->use_speaker_boost == true ? 'checked' : '' }}>
                        <label class="form-check-label" for="use_speaker_boost">{{ localize('Speaker Boost') }}</label>
                    </div>

                </div>
            </div>




        </div>
    </div>
</div>
