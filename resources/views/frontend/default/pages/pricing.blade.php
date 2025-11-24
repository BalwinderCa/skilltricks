@extends('frontend.default.layouts.master')

@section('title')
    {{ localize('Pricing') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('page-header-title')
    {{ localize('Our Pricing') }}
@endsection

@section('contents')
    <!--page header-->
    @include('frontend.default.inc.page-header')

    <!--our pricing plan start-->
    <section class="tt-pricing-section ptb-100 position-relative rounded-custom-top bg-light-subtle">
        @include('frontend.default.pages.partials.home.pricing')
    </section>
    <!--our pricing plan end-->

    <!--faq section start-->
    <section class="tt-faq ptb-100 bg-secondary-subtle">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="tt-section-heading text-center mb-5">
                        <h2>{{ localize('Frequently Asked Questions') }}</h2>
                        <p>{{ localize('Everything you need to know about the product and billing.') }}</p>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="accordion" id="accordionFaq">
                        @if (!empty($faqs))
                            @foreach ($faqs as $faq)
                                <div class="card accordion-item {{ $loop->iteration == 1 ? 'active' : '' }}">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse"
                                            data-bs-target="#accordionFaq{{ $faq->id }}"
                                            aria-expanded="{{ $loop->iteration == 1 ? 'true' : 'false' }}"
                                            aria-controls="accordionFaq{{ $faq->id }}">
                                            {{ $faq->collectLocalization('question') }}
                                        </button>
                                    </h2>

                                    <div id="accordionFaq{{ $faq->id }}"
                                        class="accordion-collapse collapse {{ $loop->iteration == 1 ? 'show' : '' }}"
                                        data-bs-parent="#accordionFaq">
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
    </section>
    <!--faq section end-->

    <!--cta start-->
    <section class="cta-subscribe pb-100 bg-secondary-subtle">
        @include('frontend.default.pages.partials.home.cta')
    </section>
@endsection
