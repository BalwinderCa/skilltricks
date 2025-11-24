  @extends('frontend.theme1.layouts.master')

  @section('title')
      {{ localize('About Us') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
  @endsection

  @section('content')
      <section class="about-section-4">
          <div class="container">
              <div class="row justify-content-center">
                  <div class="col-xxl-6 col-xl-7">
                      <div class="text-center">
                          <div class="d-inline-flex align-items-center py-2 px-4 bg-info-10 bg-opacity-3 rounded-1">
                              <a href="index-7.html" class="link fs-12 clr-white">{{ localize('Home') }}</a>
                              <span class="fs-12 clr-white">-</span>
                              <span class="fs-12 clr-white">{{ localize('About Us') }}</span>
                          </div>
                          <h2 class="h3 fw-bold clr-neutral-90 mt-4">
                              {{ localize('Your Gateway to Cutting Edge AI Innovations') }}</h2>
                      </div>
                  </div>
              </div>

          </div>
          <img src="{{ asset('public/frontend/theme1/') }}/assets/img/about-section-4-shape-top.png" alt="image"
              class="img-fluid about-section-4-shape about-section-4-shape-top">
          <img src="{{ asset('public/frontend/theme1/') }}/assets/img/about-section-4-shape-left.png" alt="image"
              class="img-fluid about-section-4-shape about-section-4-shape-left">
          <img src="{{ asset('public/frontend/theme1/') }}/assets/img/about-section-4-shape-right.png" alt="image"
              class="img-fluid about-section-4-shape about-section-4-shape-right">
          <img src="{{ asset('public/frontend/theme1/') }}/assets/img/about-section-4-shape-line-left.png" alt="image"
              class="img-fluid about-section-4-shape about-section-4-shape-line-left">
          <img src="{{ asset('public/frontend/theme1/') }}/assets/img/about-section-4-shape-line-right.png" alt="image"
              class="img-fluid about-section-4-shape about-section-4-shape-line-right">
      </section>
      <section class="mission-section">
          <div class="container">
              <div class="mb-xl-5 mb-8">
                  <div class="row">
                      <div class="col-lg-12">
                          <div class="d-flex align-items-center gap-4">
                              <div
                                  class="w-14 h-14 bg-primary-key rounded-circle d-flex align-items-center justify-content-center clr-primary-key fs-24 bg-opacity-2">
                                  <i class="bi bi-gear"></i>
                              </div>
                              <h3 class="h3 fw-bold mb-0 clr-neutral-90">{{ localize('About Us') }}</h3>
                          </div>
                      </div>
                  </div>
              </div>
              <div class="mission-wrapper">
                  <div class="row align-items-center">
                      <div class="col-xl-12">
                          <p class="clr-neutral-80">
                              {!! systemSettingsLocalization('aboutUsContents') !!}
                          </p>
                      </div>

                  </div>
                  <img src="{{ asset('public/frontend/theme1/') }}/assets/img/mission-line-top.png" alt="image"
                      class="img-fluid mission-shape-line mission-shape-line-top">
                  <img src="{{ asset('public/frontend/theme1/') }}/assets/img/mission-line-right.png" alt="image"
                      class="img-fluid mission-shape-line mission-shape-line-right">
              </div>
          </div>
          <img src="{{ asset('public/frontend/theme1/') }}/assets/img/mission-shape-circle.png" alt="image"
              class="img-fluid mission-shape-circle">
      </section>
  @endsection
