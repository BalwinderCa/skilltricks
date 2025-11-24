@extends('backend.layouts.master')

@section('title')
    {{ $template->collectLocalization('name') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4 g-3">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ $template->collectLocalization('name') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('templates.index') }}">{{ localize('Templates') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Generate Contents') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <x-open-ai-error-message/>
                <div class="col-xl-5 col-lg-5 pe-xl-4">
                    <div class="tt-template-field flex-grow-1">
                        <div class="row justify-content-center">
                            <div class="col-12">
                                <div class="tt-article-generate">

                                    @if (isCustomer())
                                    @php
                                            $latestPackage = activePackageHistory(auth()->user()->id);
                                        @endphp 
                                         @if($latestPackage)
                                            @if($latestPackage->new_word_balance != -1) 
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center flex-column used-words-percentage">
                                                        @include('backend.pages.templates.inc.used-words-percentage')
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        @endif
                                    @endif

                                    <form action="" method="POST" class="generate-contents-form"
                                        data-url={{ route('templates.generate') }}>
                                        @csrf
                                        <input type="hidden" name="template_code" value="{{ $template->code }}">
                                        <input class="project_id" type="hidden" name="project_id" value="">

                                        {{-- @if ($template->description)
                                            <div class="tt-template-heading mb-4 alert bg-soft-primary alert-primary">
                                                <p class="mb-0">{{ $template->description }}</p>
                                            </div>
                                        @endif --}}

                                        @include('backend.pages.templates.inc.language', [
                                            'languages' => $languages,
                                        ])

                                        @php
                                            $fields = json_decode(json_decode($template->fields, true), true);
                                            $lastCounter = count($fields) + 2;
                                        @endphp

                                        @foreach ($fields as $fieldKey => $field)
                                            <div class="mb-4">
                                                <label for="{{ $field['field']['name'] }}" class="form-label"><span
                                                        class="fw-bold tt-promot-number fw-bold fs-4 me-2">{{ $fieldKey + 2 }}.</span>{{ localize($field['label']) }}
                                                    @if ($field['is_required'])
                                                        <span class="text-danger ms-1">*</span>
                                                    @endif
                                                </label>

                                                @if ($field['field']['type'] == 'text')
                                                    <input class="form-control" type="text"
                                                        id="{{ $field['field']['name'] }}"
                                                        name="{{ $field['field']['name'] }}"
                                                        placeholder="{{ localize($field['field']['placeholder']) }}"
                                                        @if ($field['is_required']) required @endif>
                                                @else
                                                    <textarea class="form-control" name="{{ $field['field']['name'] }}" id="{{ $field['field']['name'] }}"
                                                        placeholder="{{ localize($field['field']['placeholder']) }}" rows="4"
                                                        @if ($field['is_required']) required @endif></textarea>
                                                @endif
                                            </div>
                                        @endforeach

                                        <div class="mb-4">
                                            <div class="d-flex flex-column">
                                                <div
                                                    class="d-flex align-items-center justify-content-between tt-advance-options cursor-pointer">
                                                    <label class="form-label cursor-pointer"><span
                                                            class="fw-bold tt-promot-number fw-bold fs-4 me-2">{{ $lastCounter }}.</span>{{ localize('Advance Options') }}<span
                                                            class="ms-1 cursor-pointer" data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-title="{{ localize('Browse more fields') }}"><i
                                                                data-feather="help-circle"
                                                                class="icon-14"></i></span></label>
                                                    <span class="tt-advance-arrow bg-light"><i data-feather="arrow-down"
                                                            class="icon-16 text-muted"></i></span>
                                                </div>

                                                <div class="card tt-advance-options-wrapper" id="tt-advance-options">
                                                    <div class="card-body">
                                                        <div class="row g-3">

                                                            <div class="col-lg-12">
                                                                <div class="form-input">
                                                                    <label for="max_tokens"
                                                                        class="form-label">{{ localize('Max Results Length') }}
                                                                        <span class="ms-1 cursor-pointer"
                                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                                            data-bs-title="{{ localize('Maximum words for each result') }}"><i
                                                                                data-feather="help-circle"
                                                                                class="icon-14"></i></span>
                                                                    </label>
                                                                    <input class="form-control" type="number"
                                                                        id="max_tokens" name="max_tokens"
                                                                        @if (getSetting('default_max_result_length') != -1) value="{{ getSetting('default_max_result_length') }}" @endif
                                                                        placeholder="{{ localize('Enter maximum word limit') }}"
                                                                        @if (isCustomer()) max="{{ auth()->user()->this_month_available_words }}" @endif>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-6">
                                                                <div class="form-input">
                                                                    <label for="creativity"
                                                                        class="form-label">{{ localize('Creative Label') }}
                                                                        <span class="ms-1 cursor-pointer"
                                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                                            data-bs-title="{{ localize('Creativity level of the output will be as selected') }}"><i
                                                                                data-feather="help-circle"
                                                                                class="icon-14"></i></span>
                                                                    </label>
                                                                    <select class="form-select select2" id="creativity"
                                                                        name="creativity" required>
                                                                        <option value="1"
                                                                            @if (getSetting('default_creativity') == '1') selected @endif>
                                                                            {{ localize('High') }}
                                                                        </option>
                                                                        <option value="0.5"
                                                                            @if (getSetting('default_creativity') == '0.5') selected @endif>
                                                                            {{ localize('Medium') }}
                                                                        </option>
                                                                        <option value="0"
                                                                            @if (getSetting('default_creativity') == '0') selected @endif>
                                                                            {{ localize('Low') }}
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-6">
                                                                <div class="form-input">
                                                                    <label for="tone"
                                                                        class="form-label">{{ localize('Choose a Tone') }}
                                                                        <span class="ms-1 cursor-pointer"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            data-bs-title="{{ localize('Choose the tone of the result text as you need') }}"><i
                                                                                data-feather="help-circle"
                                                                                class="icon-14"></i></span>
                                                                    </label>
                                                                    <select class="form-select select2" id="tone"
                                                                        name="tone" required>
                                                                        <option value="{{ localize('Friendly') }}"
                                                                            @if (getSetting('default_tone') == 'Friendly') selected @endif>
                                                                            {{ localize('Friendly') }}</option>
                                                                        <option value="{{ localize('Luxury') }}"
                                                                            @if (getSetting('default_tone') == 'Luxury') selected @endif>
                                                                            {{ localize('Luxury') }}
                                                                        </option>
                                                                        <option value="{{ localize('Relaxed') }}"
                                                                            @if (getSetting('default_tone') == 'Relaxed') selected @endif>
                                                                            {{ localize('Relaxed') }}
                                                                        </option>
                                                                        <option value="{{ localize('Professional') }}"
                                                                            @if (getSetting('default_tone') == 'Professional') selected @endif>
                                                                            {{ localize('Professional') }}</option>
                                                                        <option value="{{ localize('Casual') }}"
                                                                            @if (getSetting('default_tone') == 'Casual') selected @endif>
                                                                            {{ localize('Casual') }}
                                                                        </option>
                                                                        <option value="{{ localize('Excited') }}"
                                                                            @if (getSetting('default_tone') == 'Excited') selected @endif>

                                                                            {{ localize('Excited') }}
                                                                        </option>
                                                                        <option value="{{ localize('Bold') }}"
                                                                            @if (getSetting('default_tone') == 'Bold') selected @endif>
                                                                            {{ localize('Bold') }}
                                                                        </option>
                                                                        <option value="{{ localize('Masculine') }}"
                                                                            @if (getSetting('default_tone') == 'Masculine') selected @endif>
                                                                            {{ localize('Masculine') }}</option>
                                                                        <option value="{{ localize('Dramatic') }}"
                                                                            @if (getSetting('default_tone') == 'Dramatic') selected @endif>
                                                                            {{ localize('Dramatic') }}</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button class="btn btn-primary btn-create-content">
                                            <span class="me-2 btn-create-text">{{ localize('Create Content') }}</span>
                                            <!-- text preloader start -->
                                            <span class="tt-text-preloader d-none">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </span>
                                            <!-- text preloader end -->
                                        </button>
                                        <button class="btn btn-outline-secondary btn-stop-content" disabled>
                                            <span class="me-2">{{ localize('Stop Generation') }}</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-7 col-lg-7 mt-4 mt-md-0">
                    <div class="tt-generate-text">
                        <div class="row">
                            <div class="col-12">
                                <div
                                    class="card flex-column h-100 {{ auth()->user()->user_type != 'customer' ? 'mt-2' : '' }}">
                                    @include('backend.pages.templates.inc.content')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('modals')
    @include('backend.pages.templates.inc.saveToFolderModal')
@endsection

@section('scripts')
    @include('backend.pages.templates.inc.template-scripts')
    @include('backend.pages.templates.inc.rewrite-scripts')
@endsection
