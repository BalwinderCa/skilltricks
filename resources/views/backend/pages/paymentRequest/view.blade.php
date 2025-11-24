@extends('backend.layouts.master')


@section('title')
    {{ localize('Payment Request') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section py-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Payment Request') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Payment Request') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-4">
                    <div class="card mb-4" id="section-1">
                        @include('backend.pages.paymentRequest._package_deatils', [
                            'package' => $package,
                        ])
                    </div>
                </div>
                <div class="col-8 order-2 order-md-2 order-lg-2 order-xl-1">

                    <input type="hidden" name="history_id" value="{{ $history->id }}">
                    <!--basic information start-->
                    <div class="card mb-4" id="section-1">
                        <div class="card-body">



                            @if ($history->feedback_note)
                                <div class="mb-4">
                                    <h5 class="mb-4">{{ localize('Note') }} </h5>
                                    <p>[ {{ $history->feedback_note }} ]</p>
                                </div>
                            @endif
                            <div class="mb-4">
                                <h5 class="mb-4">{{ localize('Note') }} </h5>

                                <p> {{ $history->offlinePaymentMethod->name }}</p>
                            </div>
                            <div class="mb-4">
                                <h5 class="mb-4">{{ localize('Paid Amount') }} </h5>

                                <p> {{ $history->paid }}</p>
                            </div>
                            <div class="mb-4">
                                <h5 class="mb-4">{{ localize('Payment Details') }} </h5>

                                <p> {{ $history->payment_details ? json_decode($history->payment_details) : null }}</p>
                            </div>
                            <div class="mb-4">
                                <h5 class="mb-4">{{ localize('Payment Note') }} </h5>

                                <p> {{ $history->feedback_note }}</p>
                            </div>
                            @if ($history->file)
                                <div class="mb-4">
                                    <h5 class="mb-4">{{ localize('File') }} </h5>

                                    <p>

                                        <img src="{{ asset($history->file) }}" alt="" class="mb-2 img-fluid">

                                    </p>
                                </div>
                            @endif

                        </div>

                    </div>
                    <!--basic information end-->

                </div>
            </div>
        </div>
    </section>
@endsection
