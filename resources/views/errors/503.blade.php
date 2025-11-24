@extends('frontend.default.layouts.master')

@section('title')
    503 {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-error-page tt-blog-section pt-5 position-relative rounded-custom-top bg-light-subtle">
        <div class="container">
            <div class="row g-3">
                <div class="content-404 text-center">
                    <img src="{{ staticAsset('frontend/default/assets/img/website/503.png') }}" alt="not found"
                        class="img-fluid h-75 w-25">
                    <h2 class="mt-4">We are under development.</h2>
                    <p class="mb-6">The page you are looking for is
                        temporarily unavailable.</p>
                    <a href="{{ env('APP_URL') }}" class="btn btn-secondary btn-md rounded-1">Back to Home Page</a>
                </div>

            </div>
        </div>
    </section>
@endsection
