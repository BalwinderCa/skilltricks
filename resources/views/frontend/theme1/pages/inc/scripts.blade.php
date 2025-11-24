<script src="{{ staticAsset('frontend/default/assets/js/vendors/jquery-3.7.0.min.js') }}"></script>
<script src="{{ staticAsset('frontend/default/assets/js/vendors/bootstrap.bundle.min.js') }}"></script>
<script src="{{ staticAsset('frontend/default/assets/js/vendors/swiper-bundle.min.js') }}"></script>
<script src="{{ staticAsset('frontend/default/assets/js/vendors/simplebar.min.js') }}"></script>
<script src="{{ staticAsset('frontend/default/assets/js/vendors/feather.min.js') }}"></script>
<script src="{{ staticAsset('frontend/default/assets/js/vendors/parallax.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/highlight.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/highlight-numbers.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/summernote-lite.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/magnific-popup.min.js') }}"></script>
<script src="{{ staticAsset('frontend/common/js/toastr.min.js') }}"></script>
<script src="{{ staticAsset('frontend/default/assets/js/app.js') }}"></script>

<script>
    "use strict"

    // tooltip
    $(function() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });

    // on click delete confirmation -- outside footable
    function confirmDelete(thisLink) {
        var url = $(thisLink).data("href");
        $("#delete-modal").modal("show");
        $("#delete-link").attr("href", url);
    }

    // change language
    function changeLocaleLanguage(e) {
        var locale = e.dataset.flag;
        $.post("{{ route('backend.changeLanguage') }}", {
            _token: '{{ csrf_token() }}',
            locale: locale
        }, function(data) {
            setTimeout(() => {
                location.reload();
            }, 300);
        });
    }

    // change currency
    function changeLocaleCurrency(e) {
        var currency_code = e.dataset.currency;
        $.post("{{ route('backend.changeCurrency') }}", {
            _token: '{{ csrf_token() }}',
            currency_code: currency_code
        }, function(data) {
            setTimeout(() => {
                location.reload();
            }, 300);
        });
    }

    // ajax toast 
    function notifyMe(level, message) {
        if (level == 'danger') {
            level = 'error';
        }
        toastr.options = {
            "timeOut": "5000",
            "closeButton": true,
            "positionClass": "toast-top-center",
        };
        toastr[level](message);
    }

    // laravel flash as toast messages
    @foreach (session('flash_notification', collect())->toArray() as $message)
        notifyMe("{{ $message['level'] }}", "{{ $message['message'] }}");
    @endforeach


    @if (!empty($errors->all()))
        @foreach ($errors->all() as $error)
            notifyMe("error", '{{ $error }}')
        @endforeach
    @endif

    $(window).on("scroll", function() {
        // checks if window is scrolled more than 500px, adds/removes solid class
        if ($(this).scrollTop() > 0) {
            $(".scroll-to-target").addClass("open");
        } else {
            $(".scroll-to-target").removeClass("open");
        }
        // checks if window is scrolled more than 500px, adds/removes top to target class
        if ($(this).scrollTop() > 500) {
            $(".scroll-to-target").addClass("open");
        } else {
            $(".scroll-to-target").removeClass("open");
        }
    });
    if ($(".scroll-to-target").length) {
        $(".scroll-to-target").on("click", function() {
            var target = $(this).attr("data-target");
            // animate
            $("html, body").animate({
                scrollTop: $(target).offset().top
            }, 500);
        });
    }

    // getPackageTemplates
    function getPackageTemplates(package_id) {
        $('.package-template-contents').empty();
        var data = {
            package_id
        }
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'GET',
            url: '{{ route('subscriptions.getPackageTemplates') }}',
            data: data,
            success: function(data) {
                $('.template-please-wait').addClass('d-none');
                $('.package-template-contents').html(data);
            },
            error: function(data) {
                notifyMe('error', '{{ localize('Something went wrong') }}');
            }
        });
    }

    // get & set cookie 
    function getCookie(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) === 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }

    function acceptCookie() {
        if (!getCookie("acceptCookies")) {
            $(".cookie-alert").addClass("show");
        }
        $(".cookie-accept").on("click", function() {
            setCookie("acceptCookies", true, 60);
            $(".cookie-alert").removeClass("show");
        });
    }
    acceptCookie();
</script>

@include('backend.pages.templates.inc.template-scripts')
