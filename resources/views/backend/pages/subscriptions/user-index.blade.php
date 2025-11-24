@extends('backend.layouts.master')

@section('title')
    {{ localize('Subscription Packages') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section py-4">
        <div class="container">
            <!-- packages list -->
            <div class="row g-3 justify-content-center subscription-package-wrapper mt-4">
                @include('frontend.default.pages.partials.home.pricing')
            </div>

        </div>
    </section>
@endsection
