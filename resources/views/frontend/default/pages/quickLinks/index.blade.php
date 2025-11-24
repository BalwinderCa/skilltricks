@extends('frontend.default.layouts.master')

@section('title')
    {{ localize('Quick Links') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('page-header-title')
    {{ $page->collectLocalization('title') }}
@endsection

@section('contents')
    <!--page header-->
    @include('frontend.default.inc.page-header')

    <!--page section start-->
    <section class="tt-pricing-section ptb-80 position-relative rounded-custom-top bg-light-subtle">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 col-xl-9 mx-auto">
                    <div class="bg-white px-5 py-4 rounded">
                        {!! $page->collectLocalization('content') !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--page section end-->
@endsection
