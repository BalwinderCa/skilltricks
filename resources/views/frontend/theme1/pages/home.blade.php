@extends('frontend.theme1.layouts.master')

@section('title')
    {{ localize('Home') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('content')
    <!-- /Header 5 -->
    <!-- Hero 7 -->
    @include('frontend.theme1.pages.partials.hero-section')
    <!-- /Hero 7 -->
    <!-- Feature section -->
    @include('frontend.theme1.pages.partials.feature-section')
    <!-- /Feature section -->
    <!-- AI Tools Section -->
    @include('frontend.theme1.pages.partials.ai-tools-section')
    <!-- /AI Tools Section -->
    <!-- AI Template Section -->
    @include('frontend.theme1.pages.partials.ai-templates-section')
    <!-- /AI Template Section -->
    <!-- AI Image Section -->
    @include('frontend.theme1.pages.partials.ai-image-section')

    <!-- /AI Image Section -->
    <!-- Pricing Section -->
    @include('frontend.theme1.pages.partials.pricing-section')

    <!-- /Pricing Section -->
    <!-- AI Application Section -->
    @include('frontend.theme1.pages.partials.ai-application-section')

    <!-- /AI Application Section -->
    <!-- Testimonial Section -->
    @include('frontend.theme1.pages.partials.testimonial-section')

    <!-- /Testimonial Section -->
    <!-- Cta Section -->
    @include('frontend.theme1.pages.partials.cta-section')
@endsection
