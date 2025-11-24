@extends('frontend.default.layouts.master')

@section('title')
    {{ localize('Testimonials') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('page-header-title')
    {{ localize('Testimonials') }}
@endsection

@section('contents')
    <!--page header-->
    @include('frontend.default.inc.page-header')

    <!--trusted client list start-->
    <section class="tt-clients bg-secondary-subtle">
        <div class="container">
            <div class="tt-client-wrap bg-white rounded-custom-top rounded-bottom p-5 ptb-60 shadow">
                @include('frontend.default.pages.partials.home.trustedBy')
            </div>
        </div>
    </section>
    <!--trusted client list end-->

    <!--testimonial seciton start-->
    <section class="tt-testimonial-section ptb-80 bg-secondary-subtle">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="grid-wrapper">
                        @include('frontend.default.pages.partials.home.testimonials')
                    </div>
                </div>
            </div>
    </section>
    <!--testimonial seciton end-->

    <!--cta start-->
    <section class="cta-subscribe pb-100  bg-secondary-subtle">
        @include('frontend.default.pages.partials.home.cta')
    </section>
@endsection
