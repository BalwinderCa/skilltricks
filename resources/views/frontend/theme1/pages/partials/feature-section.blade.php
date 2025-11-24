<section class="feature-section-2 section-space-top section-space-bottom home-7-section-top-border">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center">
                    <span
                        class="rounded-1 bg-primary-key bg-opacity-2 clr-white fs-12 fw-bold px-4 py-2 d-inline-block mb-4 fadeIn_bottom">{{ localize('Our Best Features') }}</span>
                    <h3 class="clr-neutral-90 fw-bold animate-line-3d">{{ localize('We Are More Powerful Than Others') }}
                    </h3>
                </div>
                <ul class="nav nav-tabs feature-2-tabs justify-content-center py-4 px-6 mt-12 fadeIn_bottom"
                    role="tablist">

                    @foreach ($featureCategories as $feature)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $feature->id }}-tab"
                                data-bs-toggle="tab" data-bs-target="#{{ $feature->id }}-tab-pane" type="button"
                                role="tab" aria-controls="{{ $feature->id }}-tab-pane" aria-selected="true">
                                <i class="{{ $feature->icon }}"></i>
                                <span class="nav-link-text">{{ $feature->collectLocalization('name') }}</span>
                            </button>
                        </li>
                    @endforeach

                </ul>
            </div>
        </div>
        <div class="mt-24">
            <div class="tab-content feature-2-tab-content">
                @foreach ($featureCategories as $fe)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $fe->id }}-tab-pane"
                        role="tabpanel" aria-labelledby="feature-1-tab" tabindex="0">
                        <div class="row gy-4 justify-content-center">
                            @if ($fe->feature_category_detail)
                                @foreach ($fe->feature_category_detail as $key => $feature)
                                    <div class="col-xl-3 col-md-6">
                                        <div class="feature-2-card fadein_bottom_36">
                                            <figure class="feature-2-card-thumb mb-0">
                                                <img src="{{uploadedAsset($feature->image) ?? asset('public/frontend/theme1/assets/img/feature-2-1.webp')}}"
                                                    alt="image" class="img-fluid">
                                                <i class="{{$feature->icon ?? 'bi bi-stars'}}"></i>
                                            </figure>
                                            <div class="feature-2-card-content text-center">
                                                <h4 class="fs-16 fw-extrabold clr-neutral-90 title">
                                                    {{ $feature->collectLocalization('title') }}</h4>
                                                <p class="fs-14 mb-0 clr-neutral-80">
                                                    {{ $feature->collectLocalization('short_description') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/feature-2-top.png" alt="image"
        class="img-fluid feature-2-top-shape">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/feature-2-shape-1.png" alt="image"
        class="img-fluid feature-2-shape feature-2-shape-1">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/feature-2-shape-2.png" alt="image"
        class="img-fluid feature-2-shape feature-2-shape-2">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/feature-2-shape-3.png" alt="image"
        class="img-fluid feature-2-shape feature-2-shape-3">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/feature-2-shape-4.png" alt="image"
        class="img-fluid feature-2-shape feature-2-shape-4">
</section>
