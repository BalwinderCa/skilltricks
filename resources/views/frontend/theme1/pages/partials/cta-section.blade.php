<section class="cta-section-4 section-space-sm-top">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-12">
          <div class="cta-wrapper-4 fadeIn_bottom">
            <div class="text-center">
              <span class="rounded-1 bg-primary-key bg-opacity-2 clr-white fs-12 fw-bold px-4 py-2 d-inline-block mb-4 fadeIn_bottom">{{ systemSettingsLocalization('cta_colored_title') }}</span>
              <h3 class="clr-neutral-90 fw-bold animate-line-3d">{{ systemSettingsLocalization('cta_heading_title') }}</h3>
              <p class="mb-0 fs-14 clr-neutral-80 animte-text-from-right">{{ systemSettingsLocalization('cta_short_description') }}</p>
              <a href="{{ getSetting('cta_btn_link') }}" class="link d-inline-flex justify-content-center align-items-center gap-2 py-4 px-6 border border-primary-key :border-primary-30 bg-primary-key :bg-primary-30 rounded-1 fw-bold clr-white mt-8 :arrow-btn fadeIn_bottom">
                <span>{{ systemSettingsLocalization('cta_btn_title') }}</span>
                <i class="bi bi-arrow-right"></i>
              </a>
              <ul class="nav justify-content-center list-unstyled mt-4">
                        
                @php
                    $features = systemSettingsLocalization('cta_features') != null ? explode(',', systemSettingsLocalization('cta_features')) : [];
                @endphp

                @foreach ($features as $feature)
                    <li class="me-3">
                        <span class="clr-white"><i class="bi bi-check-circle-fill text-success me-1"
                                class="text-white"></i>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>
            </div>
            <img src="{{asset('public/frontend/theme1/')}}/assets/img/cta-4-bg.png" alt="image" class="img-fluid cta-wrapper-4-bg w-100 h-100 object-fit-cover">
          </div>
        </div>
      </div>
    </div>
  </section>