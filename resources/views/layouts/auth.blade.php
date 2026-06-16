<!DOCTYPE html>

@php
    
    $locale = str_replace('', '-', app()->getLocale());
    $localLang = \App\Models\Language::where('code', $locale)->first();
    if ($localLang == null) {
        $localLang = \App\Models\Language::where('code', 'en')->first();
    }
@endphp

@if ($localLang->is_rtl == 1)
    <html dir="rtl" lang="{{ $locale }}" data-bs-theme="{{getSetting('theme_mode') ?? "light"}}">
@else
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{getSetting('theme_mode') ?? "light"}}">
@endif


<head>
    <!--required meta tags-->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!--favicon icon-->
    <link rel="icon" href="{{ uploadedAsset(getSetting('favicon')) }}" type="image/png" sizes="16x16">

    <!--title-->
    <title>
        @yield('title')
    </title>

    @if (config('custom.enable_google_analytics') == 1)
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('custom.tracking_id') }}"></script>

        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());
            gtag('config', '{{ config('custom.tracking_id') }}');
        </script>
    @endif

    <!--build:css-->
    @include('frontend.default.inc.css')
    <!-- endbuild -->
    
    <!-- recaptcha -->
    @if (getSetting('enable_recaptcha') == 1)
        {!! RecaptchaV3::initJs() !!}
    @endif
    <!-- recaptcha -->


    @php
        echo getSetting('header_custom_css');
    @endphp

    @php
        echo getSetting('header_custom_scripts');
    @endphp
    
</head>

<body>

    <!--preloader start-->
    @if (getSetting('enable_preloader') != '0')
        <div id="preloader" class="bg-light-subtle">
            <div class="preloader-wrap">
                <img src="{{ uploadedAsset(getSetting('navbar_logo_dark')) }}" class="img-fluid">
                <div class="loading-bar"></div>
            </div>
        </div>
    @endif
    <!--preloader end-->

    <!--main content wrapper start-->
    <main class="tt-main-wrapper position-relative z-1">

        @yield('contents')

    </main>

    <!-- scripts -->
    
    <!--build:js-->
    @include('frontend.default.inc.scripts')
    <!--endbuild-->
    @yield('scripts')
    
</body>

</html>
