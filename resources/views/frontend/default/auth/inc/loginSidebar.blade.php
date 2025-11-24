<div class="tt-login-blank position-relative overflow-hidden w-100 z-2 text-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-7 col-lg-10 col-md-12">
                <div class="testimonial-tab-slider-wrap text-center mt-5">

                    <h1 class="fw-bold text-white display-6">
                        {!! getSetting('login_leftbar_title') !!}<span class="tt-text-gradient-primary">
                            {{ getSetting('login_leftbar_colored_title') }}</span>
                    </h1>

                    @php
                        $client_feedback = [];
                        if (getSetting('client_feedback') != null) {
                            $client_feedback = json_decode(getSetting('client_feedback'));
                        }
                    @endphp

                    <div class="swiper custom-swiper mt-4"
                        data-swiper='{
                        "slidesPerView": 4,
                        "centeredSlides": false,
                        "speed": 1000,
                        "loop": false,
                        "autoplay": {"delay":1000},
                        "spaceBetween": 15,
                        "breakpoints":{"320":{"slidesPerView":1},  "540":{"slidesPerView":1}, "768":{"slidesPerView":1}, "991":{"slidesPerView":1}, "1200":{"slidesPerView":1}, "1440":{"slidesPerView":1}},
                        "navigation": {"nextEl": ".swiper-pagination", "prevEl": ".swiper-button-prev"},
                        "pagination": { "el": ".swiper-pagination", "clickable": "true"}
                        }'>

                        <div class="swiper-wrapper">
                            @foreach ($client_feedback as $feedback)
                                <div class="swiper-slide">
                                    <div class="tt-single-testimonial">
                                        <blockquote class="blockquote">
                                            <p class="lead">{{ $feedback->review }}</p>
                                        </blockquote>
                                        <ul
                                            class="tt-testimonial-ratting d-flex justify-content-center list-unstyled mb-1">
                                            {{ renderStarRatingFront($feedback->rating) }}
                                        </ul>
                                        <div class="author-info mt-4">
                                            <span class="h6 text-light">{{ $feedback->name }}</span>
                                            <span class="d-block">{{ $feedback->designation }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <!--indicator buttons-->
                        <div class="tt-swiper-indicator mt-5">
                            <div class="swiper-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
