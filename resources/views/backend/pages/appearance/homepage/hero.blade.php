@extends('backend.layouts.master')

@section('title')
    {{ localize('Website Homepage Configuration') }} {{ systemSettingsLocalization('title_separator') }}
    {{ systemSettingsLocalization('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Hero Section Configuration') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Hero') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <x-change-language :langkey="$lang_key" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data"
                        class="">
                        @csrf
                        <input type="hidden" name="language_key" id="language_id" value="{{ $lang_key }}">
                        <!--Navbar-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Hero Information') }}</h5>


                                <div class="mb-3">
                                    <label for="hero_title" class="form-label">{{ localize('Title') }}</label>
                                    <input type="hidden" name="types[]" value="hero_title">
                                    <input type="text" id="hero_title" name="hero_title" class="form-control"
                                        value="{{ systemSettingsLocalization('hero_title', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    @if (getTheme() == appStatic()::defaultTheme)
                                        <label for="hero_colorful_title"
                                            class="form-label">{{ localize('Colorful Title') }}</label>
                                    @elseif(getTheme() == appStatic()::theme1)
                                        <label for="hero_colorful_title"
                                            class="form-label">{{ localize('Sub Title') }}</label>
                                    @endif
                                    <input type="hidden" name="types[]" value="hero_colorful_title">
                                    <input type="text" id="hero_colorful_title" name="hero_colorful_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('hero_colorful_title', $lang_key) }}">
                                </div>
                                @if (getTheme() == appStatic()::defaultTheme)
                                    <div class="mb-3">
                                        <label for="hero_sub_title" class="form-label">{{ localize('Sub Title') }}</label>
                                        <input type="hidden" name="types[]" value="hero_sub_title">
                                        <input type="text" id="hero_sub_title" name="hero_sub_title" class="form-control"
                                            value="{{ systemSettingsLocalization('hero_sub_title', $lang_key) }}">
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <label for="hero_sub_title_btn_text"
                                        class="form-label">{{ localize('Sub Title Button Text') }}</label>
                                    <input type="hidden" name="types[]" value="hero_sub_title_btn_text">
                                    <input type="text" id="hero_sub_title_btn_text" name="hero_sub_title_btn_text"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('hero_sub_title_btn_text', $lang_key) ?? "Start Writing - It\'s Free" }}">
                                </div>
                                <div class="mb-3">
                                    <label for="hero_sub_title_btn_link"
                                        class="form-label">{{ localize('Sub Title Button Link') }}</label>
                                    <input type="hidden" name="types[]" value="hero_sub_title_btn_link">
                                    <input type="text" id="hero_sub_title_btn_link" name="hero_sub_title_btn_link"
                                        class="form-control"
                                        value="{{ getSetting('hero_sub_title_btn_link') ?? route('login') }}">
                                </div>
                                @if (env('DEFAULT_LANGUAGE') == $lang_key)
                                    @if (getTheme() == appStatic()::defaultTheme)
                                        <div class="mb-3">
                                            <label class="form-label">{{ localize('Background Image') }}</label>
                                            <input type="hidden" name="types[]" value="hero_background_image">
                                            <div class="tt-image-drop rounded">
                                                <span class="fw-semibold">{{ localize('Choose Background') }}</span>
                                                <!-- choose media -->
                                                <div class="tt-product-thumb show-selected-files mt-3">
                                                    <div class="avatar avatar-xl cursor-pointer choose-media"
                                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                        onclick="showMediaManager(this)" data-selection="single">
                                                        <input type="hidden" name="hero_background_image"
                                                            value="{{ systemSettingsLocalization('hero_background_image') }}">
                                                        <div class="no-avatar rounded-circle">
                                                            <span><i data-feather="plus"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- choose media -->
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ localize('Animated Image') }}</label>
                                            <input type="hidden" name="types[]" value="hero_animated_image">
                                            <div class="tt-image-drop rounded">
                                                <span class="fw-semibold">{{ localize('Choose Animated Image') }}</span>
                                                <!-- choose media -->
                                                <div class="tt-product-thumb show-selected-files mt-3">
                                                    <div class="avatar avatar-xl cursor-pointer choose-media"
                                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                        onclick="showMediaManager(this)" data-selection="single">
                                                        <input type="hidden" name="hero_animated_image"
                                                            value="{{ systemSettingsLocalization('hero_animated_image') }}">
                                                        <div class="no-avatar rounded-circle">
                                                            <span><i data-feather="plus"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- choose media -->
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <label class="form-label">{{ localize('Sroll to Top Image') }}</label>
                                        <input type="hidden" name="types[]" value="scrol_to_top_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Scroll to Top Image') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="scrol_to_top_image"
                                                        value="{{ systemSettingsLocalization('scrol_to_top_image') }}">
                                                    <div class="no-avatar rounded-circle">
                                                        <span><i data-feather="plus"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- choose media -->
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>


                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save Changes') }}
                            </button>
                        </div>
                    </form>
                    @if(getTheme() == appStatic()::theme1)
                    <form action="{{ route('admin.appearance.video.upload') }}" method="POST" enctype="multipart/form-data"
                        class="">
                        @csrf
                        <input type="hidden" name="language_key" id="language_id" value="{{ $lang_key }}">
                        <input type="hidden" name="entity" id="entity" value="hero_video">
                        <div class="card mb-4" >
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Video Information') }}</h5>
                                <div class="mb-3">
                                    <label for="default_creativity" class="form-label">{{ localize('Video') }}
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
                                            @if (getSetting('hero_video'))
                                                {{ getSetting('hero_video') }}
                                            @else
                                                <small>* {{ localize('Allowed file types: ') }} .mp4
                                                </small>
                                            @endif

                                        </p>
                                    </div>
                                    @if ($errors->has('file'))
                                        <span class="text-danger">{{ $errors->first('file') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save Changes') }}
                            </button>
                        </div>
                    </form>
                    @endif
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Homepage Configuration') }}</h5>
                            <div class="tt-vertical-step-link">
                                <ul class="list-unstyled">
                                    @include('backend.pages.appearance.homepage.inc.rightSidebar')
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
        "use strict";

        // runs when the document is ready --> for media files
        $(document).ready(function() {
            getChosenFilesCount();
            showSelectedFilePreviewOnLoad();
        });
    </script>
@endsection
