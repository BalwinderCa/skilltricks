<!DOCTYPE html>
@php
    $locale = str_replace('_', '-', app()->getLocale()) ?? 'en';
    $localLang = \App\Models\Language::where('code', $locale)->first();
@endphp

@if (@$localLang->is_rtl == 1)
    <html dir="rtl" lang="{{ $locale }}" data-bs-theme="{{ getSetting('theme_mode') ?? 'light' }}">
@else
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
        data-bs-theme="{{ getSetting('theme_mode') ?? 'light' }}">
@endif

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Modern AI Landing HTML Template">
    <meta name="keywords" content="bootstrap 5, saas, landing page">
    <meta name="author" content="Themetags">

    <meta name="robots" content="index, follow">
    <meta itemprop="name" content="{{ getSetting('global_meta_title') }}" />

    <title>
        @yield('title', getSetting('system_title'))
    </title>
    @if (!empty($_SERVER['HTTPS']))
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" />
    @endif
    @laravelPWA
    @if (env('ENABLE_GOOGLE_ANALYTICS') == 1)
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('TRACKING_ID') }}"></script>

        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());
            gtag('config', '{{ env('TRACKING_ID') }}');
        </script>
    @endif

    @yield('meta')

    @if (!isset($blog))

        <meta name="title" content="{{ getSetting('global_meta_title') }}">
        <meta name="description" content="{{ getSetting('global_meta_description') }}">
        <meta name="keywords" content="{{ getSetting('global_meta_keywords') }}">
        <!-- Schema.org markup for Google+ -->

        <meta itemprop="image" content="{{ uploadedAsset(getSetting('global_meta_image')) }}" />


        <!-- Twitter Card data -->
        <meta name="twitter:card" content="product" />
        <meta name="twitter:site" content="@publisher_handle" />
        <meta name="twitter:title" content="{{ getSetting('global_meta_title') }}" />
        <meta name="twitter:description" content="{{ getSetting('global_meta_description') }}" />
        <meta name="twitter:creator" content="@author_handle"/>
        <meta name="twitter:image" content="{{ uploadedAsset(getSetting('global_meta_image')) }}"/>

        <!-- Open Graph data -->
        <meta property="og:title" content="{{ getSetting('global_meta_title') }}" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ route('home') }}" />
        <meta property="og:image" content="{{ uploadedAsset(getSetting('global_meta_image')) }}" />
        <meta property="og:description" content="{{ getSetting('global_meta_description') }}" />
        <meta property="og:site_name" content="{{ env('APP_NAME') }}" /> 
        <meta property="fb:app_id" content="{{ env('FACEBOOK_PIXEL_ID') }}">

    @else
    
        <meta name="title" content="{{ $blog->meta_title }}"> 
        <meta name="description" content="{{ $blog->meta_description }}"> 

        <!-- Schema.org markup for Google+ --> 

        <meta itemprop="image" content="{{ uploadedAsset($blog->meta_img) }}" />

        <!-- Twitter Card data -->
        <meta name="twitter:card" content="product" />
        <meta name="twitter:site" content="@publisher_handle" />
        <meta name="twitter:title" content="{{ $blog->meta_title }}" />
        <meta name="twitter:description" content="{{ $blog->meta_description }}" />
        <meta name="twitter:creator"
            content="@author_handle"/>
        <meta name="twitter:image" content="{{ uploadedAsset($blog->meta_img) }}"/>

        <!-- Open Graph data -->
        <meta property="og:title" content="{{ $blog->meta_title }}" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ route('home') }}" />
        <meta property="og:description" content="{{ $blog->meta_description }}" />
        <meta property="og:image" content="{{ uploadedAsset($blog->meta_img) }}" />
        <meta property="og:site_name" content="{{ env('APP_NAME') }}" /> 
        <meta property="fb:app_id" content="{{ env('FACEBOOK_PIXEL_ID') }}">
    @endif

    <!-- recaptcha -->
    @if (getSetting('enable_recaptcha') == 1)
        {!! RecaptchaV3::initJs() !!}
    @endif
    @if (env('ENABLE_GOOGLE_ADSENSE') == 1) @endif
    <!-- recaptcha -->
    <link rel="icon"
            href="{{ uploadedAsset(getSetting('favicon')) }}" type="image/png">
        @include('frontend.theme1.inc.css')

        @php
        echo getSetting('header_custom_css');
    @endphp

    @php
        echo getSetting('header_custom_scripts');
    @endphp 
    @if (getSetting('enable_google_adsense') == 1 && getSetting('adsense_code_snippet'))
        @php
            echo getSetting('adsense_code_snippet');
        @endphp @endif
        
