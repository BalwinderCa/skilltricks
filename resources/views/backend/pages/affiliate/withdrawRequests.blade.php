@extends('backend.layouts.master')

@section('title')
    {{ localize('Affiliate Withdraw Requests') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Withdraw Requests') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Withdraw Requests') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row g-4">
                <div class="col-12">
                    <div class="card mb-4" id="section-1">
                        @include('backend.pages.affiliate.inc.paymentHistoryTable', [
                            'paymentHistories' => $paymentHistories,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
