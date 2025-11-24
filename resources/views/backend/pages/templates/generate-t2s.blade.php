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
                <div class="col-12 mb-5">
                    <div class="card flex-column h-100 pb-0">
                        <div class="card-header p-3 p-md-4 p-lg-5">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-lg-10 col-12">
                                    <!-- image generate form -->
                                    <form class="header-search-form generate-voice-form">
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
                                                    @if($latestPackage)
                                                        @if($latestPackage->new_word_balance != -1) 
                                                                    <div
                                                                        class="d-flex align-items-center flex-column used-words-percentage">
                                                                        @include('backend.pages.templates.inc.used-words-percentage')
                                                                    </div>
                                                        @endif
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <input type="hidden" name="" id="status" value="{{ $status }}">
                                        <!-- advance options -->
                                        <div class="card mb-3 tt-advance-options-wrapper" id="tt-advance-options">
                                            <div class="card-body">
                                                <div class="row g-2">
                                                    <div class="col-lg-3">
                                                        <div class="form-input">
                                                            <label for="language"
                                                                class="form-label">{{ localize('Language') }}
                                                                <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="{{ localize('Select the language') }}"><i
                                                                        data-feather="help-circle"
                                                                        class="icon-14"></i></span>
                                                            </label>
                                                            <select class="form-select select2" id="languages"
                                                                name="language">
                                                                @foreach ($languages as $key => $language)
                                                                    <option value="{{ $key }}"
                                                                        {{ $key == 'en-GB' ? 'selected' : '' }}>
                                                                        {{ $language }}</option>
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
                                                                <option value="x-slow">{{ localize('Very Slow') }}
                                                                </option>
                                                                <option value="slow">{{ localize('Slow') }}
                                                                </option>
                                                                <option value="medium" selected>
                                                                    {{ localize('Medium') }}</option>
                                                                <option value="fast">
                                                                    {{ localize('Fast') }}</option>
                                                                <option value="x-fast">
                                                                    {{ localize('Very Fast') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="form-input"
                                                            @if (env('DEMO_MODE') == 'On') data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-title="{{ localize('Disabled in demo') }}" @endif>
                                                            <label for="break"
                                                                class="form-label">{{ localize('Break') }}
                                                                <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="{{ localize('Select the break time') }}"><i
                                                                        data-feather="help-circle"
                                                                        class="icon-14"></i></span>
                                                            </label>
                                                            <select class="form-select select2" id="break"
                                                                name="break" required
                                                                @if (env('DEMO_MODE') == 'On') disabled @endif>
                                                                <option value="1"
                                                                    @if (getSetting('default_number_of_results') == '1') selected @endif>
                                                                    1
                                                                </option>
                                                                <option value="2"
                                                                    @if (getSetting('default_number_of_results') == '2') selected @endif>
                                                                    2
                                                                </option>
                                                                <option value="3"
                                                                    @if (getSetting('default_number_of_results') == '3') selected @endif>
                                                                    3
                                                                </option>
                                                                <option value="4"
                                                                    @if (getSetting('default_number_of_results') == '4') selected @endif>
                                                                    4
                                                                </option>
                                                                <option value="5"
                                                                    @if (getSetting('default_number_of_results') == '5') selected @endif>
                                                                    5
                                                                </option>
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
                                                <div class="d-flex justify-content-between align-items-center"><label
                                                        for="break" class="form-label">{{ localize('Add Text') }}
                                                        <span class="text-danger ms-1">*</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-4">
                                                
                                                <div class="mb-4 speech">
                                                    <div class="col-12  col-lg-4">
                                                    
                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <select class="form-select select2 say-as">
                                                                <option value="0" selected>{{__('say-as')}}</option>
                                                                <option value="currency">{{__('currency')}}</option>
                                                                <option value="telephone">{{__('telephone')}}</option>
                                                                <option value="verbatim">{{__('verbatim')}}</option>
                                                                <option value="date">{{__('date')}}</option>
                                                                <option value="characters">{{__('characters')}}</option>
                                                                <option value="cardinal">{{__('cardinal')}}</option>
                                                                <option value="ordinal">{{__('ordinal')}}</option>
                                                                <option value="fraction">{{__('fraction')}}</option>
                                                                <option value="bleep">{{__('bleep')}}</option>
                                                                <option value="unit">{{__('unit')}}</option>
                                                                <option value="unit">{{__('time')}}</option>
                                                            </select>
                                                            <a href=""></a>
                                                        </div>
                                                    </div>
                                                    <textarea class="form-control defaultcontent" name="content[]" id="text" rows="4"
                                                        placeholder="{{ localize('Type your Text') }}" required></textarea>

                                                    </button>
                                                </div>
                                               
                                                <div class="speeches">

                                                </div>
                                                <button class="btn add-new-text rounded btn-sm btn-outline-secondary"
                                                    type="button">
                                                    <i data-feather="plus" class="me-1"></i>
                                                    {{ localize('Add More') }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- submit button -->
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <button class="btn btn-primary" id="generate_speech_button"
                                                        type="submit">
                                                        {{ localize('Generate Speech') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- submit button end -->
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
    @include('backend.pages.templates.inc.generate_scripts')
@endsection