</head>

    <body class="bg-black">
        <!-- Custom Cursor  -->
        <div class="custom-cursor">
            <div>
                <span></span>
                <svg class="custom-cursor__img" width="119" height="45" viewBox="0 0 119 45" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_1130_4828)">
                        <path
                            d="M10.9627 22.9931L12.2909 16.1687C12.603 14.5633 14.1573 13.0107 15.7632 12.7011L21.5757 11.5778C23.181 11.2681 23.2911 10.4355 21.8214 9.719L2.95694 0.527836C1.48667 -0.188687 0.764833 0.470432 1.34397 1.99934L9.34853 23.1305C9.92766 24.6594 10.6501 24.5984 10.9627 22.9931Z"
                            fill="url(#paint0_linear_1130_4828)" />
                    </g>
                    <rect x="16" y="16.2383" width="103" height="28" rx="4"
                        fill="url(#paint1_linear_1130_4828)" />
                    <defs>
                        <linearGradient id="paint0_linear_1130_4828" x1="-58.0002" y1="-33.2617" x2="25.4638"
                            y2="2.23919" gradientUnits="userSpaceOnUse">
                            <stop offset="1" stop-color="#F49959" />
                        </linearGradient>
                        <linearGradient id="paint1_linear_1130_4828" x1="-53.2743" y1="34.7382" x2="110.992"
                            y2="27.6664" gradientUnits="userSpaceOnUse">
                            <stop offset="1" stop-color="#F49959" />
                        </linearGradient>
                        <clipPath id="clip0_1130_4828">
                            <rect width="24" height="24" fill="white" transform="translate(0 0.238281)" />
                        </clipPath>
                    </defs>
                </svg>
            </div>
            <div></div>
        </div>
        @if (getSetting('enable_cookie_consent') == '1')
        <div class="cookie-alert">
            <div class="p-3 bg-white rounded shadow-lg">
                <div class="mb-3">
                    {!! getSetting('cookie_consent_text') !!}
                </div>
                <button class="btn btn-primary cookie-accept">
                    {{ localize('I Understood') }}
                </button>
            </div>
        </div>
    @endif
        <!-- /Custom Cursor  -->
        @if (getSetting('enable_preloader') != '0')
        <!-- preloader -->
        <div class="preloader">
            <div class="preloader__img">
                <img src="{{ uploadedAsset(getSetting('navbar_logo_dark')) }}" alt="image">
            </div>
        </div>
        @endif
        <!-- preloader End -->
        <!-- Header 5 -->

        {{-- @include('frontend.theme1.inc.top-header') --}}
        @include('frontend.theme1.inc.header')

        @yield('content')

        <!-- /Cta Section -->
        <!-- Footer 7 Section -->
        @include('frontend.theme1.inc.footer')
        <!-- /Footer 7 Section -->
        <div class="tt-scroll-top scroll-to-target" data-target="html">
            <img src="{{ uploadedAsset(getSetting('scrol_to_top_image')) }}"  onerror="this.src = '{{ uploadedAsset(getSetting('scrol_to_top_image')) ?? staticAsset('frontend/theme1/assets/img/home-5-lightning-fill.png') }}';" alt="back to top" class="img-fluid">
        </div>
        <!-- scrpts -->
        @include('frontend.theme1.inc.scripts')
        @php
            echo getSetting('footer_custom_scripts');
        @endphp
        <!--page's scripts-->
        @yield('scripts')
        <!--page's script-->

        <!-- scripts for common layout - website-admin -->
        @yield('scripts-common')

        <!-- modals for common layout - website-admin -->
        @yield('modals-common')


        <!-- delete modal -->
        @include('backend.inc.deleteModal')
         @if (getSetting('dashboard_adSense') == 1)
            <style>
                .google-auto-placed{
                    display: none;
                }
            </style> @endif
        </body>

        </html>
