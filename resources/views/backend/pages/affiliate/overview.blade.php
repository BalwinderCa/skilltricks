@extends('backend.layouts.master')

@section('title')
    {{ localize('Affiliate Configurations') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Affiliate Program') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Affiliate') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <a href="javascript::void(0);" class="btn btn-soft-primary" data-bs-toggle="modal"
                                    data-bs-target="#withdraw-modal"><i data-feather="credit-card"></i>
                                    {{ localize('Withdraw Balance') }}</a>
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
                            <div class="row">
                                <div class="col-xl-6">
                                    <h5>{{ localize('How to use Referral Program') }}</h5>
                                    <p>
                                        {{ localize('Our affiliate program commission rate for subscriptions is') }}:
                                        <strong>{{ getSetting('affiliate_commission') }}%</strong>
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><i data-feather="check-circle" class="me-2 text-success icon-16"></i>
                                            {{ localize('1. Copy referral link') }}</li>
                                        <li><i data-feather="check-circle" class="me-2 text-success icon-16"></i>
                                            {{ localize('2. Share with your friends & others') }}</li>
                                        <li><i data-feather="check-circle" class="me-2 text-success icon-16"></i>
                                            {{ localize('3. Make money on their subscriptions') }}</li>
                                    </ul>
                                </div>

                                <div class="col-xl-6">
                                    <h5 class="text-gray-800">
                                        {{ localize('Your Referral Link') }}
                                    </h5>

                                    <p>
                                        {{ localize('Invite your friends & others and earn commissions from their subscriptions') }}.
                                    </p>

                                    <div class="d-flex">
                                        <textarea type="text" class="form-control me-3 flex-grow-1" rows="1" id="referral_code_url" readonly>{{ route('home') }}?referral_code={{ $user->referral_code }}</textarea>
                                        <button class="btn btn-soft-warning" id="ref-cpurl-btn"
                                            data-attrcpy="{{ localize('Copied') }}"
                                            onclick="copyToClipboard()">{{ localize('Copy Link') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header py-3">
                            <h3 class="h5 mb-0">{{ localize('Recent Affiliate Earnings') }}</h3>
                        </div>
                        <div class="card-body p-0">
                            @php
                                $earningHistories = auth()
                                    ->user()
                                    ->referredUserEarnings()
                                    ->latest()
                                    ->take(10)
                                    ->paginate(5);
                            @endphp
                            @include('backend.pages.affiliate.inc.earningHistoryTable', [
                                'earningHistories' => $earningHistories,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('modals')
    <div id="withdraw-modal" class="modal fade modalParentSelect2">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ localize('Withdraw Money') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">
                    @php
                        $payoutAccounts = auth()->user()->affiliatePayoutAccounts;
                        $activeAffiliatePaymentMethods = getSetting('affiliate_payout_payment_methods') != null ? json_decode(getSetting('affiliate_payout_payment_methods')) : [];
                    @endphp

                    <form action="{{ route('affiliate.withdraw.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="amount" class="form-label">{{ localize('Withdrawal Amount') }}<span
                                    class="text-danger">*</span>
                            </label>
                            <input type="number" id="amount" name="amount" class="form-control"
                                placeholder="{{ localize('Type amount') }}" step="0.001"
                                min="{{ formatPrice(getSetting('minimum_withdrawal_amount'), false, false, false) }}"
                                max="{{ auth()->user()->user_balance }}" required>

                            <small>*{{ localize('Minimum Withdrawal Amount: ') }}
                                {{ formatPrice(getSetting('minimum_withdrawal_amount')) }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="payout_account_id" class="form-label">
                                {{ localize('Payout Account') }}
                                <small class="text-danger">*</small>
                            </label>
                            <select class="form-select modalSelect2" id="payout_account_id" name="payout_account_id"
                                required>
                                <option value="">
                                    {{ localize('Select Payout Account') }}
                                </option>

                                @foreach ($payoutAccounts as $payoutAccount)
                                    @if (in_array($payoutAccount->payment_method, $activeAffiliatePaymentMethods) && $payoutAccount->account_details != null)
                                        <option value="{{ $payoutAccount->id }}">
                                            {{ ucwords(str_replace('_', ' ', $payoutAccount->payment_method)) }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="additional_info" class="form-label">{{ localize('Additional Info') }}</label>
                            <textarea id="additional_info" name="additional_info" class="form-control"
                                placeholder="{{ localize('Type additional info') }}"></textarea>
                        </div>


                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i>
                                {{ localize('Submit Request') }}
                            </button>
                        </div>
                    </form>
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
