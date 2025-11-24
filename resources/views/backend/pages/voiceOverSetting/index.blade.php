@extends('backend.layouts.master')

@section('title')
    {{ localize('Voice Settings') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Voice Settings') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Voice Settings') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-4 pb-650">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    @if (count($allVoiceSettings) > 0)
                        <!--default lang info start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Enable Voice Over') }}</h5>
                                <div class="mb-4">

                                    <select id="default_voiceover" class="form-control country-flag-select"
                                        name="default_voiceover" data-toggle="select2"
                                        onchange="handleEnableOviceOverSubmit(this)">
                                        <option value="0">{{ localize('Select Voice Over') }}</option>
                                        @foreach ($allVoiceSettings as $key => $list)
                                            <option value="{{ $list->type }}"
                                                {{ getSetting('default_voiceover') == $list->type ? 'selected' : '' }}>
                                                {{ str_replace('_', ' ',strtoupper($list->type)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endif
                    <!--default lang info end-->

                    <!--configurations start-->
                    <form action="{{ route('admin.settings.voice-settings.update') }}" method="POST" class="mt-3"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="type" value="google">
                        <div class="card mb-4" id="section-2">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="mb-4">{{ localize('Google TTS Settings') }}</h5>
                                    <a href="https://writebot.themetags.com/documentation/index.html#gtts" target="_blank"
                                        rel="noopener noreferrer">{{ localize('Documentation') }}</a>
                                </div>
                                <div class="mb-3">
                                    <label for="default_creativity" class="form-label">{{ localize('GCS File (JSON)') }}
                                        <span class="text-danger ms-1">*</span></label>


                                    <div class="file-drop-area file-upload text-center rounded-3">
                                        <input type="file" class="file-drop-input" name="file" id="json" />
                                        <div class="file-drop-icon ci-cloud-upload">
                                            <i data-feather="image"></i>
                                        </div>
                                        <p class="text-dark fw-bold mb-2 mt-3">
                                            {{ localize('Drop your files here or') }}
                                            <a href="javascript::void(0);"
                                                class="text-primary">{{ localize('Browse') }}</a>
                                        </p>
                                        <p class="mb-0 file-name text-muted">
                                            @if (isset($gtsSetting))
                                                {{ @$gtsSetting->file_name }}
                                            @else
                                                <small>* {{ localize('Allowed file types: ') }} .json
                                                </small>
                                            @endif

                                        </p>
                                    </div>
                                    @if ($errors->has('file'))
                                        <span class="text-danger">{{ $errors->first('file') }}</span>
                                    @endif
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name"
                                                class="form-label">{{ localize('GCS Project Name') }}</label>
                                            <input type="text" name="project_name" id="title"
                                                value="{{ @$gtsSetting->project_name }}"
                                                placeholder="{{ localize('Type GCS Project Name') }}" class="form-control">
                                            @if ($errors->has('title'))
                                                <span class="text-danger">{{ $errors->first('title') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="symbol" class="form-label">{{ localize('Maximum characters') }}
                                                <span>({{ localize('if not give any input maximum unlimited') }})</span></label>
                                            <input type="number" name="maximum_character" id="maximum_character"
                                                placeholder="" class="form-control"
                                                value="{{ @$gtsSetting->maximum_character }}">
                                            @if ($errors->has('maximum_character'))
                                                <span class="text-danger">{{ $errors->first('maximum_character') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i>
                                        {{ localize('Save Configurations') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <!--configurations end-->

                    <form action="{{ route('admin.settings.voice-settings.update') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <!--currency info start-->
                        <div class="card mb-4" id="section-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="mb-4">{{ localize('Azure Voiceover Settings') }}</h5>
                                    <a href="https://writebot.themetags.com/documentation/index.html#azure" target="_blank"
                                        rel="noopener noreferrer">{{ localize('Documentation') }}</a>
                                </div>
                                <input type="hidden" name="type" value="azure">
                                <div class="mb-4">
                                    <label for="name" class="form-label">{{ localize('Azure Key') }}</label>
                                    <input type="text" name="azure_key" id="azure_key"
                                        placeholder="{{ localize('Type Azure Key') }}" class="form-control"
                                        value="{{ @$azureSetting->key }}" required>
                                    @if ($errors->has('azure_key'))
                                        <span class="text-danger">{{ $errors->first('azure_key') }}</span>
                                    @endif
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="symbol"
                                                class="form-label">{{ localize('Azure Region') }}</label>
                                            <input type="text" name="azure_region" id="azure_region"
                                                placeholder="{{ localize('Type Azure Region') }}" class="form-control"
                                                value="{{ @$azureSetting->region }}" required>
                                            @if ($errors->has('azure_region'))
                                                <span class="text-danger">{{ $errors->first('azure_region') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="symbol" class="form-label">{{ localize('Maximum characters') }}
                                                <span>({{ localize('if not give any input maximum unlimited') }})</span></label>
                                            <input type="number" name="maximum_character" id="maximum_character"
                                                placeholder="" class="form-control"
                                                value="{{ @$azureSetting->maximum_character }}">
                                            @if ($errors->has('maximum_character'))
                                                <span class="text-danger">{{ $errors->first('maximum_character') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>




                            </div>
                        </div>
                        <!--currency info end-->

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i>
                                        {{ localize('Save Configurations') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    {{-- elevenLabs --}}
                    <form action="{{ route('admin.settings.voice-settings.update') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <!--currency info start-->
                        <div class="card mb-4" id="section-4">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="mb-4">{{ localize('ELEVEN LABS Voiceover Settings') }}</h5>
                                   
                                </div>
                                <input type="hidden" name="type" value="eleven_labs">
                                <div class="mb-4">
                                    <label for="name" class="form-label">{{ localize('ELEVEN LABS API KEY') }}</label>
                                    <input type="text" name="eleven_labs_api_key" id="eleven_labs_api_key"
                                        placeholder="{{ localize('Type Eleven Labs Api Key') }}" class="form-control"
                                        value="{{ @$elevenLabsSetting->key }}" required>
                                    @if ($errors->has('eleven_labs_api_key'))
                                        <span class="text-danger">{{ $errors->first('eleven_labs_api_key') }}</span>
                                    @endif
                                </div>
                                <div class="mb-4">
                                    <label for="symbol" class="form-label">{{ localize('Maximum characters') }}
                                        <span>({{ localize('if not give any input maximum unlimited') }})</span></label>
                                    <input type="number" name="maximum_character" id="maximum_character"
                                        placeholder="" class="form-control"
                                        value="{{ @$azureSetting->maximum_character }}">
                                    @if ($errors->has('maximum_character'))
                                        <span class="text-danger">{{ $errors->first('maximum_character') }}</span>
                                    @endif
                                </div>

                            </div>
                        </div>
                        <!--currency info end-->

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i>
                                        {{ localize('Save Configurations') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    {{-- end elevenlabs --}}
                    {{-- elevenLabs --}}
                    <form action="{{ route('admin.settings.voice-settings.update') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <!--currency info start-->
                        <div class="card mb-4" id="section-5">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="mb-4">{{ localize('Open Ai Text To Speech Settings') }}</h5>
                                   
                                </div>
                                <input type="hidden" name="type" value="open_ai_tts">
                                
                                <div class="mb-4">
                                    <label for="symbol" class="form-label">{{ localize('Maximum characters') }}
                                        <span>({{ localize('if not give any input maximum 4096 characters') }})</span></label>
                                    <input type="number" name="maximum_character" id="maximum_character"
                                        placeholder="" class="form-control" max="4096"
                                        value="{{ @$openAiSetting->maximum_character }}">
                                    @if ($errors->has('maximum_character'))
                                        <span class="text-danger">{{ $errors->first('maximum_character') }}</span>
                                    @endif
                                </div>

                            </div>
                        </div>
                        <!--currency info end-->

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i>
                                        {{ localize('Save Configurations') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    {{-- end elevenlabs --}}
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Voice Setting') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    @if (count($allVoiceSettings) > 0)
                                        <li>
                                            <a href="#section-1" class="active">{{ localize('Enable Voice Over') }}</a>
                                        </li>
                                    @endif
                                    <li>
                                        <a href="#section-2">{{ localize('Google TTS') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-3">{{ localize('Azure Voiceover') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-4">{{ localize('Eleven Labs') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-5">{{ localize('Open AI') }}</a>
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
        "use strict"

        function handleEnableOviceOverSubmit(el) {
            $.post('{{ route('admin.settings.voice-settings.enable') }}', {
                    _token: '{{ csrf_token() }}',
                    method: el.value
                },
                function(data) {
                    notifyMe(data.status, data.message);
                });
        }
    </script>
@endsection
