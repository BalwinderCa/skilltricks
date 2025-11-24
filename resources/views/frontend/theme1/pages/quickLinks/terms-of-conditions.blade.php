@extends('frontend.theme1.layouts.master')

@section('title')
    {{ localize('Terms of Condition') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('content')
<section class="breadcrumb-section">
    <div class="breadcrumb-section-inner">
      <div class="container">
        <div class="row g-4 justify-content-center">
          <div class="col-xxl-5 col-xl-8">
            <div class="text-center">
              <div class="d-inline-flex align-items-center py-2 px-4 bg-info-10 bg-opacity-3 rounded-1">
                <span class="fs-12 clr-white">{{ getSetting('system_title') }}</span>
              </div>
              <h2 class="h3 fw-bold clr-neutral-87 mt-4 mb-0">{{ localize('Terms of Condition') }}</h2>
            </div>
          </div>
          <div class="col-xl-8">
            <div class="section-space-xsm-y">
              
                {!! $page->collectLocalization('content') !!}
            </div>
           
          </div>
        </div>
      </div>
    </div>
    <img src="{{asset('public/frontend/theme1/')}}/assets/img/breadcrumb-shape-top.png" alt="image" class="img-fluid breadcrumb-shape breadcrumb-shape-top">
    <img src="{{asset('public/frontend/theme1/')}}/assets/img/breadcrumb-shape-left.png" alt="image" class="img-fluid breadcrumb-shape breadcrumb-shape-left">
    <img src="{{asset('public/frontend/theme1/')}}/assets/img/breadcrumb-shape-right.png" alt="image" class="img-fluid breadcrumb-shape breadcrumb-shape-right">
    <img src="{{asset('public/frontend/theme1/')}}/assets/img/breadcrumb-shape-line-left.png" alt="image" class="img-fluid breadcrumb-shape breadcrumb-shape-line-left">
    <img src="{{asset('public/frontend/theme1/')}}/assets/img/breadcrumb-shape-line-right.png" alt="image" class="img-fluid breadcrumb-shape breadcrumb-shape-line-right">
  </section>
@endsection