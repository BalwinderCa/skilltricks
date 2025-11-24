@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Settings') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')

    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI Settings') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AI Settings') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ route('admin.settings.updateOpenAiSettings') }}" method="POST"
                        enctype="multipart/form-data" class="pb-650">
                        @csrf
                        <!--general settings-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">

                                <div class="mb-3">
                                    <label for="default_ai_engine"
                                        class="form-label">{{ localize('AI Engine') }}
                                        <span class="text-danger ms-1">*</span></label>
                                    <input type="hidden" name="types[]" value="default_ai_engine">
                                    <select class="form-select select2" id="default_ai_engine" name="default_ai_engine"
                                        required>
                                        @foreach (appStatic()::ENGINE_LISTS as $key => $name)
                                            <option value="{{ $key }}" {{getSetting('default_ai_engine') == $key ? 'selected':''}}>{{ localize($name) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="default_creativity"
                                        class="form-label">{{ localize('Default Creativity Level') }}
                                        <span class="text-danger ms-1">*</span></label>
                                    <input type="hidden" name="types[]" value="default_creativity">
                                    <select class="form-select select2" id="default_creativity" name="default_creativity"
                                        required>
                                        <option value="1" @if (getSetting('default_creativity') == '1') selected @endif>
                                            {{ localize('High') }}
                                        </option>
                                        <option value="0.5" @if (getSetting('default_creativity') == '0.5') selected @endif>
                                            {{ localize('Medium') }}
                                        </option>
                                        <option value="0" @if (getSetting('default_creativity') == '0') selected @endif>
                                            {{ localize('Low') }}
                                        </option>
                                    </select>
                                </div>


                                <div class="mb-3">
                                    <label for="default_tone"
                                        class="form-label">{{ localize('Default Tone Of Output Result') }}
                                        <span class="text-danger ms-1">*</span></label>
                                    <input type="hidden" name="types[]" value="default_tone">
                                    <select class="form-select select2" id="default_tone" name="default_tone" required>
                                        <option value="Friendly" @if (getSetting('default_tone') == 'Friendly') selected @endif>
                                            {{ localize('Friendly') }}
                                        </option>
                                        <option value="Luxury" @if (getSetting('default_tone') == 'Luxury') selected @endif>
                                            {{ localize('Luxury') }}
                                        </option>
                                        <option value="Relaxed" @if (getSetting('default_tone') == 'Relaxed') selected @endif>
                                            {{ localize('Relaxed') }}
                                        </option>
                                        <option value="Professional" @if (getSetting('default_tone') == 'Professional') selected @endif>
                                            {{ localize('Professional') }}
                                        </option>
                                        <option value="Casual" @if (getSetting('default_tone') == 'Casual') selected @endif>
                                            {{ localize('Casual') }}
                                        </option>
                                        <option value="Excited" @if (getSetting('default_tone') == 'Excited') selected @endif>
                                            {{ localize('Excited') }}
                                        </option>
                                        <option value="Bold" @if (getSetting('default_tone') == 'Bold') selected @endif>
                                            {{ localize('Bold') }}
                                        </option>
                                        <option value="Masculine" @if (getSetting('default_tone') == 'Masculine') selected @endif>
                                            {{ localize('Masculine') }}
                                        </option>
                                        <option value="Dramatic" @if (getSetting('default_tone') == 'Dramatic') selected @endif>
                                            {{ localize('Dramatic') }}
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="default_number_of_results"
                                        class="form-label">{{ localize('Default Number Of Results') }}
                                        <span class="text-danger ms-1">*</span></label>
                                    <input type="hidden" name="types[]" value="default_number_of_results">
                                    <select class="form-select select2" id="default_number_of_results"
                                        name="default_number_of_results" required>
                                        <option value="1" @if (getSetting('default_number_of_results') == '1') selected @endif>1
                                        </option>
                                        <option value="2" @if (getSetting('default_number_of_results') == '2') selected @endif>2
                                        </option>
                                        <option value="3" @if (getSetting('default_number_of_results') == '3') selected @endif>3
                                        </option>
                                        <option value="4" @if (getSetting('default_number_of_results') == '4') selected @endif>4
                                        </option>
                                        <option value="5" @if (getSetting('default_number_of_results') == '5') selected @endif>5
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="default_max_result_length"
                                        class="form-label">{{ localize('Default Max Result Length') }}<span
                                            class="text-danger ms-1">*</span> <span class="ms-1 cursor-pointer"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="{{ localize('Insert -1 to make it unlimited') }}"><i
                                                data-feather="help-circle" class="icon-14"></i></span></label>
                                    <input type="hidden" name="types[]" value="default_max_result_length">
                                    <input type="number" id="default_max_result_length" name="default_max_result_length"
                                        class="form-control" value="{{ getSetting('default_max_result_length') }}"
                                        min="-1">
                                </div>
                                <div class="mb-3">
                                    <label for="default_max_result_length_blog_wizard"
                                        class="form-label">{{ localize('Default Max Result Length Blog Wizard') }}<span
                                            class="text-danger ms-1">*</span> <span class="ms-1 cursor-pointer"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="{{ localize('Insert 0 to make it unlimited') }}"><i
                                                data-feather="help-circle" class="icon-14"></i></span></label>
                                    <input type="hidden" name="types[]" value="default_max_result_length_blog_wizard">
                                    <input type="number" id="default_max_result_length_blog_wizard"
                                        name="default_max_result_length_blog_wizard" class="form-control"
                                        value="{{ getSetting('default_max_result_length_blog_wizard') }}" min="0">
                                </div>

                                <div class="mb-3">
                                    <label for="ai_filter_bad_words" class="form-label">{{ localize('Bad Words') }}<span
                                            class="text-danger ms-1">*</span> <span class="ms-1 cursor-pointer"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="{{ localize('These words will be filtered from user inputs while generating contents') }}"><i
                                                data-feather="help-circle" class="icon-14"></i></span></label>
                                    <input type="hidden" name="types[]" value="ai_filter_bad_words">
                                    <textarea type="number" id="ai_filter_bad_words" name="ai_filter_bad_words" class="form-control">{{ getSetting('ai_filter_bad_words') }}</textarea>
                                    <small>* {{ localize('Comma Separated: One, Two') }}</small>
                                </div>


                            </div>
                        </div>
                        <!--general settings-->

                        <!--feature activation settings-->
                        <div class="card mb-4" id="section-2">

                            <div class="card-body">

                                <h5 class="mb-4">{{ localize('Feature Activation') }}</h5>
                                {{-- Ai chat --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_chat">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_chat" id="enable_ai_chat" name="enable_ai_chat"
                                            @if (getSetting('enable_ai_chat') == null) checked
                                            @else                                          
                                            {{ getSetting('enable_ai_chat') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_chat">{{ localize('AI Chat') }}</label>
                                    </div>

                                </div>
                                {{-- Ai detector --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_detector">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_detector" id="enable_ai_detector" name="enable_ai_detector"
                                            @if (getSetting('enable_ai_detector') == null) checked
                                            @else                                          
                                            {{ getSetting('enable_ai_detector') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_detector">{{ localize('AI Detector') }}</label>
                                    </div>

                                </div>
                                {{-- Ai plagiarism --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_plagiarism">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_plagiarism" id="enable_ai_plagiarism" name="enable_ai_plagiarism"
                                            @if (getSetting('enable_ai_plagiarism') == null) checked
                                            @else                                          
                                            {{ getSetting('enable_ai_plagiarism') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_plagiarism">{{ localize('AI Plagiarism') }}</label>
                                    </div>

                                </div>

                                {{-- Ai ReWriter --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_rewriter">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_rewriter" id="enable_ai_rewriter"
                                            name="enable_ai_rewriter"
                                            @if (getSetting('enable_ai_rewriter') == null) checked
                                            @else                                          
                                            {{ getSetting('enable_ai_rewriter') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_rewriter">{{ localize('AI ReWriter') }}</label>
                                    </div>
                                </div>
                                {{-- Ai PDF Chat --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_pdf_chat">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_pdf_chat" id="enable_ai_pdf_chat"
                                            name="enable_ai_pdf_chat"
                                            @if (getSetting('enable_ai_pdf_chat') == null) checked
                                            @else
                                            {{ getSetting('enable_ai_pdf_chat') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_pdf_chat">{{ localize('AI PDF Chat') }}</label>
                                    </div>
                                </div>
                                {{-- Ai Vision --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_vision">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_vision" id="enable_ai_vision" name="enable_ai_vision"
                                            @if (getSetting('enable_ai_vision') == null) checked
                                            @else
                                            {{ getSetting('enable_ai_vision') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_vision">{{ localize('AI Vision') }}</label>
                                    </div>
                                </div>
                                {{-- Ai Detector --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_detector">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_detector" id="enable_ai_detector" name="enable_ai_detector"
                                            @if (getSetting('enable_ai_detector') == null) checked
                                            @else
                                            {{ getSetting('enable_ai_detector') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_detector">{{ localize('AI Detector') }}</label>
                                    </div>
                                </div>
                                {{-- Ai plagiarism --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_plagiarism">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_plagiarism" id="enable_ai_plagiarism" name="enable_ai_plagiarism"
                                            @if (getSetting('enable_ai_plagiarism') == null) checked
                                            @else
                                            {{ getSetting('enable_ai_plagiarism') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_plagiarism">{{ localize('AI Plagiarism') }}</label>
                                    </div>
                                </div>

                                {{-- Ai Image chat --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_image_chat">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_image_chat" id="enable_ai_image_chat"
                                            name="enable_ai_image_chat"
                                            @if (getSetting('enable_ai_image_chat') == null) checked
                                            @else
                                                {{ getSetting('enable_ai_image_chat') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_image_chat">{{ localize('AI Chat Image') }}</label>
                                    </div>
                                </div>
                                {{-- Built In Templates --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_built_in_templates">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_built_in_templates" id="enable_built_in_templates"
                                            name="enable_built_in_templates"
                                            @if (getSetting('enable_built_in_templates') == null) checked
                                            @else
                                                {{ getSetting('enable_built_in_templates') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_built_in_templates">{{ localize('Built In Templates') }}</label>
                                    </div>
                                </div>
                                {{-- Custom Templates --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_custom_templates">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_custom_templates" id="enable_custom_templates"
                                            name="enable_custom_templates"
                                            @if (getSetting('enable_custom_templates') == null) checked
                                            @else
                                            {{ getSetting('enable_custom_templates') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_custom_templates">{{ localize('Custom Templates') }}</label>
                                    </div>
                                </div>

                                {{-- AI Blog Wizard --}}

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_blog_wizard">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_blog_wizard" id="enable_blog_wizard"
                                            name="enable_blog_wizard"
                                            @if (getSetting('enable_blog_wizard') == null) checked
                                            @else
                                                {{ getSetting('enable_blog_wizard') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_blog_wizard">{{ localize('AI Blog Wizard') }}</label>
                                    </div>
                                </div>
                                {{-- Speech to Text --}}

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_speech_to_text">

                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_speech_to_text" id="enable_speech_to_text"
                                            name="enable_speech_to_text"
                                            @if (getSetting('enable_speech_to_text') == null) checked
                                            @else
                                                {{ getSetting('enable_speech_to_text') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_speech_to_text">{{ localize('Speech to Text') }}</label>
                                    </div>
                                </div>
                                {{-- Text to Speech --}}

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_text_to_speech">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_text_to_speech" id="enable_text_to_speech"
                                            name="enable_text_to_speech"
                                            @if (getSetting('enable_text_to_speech') == null) checked
                                            @else
                                                {{ getSetting('enable_text_to_speech') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_text_to_speech">{{ localize('Text to Speech') }}</label>
                                    </div>
                                </div>

                                {{-- ElevenLabs --}}

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_eleven_labs">

                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_eleven_labs" id="enable_eleven_labs"
                                            name="enable_eleven_labs"
                                            @if (getSetting('enable_eleven_labs') == null) checked
                                            @else
                                                {{ getSetting('enable_eleven_labs') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_eleven_labs">{{ localize('ElevenLabs') }}</label>
                                    </div>
                                </div>
                                {{-- Generate Images  --}}

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_images">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_images" id="enable_ai_images" name="enable_ai_images"
                                            @if (getSetting('enable_ai_images') == null) checked
                                            @else
                                                {{ getSetting('enable_ai_images') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_images">{{ localize('Generate Images') }}</label>
                                    </div>
                                </div>

                                {{-- Generate Images Step AI Blog Wizard  --}}

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="generate_image">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="generate_image" id="generate_image" name="generate_image"
                                            @if (getSetting('generate_image') == null) checked
                                            @else
                                                {{ getSetting('generate_image') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="generate_image">{{ localize('Generate Images Step AI Blog Wizard') }}</label>
                                    </div>
                                </div>
                                {{-- Generate Code  --}}

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="features[]" value="enable_ai_code">
                                        <input type="checkbox" class="form-check-input featureActivation"
                                            data-type="enable_ai_code" id="enable_ai_code" name="enable_ai_code"
                                            @if (getSetting('enable_ai_code') == null) checked
                                            @else
                                                {{ getSetting('enable_ai_code') == 0 ? '' : 'checked' }} @endif>
                                        <label class="form-check-label ms-1"
                                            for="enable_ai_code">{{ localize('Generate Code') }}</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="image_chat_model"
                                        class="form-label">{{ localize('AI Image Chat Model') }}</label>
                                    <input type="hidden" name="types[]" value="image_chat_model">
                                    <select id="image_chat_model" class="form-control select2" name="image_chat_model">
                                        <option value="" disabled selected>
                                            {{ localize('Set speech to text status') }}
                                        </option>
                                        <option value="dall-e-3" @if (getSetting('image_chat_model') == 'dall_e_3') selected @endif>
                                            {{ localize('Dall-E 3') }}
                                        </option>

                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="generate_image_option"
                                        class="form-label">{{ localize('Generate Images AI Blog Wizard') }}</label>
                                    <input type="hidden" name="types[]" value="generate_image_option">
                                    <select id="generate_image_option" class="form-control select2"
                                        name="generate_image_option">

                                        <option value="dall_e_2" @if (getSetting('generate_image_option') == 'dall_e_2') selected @endif>
                                            {{ localize('Dall-E 2') }}
                                        </option>
                                        <option value="dall_e_3" @if (getSetting('generate_image_option') == 'dall_e_3') selected @endif>
                                            {{ localize('Dall-E 3') }}
                                        </option>
                                        <option value="stable_diffusion"
                                            @if (getSetting('generate_image_option') == 'stable_diffusion') selected @endif>
                                            {{ localize('Stable Diffusion') }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!--feature activation settings-->


                        <!--ai model settings-->
                        <div class="card mb-4" id="section-3">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Open AI Model') }}</h5>
                                <div class="mb-3">
                                    <label for="default_open_ai_model"
                                        class="form-label">{{ localize('Default AI Model') }}
                                        <span class="text-danger ms-1">*</span> <a class="ms-1"
                                            href="https://platform.openai.com/docs/models/gpt-3-5" target="_blank"
                                            rel="noopener noreferrer"><i data-feather="info"
                                                class="icon-16"></i></a></label>
                                    <input type="hidden" name="types[]" value="default_open_ai_model">

                                    <select id="default_open_ai_model" class="form-control select2"
                                        name="default_open_ai_model">
                                        @foreach (\App\Models\OpenAiModel::orderBy('order', 'asc')->get() as $openAiModel)
                                            <option value="{{ $openAiModel->key }}"
                                                @if (getSetting('default_open_ai_model') == $openAiModel->key) selected @endif>
                                                {{ $openAiModel->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="ai_blog_wizard_model"
                                        class="form-label">{{ localize('AI Blog Wizard Model') }}
                                        <span class="text-danger ms-1">*</span></label>
                                    <input type="hidden" name="types[]" value="ai_blog_wizard_model">

                                    <select id="ai_blog_wizard_model" class="form-control select2"
                                        name="ai_blog_wizard_model">
                                        @foreach (\App\Models\OpenAiModel::orderBy('order', 'asc')->get() as $openAiModel)
                                            <option value="{{ $openAiModel->key }}"
                                                @if (getSetting('ai_blog_wizard_model') == $openAiModel->key) selected @endif>
                                                {{ $openAiModel->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>


                                <div class="mb-3">
                                    <label for="ai_chat_model" class="form-label">{{ localize('AI Chat Model') }}
                                        <span class="text-danger ms-1">*</span></label>
                                    <input type="hidden" name="types[]" value="ai_chat_model">

                                    <select id="ai_chat_model" class="form-control select2" name="ai_chat_model">
                                        @foreach (\App\Models\OpenAiModel::orderBy('order', 'asc')->get() as $openAiModel)
                                            <option value="{{ $openAiModel->key }}"
                                                @if (getSetting('ai_chat_model') == $openAiModel->key) selected @endif>
                                                {{ $openAiModel->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="max_tokens" class="form-label">{{ localize('Max token for Model') }}
                                        <span class="text-danger ms-1"></span></label>
                                    <span class="float-end text-info checkMaxToken cursor-pointer" id="checkMaxToken">
                                        <i data-feather="refresh-cw"
                                            class="icon-16 me-2"></i>{{ localize('Check Supported Max Token With OpenAI') }}</span>

                                    <input type="hidden" name="types[]" value="max_tokens">
                                    <input type="number" id="max_tokens" name="max_tokens" class="form-control"
                                        placeholder="" value="{{ getSetting('max_tokens') }}">

                                    <span class="text-danger" id="maxTokenErrorMessage"></span>
                                    <span class="text-success" id="maxTokenSuccessMessage"></span>
                                </div>


                            </div>
                        </div>
                        <!--ai model settings-->

                        <!--ai api key-->
                        <div class="card mb-4" id="section-4">
                            <div class="card-body">
                                <h5 class="mb-4">
                                    {{ localize('Open AI Secret Key') }}
                                    <a href="{{ route('admin.multiOpenAi.index') }}"
                                        class="btn btn-sm btn-outline-secondary float-end"><i data-feather="plus"></i>
                                        {{ localize('Create Multipe API Key') }}</a>
                                </h5>

                                <div class="mb-3">
                                    <label for="OPENAI_SECRET_KEY"
                                        class="form-label">{{ localize('Open AI Secret Key') }}<span
                                            class="text-danger ms-1">*</span></label>
                                    @can('view_open_ai_models')
                                        @if (openAiKey())
                                            <a class="float-end text-info" target="_blank"
                                                href="{{ route('admin.settings.openAi.models') }}"><i data-feather="info"
                                                    class="icon-16 me-2"></i>{{ localize('Supported Model') }}</a>
                                        @endif
                                    @endcan
                                    <input type="hidden" name="types[]" value="OPENAI_SECRET_KEY">
                                    
                                    <div class="input-group">
                                        <input class="form-control rounded-end" type="password"
                                            placeholder="{{!isAdmin() ? '****************************' : ''}}"
                                            name="OPENAI_SECRET_KEY" @if(isAdmin()) value="{{ env('OPENAI_SECRET_KEY') }}"@endif>
                                        <span
                                            class="position-absolute top-50 right-0 translate-middle-y me-2 btn-reveal-pw">
                                            <i data-feather="eye"
                                                class="icon-14 cursor-pointer eyeShowHide eyeIcon"></i></span>
                                    </div>
                                    <span class="text-warning me-2">**{{ localize('Note') }} :
                                        <small>{{ localize('If you are not getting any response please re-check your api balance and model') }}**</small>
                                    </span>

                                </div>
                                <div class="mb-3">
                                    <label for="api_key_use"
                                        class="form-label">{{ localize('Openai API Key Usage Model') }}</label>

                                    <input type="hidden" name="types[]" value="api_key_use">
                                    <select id="api_key_use" class="form-control text-uppercase select2"
                                        name="api_key_use" data-toggle="select2">

                                        <option value="main"
                                            {{ getSetting('api_key_use') == 'main' || !getSetting('api_key_use') ? 'selected' : '' }}>
                                            {{ localize('Main Api key') }}
                                        </option>
                                        <option value="random"
                                            {{ getSetting('api_key_use') == 'random' ? 'selected' : '' }}>
                                            {{ localize('Random Api Key') }}
                                        </option>
                                    </select>
                                    <span class="text-warning me-2 d-none" id="random">**{{ localize('Note') }} :
                                        <small>{{ localize('when you choose random api sometimes not get any response if api balance empty or mis-match model') }}**</small>
                                    </span>
                                </div>


                            </div>
                        </div>
                        <!--ai api key-->


                        <!--ai api key-->
                        <div class="card mb-4" id="section-5">
                            <div class="card-body">
                                <h5 class="mb-4">
                                    {{ localize('Stable Diffusion') }}
                                    <a href="{{ route('admin.multiOpenAi.index') }}"
                                        class="btn btn-sm btn-outline-secondary float-end"><i data-feather="plus"></i>
                                        {{ localize('Create Multipe API Key') }}</a>
                                </h5>

                                <div class="mb-3">
                                    <label for="SD_API_KEY"
                                        class="form-label">{{ localize('Stable Diffusion Api Key') }}<span
                                            class="text-danger ms-1">*</span></label>
                                    <input type="hidden" name="types[]" value="SD_API_KEY">

                                    <div class="input-group">
                                        <input type="password" id="SD_API_KEY" name="SD_API_KEY" class="form-control"
                                            placeholder="{{!isAdmin() ? '****************************' : ''}}" @if(isAdmin()) value="{{env('SD_API_KEY')}}" @endif> 
                                        @if (isAdmin())
                                            <span
                                                class="position-absolute top-50 right-0 translate-middle-y me-2 btn-reveal-pw">
                                                <i data-feather="eye"
                                                    class="icon-14 cursor-pointer eyeShowHide eyeIcon"></i></span>
                                        @endif
                                    </div>

                                </div>
                                <div class="mb-3">
                                    <label for="sd_api_key_use"
                                        class="form-label">{{ localize('Stable Diffusion Key Usage Model') }}</label>

                                    <input type="hidden" name="types[]" value="sd_api_key_use">
                                    <select id="sd_api_key_use" class="form-control text-uppercase select2"
                                        name="sd_api_key_use" data-toggle="select2">
                                        <option value="main"
                                            {{ getSetting('sd_api_key_use') == 'main' || !getSetting('sd_api_key_use') ? 'selected' : '' }}>
                                            {{ localize('Main Api key') }}
                                        </option>
                                        <option value="random"
                                            {{ getSetting('sd_api_key_use') == 'random' ? 'selected' : '' }}>
                                            {{ localize('Random Api Key') }}
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="image_stable_diffusion_engine"
                                        class="form-label">{{ localize('Stable Diffusion Engine ID') }}</label>
                                    <input type="hidden" name="types[]" value="image_stable_diffusion_engine">
                                    <select id="image_stable_diffusion_engine" class="form-control text-uppercase select2"
                                        name="image_stable_diffusion_engine" data-toggle="select2">

                                        <option value='stable-diffusion-v1-6'
                                            @if (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-v1-6') selected @endif>
                                            {{ localize('Stable Diffusion v1.6') }}
                                        </option>
                                        <option value='stable-diffusion-512-v2-1'
                                            @if (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-512-v2-1') selected @endif>
                                            {{ localize('Stable Diffusion v2.1') }}
                                        </option>
                                        <option value='stable-diffusion-768-v2-1'
                                            @if (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-768-v2-1') selected @endif>
                                            {{ localize('Stable Diffusion v2.1-768') }}
                                        </option>
                                        <option value='stable-diffusion-xl-beta-v2-2-2'
                                            @if (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-xl-beta-v2-2-2') selected @endif>
                                            {{ localize('Stable Diffusion v2.2.2-XL Beta') }}
                                        </option>
                                        <option value='stable-diffusion-xl-1024-v1-0'
                                            @if (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-xl-1024-v1-0') selected @endif>
                                            {{ localize('SDXL v1.0') }}
                                        </option>
                                        <option value='stable-diffusion-xl-1024-v0-9'
                                            @if (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-xl-1024-v0-9') selected @endif>
                                            {{ localize('SDXL v0.9') }}
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="image_upscaler_engine"
                                        class="form-label">{{ localize('Image Upscaler Engine') }}</label>
                                    <input type="hidden" name="types[]" value="image_upscaler_engine">
                                    <select id="image_upscaler_engine" class="form-control text-uppercase select2"
                                        name="image_upscaler_engine" data-toggle="select2">
                                        <option value='esrgan-v1-x2plus'
                                            @if (getSetting('image_upscaler_engine') == 'esrgan-v1-x2plus') selected @endif>
                                            Real-ESRGAN x2
                                        </option>
                                        <option value='stable-diffusion-x4-latent-upscaler'
                                            @if (getSetting('image_upscaler_engine') == 'stable-diffusion-x4-latent-upscaler') selected @endif>
                                            Stable Diffusion x4 Latent Upscaler</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!--ai api key-->
                        <!--ai api key-->
                        <div class="card mb-4" id="section-6">
                            <div class="card-body">

                                <div class="mb-3">
                                    <label for="serper_api_key"
                                        class="form-label">{{ localize('Serper Api key for real time data') }}<span
                                            class="text-danger ms-1">*</span></label>
                                    <input type="hidden" name="types[]" value="serper_api_key">

                                    <div class="input-group">
                                        <input class="form-control rounded-end" type="password"
                                            placeholder="{{!isAdmin() ? '****************************' : ''}}" name="serper_api_key"
                                            @if(isAdmin()) value="{{ getSetting('serper_api_key') }}" @endif>
                                        @if (isAdmin())
                                            <span
                                                class="position-absolute top-50 right-0 translate-middle-y me-2 btn-reveal-pw">
                                                <i data-feather="eye"
                                                    class="icon-14 cursor-pointer eyeShowHide eyeIcon"></i></span>
                                        @endif
                                    </div>

                                </div>



                            </div>
                        </div>
                        <!--ai api key-->

                        <!-- claude ai -->
                        <div class="card mb-4" id="section-7">
                            <div class="card-body">
                                <h5 class="mb-4">
                                    {{ localize('Claude AI') }}
                                </h5>

                                <div class="mb-3">
                                    <label for="ANTHROPIC_API_KEY" class="form-label">{{ localize('Claude AI API Key') }}<span class="text-danger ms-1">*</span></label>
                                    <input type="hidden" name="types[]" value="ANTHROPIC_API_KEY">
                                    <div class="input-group">
                                        <input class="form-control rounded-end" type="password"
                                            placeholder="{{!isAdmin() ? '****************************' : ''}}" name="ANTHROPIC_API_KEY"
                                            @if(isAdmin()) value="{{ env('ANTHROPIC_API_KEY') }}" @endif>
                                        @if (isAdmin())
                                            <span
                                                class="position-absolute top-50 right-0 translate-middle-y me-2 btn-reveal-pw">
                                                <i data-feather="eye"
                                                    class="icon-14 cursor-pointer eyeShowHide eyeIcon"></i></span>
                                        @endif
                                    </div>
                                    
                                    <span class="text-warning me-2">**{{ localize('Note') }} :
                                        <small>{{ localize('Used in Templates, Custom Templates, AI Chat, AI Re-writer, AI Code') }}**</small>
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <label for="default_claude_ai_model"
                                        class="form-label">{{ localize('Claude AI Model') }}</label>
                                    <input type="hidden" name="types[]" value="default_claude_ai_model">
                                    <select id="default_claude_ai_model" class="form-control text-uppercase select2"
                                        name="default_claude_ai_model" data-toggle="select2"> 
                                        @foreach (appStatic()::CLAUDE_AI_MODELS as $key => $model)
                                            <option value="{{ $key }}"
                                                {{ getSetting('default_claude_ai_model') == $key ? 'selected' : '' }}>
                                                {{ localize($model) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- claude ai -->
                        
                        <!-- deepseek ai -->
                        <div class="card mb-4" id="section-8">
                            <div class="card-body">
                                <h5 class="mb-4">
                                    {{ localize('DeepSeek AI') }}
                                </h5>

                                <div class="mb-3">
                                    <label for="DEEPSEEK_API_KEY" class="form-label">{{ localize('DeepSeek AI API Key') }}<span class="text-danger ms-1">*</span></label>
                                    <input type="hidden" name="types[]" value="DEEPSEEK_API_KEY">
                                    <div class="input-group">
                                        <input class="form-control rounded-end" type="password"
                                            placeholder="{{!isAdmin() ? '****************************' : ''}}" name="DEEPSEEK_API_KEY"
                                            @if(isAdmin()) value="{{ env('DEEPSEEK_API_KEY') }}" @endif>
                                        @if (isAdmin())
                                            <span
                                                class="position-absolute top-50 right-0 translate-middle-y me-2 btn-reveal-pw">
                                                <i data-feather="eye"
                                                    class="icon-14 cursor-pointer eyeShowHide eyeIcon"></i></span>
                                        @endif
                                    </div>
                                    
                                    <span class="text-warning me-2">**{{ localize('Note') }} :
                                        <small>{{ localize('Used in Templates, Custom Templates, AI Chat, AI Re-writer, AI Code') }}**</small>
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <label for="default_deepseek_ai_model"
                                        class="form-label">{{ localize('DeepSeek AI Model') }}</label>
                                    <input type="hidden" name="types[]" value="default_deepseek_ai_model">
                                    <select id="default_deepseek_ai_model" class="form-control text-uppercase select2"
                                        name="default_deepseek_ai_model" data-toggle="select2"> 
                                        @foreach (appStatic()::DEEPSEEK_AI_MODELS as $key => $model)
                                            <option value="{{ $key }}"
                                                {{ getSetting('default_deepseek_ai_model') == $key ? 'selected' : '' }}>
                                                {{ localize($model) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- deepseek ai -->

                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save Configuration') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Configure Settings') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('General Information') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-2">{{ localize('Feature Activation') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-3">{{ localize('Open AI Model') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-4">{{ localize('Open AI Secret Key') }}</a>
                                    </li>

                                    <li>
                                        <a href="#section-5">{{ localize('Stable Diffusion') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-6">{{ localize('Serper API Key') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-7">{{ localize('Claude API Key') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-8">{{ localize('DeepSeek API Key') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('scripts')
    <script>
        $(document).on('change', '#api_key_use', function(e) {
            let value = $(this).val();
            if (value == 'random') {
                $('#random').removeClass('d-none');
            } else {
                $('#random').addClass('d-none');
            }
        });
        $(document).on('click', '#checkMaxToken', function(e) {
            e.preventDefault();
            let max_token_ai_model = $('#max_tokens').val();
            if (!max_token_ai_model) {
                notifyMe('error', 'max token value not found');
                return;
            }
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'GET',
                url: '{{ route('admin.settings.check-max-token') }}',
                data: {
                    max_token: max_token_ai_model
                },
                success: function(data) {
                    if (data.status == 'error') {
                        $('#maxTokenErrorMessage').html(data.message);
                        $('#maxTokenSuccessMessage').html('');
                    } else if (data.status == 'success') {
                        $('#maxTokenSuccessMessage').html(data.message);
                        $('#maxTokenErrorMessage').html('');

                    } else {

                    }
                },
                error: function(e) {
                    console.log(e);
                }
            })
        })
        $(document).on('click', '.eyeShowHide', function(e) {
            e.preventDefault();
            $(this).toggleClass("eyeIcon");
            input = $(this).parent().parent().find("input");
            if (input.attr("type") == "password") {
                input.attr("type", "text");
            } else {
                input.attr("type", "password");
            }
        });
    </script>
@endsection
