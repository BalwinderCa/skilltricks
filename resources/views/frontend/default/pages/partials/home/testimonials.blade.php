@php
    $client_feedback = [];
    if (getSetting('client_feedback') != null) {
        $client_feedback = json_decode(getSetting('client_feedback'));
        $lang = App::getLocale();
        $generalSetupLocalization = \App\Models\GeneralSetupLocalization::where('lang_key', $lang)
            ->where('entity', 'client_feedback')
            ->first();
        if ($generalSetupLocalization) {
            $client_feedback = json_decode($generalSetupLocalization->value);
        }
    }
@endphp
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="tt-section-heading text-center mb-5">
                <h2 class="fw-bold fs-1">{{ localize('What Customers Saying') }}<br>
                    <span class="tt-text-gradient-primary">{{ localize('About Us') }}</span>
                </h2>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="swiper custom-swiper left-right-arrow"
                data-swiper='{
        "slidesPerView": 4,
        "centeredSlides": false,
        "speed": 1000,
        "loop": false,
        "spaceBetween": 15,
        "breakpoints":{"320":{"slidesPerView":1},  "540":{"slidesPerView":1}, "768":{"slidesPerView":2}, "991":{"slidesPerView":2}, "1200":{"slidesPerView":3}, "1440":{"slidesPerView":3}},
        "navigation": {"nextEl": ".swiper-button-next", "prevEl": ".swiper-button-prev"}
        }'>

                <div class="swiper-wrapper">

                    @foreach ($client_feedback as $feedback)
                        <div class="swiper-slide">
                            <div class="tt-testimonial-single tt-corner-shape card border-0 flex-coloumn h-100 p-4">
                                <div class="w-100">
                                    <ul class="tt-testimonial-ratting d-flex list-unstyled mb-1">
                                        {{ renderStarRatingFront($feedback->rating) }}
                                    </ul>
                                    <h3 class="h6">{{ $feedback->heading }}</h3>
                                    <p>{{ $feedback->review }}</p>
                                </div>
                                <div class="tt-testimonial-author position-relative mt-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-md me-2 flex-shrink-0">
                                            <img class="rounded-circle" src="{{ uploadedAsset($feedback->image) }}"
                                                alt="" />
                                        </div>
                                        <div class="me-2 flex-1">
                                            <h6 class="mb-0 fs-base">{{ $feedback->name }}</h6>
                                            <small class="text-muted">{{ $feedback->designation }}</small>
                                        </div>
                                    </div>
                                    <img src="{{ staticAsset('frontend/default/assets/img/website/quotes.svg') }}"
                                        width="50" alt="quotes" class="position-absolute quote-image">
                                </div>
                            </div>
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
</div>
