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
                                <h1 class="h4 mb-lg-1">{{ localize('CTA Section') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('CTA Section') }}</li>
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
                                <h5 class="mb-4">{{ localize('CTA Configurations') }}</h5>

                                <div class="mb-3">
                                    <label for="cta_colored_title"
                                        class="form-label">{{ localize('Title Colored') }}</label>
                                    <input type="hidden" name="types[]" value="cta_colored_title">
                                    <input type="text" id="cta_colored_title" name="cta_colored_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('cta_colored_title', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="cta_heading_title" class="form-label">{{ localize('Heading') }}</label>
                                    <input type="hidden" name="types[]" value="cta_heading_title">
                                    <input type="text" id="cta_heading_title" name="cta_heading_title"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('cta_heading_title', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="cta_short_description"
                                        class="form-label">{{ localize('Short Description') }}</label>
                                    <input type="hidden" name="types[]" value="cta_short_description">
                                    <input type="text" id="cta_short_description" name="cta_short_description"
                                        class="form-control"
                                        value="{{ systemSettingsLocalization('cta_short_description', $lang_key) }}">
                                </div>


                                <div class="mb-3">
                                    <label for="cta_btn_title" class="form-label">{{ localize('Button Title') }}</label>
                                    <input type="hidden" name="types[]" value="cta_btn_title">
                                    <input type="text" id="cta_btn_title" name="cta_btn_title" class="form-control"
                                        value="{{ systemSettingsLocalization('cta_btn_title', $lang_key) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="cta_btn_link" class="form-label">{{ localize('Button Link') }}</label>
                                    <input type="hidden" name="types[]" value="cta_btn_link">
                                    <input type="url" id="cta_btn_link" name="cta_btn_link" class="form-control"
                                        value="{{ getSetting('cta_btn_link') }}">
                                </div>

                                <div class="mb-3">
                                    <label for="cta_features" class="form-label">{{ localize('Features') }}</label>
                                    <input type="hidden" name="types[]" value="cta_features">
                                    <textarea rows="4" id="cta_features" name="cta_features" class="form-control">{{ systemSettingsLocalization('cta_features', $lang_key) }}</textarea>
                                    <small>* {{ localize('Comma Separated: One, Two') }}</small>
                                </div>
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
