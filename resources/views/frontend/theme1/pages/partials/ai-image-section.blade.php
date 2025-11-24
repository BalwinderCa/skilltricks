<section class="ai-image-section section-space-top section-space-bottom home-7-section-top-border">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center">
                    <span
                        class="rounded-1 bg-primary-key bg-opacity-2 clr-white fs-12 fw-bold px-4 py-2 d-inline-block mb-4 fadeIn_bottom">{{ getSetting('system_title') }}</span>
                    <h3 class="clr-neutral-90 fw-bold animate-line-3d">{{ localize('AI Images Generate') }}</h3>
                </div>
            </div>
        </div>
        <div class="mt-10">
            <div class="row justify-content-center gy-4">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="feature-2-card style-two fadein_bottom_38">
                        <figure class="feature-2-card-thumb mb-0">
                            <img src="{{ uploadedAsset(getSetting('ai_image_section_1_image')) }}" alt="image"
                                class="img-fluid">
                            <i class="bi bi-stars"></i>
                        </figure>
                        <div class="feature-2-card-content text-center">
                            <h4 class="fs-16 fw-extrabold clr-neutral-90 reveal-text">
                                {{ systemSettingsLocalization('ai_image_section_1_title') }}</h4>
                            <p class="fs-14 mb-0 animate-text-from-bottom clr-neutral-80">
                                {{ systemSettingsLocalization('ai_image_section_1_short_description') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-4 col-md-6">
                    <div class="feature-2-card style-big style-two fadein_bottom_38">
                        <figure class="feature-2-card-thumb mb-0">
                            <img src="{{ uploadedAsset(getSetting('ai_image_section_2_image')) }}" alt="image"
                                class="img-fluid">
                            <i class="bi bi-stars"></i>
                        </figure>
                        <div class="feature-2-card-content text-center">
                            <h4 class="fs-16 fw-extrabold clr-neutral-90 reveal-text">
                                {{ systemSettingsLocalization('ai_image_section_2_title') }}</h4>
                            <p class="fs-14 mb-0 max-text-40 mx-auto animate-text-from-bottom clr-neutral-80">
                                {{ systemSettingsLocalization('ai_image_section_2_short_description') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="feature-2-card style-two fadein_bottom_38">
                        <figure class="feature-2-card-thumb mb-0">
                            <img src="{{ uploadedAsset(getSetting('ai_image_section_3_image')) }}" alt="image"
                                class="img-fluid">
                            <i class="bi bi-stars"></i>
                        </figure>
                        <div class="feature-2-card-content text-center">
                            <h4 class="fs-16 fw-extrabold clr-neutral-90 reveal-text">
                                {{ systemSettingsLocalization('ai_image_section_3_title') }}</h4>
                            <p class="fs-14 mb-0 animate-text-from-bottom clr-neutral-80">
                                {{ systemSettingsLocalization('ai_image_section_3_short_description') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ai-image-slider-wrapper">
                        <div class="scroller-x mb-4" data-direction="right" data-speed="slow" data-animated="true">
                            <ul class="list list-row gap-4 scroller-x__list">
                                @foreach ($projectImages as $project)
                                    @if($loop->even)
                                    @php
                                        $image_path =
                                            $project->storage_type == 'aws'
                                                ? $project->content
                                                : staticAsset($project->content);
                                    @endphp
                                    <li>
                                        <img src="{{ $image_path }}" alt="image" class="img-fluid rounded-1"
                                            height="192" width="122">
                                    </li>
                                    @endif
                                @endforeach

                            </ul>
                        </div>
                        <div class="scroller-x mb-4" data-direction="left" data-speed="slow" data-animated="true">
                            <ul class="list list-row gap-4 scroller-x__list">
                                @foreach ($projectImages as $project)
                                    @if($loop->odd)
                                    @php
                                        $image_path =
                                            $project->storage_type == 'aws'
                                                ? $project->content
                                                : staticAsset($project->content);
                                    @endphp
                                    <li>
                                        <img src="{{ $image_path }}" alt="image" class="img-fluid rounded-1"
                                            height="192" width="122">
                                    </li>
                                    @endif
                                @endforeach

                            </ul>
                        </div>
                        <div class="scroller-x mb-4" data-direction="right" data-speed="slow" data-animated="true">
                            <ul class="list list-row gap-4 scroller-x__list">
                                @foreach ($randomPublishedImages as $project)
                                @php
                                    $image_path =
                                        $project->storage_type == 'aws'
                                            ? $project->content
                                            : staticAsset($project->content);
                                @endphp
                                <li>
                                    <img src="{{ $image_path }}" alt="image" class="img-fluid rounded-1"
                                        height="192" width="122">
                                </li>
                            @endforeach

                            </ul>
                        </div>
                        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/ai-image-slide-overlay.png"
                            alt="image" class="img-fluid ai-image-slide-overlay">
                        <div class="radar-logo-wrapper">
                            <div class="radar">
                                <div class="animated-text-wrapper">
                                    <p class="cd-headline slide mb-0">
                                        <span class="cd-words-wrapper">
                                            @foreach ($featureCategories as $item)
                                                <b class="{{ $loop->first ? 'is-visible' : '' }}">
                                                    {{ $item->collectLocalization('name') }}</b>
                                            @endforeach
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="logo-wrapper d-flex justify-content-center align-items-center">
                                <img src="{{ uploadedAsset(getSetting('favicon')) ?? asset('public/frontend/theme1/assets/img/hero-7-logo.png') }}"
                                    alt="image" class="img-fluid hero-7-logo">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/ai-image-shape-top.png" alt="image"
        class="img-fluid ai-image-section-shape ai-image-section-shape-top">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/ai-image-shape-left.png" alt="image"
        class="img-fluid ai-image-section-shape ai-image-section-shape-left">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/ai-image-shape-right.png" alt="image"
        class="img-fluid ai-image-section-shape ai-image-section-shape-right">
</section>
