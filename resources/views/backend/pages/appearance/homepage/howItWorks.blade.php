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
                                <h1 class="h4 mb-lg-1">{{ localize('How It Works?') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('How It Works?') }}</li>
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
                                <h5 class="mb-4">{{ localize('Step 1:') }}</h5>
                                <div class="mb-3">
                                    <label for="how_it_works_1_title" class="form-label">{{ localize('Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_1_title">
                                    <input type="text" id="how_it_works_1_title" name="how_it_works_1_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_1_title', $lang_key) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="how_it_works_1_sub_title"
                                        class="form-label">{{ localize('Sub Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_1_sub_title">
                                    <input type="text" id="how_it_works_1_sub_title" name="how_it_works_1_sub_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_1_sub_title', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="how_it_works_1_short_description"
                                        class="form-label">{{ localize('Short Description') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_1_short_description">
                                    <input type="text" id="how_it_works_1_short_description"
                                        name="how_it_works_1_short_description" class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_1_short_description', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="how_it_works_1_features"
                                        class="form-label">{{ localize('Features') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_1_features">
                                    <textarea rows="4" id="how_it_works_1_features" name="how_it_works_1_features" class="form-control">{{ systemSettingsLocalization('how_it_works_1_features', $lang_key) }}</textarea>
                                    <small>* {{ localize('Comma Separated: One, Two') }}</small>
                                </div>


                                <div class="mb-3">
                                    <label for="how_it_works_1_btn_title"
                                        class="form-label">{{ localize('Button Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_1_btn_title">
                                    <input type="text" id="how_it_works_1_btn_title" name="how_it_works_1_btn_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_1_btn_title', $lang_key) }}">
                                </div>
                               
                                    <div class="mb-3">
                                        <label for="how_it_works_1_btn_link"
                                            class="form-label">{{ localize('Button Link') }}</label>
                                        <input type="hidden" name="types[]" value="how_it_works_1_btn_link">
                                        <input type="url" id="how_it_works_1_btn_link" name="how_it_works_1_btn_link"
                                            class="form-control" value="{{ systemSettingsLocalization('how_it_works_1_btn_link', $lang_key) }}">
                                    </div>
                                @if (checkLanguage($lang_key))
                                    <div class="mb-3">
                                        <label class="form-label">{{ localize('Step 1 Image') }}</label>
                                        <input type="hidden" name="types[]" value="how_it_works_1_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Images') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="how_it_works_1_image"
                                                        value="{{ getSetting('how_it_works_1_image') }}">
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
                                <h5 class="mb-4">{{ localize('Step 2:') }}</h5>
                                <div class="mb-3">
                                    <label for="how_it_works_2_title" class="form-label">{{ localize('Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_2_title">
                                    <input type="text" id="how_it_works_2_title" name="how_it_works_2_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_2_title', $lang_key) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="how_it_works_2_sub_title"
                                        class="form-label">{{ localize('Sub Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_2_sub_title">
                                    <input type="text" id="how_it_works_2_sub_title" name="how_it_works_2_sub_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_2_sub_title', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="how_it_works_2_short_description"
                                        class="form-label">{{ localize('Short Description') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_2_short_description">
                                    <input type="text" id="how_it_works_2_short_description"
                                        name="how_it_works_2_short_description" class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_2_short_description', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="how_it_works_2_features"
                                        class="form-label">{{ localize('Features') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_2_features">
                                    <textarea rows="4" id="how_it_works_2_features" name="how_it_works_2_features" class="form-control">{{ systemSettingsLocalization('how_it_works_2_features', $lang_key) }}</textarea>
                                    <small>* {{ localize('Comma Separated: One, Two') }}</small>
                                </div>


                                <div class="mb-3">
                                    <label for="how_it_works_2_btn_title"
                                        class="form-label">{{ localize('Button Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_2_btn_title">
                                    <input type="text" id="how_it_works_2_btn_title" name="how_it_works_2_btn_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_2_btn_title', $lang_key) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="how_it_works_2_btn_link"
                                        class="form-label">{{ localize('Button Link') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_2_btn_link">
                                    <input type="url" id="how_it_works_2_btn_link" name="how_it_works_2_btn_link"
                                        class="form-control" value="{{ systemSettingsLocalization('how_it_works_2_btn_link', $lang_key) }}">
                                </div>
                                @if (checkLanguage($lang_key))

                                    <div class="mb-3">
                                        <label class="form-label">{{ localize('Step 2 Image') }}</label>
                                        <input type="hidden" name="types[]" value="how_it_works_2_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Images') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="how_it_works_2_image"
                                                        value="{{ getSetting('how_it_works_2_image') }}">
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
                                <h5 class="mb-4">{{ localize('Step 3:') }}</h5>
                                <div class="mb-3">
                                    <label for="how_it_works_3_title" class="form-label">{{ localize('Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_3_title">
                                    <input type="text" id="how_it_works_3_title" name="how_it_works_3_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_3_title', $lang_key) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="how_it_works_3_sub_title"
                                        class="form-label">{{ localize('Sub Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_3_sub_title">
                                    <input type="text" id="how_it_works_3_sub_title" name="how_it_works_3_sub_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_3_sub_title', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="how_it_works_3_short_description"
                                        class="form-label">{{ localize('Short Description') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_3_short_description">
                                    <input type="text" id="how_it_works_3_short_description"
                                        name="how_it_works_3_short_description" class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_3_short_description', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="how_it_works_3_features"
                                        class="form-label">{{ localize('Features') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_3_features">
                                    <textarea rows="4" id="how_it_works_3_features" name="how_it_works_3_features" class="form-control">{{ systemSettingsLocalization('how_it_works_3_features', $lang_key) }}</textarea>
                                    <small>* {{ localize('Comma Separated: One, Two') }}</small>
                                </div>


                                <div class="mb-3">
                                    <label for="how_it_works_3_btn_title"
                                        class="form-label">{{ localize('Button Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_3_btn_title">
                                    <input type="text" id="how_it_works_3_btn_title" name="how_it_works_3_btn_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_3_btn_title', $lang_key) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="how_it_works_3_btn_link"
                                        class="form-label">{{ localize('Button Link') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_3_btn_link">
                                    <input type="url" id="how_it_works_3_btn_link" name="how_it_works_3_btn_link"
                                        class="form-control" value="{{ systemSettingsLocalization('how_it_works_3_btn_link', $lang_key) }}">
                                </div>
                                @if (checkLanguage($lang_key))

                                    <div class="mb-3">
                                        <label class="form-label">{{ localize('Step 3 Image') }}</label>
                                        <input type="hidden" name="types[]" value="how_it_works_3_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Images') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="how_it_works_3_image"
                                                        value="{{ getSetting('how_it_works_3_image') }}">
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
                                <h5 class="mb-4">{{ localize('Step 4:') }}</h5>
                                <div class="mb-3">
                                    <label for="how_it_works_4_title" class="form-label">{{ localize('Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_4_title">
                                    <input type="text" id="how_it_works_4_title" name="how_it_works_4_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_4_title', $lang_key) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="how_it_works_4_sub_title"
                                        class="form-label">{{ localize('Sub Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_4_sub_title">
                                    <input type="text" id="how_it_works_4_sub_title" name="how_it_works_4_sub_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_4_sub_title', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="how_it_works_4_short_description"
                                        class="form-label">{{ localize('Short Description') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_4_short_description">
                                    <input type="text" id="how_it_works_4_short_description"
                                        name="how_it_works_4_short_description" class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_4_short_description', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="how_it_works_4_features"
                                        class="form-label">{{ localize('Features') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_4_features">
                                    <textarea rows="4" id="how_it_works_4_features" name="how_it_works_4_features" class="form-control">{{ systemSettingsLocalization('how_it_works_4_features', $lang_key) }}</textarea>
                                    <small>* {{ localize('Comma Separated: One, Two') }}</small>
                                </div>


                                <div class="mb-3">
                                    <label for="how_it_works_4_btn_title"
                                        class="form-label">{{ localize('Button Title') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_4_btn_title">
                                    <input type="text" id="how_it_works_4_btn_title" name="how_it_works_4_btn_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('how_it_works_4_btn_title', $lang_key) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="how_it_works_4_btn_link"
                                        class="form-label">{{ localize('Button Link') }}</label>
                                    <input type="hidden" name="types[]" value="how_it_works_4_btn_link">
                                    <input type="url" id="how_it_works_4_btn_link" name="how_it_works_4_btn_link"
                                        class="form-control" value="{{ systemSettingsLocalization('how_it_works_4_btn_link', $lang_key) }}">
                                </div>
                                @if (checkLanguage($lang_key))

                                    <div class="mb-3">
                                        <label class="form-label">{{ localize('Step 4 Image') }}</label>
                                        <input type="hidden" name="types[]" value="how_it_works_4_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Images') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="how_it_works_4_image"
                                                        value="{{ getSetting('how_it_works_4_image') }}">
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
