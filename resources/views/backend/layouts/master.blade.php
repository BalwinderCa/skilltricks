<!DOCTYPE html>
@php
    $locale = str_replace('_', '-', app()->getLocale()) ?? 'en';
    $localLang = \App\Models\Language::where('code', $locale)->first();
@endphp

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-bs-theme="{{ !empty($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light' }}">

<head>
    <!--required meta tags-->
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @if (!empty($_SERVER['HTTPS']))
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" />
    @endif

    <!--favicon icon-->
    <link rel="shortcut icon" href="{{ uploadedAsset(getSetting('favicon')) }}">

    <!--title-->
    <title>
        @yield('title')

    </title>

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


    <!--build:css-->
    @include('backend.inc.styles')
    <!-- end build -->

    @php
        echo getSetting('header_custom_css');
    @endphp

    @php
        echo getSetting('header_custom_scripts');
    @endphp
    @stack('styles')

    @yield('css')
    @laravelPWA

    <style>
        .preloader-wrap{width: 150px;max-width: 100%;margin:auto;}

    </style>
</head>

<body>
    <input type="hidden" name="url" id="url" value="">
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

    <!--sidebar section start-->
    @include('backend.inc.leftSidebar')
    <!--sidebar section end-->

    <!--main content wrapper start-->
    <main class="tt-main-wrapper bg-secondary-subtle" id="content">

        <!--header section start-->
        @include('backend.inc.navbar')
        <!--header section end-->
        {!! adSense_dashboard_header_bottom_section() !!}
        <!-- Start Content-->
        @yield('contents')
        <!-- container -->


        <!--footer section start-->
        <!--Google AdSense-->
        {!! adSense_dashboard_footer_top_section() !!}
        <!--Google AdSense End-->
        @include('backend.inc.footer')
        <!--footer section end-->

        <!-- media-manager -->
        @include('backend.inc.media-manager.media-manager')

    </main>
    <!--main content wrapper end-->

    <!-- modals -->
    @yield('modals')

    <!-- modals for common layout - website-admin -->
    @yield('modals-common')

    <!-- delete modal -->
    @include('backend.inc.deleteModal')

    <!-- delete modal -->
    @include('backend.inc.deleteAllModal')

    <!-- hide modal -->
    @include('backend.inc.hideModal')

    <!-- approve modal -->
    @include('backend.inc.approveModal')

    <!-- reject modal -->
    @include('backend.inc.rejectModal')

    <!-- reSubmit modal -->
    @include('backend.inc.reSubmitModal')




    <!--build:js-->
    @include('backend.inc.scripts')
    <!--endbuild-->
    <!-- required scripts -->
    <script>
        "use strict";

        // scroll
        function initScrollToChatBottom() {
            var ChatDiv = $(".tt-conversation");
            var height = ChatDiv[0]?.scrollHeight;
            ChatDiv.scrollTop(height);
        }

        // change language
        function changeLocaleLanguage(e) {
            var locale = e.dataset.flag;
            $.post("{{ route('backend.changeLanguage') }}", {
                _token: '{{ csrf_token() }}',
                locale: locale
            }, function(data) {
                location.reload();
            });
        }


        // change currency
        function changeLocaleCurrency(e) {
            var currency_code = e.dataset.currency;
            $.post("{{ route('backend.changeCurrency') }}", {
                _token: '{{ csrf_token() }}',
                currency_code: currency_code
            }, function(data) {
                location.reload();
            });
        }

        // localize data
        function localizeData(langKey) {
            window.location = '{{ url()->current() }}?lang_key=' + langKey + '';
        }

        // ajax toast
        function notifyMe(level, message) {
            if (level == 'danger') {
                level = 'error';
            }
            toastr.options = {
                closeButton: true,
                newestOnTop: false,
                progressBar: true,
                positionClass: "toast-top-center",
                preventDuplicates: false,
                onclick: null,
                showDuration: "3000",
                hideDuration: "1000",
                timeOut: "2500",
                extendedTimeOut: "1000",
                showEasing: "swing",
                hideEasing: "linear",
                showMethod: "fadeIn",
                hideMethod: "fadeOut",
            };
            toastr[level](message);
        }

        //laravel flash toast messages
        @foreach (session('flash_notification', collect())->toArray() as $message)
            notifyMe("{{ $message['level'] }}", "{{ $message['message'] }}");
        @endforeach
    </script>

    <!-- scripts from different pages -->
    @yield('scripts')

    <!-- scripts for common layout - website-admin -->
    @yield('scripts-common')

    @php
        echo getSetting('footer_custom_scripts');
    @endphp

</body>

</html>
