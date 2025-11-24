@extends('backend.layouts.master')


@section('title')
    {{ localize('Subscription Histories') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section py-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Subscription Histories') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Subscription Histories') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card mb-4" id="section-1">
                        <form class="app-search" action="{{ Request::fullUrl() }}" method="GET">
                            <div class="card-header border-bottom-0">
                                <div class="row justify-content-between g-3">
                                    <div class="col-auto flex-grow-1">
                                        <div class="tt-search-box">
                                            <div class="input-group">
                                                <span class="position-absolute top-50 start-0 translate-middle-y ms-2">
                                                    <i data-feather="search"></i></span>
                                                <input class="form-control rounded-start w-100" type="text"
                                                    id="search" name="search" placeholder="{{ localize('Search') }}..."
                                                    @isset($searchKey)
                                                value="{{ $searchKey }}"
                                            @endisset>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary">
                                            <i data-feather="search" width="18"></i>
                                            {{ localize('Search') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>


                        <table class="table tt-footable border-top align-middle" data-use-parent-width="true" >
                            <thead>
                                <tr>
                                    <th class="text-center">{{ localize('S/L') }}</th>
                                    @if (auth()->user()->user_type != 'customer')
                                        <th data-breakpoints="">{{ localize('User') }}</th>
                                       <th data-breakpoints="xs sm">{{ localize('Email') }}</th>
                                    @endif
                                    <th data-breakpoints="xs sm">{{ localize('Package') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Package Price') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Paid Amount') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Start Date') }}</th>
                                    <th data-breakpoints="xs sm md">{{ localize('Expire Date') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Recurring') }}</th>
                                    <th data-breakpoints="xs sm md lg">{{ localize('Payment Method') }}</th>
                                    <th data-breakpoints="xs sm md lg">{{ localize('Created Date') }}</th>
                                    <th data-breakpoints="xs sm md lg">{{ localize('Payment Status') }}</th>
                                    <th data-breakpoints="xs sm md lg">{{ localize('Status') }}</th>
                                    <th data-breakpoints="xs sm md lg">{{ localize('Actions') }}</th>

                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($histories as $key => $history)
                                @php
                                   $recurringPayment= $history->recurringPayment ? $history->recurringPayment : \App\Models\SubscriptionRecurringPayment::where('subscription_history_id', $history->id)->first();
                                @endphp
                                    <tr>
                                        <td class="text-center fs-sm">
                                            {{ $key + 1 + ($histories->currentPage() - 1) * $histories->perPage() }}
                                        </td>

                                        @if (auth()->user()->user_type != 'customer')
                                            <td>
                                                <a href="javascript:void(0);" class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm">
                                                        <img class="rounded-circle"
                                                            src="{{ uploadedAsset(@$history->user->avatar) }}"
                                                            alt=""
                                                            onerror="this.onerror=null;this.src='{{ staticAsset('backend/assets/img/placeholder-thumb.png') }}';" />
                                                    </div>
                                                    <h6 class="fs-sm mb-0 ms-2">{{ @$history->user->name }}
                                                    </h6>
                                                    <br>
                                                    
                                                </a>
                                            </td>
                                            <td>{{@$history->user->email}}</td>
                                      
                                        @endif


                                        <td class="text-capitalize fw-sm">
                                            {!! html_entity_decode($history->subscriptionPackage->title) !!}
                                            /{{ $history->subscriptionPackage->package_type == 'starter' ? localize('Monthly') : localize($history->subscriptionPackage->package_type) }}
                                        </td>
                                        

                                        <td class="text-capitalize fw-sm">
                                            {{ $history->subscriptionPackage->price > 0 ? formatPrice($history->subscriptionPackage->price) : localize('Free') }}
                                        </td>
                                        <td class="text-capitalize fw-sm">
                                            {{ $history->price > 0 ? formatPrice($history->price, false, false, true, true, $history->currency_code) : localize('Free') }}
                                        </td>

                                        <td class="text-center fs-sm">
                                            <span class="fs-sm">{{ $history->start_date }}</span>
                                        </td>

                                        <td class="text-center fs-sm">

                                            <span class="fs-sm text-capitalize">{{ $history->end_date }}
                                        </td>
                                        <td class="text-capitalize fw-sm">
                                          
                                            @if($recurringPayment)
                                                @if($recurringPayment->is_active == 1)
                                                <span class="badge bg-soft-success rounded-pill text-capitalize"  >
                                                    {{ localize('Active') }} </span>
                                                    @if(getSetting('auto_subscription_active') == 1)
                                                    <button class="btn btn-xs btn-soft-danger py-0 px-2 rounded-pill fs-xs" onclick="handleRecurringPayment(this)"
                                                    data-package_history_id="{{ $history->id }}"  data-gateway="{{ $recurringPayment->gateway }}" data-type="cancel"> {{localize('Cancel Now')}}</button>
                                                    @endif
                                                @else
                                                <span class="badge bg-soft-warning rounded-pill text-capitalize"  >
                                                    {{ localize('DeActived') }} </span>
                                                    @if(getSetting('auto_subscription_deactive') == 1 && $recurringPayment->gateway == 'paypal')
                                                    <button class="btn btn-xs btn-soft-success py-0 px-2 rounded-pill fs-xs" onclick="handleRecurringPayment(this)"
                                                    data-package_history_id="{{ $history->id }}" data-gateway="{{ $recurringPayment->gateway }}" data-type="active"> {{localize('Active Now')}}</button>
                                                    @endif
                                                @endif
                                               
                                            @else
                                            <span class="badge bg-soft-primary rounded-pill text-capitalize">
                                                {{ localize('No') }}
                                            </span>   
                                            @endif
                                        </td>
                                        <td><span class="badge bg-soft-primary rounded-pill text-capitalize">
                                                {{ $history->payment_method }}</span>

                                            @if ($history->offline_payment_id)
                                                <span class="badge bg-soft-primary rounded-pill text-capitalize">
                                                    {{ $history->offlinePaymentMethod->name }}
                                                </span>
                                            @endif


                                        </td>
                                        <td>{{ date('Y-m-d', strtotime($history->created_at)) }}</td>

                                        <td>

                                            @if ($history->payment_status == 1)
                                                <span class="badge bg-soft-success rounded-pill text-capitalize">
                                                    {{ $history->price > 0 ? localize('Paid')  : localize('Free')}} </span>
                                            @elseif($history->payment_status == 2)
                                                <span class="badge bg-soft-primary rounded-pill text-capitalize">
                                                    {{ localize('Pending') }}
                                                </span>
                                            @elseif($history->payment_status == 3)
                                                <span class="badge bg-soft-danger rounded-pill text-capitalize">
                                                    {{ localize('Reject') }}
                                                </span>
                                            @elseif($history->payment_status == 4)
                                                <span class="badge bg-soft-danger rounded-pill text-capitalize">
                                                    {{ localize('Incomplete') }}
                                                </span>
                                                @if ($history->feedback_note)
                                                    <span class="badge bg-soft-primary rounded-pill text-capitalize">

                                                        <span><a href="{{ route('admin.re-submit-request.index', $history->id) }}"
                                                                title="{{ $history->feedback_note }}" target="_blank">
                                                                <span
                                                                    class="text-danger">{{ localize('Action Required') }}</span></a></span>

                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-soft-primary rounded-pill text-capitalize">
                                                    {{ localize('Pending') }}
                                                </span>
                                            @endif
                                            </span>
                                        </td>
                                        <td>
                                            @if ($history->subscription_status == 1)
                                                <span class="badge bg-soft-success rounded-pill text-capitalize">
                                                    {{ getSubscriptionStatusName($history->subscription_status) }}</span>
                                            @elseif($history->subscription_status == 2)
                                                <span class="badge bg-soft-danger rounded-pill text-capitalize">
                                                    {{ getSubscriptionStatusName($history->subscription_status) }}</span>
                                            @elseif ($history->subscription_status == 3)
                                                <span class="badge bg-soft-info rounded-pill text-capitalize">
                                                    {{ getSubscriptionStatusName($history->subscription_status) }}</span>
                                            @else
                                                <span class="badge bg-soft-warning rounded-pill text-capitalize">
                                                    {{ localize('Invalid') }}</span>
                                            @endif

                                        </td>
                                        <td class="">
                                            <div class="d-flex align-items-center">
                                                @if ($history->payment_method == 'offline')
                                                    <x-action-drop-down>
                                                        @if (isCustomer() && $history->payment_status != 1)
                                                            <a href="#" class="dropdown-item confirm-delete"
                                                                data-href="{{ route('admin.payment-request.delete', $history->id) }}"
                                                                title="{{ localize('Delete') }}">
                                                                <i data-feather="trash-2" class="me-2"></i>
                                                                {{ localize('Delete') }}
                                                            </a>

                                                            @if (isCustomer() && $history->feedback_note && $history->payment_status == 4)
                                                                <a href="{{ route('admin.re-submit-request.index', $history->id) }}"
                                                                    target="_blank" class="dropdown-item" data-href=""
                                                                    title="{{ localize('Edit') }}">
                                                                    <i data-feather="edit-3" class="me-2"></i>
                                                                    {{ localize('Edit') }}
                                                                </a>
                                                            @endif
                                                        @endif

                                                        <a href="{{ route('admin.payment-request.view', $history->id) }}"
                                                            target="_blank" class="dropdown-item" data-href=""
                                                            title="{{ localize('View') }}">
                                                            <i data-feather="eye" class="me-2"></i>
                                                            {{ localize('View') }}
                                                        </a>
                                                    </x-action-drop-down>
                                                @else
                                                <x-action-drop-down>
                                                    <a href="{{ route('subscriptions.purchase-invoice', $history->id) }}"
                                                        target="_blank" class="dropdown-item" data-href=""
                                                        title="{{ localize('Invoice') }}">
                                                        <i data-feather="file" class="me-2"></i>
                                                        {{ localize('Invoice') }}
                                                    </a>
                                                </x-action-drop-down>
                                                -
                                                @endif

                                                @if ($history->subscription_status == 3 && $history->payment_status == 1)
                                                    <a href="javascript:void(0);"
                                                        class="rounded-pill text-capitalize cursor-pointer ms-1 fs-sm text-underline"
                                                        onclick="handlePackageActive(this)"
                                                        data-package_history_id="{{ $history->id }}"
                                                        data-is_carried_over="{{ $history->is_carried_over }}">
                                                        {{ localize('Active Now') }}</a>
                                                @endif
                                                
                                            </div>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>


                        <!--pagination start-->
                        <x-pagination-component :list="$histories" />
                        <!--pagination end-->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('modals-common')
    <!-- Modal -->
    <div class="modal fade" id="activePackageNow" tabindex="-1" aria-labelledby="activePackageNowLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ localize('Active Now Confirmation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('customer.package.active') }}" method="post">
                        @csrf
                        <input type="hidden" name="package_history_id" id="package_history_id">
                        <div class="my-0 carried_over_info">
                            @if (getSetting('carry_forward'))
                                {{ localize('Remaining balance of previous subscription will be added to this Package and previous pacakge will be expired. Start New Package Today, Enjoy !') }}
                            @else
                                {{ localize('Expire Previous Package and Start New package From Now, Enjoy !!') }}
                            @endif
                        </div>
                        <h6 class="my-3">{{ localize('Are you sure to Active this?') }}</h6>

                        <div class="justify-content-center pb-3">
                            <button type="submit" class="btn btn-danger mt-2"
                                data-bs-dismiss="modal">{{ localize('Procced') }}</button>
                            <button type="button" class="btn btn-secondary mt-2"
                                data-bs-dismiss="modal">{{ localize('Cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
     <!-- Active subscription payment -->
    <div class="modal fade" id="activeRecurringPaymentNow" tabindex="-1" aria-labelledby="activeRecurringPaymentNowLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ localize('Active Now Confirmation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('subscriptions.recurring.status') }}" method="post">
                        @csrf
                        <input type="hidden" name="package_history_id" class="package_history_id">
                        <input type="hidden" name="status" value="active">

                        <div class="mb-4">
                            <label for="reason" class="form-label">{{ localize('Reason') }} <span
                                    class="text-danger ms-1">*</span></label>
                            <textarea name="reason" class="form-control" id="reason" cols="30" rows="10" required></textarea>

                            @if ($errors->has('reason'))
                                <span class="text-danger">{{ $errors->first('reason') }}</span>
                            @endif
                        </div>

                        <div class="justify-content-center pb-3">
                           
                            <button type="button" class="btn btn-secondary mt-2"
                                data-bs-dismiss="modal">{{ localize('Cancel') }}</button>
                            <button type="submit" class="btn btn-danger mt-2"
                                data-bs-dismiss="modal">{{ localize('Procced') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
     <!-- Cancel subscription payment -->
     <div class="modal fade" id="cancelReecurringPaymentNow" tabindex="-1" aria-labelledby="cancelReecurringPaymentNowLabel"
     aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ localize('Cancel Auto Subscription') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('subscriptions.recurring.status') }}" method="post">
                        @csrf
                        <input type="hidden" name="package_history_id" class="package_history_id">
                        <input type="hidden" name="payment_gateway_auto_payment" class="payment_gateway_auto_payment">
                        <input type="hidden" name="status" value="cancel">
                        <div class="mb-4">
                            <label for="reason" class="form-label">{{ localize('Reason') }} <span
                                    class="text-danger ms-1">*</span></label>
                            <textarea name="reason" class="form-control" id="reason" cols="10" rows="5" required></textarea>

                            @if ($errors->has('reason'))
                                <span class="text-danger">{{ $errors->first('reason') }}</span>
                            @endif
                        </div>
          

                        <div class="justify-content-center pb-3">
                            <button type="button" class="btn btn-secondary mt-2"
                            data-bs-dismiss="modal">{{ localize('Cancel') }}</button>
                            <button type="submit" class="btn btn-danger mt-2"
                                >{{ localize('Procced') }}</button>
                           
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts-common')
    <script>
        "use strict";

        // handle package payment
        function handlePackageActive($this) {
            let package_history_id = $($this).data('package_history_id');
            let is_carried_over = $($this).data('is_carried_over');
            $('#package_history_id').val(package_history_id);
            if (is_carried_over == 0) {
                $('.carried_over_info').addClass('d-none')
            } else {
                $('.carried_over_info').removeClass('d-none')
            }
            showactivePackageNow();
        }

        // show package payment modal
        function showactivePackageNow() {
            $('#activePackageNow').modal('show')
        }

        // handle package payment
        function handleRecurringPayment($this) {
       
            let package_history_id = $($this).data('package_history_id');
            let gateway            = $($this).data('gateway');
            let type               = $($this).data('type');

            $('.package_history_id').val(package_history_id);
            $('.payment_gateway_auto_payment').val(gateway);
            
            if(type == 'active') {
                $('#activeRecurringPaymentNow').modal('show');
            }else if(type =='cancel'){
                $('#cancelReecurringPaymentNow').modal('show');
            }
        }



        // clear data
        function clearData() {
            $('#package_history_id').val('');
        }
    </script>
@endsection
