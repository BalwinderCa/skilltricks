<section class="ai-application-section section-space-sm-top section-space-bottom">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-xxl-5">
                <div class="text-center">
                    <span
                        class="rounded-1 bg-primary-key bg-opacity-2 clr-white fs-12 fw-bold px-4 py-2 d-inline-block mb-4 fadeIn_bottom">{{ getSetting('system_title') }}</span>
                    <h3 class="clr-neutral-90 fw-bold animate-line-3d">
                        {{ localize('One Platform Multiple AI Applications') }}</h3>
                </div>
            </div>
        </div>
        <div class="mt-10 ai-application-item-wrapper">
            <div class="row gy-4 align-items-center justify-content-center">
                <div class="col-sm-6 col-lg-4 col-xxl-3">
                    <div class="ai-app-item text-center fadeIn_bottom">
                        <img src="{{ uploadedAsset(getSetting('feature_integration_1_image')) }}" alt="image"
                            class="img-fluid">
                        <h4 class="fs-18 fw-bold clr-neutral-90 mt-4 mb-3">
                            {{ systemSettingsLocalization('feature_integration_1_title') }}</h4>
                        <p class="mb-0 clr-neutral-80">
                            {{ systemSettingsLocalization('feature_integration_1_short_description') }}</p>
                    </div>
                    <div class="ai-app-item text-center fadeIn_bottom">
                        <img src="{{ uploadedAsset(getSetting('feature_integration_3_image')) }}" alt="image"
                            class="img-fluid">
                        <h4 class="fs-18 fw-bold clr-neutral-90 mt-4 mb-3">
                            {{ systemSettingsLocalization('feature_integration_3_title') }}</h4>
                        <p class="mb-0 clr-neutral-80">
                            {{ systemSettingsLocalization('feature_integration_3_short_description') }}</p>
                    </div>
                </div>
                <div class="d-none d-lg-block col-lg-4 col-xxl-3 fadeIn_bottom">
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
                            <img src="{{ uploadedAsset(getSetting('favicon')) ?? asset('public/frontend/theme1/assets/img/hero-7-logo.png') }}" alt="image"
                                class="img-fluid hero-7-logo">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 col-xxl-3">
                    <div class="ai-app-item text-center fadeIn_bottom">
                        <img src="{{ uploadedAsset(getSetting('feature_integration_2_image')) }}" alt="image"
                            class="img-fluid">
                        <h4 class="fs-18 fw-bold clr-neutral-90 mt-4 mb-3">
                            {{ systemSettingsLocalization('feature_integration_2_title') }}</h4>
                        <p class="mb-0 clr-neutral-80">
                            {{ systemSettingsLocalization('feature_integration_2_short_description') }}</p>
                    </div>
                    <div class="ai-app-item text-center fadeIn_bottom">
                        <img src="{{ uploadedAsset(getSetting('feature_integration_4_image')) }}" alt="image"
                            class="img-fluid">
                        <h4 class="fs-18 fw-bold clr-neutral-90 mt-4 mb-3">
                            {{ systemSettingsLocalization('feature_integration_4_title') }}</h4>
                        <p class="mb-0 clr-neutral-80">
                            {{ systemSettingsLocalization('feature_integration_4_short_description') }}</p>
                    </div>
                </div>
            </div>
            <img src="{{ asset('public/frontend/theme1/') }}/assets/img/ai-application-shape-center.png" alt="image"
                class="img-fluid ai-application-shape-center">
        </div>
    </div>
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/ai-application-shape-line-left.png" alt="image"
        class="img-fluid ai-application-shape ai-application-shape-left">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/ai-application-shape-line-right.png" alt="image"
        class="img-fluid ai-application-shape ai-application-shape-right">
</section>
