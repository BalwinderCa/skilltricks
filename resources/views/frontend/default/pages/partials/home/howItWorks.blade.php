<section class="tt-how-it-work ptb-100">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="tab-content position-relative" id="pills-tabContent">
                    {{-- 1 --}}
                    <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab"
                        tabindex="0">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-lg-6">
                                <div class="tt-feature-img-wrap">
                                    <img src="{{ uploadedAsset(getSetting('how_it_works_1_image')) }}" alt=""
                                        class="img-fluid">
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="tt-feature-content-wrap">
                                    <h3>{{ systemSettingsLocalization('how_it_works_1_title') }}</h3>
                                    <p>{{ systemSettingsLocalization('how_it_works_1_short_description') }}</p>
                                    @php
                                        $features1 = systemSettingsLocalization('how_it_works_1_features') != null ? explode(',', systemSettingsLocalization('how_it_works_1_features')) : [];
                                    @endphp
                                    <ul class="list-unstyled">
                                        @foreach ($features1 as $feature1)
                                            <li class="pb-1"><i data-feather="check-circle"
                                                    class="icon-14 text-success me-2"></i>{{ $feature1 }}</li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ systemSettingsLocalization('how_it_works_1_btn_link') }}"
                                        class="btn btn-primary mt-4">{{ systemSettingsLocalization('how_it_works_1_btn_title') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- 2 --}}
                    <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab"
                        tabindex="0">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-lg-6">
                                <div class="tt-feature-img-wrap">
                                    <img src="{{ uploadedAsset(getSetting('how_it_works_2_image')) }}" alt=""
                                        class="img-fluid">
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="tt-feature-content-wrap">
                                    <h3>{{ systemSettingsLocalization('how_it_works_2_title') }}</h3>
                                    <p>{{ systemSettingsLocalization('how_it_works_2_short_description') }}</p>

                                    @php
                                        $features2 = systemSettingsLocalization('how_it_works_2_features') != null ? explode(',', systemSettingsLocalization('how_it_works_2_features')) : [];
                                    @endphp
                                    <ul class="list-unstyled">
                                        @foreach ($features2 as $feature2)
                                            <li class="pb-1"><i data-feather="check-circle"
                                                    class="icon-14 text-success me-2"></i>{{ $feature2 }}</li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ systemSettingsLocalization('how_it_works_2_btn_link') }}"
                                        class="btn btn-primary mt-4">{{ systemSettingsLocalization('how_it_works_2_btn_title') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3 --}}
                    <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab"
                        tabindex="0">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-lg-6">
                                <div class="tt-feature-img-wrap">
                                    <img src="{{ uploadedAsset(getSetting('how_it_works_3_image')) }}" alt="feature"
                                        class="img-fluid">
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="tt-feature-content-wrap">
                                    <h3>{{ systemSettingsLocalization('how_it_works_3_title') }}</h3>
                                    <p>{{ systemSettingsLocalization('how_it_works_3_short_description') }}</p>

                                    @php
                                        $features3 = systemSettingsLocalization('how_it_works_3_features') != null ? explode(',', systemSettingsLocalization('how_it_works_3_features')) : [];
                                    @endphp
                                    <ul class="list-unstyled">
                                        @foreach ($features3 as $feature3)
                                            <li class="pb-1"><i data-feather="check-circle"
                                                    class="icon-14 text-success me-2"></i>{{ $feature3 }}</li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ systemSettingsLocalization('how_it_works_3_btn_link') }}"
                                        class="btn btn-primary mt-4">{{ systemSettingsLocalization('how_it_works_3_btn_title') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- 4 --}}
                    <div class="tab-pane fade" id="pills-copy" role="tabpanel" aria-labelledby="pills-copy-tab"
                        tabindex="0">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-lg-6">
                                <div class="tt-feature-img-wrap">
                                    <img src="{{ uploadedAsset(getSetting('how_it_works_4_image')) }}" alt="feature"
                                        class="img-fluid">
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="tt-feature-content-wrap">
                                    <h3>{{ systemSettingsLocalization('how_it_works_4_title') }}</h3>
                                    <p>{{ systemSettingsLocalization('how_it_works_4_short_description') }}</p>

                                    @php
                                        $features4 = systemSettingsLocalization('how_it_works_4_features') != null ? explode(',', systemSettingsLocalization('how_it_works_4_features')) : [];
                                    @endphp
                                    <ul class="list-unstyled">
                                        @foreach ($features4 as $feature4)
                                            <li class="pb-1"><i data-feather="check-circle"
                                                    class="icon-14 text-success me-2"></i>{{ $feature4 }}</li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ systemSettingsLocalization('how_it_works_4_btn_link') }}"
                                        class="btn btn-primary mt-4">{{ systemSettingsLocalization('how_it_works_4_btn_title') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <ul class="nav mt-5 nav-justified tt-tab-nav">
                    {{-- 1 --}}
                    <li class="nav-item text-start mt-3">
                        <a class="nav-link p-0 active" href="" id="pills-home-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
                            aria-selected="true">
                            <div class="tt-step-content pt-3 mx-3">
                                <span class="tt-step-count fw-bold h2 d-block">
                                    01.
                                </span>
                                <h6>{{ systemSettingsLocalization('how_it_works_1_title') }}</h6>
                                <p>{{ systemSettingsLocalization('how_it_works_1_sub_title') }}</p>
                            </div>
                        </a>
                    </li>
                    {{-- 2 --}}
                    <li class="nav-item text-start mt-3">
                        <a class="nav-link p-0" href="#" id="pills-profile-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-profile" type="button" role="tab"
                            aria-controls="pills-profile" aria-selected="false">
                            <div class="tt-step-content pt-3 mx-3">
                                <span class="tt-step-count fw-bold h2 d-block">
                                    02.
                                </span>
                                <h6>{{ systemSettingsLocalization('how_it_works_2_title') }}</h6>
                                <p>{{ systemSettingsLocalization('how_it_works_2_sub_title') }}</p>
                            </div>
                        </a>
                    </li>
                    {{-- 3 --}}
                    <li class="nav-item text-start mt-3">
                        <a class="nav-link p-0" href="#" id="pills-contact-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-contact" type="button" role="tab"
                            aria-controls="pills-contact" aria-selected="false">
                            <div class="tt-step-content pt-3 mx-3">
                                <span class="tt-step-count fw-bold h2 d-block">
                                    03.
                                </span>
                                <h6>{{ systemSettingsLocalization('how_it_works_3_title') }}</h6>
                                <p>{{ systemSettingsLocalization('how_it_works_3_sub_title') }}</p>
                            </div>
                        </a>
                    </li>
                    {{-- 4 --}}
                    <li class="nav-item text-start mt-3">
                        <a class="nav-link p-0" href="#" id="pills-copy-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-copy" type="button" role="tab" aria-controls="pills-copy"
                            aria-selected="false">
                            <div class="tt-step-content pt-3 mx-3">
                                <span class="tt-step-count fw-bold h2 d-block">
                                    04.
                                </span>
                                <h6>{{ systemSettingsLocalization('how_it_works_4_title') }}</h6>
                                <p>{{ systemSettingsLocalization('how_it_works_4_sub_title') }}</p>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>
