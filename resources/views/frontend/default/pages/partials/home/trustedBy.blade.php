<div class="row">
    <div class="col-12">
        <div class="tt-section-heading text-center mb-5">
            <h2 class="h5">{{ systemSettingsLocalization('homepage_trusted_by_title') }}</h2>
        </div>
        <div class="swiper custom-swiper left-right-arrow"
            data-swiper='{
              "slidesPerView": 4,
              "centeredSlides": false,
              "speed": 1000,
              "loop": false,
              "spaceBetween": 15,
              "breakpoints":{"320":{"slidesPerView":2},  "540":{"slidesPerView":3}, "768":{"slidesPerView":3}, "991":{"slidesPerView":4}, "1200":{"slidesPerView":4}, "1440":{"slidesPerView":5}},
              "navigation": {"nextEl": ".swiper-button-next", "prevEl": ".swiper-button-prev"}
              }'>

            <div class="swiper-wrapper">

                @php
                    $sliders = [];
                    if (getSetting('homepage_trusted_by_images') != null) {
                        $sliders = explode(',', getSetting('homepage_trusted_by_images'));
                    }
                @endphp
                @foreach ($sliders as $slider)
                    <div class="swiper-slide my-3">
                        <img src="{{ uploadedAsset($slider) }}" alt="" class="img-fluid">
                    </div>
                @endforeach

            </div>
            <!--navigation buttons-->
            <div class="tt-slider-indicator">
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
    </div>
</div>
