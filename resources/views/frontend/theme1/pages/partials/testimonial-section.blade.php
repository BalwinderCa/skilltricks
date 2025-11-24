<section class="testimonial-section-4 section-space-top section-space-sm-bottom">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xxl-7">
                <div class="text-center">
                    <h3 class="clr-neutral-90 fw-bold animate-line-3d">{{ localize('WritBot AI Loved by thinkers') }}
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-10">
        <div class="scroller-x mb-4" data-direction="right" data-speed="slow" data-animated="true">
            <ul class="list list-row gap-4 scroller-x__list">
                @foreach ($oddNumberClientFeedback as $feedback)
                    <li>
                        <div class="testimonial-4-card py-10 px-9">
                            <div class="d-flex flex-wrap gap-4 align-items-center">
                                <img src="{{ uploadedAsset($feedback->image) }}" alt="image"
                                    class="img-fluid w-11 h-11 object-fit-cover rounded-circle">
                                <div>
                                    <h5 class="clr-neutral-90 fs-18 mb-0">{{ $feedback->name }}</h5>
                                    <span
                                        class="fs-14 clr-neutral-80 d-inline-block">{{ $feedback->designation }}</span>
                                </div>
                            </div>
                            <p class="fs-18 clr-neutral-80 mb-0 mt-6">{{ $feedback->review }}</p>
                            <ul class="list list-row gap-2">
                                {{ renderStarRatingFront($feedback->rating) }}
                            </ul>
                        </div>
                    </li>
                @endforeach

            </ul>
        </div>
        <div class="scroller-x mb-4" data-direction="left" data-speed="slow" data-animated="true">
            <ul class="list list-row gap-4 scroller-x__list">
                @foreach ($evenNumberClientFeedback as $feedback)
                    <li>
                        <div class="testimonial-4-card py-10 px-9">
                            <div class="d-flex flex-wrap gap-4 align-items-center">
                                <img src="{{ uploadedAsset($feedback->image) }}" alt="image"
                                    class="img-fluid w-11 h-11 object-fit-cover rounded-circle">
                                <div>
                                    <h5 class="clr-neutral-90 fs-18 mb-0">{{ $feedback->name }}</h5>
                                    <span
                                        class="fs-14 clr-neutral-80 d-inline-block">{{ $feedback->designation }}</span>
                                </div>
                            </div>
                            <p class="fs-18 clr-neutral-80 mb-0 mt-6">{{ $feedback->review }}</p>
                            <ul class="list list-row gap-2">

                                {{ renderStarRatingFront($feedback->rating) }}
                            </ul>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/testimonial-4-shape-circle.png" alt="image"
        class="img-fluid testimonial-4-shape testimonial-4-shape-circle">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/testimonial-4-shape-dots.png" alt="image"
        class="img-fluid testimonial-4-shape testimonial-4-shape-dots">
</section>
