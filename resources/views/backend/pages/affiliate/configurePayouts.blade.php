@extends('backend.layouts.master')

@section('title')
    {{ localize('Payout Configurations') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Payout Configurations') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Payouts') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- info cards --}}
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-2 flex-shrink-0">
                                    <div class="text-center rounded bg-soft-warning">
                                        <span><i data-feather="dollar-sign"></i></span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0">{{ localize('Available Balance') }}</p>
                                    <h4 class="mb-0">{{ formatPrice($user->user_balance) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-2 flex-shrink-0">
                                    <div class="text-center rounded bg-soft-primary">
                                        <span><i data-feather="credit-card"></i></span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0">{{ localize('Total Subscriptions') }}</p>
                                    <h4 class="mb-0">{{ $user->referredUserEarnings()->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-2 flex-shrink-0">
                                    <div class="text-center rounded bg-soft-danger">
                                        <span><i data-feather="link"></i></span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0">{{ localize('Total Clicks') }}</p>
                                    <h4 class="mb-0">{{ $user->num_of_clicks }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-2 flex-shrink-0">
                                    <div class="text-center rounded bg-soft-info">
                                        <span><i data-feather="dollar-sign"></i></span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0">{{ localize('Referral Signups') }}</p>
                                    <h4 class="mb-0">{{ $user->referredUsers()->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            {{-- info cards --}}


            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">{{ localize('Configure Payout Accounts') }}</h5>
                            @php
                                $activeAffiliatePaymentMethods = getSetting('affiliate_payout_payment_methods') != null ? json_decode(getSetting('affiliate_payout_payment_methods')) : [];
                            @endphp

                            <div class="row">
                                <form action="{{ route('affiliate.payout.configureStore') }}" method="POST">
                                    @csrf
                                    <!--general settings-->
                                    <div class="card mb-4" id="section-1">
                                        <div class="card-body">

                                            @if (in_array('bank_payment', $activeAffiliatePaymentMethods))
                                                @php
                                                    $paymentDetails = \App\Models\AffiliatePayoutAccount::where('user_id', $user->id)
                                                        ->where('payment_method', 'bank_payment')
                                                        ->first();
                                                @endphp
                                                <div class="mb-3">
                                                    <label for="affiliate_commission" class="form-label">
                                                        {{ localize('Bank Details') }}
                                                    </label>
                                                    <div class="input-group mb-3">
                                                        <input type="text" id="bank_payment" name="bank_payment"
                                                            class="form-control"
                                                            placeholder="{{ localize('Type bank payment details') }}"
                                                            value="{{ $paymentDetails != null ? $paymentDetails->account_details : '' }}">
                                                    </div>
                                                </div>
                                            @endif


                                            @if (in_array('paypal', $activeAffiliatePaymentMethods))
                                                @php
                                                    $paymentDetails = \App\Models\AffiliatePayoutAccount::where('user_id', $user->id)
                                                        ->where('payment_method', 'paypal')
                                                        ->first();
                                                @endphp
                                                <div class="mb-3">
                                                    <label for="affiliate_commission" class="form-label">
                                                        {{ localize('Paypal Details') }}
                                                    </label>
                                                    <div class="input-group mb-3">
                                                        <input type="text" id="paypal" name="paypal"
                                                            class="form-control"
                                                            placeholder="{{ localize('Type paypal payment details') }}"
                                                            value="{{ $paymentDetails != null ? $paymentDetails->account_details : '' }}">
                                                    </div>
                                                </div>
                                            @endif


                                            @if (in_array('stripe', $activeAffiliatePaymentMethods))
                                                @php
                                                    $paymentDetails = \App\Models\AffiliatePayoutAccount::where('user_id', $user->id)
                                                        ->where('payment_method', 'stripe')
                                                        ->first();
                                                @endphp
                                                <div class="mb-3">
                                                    <label for="affiliate_commission" class="form-label">
                                                        {{ localize('Stripe Details') }}
                                                    </label>
                                                    <div class="input-group mb-3">
                                                        <input type="text" id="stripe" name="stripe"
                                                            class="form-control"
                                                            placeholder="{{ localize('Type stripe payment details') }}"
                                                            value="{{ $paymentDetails != null ? $paymentDetails->account_details : '' }}">
                                                    </div>
                                                </div>
                                            @endif


                                            @if (in_array('paytm', $activeAffiliatePaymentMethods))
                                                @php
                                                    $paymentDetails = \App\Models\AffiliatePayoutAccount::where('user_id', $user->id)
                                                        ->where('payment_method', 'paytm')
                                                        ->first();
                                                @endphp
                                                <div class="mb-3">
                                                    <label for="affiliate_commission" class="form-label">
                                                        {{ localize('PayTm Details') }}
                                                    </label>
                                                    <div class="input-group mb-3">
                                                        <input type="text" id="paytm" name="paytm"
                                                            class="form-control"
                                                            placeholder="{{ localize('Type paytm payment details') }}"
                                                            value="{{ $paymentDetails != null ? $paymentDetails->account_details : '' }}">
                                                    </div>
                                                </div>
                                            @endif

                                            @if (in_array('razorpay', $activeAffiliatePaymentMethods))
                                                @php
                                                    $paymentDetails = \App\Models\AffiliatePayoutAccount::where('user_id', $user->id)
                                                        ->where('payment_method', 'razorpay')
                                                        ->first();
                                                @endphp
                                                <div class="mb-3">
                                                    <label for="affiliate_commission" class="form-label">
                                                        {{ localize('Razorpay Details') }}
                                                    </label>
                                                    <div class="input-group mb-3">
                                                        <input type="text" id="razorpay" name="razorpay"
                                                            class="form-control"
                                                            placeholder="{{ localize('Type razorpay payment details') }}"
                                                            value="{{ $paymentDetails != null ? $paymentDetails->account_details : '' }}">
                                                    </div>
                                                </div>
                                            @endif

                                            @if (in_array('iyzico', $activeAffiliatePaymentMethods))
                                                @php
                                                    $paymentDetails = \App\Models\AffiliatePayoutAccount::where('user_id', $user->id)
                                                        ->where('payment_method', 'iyzico')
                                                        ->first();
                                                @endphp
                                                <div class="mb-3">
                                                    <label for="affiliate_commission" class="form-label">
                                                        {{ localize('IyZico Details') }}
                                                    </label>
                                                    <div class="input-group mb-3">
                                                        <input type="text" id="iyzico" name="iyzico"
                                                            class="form-control"
                                                            placeholder="{{ localize('Type iyzico payment details') }}"
                                                            value="{{ $paymentDetails != null ? $paymentDetails->account_details : '' }}">
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <!--general settings-->

                                    <div class="mb-3">
                                        <button class="btn btn-primary" type="submit">
                                            <i data-feather="save" class="me-1"></i>
                                            {{ localize('Save Configuration') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@section('modals')
    <div id="withdraw-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ localize('Withdraw Money') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">

                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        "use strict";

        function copyToClipboard(btn) {
            var el_url = document.getElementById('referral_code_url');
            var c_u_b = document.getElementById('ref-cpurl-btn');
            if (el_url != null && c_u_b != null) {
                el_url.select();
                document.execCommand('copy');
                c_u_b.innerHTML = c_u_b.dataset.attrcpy;
            }
        }
    </script>
@endsection
