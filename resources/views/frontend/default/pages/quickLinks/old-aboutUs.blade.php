@extends('frontend.default.layouts.master')

@section('title')
    {{ localize('About Us') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('page-header-title')
    {{ localize('About Us') }}
@endsection


@section('contents')
    <!--page header-->
    @include('frontend.default.inc.page-header')

    <section class="tt-pricing-section pt-80 position-relative rounded-custom-top bg-light-subtle">
        <!--about us content start-->
        <div class="container pb-100">
            <div class="row">
                <div class="col-lg-10 col-xl-10 mx-auto">
                    <div class="bg-white px-5 py-4 rounded">
                        {!! systemSettingsLocalization('aboutUsContents') !!}
                    </div>
                </div>
            </div>
        </div>

        <!--about us content end-->

        <!--trusted client list start-->
        <section class="tt-clients pb-100 bg-light-subtle">
            <div class="container">
                @include('frontend.default.pages.partials.home.trustedBy')
            </div>
        </section>
        <!--trusted client list end-->

        <!--testimonial seciton start-->
        <section class="tt-testimonial-section ptb-100 bg-secondary-subtle">
            @include('frontend.default.pages.partials.home.testimonials')
        </section>
        <!--testimonial seciton end-->

        <!--cta seciton start-->
        <section class="cta-action pb-100 bg-secondary-subtle position-relative">
            @include('frontend.default.pages.partials.home.cta')
        </section>
    </section>
@endsection
