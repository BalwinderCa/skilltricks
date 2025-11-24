@extends('backend.layouts.master')

@section('title')
    {{ localize('Payment Methods Settings') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Payment Methods Settings') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Payment Methods Settings') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                @foreach ($paymentmethods as $paymentMethod)
                    {{-- paypal --}}
                    <div class="col-lg-3 col-md-6">
                        <div class="tt-payment-gateway rounded-3 shadow-sm card border-0 h-100 flex-column cursor-pointer"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvas{{ucfirst($paymentMethod->gateway)}}">
                            <div class="card-body tt-payment-info">
                                <div class="d-flex align-items-center justify-content-between">
                                    <img class="img-fluid" src="{{ staticAsset($paymentMethod->image) }}"
                                        alt="avatar" />
                                    <div class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input"
                                            @if ($paymentMethod->is_active == 1) checked @endif>
                                    </div>
                                </div>
                                <div class="tt-payment-setting position-absolute btn rounded-pill btn-light">
                                    {{ localize('Settings') }}<i data-feather="arrow-right" class="ms-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach                                   
                 
            </div>

            {{-- offline --}}
            <div class="mt-5">
                <h4>{{ localize('Offline Payment Method') }}</h4>
                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="tt-payment-gateway rounded-3 shadow-sm card border-0 h-100 flex-column cursor-pointer"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasOffline">
                            <div class="card-body tt-payment-info">
                                <div class="d-flex align-items-center justify-content-between">
                                    <img class="img-fluid" src="{{ uploadedAsset(getSetting('offline_image')) }}"
                                        alt="offline" />
                                    <div class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input"
                                            @if (getSetting('enable_offline') == 1) checked @endif>
                                    </div>
                                </div>
                                <div class="tt-payment-setting position-absolute btn rounded-pill btn-light">
                                    {{ localize('Settings') }}<i data-feather="arrow-right" class="ms-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- payment form --}}
    @include('backend.pages.systemSettings.paymentForm.paypal')
    @include('backend.pages.systemSettings.paymentForm.stripe')
    @include('backend.pages.systemSettings.paymentForm.paytm')
    @include('backend.pages.systemSettings.paymentForm.razorpay')
    @include('backend.pages.systemSettings.paymentForm.iyzico')
    @include('backend.pages.systemSettings.paymentForm.paystack')
    @include('backend.pages.systemSettings.paymentForm.flutterwave')
    @include('backend.pages.systemSettings.paymentForm.duitku')
    @include('backend.pages.systemSettings.paymentForm.yookassa')
   
    @include('backend.pages.systemSettings.paymentForm.molile')
    @include('backend.pages.systemSettings.paymentForm.mercadopago')
    @include('backend.pages.systemSettings.paymentForm.midtrans')
    @include('backend.pages.systemSettings.paymentForm.offline')
@endsection



@section('scripts')
    <script>
        "use strict";
        // runs when the document is ready --> for media files
        $(document).ready(function() {
            getChosenFilesCount();
            showSelectedFilePreviewOnLoad();
        });
    </script>
@endsection
