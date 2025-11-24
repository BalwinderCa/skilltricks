<section class="hero-7 section-space-bottom">
    <div class="section-space-top">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h1 class="clr-white text-center position-relative z-index-1 mb-4 animate-line-3d h2 fw-bold">
                        {{ systemSettingsLocalization('hero_title') }} <br>
                        <span class="rotate rotating text-danger">
                            @foreach ($featureCategories as $item)
                                {{ $item->collectLocalization('name') }} {{ !$loop->last ? ',' : '' }}
                            @endforeach
                        </span>
                    </h1>
                    <p class="lead text-center clr-white z-index-1 position-relative mb-8">
                        {{ systemSettingsLocalization('hero_colorful_title') }} </p>
                    <div
                        class="d-flex flex-wrap gap-6 justify-content-center align-items-center position-relative z-index-1 fadeIn_bottom my-5">
                        <a href="{{ getSetting('hero_sub_title_btn_link') ?? route('login') }}"
                            class="link d-inline-flex justify-content-center align-items-center gap-2 py-4 px-6 border border-primary-key :border-primary-30 bg-primary-key :bg-primary-30 rounded-1 fw-bold clr-white :arrow-btn">
                            <span>{{ systemSettingsLocalization('hero_sub_title_btn_text') ?? localize('Get Started Now') }}</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                @if(getSetting('hero_video'))
                <div class="col-lg-7">
                    <div class="tt-video-wrap my-5 position-relative z-1">
                        <video loop="" muted="" autoplay="" class="tt-video">
                            <source src="{{asset(getSetting('hero_video'))}}"
                                type="video/mp4">
                        </video>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="hero-7-main-cirlce">
        <div class="hero-7-main-cirlce-left"></div>
        <div class="hero-7-main-cirlce-right"></div>
    </div>
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/hero-7-top-line.png" alt="image"
        class="img-fluid hero-7-shape hero-7-shape-top-line">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/hero-7-top-dots.png" alt="image"
        class="img-fluid hero-7-shape hero-7-shape-top-dots">

</section>
