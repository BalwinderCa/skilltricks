@extends('frontend.theme1.layouts.master')

@section('title')
    {{ localize('Home') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('content')
    @php
        $yearlyCounter = \App\Models\Subscriptionpackage::isActive()->where('package_type', 'yearly')->count();
        $lifetimeCounter = \App\Models\Subscriptionpackage::isActive()->where('package_type', 'lifetime')->count();
        $prepaidCounter = \App\Models\Subscriptionpackage::isActive()->where('package_type', 'prepaid')->count();
    @endphp
    <section class="breadcrumb-section">
        <div class="breadcrumb-section-inner">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xxl-7 col-xl-8">
                        <div class="text-center">
                            <div class="d-inline-flex align-items-center py-2 px-4 bg-info-10 bg-opacity-3 rounded-1">
                                <span class="fs-12 clr-white">{{ getSetting('system_title') }}</span>
                            </div>
                            <h2 class="h3 fw-bold clr-neutral-90 mt-4">{{ localize('Our Subscription Packages') }} </h2>
                        </div>
                    </div>
                </div>
                <div class="pricing- mt-10 d-flex justify-content-center overflow-hidden">
                    <ul class="pricing-5-list nav list list-sm-row align-items-center gap-3 fadeIn_bottom">

                        <li>
                            <button type="button"
                                class="link nav-link active d-inline-flex py-2 px-3 fs-12 fw-bold clr-neutral-70 border-0 bg-transparent"
                                data-bs-toggle="tab" data-bs-target="#monthlyPricing"
                                aria-selected="true">{{ localize('Monthly') }}</button>
                        </li>


                        @if ($yearlyCounter > 0)
                            <li>
                                <button type="button"
                                    class="link nav-link d-inline-flex py-2 px-3 fs-12 fw-bold clr-neutral-70 border-0 bg-transparent"
                                    data-bs-toggle="tab" data-bs-target="#yearlyPricing"
                                    aria-selected="false">{{ localize('Yearly') }}</button>
                            </li>
                        @endif

                        @if ($lifetimeCounter > 0)
                            <li>
                                <button type="button"
                                    class="link nav-link d-inline-flex py-2 px-3 fs-12 fw-bold clr-neutral-70 border-0 bg-transparent"
                                    data-bs-toggle="tab" data-bs-target="#lifetimePricing"
                                    aria-selected="false">{{ localize('Lifetime') }}</button>
                            </li>
                            <li>
                                <button type="button"
                                    class="link nav-link d-inline-flex py-2 px-3 fs-12 fw-bold clr-neutral-70 border-0 bg-transparent"
                                    data-bs-toggle="tab" data-bs-target="#prepaidPricing"
                                    aria-selected="false">{{ localize('Prepaid') }}</button>
                            </li>
                        @endif

                    </ul>
                </div>
                <div class="tab-content mt-10">
                    <div class="tab-pane fade show active" id="monthlyPricing">
                        <div class="row gy-4">
                            @foreach ($packages as $package)
                                @if ($package->package_type == 'starter' || $package->package_type == 'monthly')
                                    <div class="col-sm-6 col-lg-4 col-xxl-3">
                                        @include('frontend.theme1.pages.partials.home.pricing-card', [
                                            'package' => $package,
                                        ])
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane fade" id="yearlyPricing">
                        <div class="row gy-4">
                            @foreach ($packages as $package)
                                @if ($package->package_type == 'yearly')
                                    <div class="col-sm-6 col-lg-4 col-xxl-3">
                                        @include('frontend.theme1.pages.partials.home.pricing-card', [
                                            'package' => $package,
                                        ])
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane fade" id="lifetimePricing">
                        <div class="row gy-4">
                            @foreach ($packages as $package)
                                @if ($package->package_type == 'lifetime')
                                    <div class="col-sm-6 col-lg-4 col-xxl-3">
                                        @include('frontend.theme1.pages.partials.home.pricing-card', [
                                            'package' => $package,
                                        ])
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane fade" id="prepaidPricing">
                        <div class="row gy-4">
                            @foreach ($packages as $package)
                                @if ($package->package_type == 'prepaid')
                                    <div class="col-sm-6 col-lg-4 col-xxl-3">
                                        @include('frontend.theme1.pages.partials.home.pricing-card', [
                                            'package' => $package,
                                        ])
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-top.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-top">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-left.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-left">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-right.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-right">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-line-left.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-line-left">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-line-right.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-line-right">
    </section>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExample" data-bs-scroll="true">
        <div class="offcanvas-header border-bottom">
            <h6 class="mb-0">{{ localize('All Templates') }}</h6>
            <button type="button" class="btn btn-sm bg-primary-90 :bg-primary-50 clr-primary-30 :clr-primary-95 rounded"
                data-bs-dismiss="offcanvas" aria-label="Close"> {{ localize('Close') }} </button>
        </div>
        <div class="offcanvas-body package-template-contents" id="package-template-contents">

        </div>
    </div>

    <div class="pricing-faq-section section-space-top section-space-bottom">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <h3 class="clr-neutral-90 fw-extrabold animate-line-3d"> {{ localize('Frequently Asked Questions') }}
                        👍 </h3>
                    <p class="mb-6 fw-semibold clr-neutral-80 max-text-32 animate-text-from-right">
                        {{ localize('Have a question that is
                                                                        not answered? You can contact us at') }}
                    </p>
                    @if (!getSetting('is_hide_contact_us'))
                        <a href="{{ route('home.pages.contactUs') }}"
                            class="link d-inline-block py-3 px-6 rounded-pill bg-neutral-10 :bg-primary-key clr-white fs-14 fw-semibold fadeIn_bottom">
                            {{ localize('Have a question? Submit a Ticket') }} </a>
                    @endif
                </div>
                <div class="col-lg-7">
                    <div class="accordion custom-accordion custom-accordion--faq mb-8 fadeIn_bottom" id="faqAccordionOne">

                        @if (!empty($faqs))
                            @foreach ($faqs as $faq)
                                <div class="accordion-item top-shadow rounded-2 gradient-card mb-4">
                                    <h2 class="accordion-header">
                                        <button
                                            class="accordion-button fs-16 fw-bold {{ !$loop->first ? 'collapsed' : '' }} rounded-2"
                                            type="button" data-bs-toggle="collapse"
                                            data-bs-target="#faqAccordion2{{ $faq->id }}" aria-expanded="false">
                                            {{ $faq->collectLocalization('question') }}
                                        </button>
                                    </h2>
                                    <div id="faqAccordion2{{ $faq->id }}"
                                        class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                        data-bs-parent="#faqAccordionOne">
                                        <div class="accordion-body">
                                            {{ $faq->collectLocalization('answer') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                    </div>
                </div>
            </div>

        </div>
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/pricing-faq-top-left.png" alt="image"
            class="img-fluid pricing-fag-shape pricing-fag-shape-top-left">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/pricing-faq-top-right.png" alt="image"
            class="img-fluid pricing-fag-shape pricing-fag-shape-top-right">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/pricing-faq-bottom-right.png" alt="image"
            class="img-fluid pricing-fag-shape pricing-fag-shape-bottom-right">
    </div>
@endsection
@section('modals-common')
    <!-- Modal -->
    <div class="modal fade" id="packagePaymentModal" tabindex="-1" aria-labelledby="packagePaymentModalLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="packagePaymentModalLabel">{{ localize('Select Payment Method') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">

                    <form action="{{ route('website.subscriptions.subscribe') }}" method="POST"
                        class="payment-method-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="package_id" value="" class="payment_package_id">
                        <!-- Online payment gateway -->
                        @auth
                            <div class="row g-3">
                                @if (getSetting('new_package_purchase') != 1 && activePackageHistory())
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check tt-checkbox">
                                            <label for="new_package_purchase" class="form-check-label fw-medium ">
                                                <input class="form-check-input cursor-pointer" type="checkbox"
                                                    id="new_package_purchase" name="active_now">
                                                <strong>{{ localize('Do you want to active this package ?') }}</strong>

                                            </label>
                                            {{ localize('Your current active package will be expired and This Will be active.') }}

                                        </div>
                                    </div>
                                @endif

                            </div>
                        @endauth
                        @if (count($payments) > 0)
                            <div class="online_payment mt-4" id="online_payment">
                                <div class="row g-3 mb-4">
                                    @foreach ($payments as $method)
                                        <div class="col-lg-3 col-md-6">
                                            <div class="tt-single-gateway text-center">
                                                <input type="radio" class="tt-custom-radio" name="payment_method"
                                                    id="{{ $method->gateway }}" value="{{ $method->gateway }}" required>
                                                <label class="tt-gateway-info card p-3 cursor-pointer flex-column h-100"
                                                    for="{{ $method->gateway }}">
                                                    <div class="tt-gateway-icon">
                                                        <img src="{{ staticAsset($method->image) }}"
                                                            alt="{{ strtoupper($method->gateway) }}" class="img-fluid">
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                    <!--offline-->
                                    @if (getSetting('enable_offline') == 1)
                                        <div class="col-lg-3 col-md-6">
                                            <div class="tt-single-gateway text-center">
                                                <input type="radio" class="tt-custom-radio" name="payment_method"
                                                    id="offline" value="offline" required>
                                                <label
                                                    class="tt-gateway-info card p-3 cursor-pointer flex-column h-100 oflinePayment"
                                                    data-method="offline" for="offline">
                                                    <div class="tt-gateway-icon">
                                                        <img src="{{ uploadedAsset(getSetting('offline_image')) }}"
                                                            alt="offline payment" class="img-fluid">
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <button type="submit"
                                    class="link py-3 px-6 mt-auto  border border-primary-key :border-primary-30 bg-primary-key :bg-primary-30 rounded-1 fw-bold clr-white mx-auto align-items-center   fs-12 btn-outline-primary">
                                    {{ localize('Proceed') }}</button>
                            </div>
                        @endif
                        <!--payment -->

                        @include('frontend.default.pages.partials.home._offline_payment')
                        <!-- End Offline  payment -->



                    </form>
                </div>


            </div>

        </div>
    </div>
    </div>
    </div>
@endsection
@section('scripts-common')
    <script>
        "use strict";

        // handle package payment
        function handlePackagePayment($this) {
            let package_type = $($this).data('package-type');
            let subscribed_package_type = $($this).data('previous-package-type');

            let check = true;
            let packageChangeCheck;
            let user_type = $($this).data('user-type') == "customer" ? 'customer' : 'admin';

            let carryForward = '{{ getSetting('carry_forward ') ? 1 : 0 }}';

            if ((subscribed_package_type == "prepaid" || subscribed_package_type == "lifetime") && (
                    package_type != "prepaid" && package_type != "lifetime")) {
                packageChangeCheck = confirm(
                    `{{ localize('You current package ${subscribed_package_type} will be expired if you want to subscribe to ${package_type}. Do you want to continue?') }}`
                )

            }
            if (subscribed_package_type != package_type && user_type == "customer" && carryForward == "0") {
                check = confirm(
                    `{{ localize('You are changing your subscription package type to ${package_type}, your balance will be reset with new package balance. Want to continue?') }}`
                )
            }

            if (check || packageChangeCheck) {
                let package_id = $($this).data('package-id');
                let price = parseFloat($($this).data('price'));
                $('.payment_package_id').val(package_id);

                let isLoggedIn = parseInt('{{ Auth::check() }}');
                let authUserType = 'customer';

                if (isLoggedIn == 1) {
                    authUserType = "{{ Auth::user()->user_type ?? 'customer' }}";
                    if (authUserType == "customer") {
                        if (price > 0) {
                            showPackagePaymentModal();
                        } else {
                            $('.payment-method-form').submit();
                        }
                    } else {
                        var redirectLink = "{{ route('subscriptions.index') }}";
                        $(location).prop('href', redirectLink)
                    }
                } else {
                    var redirectLink = "{{ route('subscriptions.index') }}";
                    $(location).prop('href', redirectLink)
                }
            }
        }

        // show package payment modal
        function showPackagePaymentModal() {
            $('#packagePaymentModal').modal('show')
        }

        // on submit payment form

        $(document).on('click', '.oflinePayment', function(e) {
            let payment_type = $(this).data('method');
            hideShow(payment_type);
        })
        $(document).on('click', '.cancel', function(e) {
            let payment_type = 'online';
            hideShow(payment_type);
        })
        // 
        $(document).on('change', '#offline_payment_method', function(e) {
            let id = $(this).val();
            if (id) {
                $('.all-description').addClass('d-none');
                $('#description_' + id).removeClass('d-none');
            } else {
                $('.all-description').addClass('d-none');
            }


        })

        // hide show
        function hideShow(payment_type) {
            if (payment_type == 'offline') {
                $('#online_payment').addClass('d-none');
                $('#offline_payment').removeClass('d-none');
                $('#offline_payment_method').attr('required', 'required');
                $('#offline_amount').attr('required', 'required');
                $('#offline_payment_detail').attr('required', 'required');
            } else {
                $('#online_payment').removeClass('d-none');
                $('#offline_payment').addClass('d-none');
                $('#offline_payment_method').removeAttr('required');
                $('#offline_amount').removeAttr('required');
                $('#offline_payment_detail').removeAttr('required');
            }
        }
        // clear data
        function clearData() {
            $('#offline_payment_method').val('')
            $('#offline_amount').val('')
            $('#offline_payment_method').val('')
        }
    </script>
@endsection
