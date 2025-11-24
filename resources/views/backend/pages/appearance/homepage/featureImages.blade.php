@extends('backend.layouts.master')

@section('title')
    {{ localize('Website Homepage Configuration') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Feature Images') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Feature Images') }}</li>
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
                        class="pb-650">
                        @csrf
                        <input type="hidden" name="language_key" id="language_id" value="{{ $lang_key }}">
                        {{-- 1 --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Image 1:') }}</h5>
                                <div class="mb-3">
                                    <label for="feature_image_1_title" class="form-label">{{ localize('Title') }}</label>
                                    <input type="hidden" name="types[]" value="feature_image_1_title">
                                    <input type="text" id="feature_image_1_title" name="feature_image_1_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('feature_image_1_title', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="feature_image_1_short_description"
                                        class="form-label">{{ localize('Short Description') }}</label>
                                    <input type="hidden" name="types[]" value="feature_image_1_short_description">
                                    <input type="text" id="feature_image_1_short_description"
                                        name="feature_image_1_short_description" class="form-control"
                                        value="{{ systemSettingsLocalization('feature_image_1_short_description', $lang_key) }}">
                                </div>
                                @if (checkLanguage($lang_key))
                                    <div class="mb-3">
                                        <label class="form-label">{{ localize('Feature Image 1') }}</label>
                                        <input type="hidden" name="types[]" value="feature_image_1_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Images') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="feature_image_1_image"
                                                        value="{{ getSetting('feature_image_1_image') }}">
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

                        {{-- 2 --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Feature Image 2:') }}</h5>
                                <div class="mb-3">
                                    <label for="feature_image_2_title" class="form-label">{{ localize('Title') }}</label>
                                    <input type="hidden" name="types[]" value="feature_image_2_title">
                                    <input type="text" id="feature_image_2_title" name="feature_image_2_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('feature_image_2_title', $lang_key) }}">
                                </div>
                                @if (checkLanguage($lang_key))
                                    <div class="mb-3">
                                        <label class="form-label">{{ localize('Feature Image 2') }}</label>
                                        <input type="hidden" name="types[]" value="feature_image_2_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Images') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="feature_image_2_image"
                                                        value="{{ getSetting('feature_image_2_image') }}">
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

                        {{-- 3 --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Feature Image 3:') }}</h5>
                                <div class="mb-3">
                                    <label for="feature_image_3_title" class="form-label">{{ localize('Title') }}</label>
                                    <input type="hidden" name="types[]" value="feature_image_3_title">
                                    <input type="text" id="feature_image_3_title" name="feature_image_3_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('feature_image_3_title', $lang_key) }}">
                                </div>
                                @if (checkLanguage($lang_key))
                                    <div class="mb-3">
                                        <label class="form-label">{{ localize('Feature Image 3') }}</label>
                                        <input type="hidden" name="types[]" value="feature_image_3_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Images') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="feature_image_3_image"
                                                        value="{{ getSetting('feature_image_3_image') }}">
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

                        {{-- 4 --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Feature Image 4:') }}</h5>
                                <div class="mb-3">
                                    <label for="feature_image_4_title" class="form-label">{{ localize('Title') }}</label>
                                    <input type="hidden" name="types[]" value="feature_image_4_title">
                                    <input type="text" id="feature_image_4_title" name="feature_image_4_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('feature_image_4_title', $lang_key) }}">
                                </div>
                                @if (checkLanguage($lang_key))
                                    <div class="mb-3">
                                        <label class="form-label">{{ localize('Feature Image 4') }}</label>
                                        <input type="hidden" name="types[]" value="feature_image_4_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Images') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="feature_image_4_image"
                                                        value="{{ getSetting('feature_image_4_image') }}">
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

                        {{-- 5 --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Feature Image 5:') }}</h5>
                                <div class="mb-3">
                                    <label for="feature_image_5_title" class="form-label">{{ localize('Title') }}</label>
                                    <input type="hidden" name="types[]" value="feature_image_5_title">
                                    <input type="text" id="feature_image_5_title" name="feature_image_5_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('feature_image_5_title', $lang_key) }}">
                                </div>
                                @if (checkLanguage($lang_key))
                                    <div class="mb-3">
                                        <label class="form-label">{{ localize('Feature Image 5') }}</label>
                                        <input type="hidden" name="types[]" value="feature_image_5_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Images') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="feature_image_5_image"
                                                        value="{{ getSetting('feature_image_5_image') }}">
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
