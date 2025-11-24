@extends('frontend.theme1.layouts.master')

@section('title')
    {{ localize('FAQ') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('content')
    <section class="breadcrumb-section">
        <div class="breadcrumb-section-inner">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xxl-5 col-xl-8">
                        <div class="text-center">
                            <div class="d-inline-flex align-items-center py-2 px-4 bg-info-10 bg-opacity-3 rounded-1">
                                <span class="fs-12 clr-white">{{ getSetting('system_title') }}</span>
                            </div>
                            <h2 class="h3 fw-bold clr-neutral-90 mt-4">{{ localize('Questions? We Have Answers.') }}</h2>
                        </div>
                    </div>
                </div>
                <div class="mt-10">
                    <div class="row">
                       
                        <div class="col-lg-12">
                            <div class="tab-content">
                                
                                <div class="tab-pane fade show active" id="aiImgGenerateTab">
                                    <div class="accordion custom-accordion custom-accordion--faq mb-8" id="faqAccordionOne">
                                        @if (!empty($faqs))
                                        @foreach ($faqs as $faq)
                                      <div class="accordion-item border-bottom-0 border-start-0 border-end-0">
                                        <h2 class="accordion-header">
                                          <button class="accordion-button fs-20 fw-bold {{!$loop->first ? 'collapsed' : ''}}" type="button" data-bs-toggle="collapse" data-bs-target="#faqAccordion{{$faq->id}}" aria-expanded="true"> 
                                            {{ $faq->collectLocalization('question') }}
                                        </button>
                                        </h2>
                                        <div id="faqAccordion{{$faq->id}}" class="accordion-collapse collapse {{$loop->first ? 'show':''}}" data-bs-parent="#faqAccordionOne">
                                          <div class="accordion-body">
                                            {{ $faq->collectLocalization('answer') }}
                                          </div>
                                        </div>
                                      </div>
                                      @endforeach
                                    @endif
                                    </div>
                                  </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-top.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-top">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-left.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-left">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-right.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-right">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-line-left.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-line-left">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-line-right.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-line-right">
    </section>
@endsection
