@extends('backend.layouts.master')

@section('title')
    {{ localize('Speech to Text') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4 g-3">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Speech to Text') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>

                                    <li class="breadcrumb-item">{{ localize('Speech to Text') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-xl-5 col-lg-5 pe-xl-4">
                    <div class="tt-template-field flex-grow-1">
                        <div class="row justify-content-center">
                            <div class="12">
                                <div class="tt-article-generate">

                                    @if (isCustomer())
                                        @php
                                            $latestPackage = activePackageHistory(auth()->user()->id);
                                        @endphp
                                        @if($latestPackage->new_s2t_balance != -1)
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center flex-column used-words-percentage">
                                                        @include('backend.pages.templates.inc.used-s2t-percentage')
                                                    </div>
                                                </div>
                                            </div>
                                            
                                        @endif
                                    @endif

                                    <form action="" method="POST" class="generate-s2t-form" enctype="multipart/form-data">
                                        @csrf

                                        <div class="mb-4">
                                            <label for="title" class="form-label"><span
                                                    class="fw-bold tt-promot-number fw-bold fs-4 me-2">1.</span>{{ localize('Type Text Title') }}
                                                <span class="text-danger ms-1">*</span>
                                            </label>
                                            <input class="form-control s2t-title" type="text" id="title"
                                                name="title" placeholder="{{ localize('Type your title') }}" required>
                                        </div>

                                        <div class="mb-4">
                                            @php
                                                $fileLimit = 0;
                                                if (isCustomer()) {
                                                    $package = optional(activePackageHistory())->subscriptionPackage ?? new \App\Models\SubscriptionPackage();
                                                    $fileLimit = $package->speech_to_text_filesize_limit;
                                                }
                                            @endphp
                                            <label for="audio" class="form-label"><span
                                                    class="fw-bold tt-promot-number fw-bold fs-4 me-2">2.</span>{{ localize('Upload Audio File') }}
                                                <span class="text-danger ms-1">*</span>
                                            </label>

                                            <div class="file-drop-area file-upload text-center rounded-3">
                                                <input type="file" class="file-drop-input" name="audio"
                                                    id="audio" />
                                                <div class="file-drop-icon ci-cloud-upload">
                                                    <i data-feather="image"></i>
                                                </div>
                                                <p class="text-dark fw-bold mb-2 mt-3">
                                                    {{ localize('Drop your files here or') }}
                                                    <a href="javascript::void(0);"
                                                        class="text-primary">{{ localize('Browse') }}</a>
                                                </p>
                                                <p class="mb-0 file-name text-muted">
                                                    <small>* {{ localize('Allowed file types: ') }} .mp3, .mp4, .mpeg,
                                                        .mpga, .m4a,
                                                        .wav, .webm @if (isCustomer())
                                                            | {{ localize('Max Size: ') }} {{ $fileLimit }}MB
                                                        @endif </small>
                                                </p>
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
                                <div class="card flex-column h-100">
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
@endsection
