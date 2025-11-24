@extends('backend.layouts.master')

@section('title')
    {{ localize('Generate Text To Speech') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Text To Speech') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a></li>
                                    <li class="breadcrumb-item">{{ localize('Text To Speech') }}</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <x-open-ai-error-message/>
                <div class="col-12 mb-5">
                    <div class="card flex-column h-100 pb-0">
                        <div class="card-header p-3 p-md-4 p-lg-5">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-lg-10 col-12">
                                    <!-- image generate form -->
                                    <form class="header-search-form generate-voice-form" method="POST"
                                        action="{{ route('t2s.generate') }}">
                                        @csrf
                                        <div class="row justify-content-between align-items-center pb-3">
                                            <div class="col-auto flex-grow-1">
                                                <div class="tt-promt-fild">
                                                    <div
                                                        class="d-flex align-items-center tt-advance-options cursor-pointer">
                                                        <label for="tt-advance-options"
                                                            class="form-label cursor-pointer mb-0 btn btn-outline-secondary btn-sm rounded-pill"><span
                                                                class="fw-bold tt-promot-number fw-bold me-1"><span
                                                                    class="me-1 cursor-pointer" data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="{{ localize('Choose style, mood, resolution, number of results') }}"><i
                                                                        data-feather="help-circle"
                                                                        class="icon-14"></i></span></span>{{ localize('Advance Options') }}
                                                            <span><i data-feather="plus"
                                                                    class="icon-16 text-primary ms-2"></i></span></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                @if (isCustomer())
                                                    @php
                                                        $latestPackage = activePackageHistory(auth()->user()->id);
                                                    @endphp
                                                    @if ($latestPackage)
                                                        @if ($latestPackage->new_word_balance != -1)
                                                            <div
                                                                class="d-flex align-items-center flex-column used-words-percentage">
                                                                @include('backend.pages.templates.inc.used-words-percentage')
                                                            </div>
                                                        @endif
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <input type="hidden" name="status" id="status" value="{{ $status }}">
                                        <!-- advance options -->
                                        <div class="card mb-3 tt-advance-options-wrapper" id="tt-advance-options">
                                            <div class="card-body">
                                                <div class="row g-2">
                                                    <div class="col-lg-3">
                                                        <div class="form-input">
                                                            <label for="model"
                                                                class="form-label">{{ localize('Model') }}
                                                                <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="{{ localize('Select the Model') }}"><i
                                                                        data-feather="help-circle"
                                                                        class="icon-14"></i></span>
                                                            </label>
                                                            <select class="form-select select2" id="models"
                                                                name="model">
                                                                @foreach ($models as $key => $model)
                                                                    <option value="{{ $model }}">
                                                                        {{ strtoupper(str_replace('-', ' ', $model)) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="form-input">
                                                            <label for="voice"
                                                                class="form-label">{{ localize('Voice') }}
                                                                <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="{{ localize('Select the voice') }}"><i
                                                                        data-feather="help-circle"
                                                                        class="icon-14"></i></span>
                                                            </label>
                                                            <select class="form-select select2" id="voice"
                                                                name="voice">
                                                                @foreach ($languages_voices as $key => $voice)
                                                                    <option value="{{ $voice }}">
                                                                        {{ ucfirst($voice) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="form-input"
                                                            @if (env('DEMO_MODE') == 'On') data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-title="{{ localize('Disabled in demo') }}" @endif>
                                                            <label for="speed"
                                                                class="form-label">{{ localize('Speed') }}
                                                                <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="{{ localize('Speech Speed') }}"><i
                                                                        data-feather="help-circle"
                                                                        class="icon-14"></i></span>
                                                            </label>
                                                            <select class="form-select select2" id="speed"
                                                                name="speed" required
                                                                @if (env('DEMO_MODE') == 'On') disabled @endif>
                                                                @foreach ($speeds as $speed)
                                                                    <option value="{{ $speed }}"
                                                                        {{ $speed == 1 ? 'selected' : '' }}>
                                                                        {{ $speed }}</option>
                                                                @endforeach

                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="form-input"
                                                            @if (env('DEMO_MODE') == 'On') data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-title="{{ localize('Disabled in demo') }}" @endif>
                                                            <label for="break"
                                                                class="form-label">{{ localize('Response Format') }}
                                                                <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="{{ localize('Select the break time') }}"><i
                                                                        data-feather="help-circle"
                                                                        class="icon-14"></i></span>
                                                            </label>
                                                            <select class="form-select select2" id="response_format"
                                                                name="response_format" required
                                                                @if (env('DEMO_MODE') == 'On') disabled @endif>
                                                                @foreach ($response_formats as $format)
                                                                    <option value="{{ $format }}"
                                                                        {{ $format == 'mp3' ? 'selected' : '' }}>
                                                                        {{ strtoupper($format) }} </option>
                                                                @endforeach

                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label for="title" class="form-label"><span
                                                    class="fw-bold tt-promot-number fw-bold fs-4"></span>{{ localize('Title') }}
                                                <span class="text-danger ms-1">*</span>
                                            </label>
                                            <input class="form-control s2t-title" type="text" id="title"
                                                name="title" placeholder="{{ localize('title') }}" required>
                                        </div>
                                        <div class="row mb-3">


                                            <div class="col-lg-12">
                                                <label for="title" class="form-label"><span
                                                        class="fw-bold tt-promot-number fw-bold fs-4"></span>{{ localize('Content') }}
                                                    <span class="text-danger ms-1">*</span>
                                                </label>
                                                <textarea class="form-control defaultcontent" name="content" id="input-textarea" rows="4"
                                                    placeholder="{{ localize('Type your Text') }}" required></textarea>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <p class="fs-md"> <strong id="charac-count">0</strong> / 4096</p>

                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <button class="btn btn-primary" id="generate_speech_button"
                                                    type="submit">
                                                    {{ localize('Generate Speech') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <h5 class="mb-4 px-3 mt-4">{{ localize('Generated Speeches') }}</h5>
              
                        <div class="col-12">
                            <div id="voice_list_table">
                                @include('backend.pages.templates.inc.voice-list', [
                                    'voiceLists' => $voiceLists,
                                ])
                            </div>
                        </div>
                    </div>
                   
                   
                </div>
            </div>

        </div>

    </section>
@endsection

@section('scripts')
    @include('backend.pages.templates.inc.template-scripts')
    <script>
        let inputTextArea = document.getElementById("input-textarea");
        let characCount = document.getElementById("charac-count");

        inputTextArea.addEventListener("input", () => {
            let textLenght = inputTextArea.value.length;

            if (textLenght > 4096) {
                notifyMe('error', '{{ localize('Content exceeds limit') }}')
            }
            characCount.textContent = textLenght;


        });
    </script>
@endsection
