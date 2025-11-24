@extends('frontend.default.layouts.master')

@section('title')
    {{ localize('Privacy Policy') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('page-header-title')
    {{ $page->collectLocalization('title') }}
@endsection

@section('contents')
    <!--page header-->
    @include('frontend.default.inc.page-header')

    <!--page section start-->
    <section class="about-container extra default-page">
        <div class="container">
            
           {!! $page->collectLocalization('content') !!}
                    
        </div>
    </section>
    
    <!--page section end-->
@endsection
