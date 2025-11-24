<div class="row g-3">
    <div class="col-12">
        <div class="card text-light rounded-4 border-0 {{ isset($mt) ? 'mt--50' : '' }} overflow-hidden">
            <img src="{{ uploadedAsset(getSetting('feature_image_1_image')) }}" class="card-img rounded-4"
                alt="about image">

            @if (getSetting('enable_built_in_templates') != '0')
                <div
                    class="card-img-overlay m-4 right-0 left-auto tt-img-overlay tt-glass-effect rounded-4 p-3 p-lg-5 animated-card z-2">
                    <div class="position-relative">
                        <h4 class="card-title">{!! systemSettingsLocalization('feature_image_1_title') !!}</h4>
                        <p class="card-text">{{ systemSettingsLocalization('feature_image_1_short_description') }}</p>
                        <ul class="list-unstyled d-flex flex-wrap tt-two-col mt-4 mb-4">
                            <li class="py-2"><i data-feather="book-open"
                                    class="me-2"></i>{{ localize('Blog Content') }}
                            </li>
                            <li class="py-2"><i data-feather="mail"
                                    class="me-2"></i>{{ localize('Email Template') }}
                            </li>
                            <li class="py-2"><i data-feather="share-2"
                                    class="me-2"></i>{{ localize('Social Media') }}
                            </li>
                            <li class="py-2"><i data-feather="video"
                                    class="me-2"></i>{{ localize('Video Content') }}
                            </li>
                            <li class="py-2"><i data-feather="monitor"
                                    class="me-2"></i>{{ localize('Website Content') }}
                            </li>
                            <li class="py-2"><i data-feather="smile" class="me-2"></i>{{ localize('Fun & Quote') }}
                            </li>
                            <li class="py-2"><i data-feather="code"
                                    class="me-2"></i>{{ localize('Medium Content') }}
                            </li>
                            <li class="py-2"><i data-feather="film" class="me-2"></i>{{ localize('Tik Tok') }}
                            </li>
                            <li class="py-2"><i data-feather="instagram"
                                    class="me-2"></i>{{ localize('Instagram') }}
                            </li>
                            <li class="py-2"><i data-feather="gift"
                                    class="me-2"></i>{{ localize('Success Story') }}
                            </li>
                        </ul>
                    </div>
                </div>
            @endif
            <div class="bg-circle rounded-circle tt-circle-shape-1 position-absolute bg-warning"></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card text-light rounded-4 border-0">
            <img src="{{ uploadedAsset(getSetting('feature_image_2_image')) }}" class="card-img rounded-4"
                alt="about image">
            <div class="card-img-overlay m-3 top-auto bottom-0 tt-glass-effect rounded-4 p-3 animated-card">
                <h5 class="card-title mb-0">{{ systemSettingsLocalization('feature_image_2_title') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card text-light rounded-4 border-0">
            <img src="{{ uploadedAsset(getSetting('feature_image_3_image')) }}" class="card-img rounded-4"
                alt="about image">
            <div class="card-img-overlay m-3 top-auto bottom-0 tt-glass-effect rounded-4 p-3 animated-card">
                <h5 class="card-title mb-0">{{ systemSettingsLocalization('feature_image_3_title') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card text-light rounded-4 border-0">
            <img src="{{ uploadedAsset(getSetting('feature_image_4_image')) }}" class="card-img rounded-4"
                alt="about image">
            <div class="card-img-overlay m-3 top-auto bottom-0 tt-glass-effect rounded-4 p-3 animated-card">
                <h5 class="card-title mb-0">{{ systemSettingsLocalization('feature_image_4_title') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card text-light rounded-4 border-0">
            <img src="{{ uploadedAsset(getSetting('feature_image_5_image')) }}" class="card-img rounded-4"
                alt="about image">
            <div class="card-img-overlay m-3 top-auto bottom-0 tt-glass-effect rounded-4 p-3 animated-card">
                <h5 class="card-title mb-0">{{ systemSettingsLocalization('feature_image_5_title') }}</h5>
            </div>
        </div>
    </div>
</div>
