@extends('backend.layouts.master')

@section('title')
    {{ localize('Generate Code') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4 g-3">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h5 mb-lg-1">{{ localize('Generate Code') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AI Code') }}</li>
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

                                    <form action="" method="POST" class="generate-codes-form">
                                        @csrf

                                        <div class="mb-4">
                                            <label for="title" class="form-label"><span
                                                    class="fw-bold tt-promot-number fw-bold fs-4 me-2">1.</span>{{ localize('Type Title') }}
                                                <span class="text-danger ms-1">*</span>
                                            </label>
                                            <input class="form-control" type="text" id="title" name="title"
                                                placeholder="{{ localize('Type code title') }}" required>
                                        </div>

                                        <div class="mb-4">
                                            <label for="description" class="form-label"><span
                                                    class="fw-bold tt-promot-number fw-bold fs-4 me-2">2.</span>{{ localize('Type Description') }}
                                                <span class="text-danger ms-1">*</span>
                                            </label>

                                            <textarea class="form-control" rows="4" id="description" name="description"
                                                placeholder="{{ localize('Generate a javascript function to add 2 numbers and return their sum') }}" required></textarea>
                                        </div>
                                        <button class="btn btn-primary btn-create-content">
                                            <span class="me-2 btn-create-text">{{ localize('Generate Code') }}</span>
                                            <!-- text preloader start -->
                                            <span class="tt-text-preloader d-none">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </span>
                                            <!-- text preloader end -->
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
                                <div class="card flex-column h-100 content-code-card">
                                    @include('backend.pages.templates.inc.contentCode')
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
@endsection
