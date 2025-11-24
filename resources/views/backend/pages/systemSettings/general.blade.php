@extends('backend.layouts.master')

@section('title')
    {{ localize('General Settings') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">


            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('General Settings') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('General Settings') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <x-change-language :langkey="$lang_key" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 pb-650">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">

                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data"
                        class="">
                        @csrf
                        <input type="hidden" name="language_key" id="language_id" value="{{ $lang_key }}">
                        @if (checkLanguage($lang_key))
                            <!--general settings-->
                            <div class="card mb-4" id="section-1">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('General Informations') }}</h5>

                                    <div class="mb-3">
                                        <label for="system_title" class="form-label">{{ localize('System Title') }}</label>
                                        <input type="hidden" name="types[]" value="system_title">
                                        <input type="text" id="system_title" name="system_title" class="form-control"
                                            value="{{ getSetting('system_title') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label for="title_separator"
                                            class="form-label">{{ localize('Browser Tab Title Separator') }}</label>
                                        <input type="hidden" name="types[]" value="title_separator">
                                        <input type="text" id="title_separator" name="title_separator"
                                            class="form-control" value="{{ getSetting('title_separator') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label for="contact_email"
                                            class="form-label">{{ localize('Contact Email') }}</label>
                                        <input type="hidden" name="types[]" value="contact_email">
                                        <input type="text" id="contact_email" name="contact_email" class="form-control"
                                            value="{{ getSetting('contact_email') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label for="contact_phone"
                                            class="form-label">{{ localize('Contact Phone') }}</label>
                                        <input type="hidden" name="types[]" value="contact_phone">
                                        <input type="text" id="contact_phone" name="contact_phone" class="form-control"
                                            value="{{ getSetting('contact_phone') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label for="enable_preloader"
                                            class="form-label">{{ localize('Enable Preloader') }}</label>
                                        <input type="hidden" name="types[]" value="enable_preloader">
                                        <select id="enable_preloader" class="form-control text-uppercase select2"
                                            name="enable_preloader" data-toggle="select2">
                                            <option value="" disabled selected>{{ localize('Set preloader status') }}
                                            </option>
                                            <option value="1"
                                                {{ getSetting('enable_preloader') == '1' ? 'selected' : '' }}>
                                                {{ localize('Enable') }}</option>
                                            <option value="0"
                                                {{ getSetting('enable_preloader') == '0' ? 'selected' : '' }}>
                                                {{ localize('Disable') }}</option>
                                        </select>
                                    </div>

                                </div>
                            </div>
                            <!--general settings-->



                            <!--logo settings-->
                            <div class="card mb-4" id="section-3">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('Dashboard Logo & Favicon') }}</h5>
                                    <div class="mb-3">
                                        <label for="admin_panel_logo"
                                            class="form-label">{{ localize('Dashboard logo for light version') }}</label>
                                        <input type="hidden" name="types[]" value="admin_panel_logo">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Dark Logo') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="admin_panel_logo"
                                                        value="{{ getSetting('admin_panel_logo') }}">
                                                    <div class="no-avatar rounded-circle">
                                                        <span><i data-feather="plus"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- choose media -->
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="admin_panel_logo_dark"
                                            class="form-label">{{ localize('Dashboard logo for dark version') }}</label>
                                        <input type="hidden" name="types[]" value="admin_panel_logo_dark">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Light Logo') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="admin_panel_logo_dark"
                                                        value="{{ getSetting('admin_panel_logo_dark') }}">
                                                    <div class="no-avatar rounded-circle">
                                                        <span><i data-feather="plus"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- choose media -->
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="favicon" class="form-label">{{ localize('Favicon') }}</label>
                                        <input type="hidden" name="types[]" value="favicon">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Favicon') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="favicon"
                                                        value="{{ getSetting('favicon') }}">
                                                    <div class="no-avatar rounded-circle">
                                                        <span><i data-feather="plus"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- choose media -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--logo settings-->

                            <!--maintenance mode settings-->
                            <div class="card mb-4" id="section-4">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('Maintenance Mode') }}</h5>
                                    <div class="mb-3">
                                        <label for="enable_maintenance_mode"
                                            class="form-label">{{ localize('Enable Maintenance Mode') }}</label>
                                        <input type="hidden" name="types[]" value="enable_maintenance_mode">
                                        <select id="enable_maintenance_mode" class="form-control text-uppercase select2"
                                            name="enable_maintenance_mode" data-toggle="select2">
                                            <option value="" disabled selected>
                                                {{ localize('Set maintenance mode') }}
                                            </option>
                                            <option value="1"
                                                {{ getSetting('enable_maintenance_mode') == '1' ? 'selected' : '' }}>
                                                {{ localize('Enable') }}</option>
                                            <option value="0"
                                                {{ getSetting('enable_maintenance_mode') == '0' ? 'selected' : '' }}>
                                                {{ localize('Disable') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!--maintenance mode settings-->

                            <!--frontend  settings-->
                            <div class="card mb-4" id="section-10">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('Frontend/Landing Page') }}</h5>
                                    <div class="mb-3">
                                        <label for="enable_frontend" class="form-label">{{ localize('Frontend Status') }}
                                            <span>({{ localize('if disable only login, registration page show other all frontend page not visiable') }})</span></label>
                                        <input type="hidden" name="types[]" value="enable_frontend">
                                        <select id="enable_frontend" class="form-control text-uppercase select2"
                                            name="enable_frontend" data-toggle="select2">

                                            <option value="yes"
                                                {{ getSetting('enable_frontend') == 'yes' || !getSetting('enable_frontend') ? 'selected' : '' }}>
                                                {{ localize('Enable') }}</option>
                                            <option value="no"
                                                {{ getSetting('enable_frontend') == 'no' ? 'selected' : '' }}>
                                                {{ localize('Disable') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!--frontend mode settings-->

                            <!--seo meta description start-->
                            <div class="card mb-4" id="section-5">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('SEO Meta Configuration') }}</h5>

                                    <div class="mb-4">
                                        <label for="global_meta_title"
                                            class="form-label">{{ localize('Meta Title') }}</label>
                                        <input type="hidden" name="types[]" value="global_meta_title">
                                        <input type="text" name="global_meta_title" id="global_meta_title"
                                            placeholder="{{ localize('Type meta title') }}" class="form-control"
                                            value="{{ getSetting('global_meta_title') }}">
                                        <span class="fs-sm text-muted">
                                            {{ localize('Set a meta tag title. Recommended to be simple and unique.') }}
                                        </span>
                                    </div>

                                    <div class="mb-4">
                                        <label for="global_meta_description"
                                            class="form-label">{{ localize('Meta Description') }}</label>
                                        <input type="hidden" name="types[]" value="global_meta_description">
                                        <textarea class="form-control" name="global_meta_description" id="global_meta_description" rows="4"
                                            placeholder="{{ localize('Type your meta description') }}">{{ getSetting('global_meta_description') }}</textarea>
                                    </div>

                                    <div class="mb-4">
                                        <label for="global_meta_keywords"
                                            class="form-label">{{ localize('Meta Keywords') }}</label>

                                        <input type="hidden" name="types[]" value="global_meta_keywords">
                                        <textarea class="form-control" name="global_meta_keywords" id="global_meta_keywords" placeholder="Keyword, Keyword">{{ getSetting('global_meta_keywords') }}</textarea>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">{{ localize('Meta Image') }}</label>
                                        <input type="hidden" name="types[]" value="global_meta_image">
                                        <div class="tt-image-drop rounded">
                                            <span class="fw-semibold">{{ localize('Choose Meta Image') }}</span>
                                            <!-- choose media -->
                                            <div class="tt-product-thumb show-selected-files mt-3">
                                                <div class="avatar avatar-xl cursor-pointer choose-media"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                    onclick="showMediaManager(this)" data-selection="single">
                                                    <input type="hidden" name="global_meta_image"
                                                        value="{{ getSetting('global_meta_image') }}">
                                                    <div class="no-avatar rounded-circle">
                                                        <span><i data-feather="plus"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- choose media -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--seo meta description end-->
                        @endif
                        <!--Cookie Consent settings-->
                        <div class="card mb-4" id="section-6">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Cookie Consent') }}</h5>
                                @if (checkLanguage($lang_key))
                                    <div class="mb-3">
                                        <label for="enable_cookie_consent"
                                            class="form-label">{{ localize('Show Cookie Consent') }}</label>
                                        <input type="hidden" name="types[]" value="enable_cookie_consent">
                                        <select id="enable_cookie_consent" class="form-control text-uppercase select2"
                                            name="enable_cookie_consent" data-toggle="select2">
                                            <option value="" disabled selected>
                                                {{ localize('Select an option') }}
                                            </option>
                                            <option value="1"
                                                {{ getSetting('enable_cookie_consent') == '1' ? 'selected' : '' }}>
                                                {{ localize('Enable') }}</option>
                                            <option value="0"
                                                {{ getSetting('enable_cookie_consent') == '0' ? 'selected' : '' }}>
                                                {{ localize('Disable') }}</option>
                                        </select>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <label for="cookie_consent_text"
                                        class="form-label">{{ localize('Cookie Consent Text') }}</label>
                                    <input type="hidden" name="types[]" value="cookie_consent_text">
                                    <textarea name="cookie_consent_text" id="cookie_consent_text" class="editor form-control">{{ systemSettingsLocalization('cookie_consent_text', $lang_key) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <!--Cookie Consent settings-->
                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save Configuration') }}
                            </button>
                        </div>

                       
                    </form>

                   

                    @if (checkLanguage($lang_key))
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <!-- custom scripts -->
                        <div class="card mb-4" id="section-7">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Custom Scripts') }}</h5>

                                <div class="mb-3">
                                    <label for="header_custom_scripts"
                                           class="form-label">{{ localize('Header custom script - before </head>') }}</label>
                                    <input type="hidden" name="types[]" value="header_custom_scripts">
                                    <textarea rows="5"
                                              name="header_custom_scripts"
                                              id="header_custom_scripts"
                                              placeholder="<script></script>"
                                              class="form-control">{{ getSetting('header_custom_scripts') }}</textarea>

                                    <small>*{{ localize('Copy or write your custom script here') }}</small>
                                </div>

                                <div class="mb-3">
                                    <label for="footer_custom_scripts"
                                           class="form-label">{{ localize('Footer custom script - before </body>') }}</label>
                                    <input type="hidden" name="types[]" value="footer_custom_scripts">
                                    <textarea rows="5"
                                              name="footer_custom_scripts"
                                              id="footer_custom_scripts"
                                              placeholder="<script></script>"
                                              class="form-control">{{ getSetting('footer_custom_scripts') }}</textarea>

                                    <small>*{{ localize('Copy or write your custom script here') }}</small>
                                </div>
                                    <h5 class="mb-4">{{ localize('Custom Css') }}</h5>
    
                                    <div class="mb-3">
                                        <label for="header_custom_css"
                                               class="form-label">{{ localize('Custom css - before </head>') }}</label>
                                        <input type="hidden" name="types[]" value="header_custom_css">
                                        <textarea rows="5"
                                                  name="header_custom_css"
                                                  id="header_custom_css"
                                                  placeholder="<style></style>"
                                                  class="form-control">{{ getSetting('header_custom_css') }}</textarea>
    
                                        <small>*{{ localize('Copy or write your custom css here') }}</small>
                                    </div>
                            </div>
                        </div>
                        <!--custom scripts-->
                        <div class="mb-3">
                            <button class="btn btn-primary saveCustomScripts" type="button">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save Configuration') }}
                            </button>
                        </div>

                       
                    </form>
                    @endif
                   
                    @if (checkLanguage($lang_key))
                        <form action="{{ route('admin.envKey.update') }}" method="POST" enctype="multipart/form-data"
                            class="mt-5">
                            @csrf
                            <!--google analytics-->
                            <div class="card mb-4" id="section-8">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('Google Analytics') }}</h5>

                                    <div class="mb-3">
                                        <label for="ENABLE_GOOGLE_ANALYTICS"
                                            class="form-label">{{ localize('Google Analytics') }}</label>
                                        <input type="hidden" name="types[]" value="ENABLE_GOOGLE_ANALYTICS">
                                        <select id="ENABLE_GOOGLE_ANALYTICS" class="form-control text-uppercase select2"
                                            name="ENABLE_GOOGLE_ANALYTICS" data-toggle="select2">
                                            <option value="" disabled selected>
                                                {{ localize('Enable Google Analytics') }}
                                            </option>
                                            <option value="1"
                                                {{ env('ENABLE_GOOGLE_ANALYTICS') == '1' ? 'selected' : '' }}>
                                                {{ localize('Enable') }}</option>
                                            <option value="0"
                                                {{ env('ENABLE_GOOGLE_ANALYTICS') == '0' ? 'selected' : '' }}>
                                                {{ localize('Disable') }}</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="TRACKING_ID" class="form-label">{{ localize('Tracking ID') }}</label>
                                        <input type="hidden" name="types[]" value="TRACKING_ID">
                                        <input type="text" id="TRACKING_ID" name="TRACKING_ID" class="form-control"
                                            value="{{ env('TRACKING_ID') }}">
                                    </div>
                                </div>
                            </div>
                            <!--google analytics-->

                            <div class="mb-3">
                                <button class="btn btn-primary" type="submit">
                                    <i data-feather="save" class="me-1"></i> {{ localize('Save Configuration') }}
                                </button>
                            </div>
                        </form>


                        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data"
                            class="mt-5 adscenseForm">
                            @csrf
                            <!--google analytics-->
                            <div class="card mb-4" id="section-11">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('Google ADSENSE') }} </h5>
                                    <div class="mb-3">
                                        <label for="header_custom_scripts"
                                            class="form-label">{{ localize('AdSense code snippet') }} <x-required-star/></label>
                                        <input type="hidden" name="types[]" value="adsense_code_snippet">
                                        <textarea rows="5" name="adsense_code_snippet" id="adsense_code_snippet"
                                            placeholder="" class="form-control">{{ getSetting('adsense_code_snippet') }}</textarea>

                                    </div>
                                    <div class="mb-3">
                                        <label for="enable_google_adsense"
                                            class="form-label">{{ localize('Google ADSENSE Status') }}</label>
                                        <input type="hidden" name="types[]" value="enable_google_adsense">
                                        <select id="enable_google_adsense" class="form-control text-uppercase select2"
                                            name="enable_google_adsense" data-toggle="select2">
                                            <option value="" disabled selected>
                                                {{ localize('Enable Google ADSENSE') }}
                                            </option>
                                            <option value="1"
                                                {{ getSetting('enable_google_adsense') == '1' ? 'selected' : '' }}>
                                                {{ localize('Enable') }}</option>
                                            <option value="0"
                                                {{ getSetting('enable_google_adsense') == '0' || !getSetting('enable_google_adsense') ? 'selected' : '' }}>
                                                {{ localize('Disable') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!--google analytics-->

                            <div class="mb-3">
                                <button class="btn btn-primary" type="submit">
                                    <i data-feather="save" class="me-1"></i> {{ localize('Save Configuration') }}
                                </button>
                            </div>
                        </form>
                    @endif

                    <form action="{{ route('admin.envKey.update') }}" method="POST" enctype="multipart/form-data"
                        class="mt-5">
                        @csrf
                        <!--google analytics-->
                        <div class="card mb-4" id="section-9">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Subscription Settings') }}</h5>

                                <div class="mb-3">
                                    <div class="form-check tt-checkbox">
                                        <label for="carry_forward" class="form-check-label fw-medium ">
                                            <input class="form-check-input cursor-pointer subcriptionSettings"
                                                data-type="carry_forward" onchange="updateStatus(this)" type="checkbox"
                                                id="carry_forward"
                                                {{ getSetting('carry_forward') == 1 ? 'checked' : '' }}>
                                            <strong>{{ localize('Balance Carry Forward: ') }}</strong>
                                            {{ localize('Remaining balance from active package(only for active) will be added to next package balance.') }}
                                        </label>
                                        {{ localize('This service is applicable for same package - Lifetime to Lifetime, Yearly to Yearly, Monthly to Monthly and Prepaid to Prepaid pacakge.') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--google analytics-->

                    </form>
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data"
                    class="">
                    @csrf

                    <div class="card mb-4" id="section-12">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Scroll Bar Icon') }}</h5>

                            <div class="mb-4">
                                <label class="form-label">{{ localize('Scroll Bar Icon') }}</label>
                                <input type="hidden" name="types[]" value="scrol_to_top_image">
                                <div class="tt-image-drop rounded">
                                    <span class="fw-semibold">{{ localize('Choose Scroll Bar Icon') }}</span>
                                    <!-- choose media -->
                                    <div class="tt-product-thumb show-selected-files mt-3">
                                        <div class="avatar avatar-xl cursor-pointer choose-media"
                                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                            onclick="showMediaManager(this)" data-selection="single">
                                            <input type="hidden" name="scrol_to_top_image"
                                                value="{{ getSetting('scrol_to_top_image') }}">
                                            <div class="no-avatar rounded-circle">
                                                <span><i data-feather="plus"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- choose media -->
                                </div>
                            </div>

                        </div>

                    </div>
                    <div class="mb-3">
                        <button class="btn btn-primary" type="submit">
                            <i data-feather="save" class="me-1"></i> {{ localize('Save Configuration') }}
                        </button>
                    </div>
                    </form>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Configure General Settings') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('General Information') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-3">{{ localize('Dashborad Logo & Favicon') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-4">{{ localize('Maintenance Mode') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-10">{{ localize('Frontend Settings') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-5">{{ localize('SEO Configuration') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-6">{{ localize('Cookie Consent') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-7">{{ localize('Custom Scripts') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-css">{{ localize('Custom Css') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-8">{{ localize('Google Analytics') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-11">{{ localize('Google Adsense') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-9">{{ localize('Subscription Settings') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-12">{{ localize('Scroll Bar Icon') }}</a>
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
        "use strict";

        // runs when the document is ready --> for media files
        $(document).ready(function() {
            getChosenFilesCount();
            showSelectedFilePreviewOnLoad();
        });

        function updateStatus(el) {
            let type = $(el).data('type');
            if (el.checked) {
                var is_active = 1;
            } else {
                var is_active = 0;
            }

            $.post('{{ route('admin.subscription-settings.store') }}', {
                    _token: '{{ csrf_token() }}',
                    is_active: is_active,
                    type: type,

                },
                function(data) {
                    if (data == 1) {
                        notifyMe('success', '{{ localize('Status updated successfully') }}');
                    } else {
                        notifyMe('danger', '{{ localize('Something went wrong') }}');
                    }
                });
        }

        /**
         * Save Custom Scripts
         * */


         let entityMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        };

        $(document).on("click",".saveCustomScripts",function () {
            let headerCustomScript =  $("#header_custom_scripts").val();
            let footerCustomScript =  $("#footer_custom_scripts").val();
            let customCss          =  $("#header_custom_css").val();

            headerCustomScript = escapeHtml(headerCustomScript);
            footerCustomScript = escapeHtml(footerCustomScript);
            customCss          = escapeHtml(customCss);

            // Write a Post ajax request

            let types = [
                "header_custom_scripts",
                "footer_custom_scripts",
                "header_custom_css"
            ];

            let typeValues = [
                headerCustomScript,
                footerCustomScript,
                customCss
            ];

            $.ajax({
                url : "{{ route('admin.settings.update') }}",
                type: "POST",
                data: {
                    _token     : '{{ csrf_token() }}',
                    types      : types,
                    typeValues : typeValues,
                },
                dataType : "JSON",
                success: function(response) {                   
                    notifyMe("success",response.message ?? "{{ localize('Saved Successfully') }}");
                    location.reload();
                },
            })
        });



        function escapeHtml (string) {

            return String(string).replace(/[&<>"'`=\/]/g, function (s) {
                return entityMap[s];
            });
        }
        

    </script>
@endsection
