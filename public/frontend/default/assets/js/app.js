/*================
 Template Name: Writebot
 Description: Writebot-AI
 Version: 1.0
 Author: https://themeforest.net/user/themetags
=======================*/
// TABLE OF CONTENTS

jQuery(function ($) {
    "use strict";

    // preloader
    $(document).ready(function () {
        $("#preloader").delay(100).fadeOut("fade");
    });

    // dropdown menu hover js
    $("ul.nav li.dropdown").hover(
        function () {
            $(this)
                .find(".dropdown-menu")
                .stop(true, true)
                .delay(100)
                .fadeIn(200);
        },
        function () {
            $(this)
                .find(".dropdown-menu")
                .stop(true, true)
                .delay(100)
                .fadeOut(200);
        }
    );

    // sticky header
    $(window).on("scroll", function () {
        var scroll = $(window).scrollTop();
        if (scroll < 2) {
            $("nav.sticky-header").removeClass("affix");
        } else {
            $("nav.sticky-header").addClass("affix");
        }
    });

    // simplebar js
    Array.from(document.querySelectorAll(".scrollbar")).forEach(
        (el) =>
            new SimpleBar(el, {
                autoHide: false,
                classNames: {
                    // defaults
                    content: "simplebar-content",
                    scrollContent: "simplebar-scroll-content",
                    scrollbar: "simplebar-scrollbar",
                    track: "simplebar-track",
                },
            })
    );

    // feather icon
    feather.replace();

    // dark light mood
    var setDarkMode = (active = false) => {
        var wrapper = document.querySelector(":root");
        if (active) {
            wrapper.setAttribute("data-bs-theme", "dark");
            localStorage.setItem("theme", "dark");
        } else {
            wrapper.setAttribute("data-bs-theme", "light");
            localStorage.setItem("theme", "light");
        }
    };

    var toggleDarkMode = () => {
        var theme = document
            .querySelector(":root")
            .getAttribute("data-bs-theme");
        // If the current theme is "light", we want to activate dark
        setDarkMode(theme === "light");
    };

    var initDarkMode = () => {
        var theme = localStorage.getItem("theme", "light");

        if (theme === "dark") {
            setDarkMode(true);
        } else {
            setDarkMode(false);
        }

        var toggleButton = document.querySelector(".tt-theme-toggle");
        toggleButton?.addEventListener("click", toggleDarkMode);
    };

    initDarkMode();

    // 2. swiper slider for all carousel
    var sliderSelector = ".custom-swiper",
        defaultOptions = {
            breakpointsInverse: true,
            observer: true,
        };
    var jSlider = $(sliderSelector);
    jSlider.each(function (i, slider) {
        var data = $(slider).attr("data-swiper") || {};
        if (data) {
            var dataOptions = JSON.parse(data);
        }
        slider.options = $.extend({}, defaultOptions, dataOptions);
        var swiper = new Swiper(slider, slider.options);

        /* stop on hover */
        if (typeof slider.options.autoplay !== "undefined") {
            $(slider).on(
                "hover",
                function () {
                    swiper.autoplay.stop();
                },
                function () {
                    swiper.autoplay.start();
                }
            );
        }

        /* stop on hover */
        if (
            typeof slider.options.autoplay !== "undefined" &&
            slider.options.autoplay !== false
        ) {
            slider.addEventListener("mouseenter", function () {
                swiper.autoplay.stop();
            });
            slider.addEventListener("mouseleave", function () {
                swiper.autoplay.start();
            });
        }
    });

    // toastr js
    toastr.options = {
        closeButton: true,
        newestOnTop: false,
        progressBar: true,
        positionClass: "toast-top-center",
        preventDuplicates: false,
        onclick: null,
        showDuration: "3000",
        hideDuration: "1000",
        timeOut: "3000",
        extendedTimeOut: "1000",
        showEasing: "swing",
        hideEasing: "linear",
        showMethod: "fadeIn",
        hideMethod: "fadeOut",
    };

    // tooltip
    function initTooltip() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }
    initTooltip();

    // parallax
    var iphone = $(".parallax-item");
    var layer = $(".parallax-element");
    layer.mousemove(function (e) {
        var ivalueX = (e.pageX * -1) / 30;
        var ivalueY = (e.pageY * -1) / 30;
        iphone.css(
            "transform",
            "translate3d(" + ivalueX + "px," + ivalueY + "px, 0)"
        );
    });

    // Pricing Table
    $(".switch-input").on("change", function () {
        if (this.checked) {
            $(".yearly-price").css({
                display: "block",
            });
            $(".monthly-price").css({
                display: "none",
            });
        } else {
            $(".yearly-price").css({
                display: "none",
            });
            $(".monthly-price").css({
                display: "block",
            });
        }
    });
});
