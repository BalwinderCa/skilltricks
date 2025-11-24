@extends('frontend.default.layouts.master')

@section('title')
    {{ localize('Home') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <!--hero section start-->
    @include('frontend.default.pages.partials.home.hero')
    <!--hero section end-->

    <!--trusted client list start-->
    <div class="container">
        <section class="tt-clients">
            <div class="tt-client-wrap bg-white rounded-custom-top rounded-bottom p-5 ptb-60 shadow">
                @include('frontend.default.pages.partials.home.trustedBy')
            </div>
        </section>
    </div>
    <!--trusted client list end-->
	<!--Google AdSense-->
	{!! adSense_bottom_trusted_by() !!}
	<!--Google AdSense End-->
    <!--how it work section start-->
    @if (getSetting('enable_built_in_templates') != '0')
        @include('frontend.default.pages.partials.home.howItWorks')
    @endif
    <!--how it work section end-->

    <!--best features section start-->
    <section class="tt-best-features pb-100 {{ getSetting('enable_built_in_templates') == '0' ? 'pt-100' : '' }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="tt-section-heading text-center mb-5">
                        <h2 class="fw-bold fs-1">{{ localize('Our Best Features') }}
                            <div class="tt-text-gradient-primary">{{ localize('We are more powerful than others') }}
                            </div>
                        </h2>
                    </div>
                </div>
            </div>
            <!--Google AdSense-->
            {!! adSense_top_best_feature() !!}
            <!--Google AdSense End-->
            @include('frontend.default.pages.partials.home.features')
        </div>
    </section>
    <!--best features section end-->
            <!--Google AdSense-->
            {!! adSense_top_template_section() !!}
            <!--Google AdSense End-->
    <!--templates section start-->
    @if (getSetting('enable_built_in_templates') != '0')
        @include('frontend.default.pages.partials.home.templates')
    @endif
    <!--templates section end-->


    <!--testimonial seciton start-->
    <section class="tt-testimonial-section ptb-100 bg-secondary-subtle">
        @include('frontend.default.pages.partials.home.testimonials')
    </section>
    <!--testimonial seciton end-->
    <!--Google AdSense-->
    {!! adSense_top_subscription_package() !!}
    <!--Google AdSense End-->
    <!--our pricing plan start-->
    <section class="tt-pricing-section pt-100">
        @include('frontend.default.pages.partials.home.pricing')
    </section>
    <!--our pricing plan end-->
    
    <!--Google AdSense-->
    {!! adSense_top_trail_banner_section() !!}
    <!--Google AdSense End-->
    <!--cta start-->
    <section class="cta-subscribe ptb-100">
        @include('frontend.default.pages.partials.home.cta')
    </section>
    <!--cta end-->
@endsection
