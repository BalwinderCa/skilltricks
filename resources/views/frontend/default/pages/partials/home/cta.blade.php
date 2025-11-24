
<div class="container">
    <div class="bg-dark text-light ptb-100 position-relative overflow-hidden rounded-5 bg-image-cta z-2">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-9">
                <div class="subscribe-info-wrap text-center position-relative z-2">
                    <div class="section-heading">
                        <h4 class="h5 text-danger">{{ systemSettingsLocalization('cta_colored_title') }}</h4>
                        <h2 class="text-light">{{ systemSettingsLocalization('cta_heading_title') }}</h2>
                        <p>{{ systemSettingsLocalization('cta_short_description') }}</p>
                    </div>
                    <div class="form-block-banner mw-60 m-auto mt-5">
                        <a href="{{ getSetting('cta_btn_link') }}"
                            class="btn btn-primary">{{ systemSettingsLocalization('cta_btn_title') }}</a>
                    </div>
                    <ul class="nav justify-content-center list-unstyled mt-4">
                        
                        @php
                            $features = systemSettingsLocalization('cta_features') != null ? explode(',', systemSettingsLocalization('cta_features')) : [];
                        @endphp

                        @foreach ($features as $feature)
                            <li class="nav-item mx-3">
                                <span><i data-feather="check-circle"
                                        class="text-primary me-2"></i>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="bg-circle rounded-circle circle-shape-3 position-absolute bg-primary left-5"></div>
        <div class="bg-circle rounded-circle circle-shape-1 position-absolute bg-warning right-5"></div>
    </div>
</div>
