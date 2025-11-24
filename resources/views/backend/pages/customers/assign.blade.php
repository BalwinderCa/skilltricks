@extends('backend.layouts.master')

@section('title')
    {{ localize('Assign Package') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Assign Package') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('admin.customers.index') }}">{{ localize('Customers') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Assign Package') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row mb-4 g-4">

                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ route('admin.customers.assignPackageStore') }}" method="POST" class="pb-650">
                        @csrf
                        <input type="hidden" name="id" value="{{ $user->id }}">
                        <input type="hidden" name="admin_assign_package" value="1">
                        <!--basic information start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>

                                <div class="mb-4">
                                    <label for="name" class="form-label">{{ localize('Customer Name') }}</label>
                                    <input class="form-control" type="text" id="name"
                                        placeholder="{{ localize('Type Customer name') }}" name="name"
                                        value="{{ $user->name }}" disabled>

                                </div>

                                <div class="mb-4">
                                    <label class="form-label">{{ localize('Package') }}<span
                                            class="text-danger ms-1">*</span></label>
                                    <select class="select2 form-control package_select" data-toggle="select2" name="package"
                                        required>
                                        @foreach ($packages as $package)
                                            <option value="{{ $package->id }}" data-price={{ $package->price }}
                                                {{ old('package') == $package->id ? 'selected' : '' }}>
                                                {!! html_entity_decode($package->title) !!}
                                                {{ '[' . ($package->price == 0 || !$package->price ? 'Free' : 'Price(' . $package->price . ')') . ']' }}
                                                {{ '[' . $package->package_type . ']' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('package'))
                                        <span class="text-danger">{{ $errors->first('package') }}</span>
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">{{ localize('Payment Method') }}<span
                                            class="text-danger ms-1"></span></label>
                                    <select class="select2 form-control payment_method" data-toggle="select2"
                                        name="payment_method" required>
                                        <option value="">{{ localize('Select Payment Method') }}</option>
                                        @include('backend.pages.customers.inc.pg_options')
                                    </select>
                                    @if ($errors->has('payment_method'))
                                        <span class="text-danger">{{ $errors->first('payment_method') }}</span>
                                    @endif
                                </div>

                                <div class="mb-4 offline_payment_method_wrapper d-none">
                                    <label class="form-label">{{ localize('Offline Payment Method') }}<span
                                            class="text-danger ms-1">*</span></label>
                                    <select class="select2 form-control offline_payment_method_id" data-toggle="select2"
                                        name="offline_payment_method_id">
                                        <option value="">{{ localize('Select Offline Method') }}</option>
                                        @foreach ($payment_methods as $method)
                                            <option value="{{ $method->id }}"
                                                {{ old('offline_payment_method_id') == $method->id ? 'selected' : '' }}>
                                                {{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('offline_payment_method_id'))
                                        <span class="text-danger">{{ $errors->first('offline_payment_method_id') }}</span>
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <label for="paid_amount" class="form-label">{{ localize('Paid Amount') }}<span
                                            class="text-danger ms-1">*</span></label>
                                    <input class="form-control" type="number" id="paid_amount"
                                        placeholder="{{ localize('Type Amount') }}" name="paid_amount"
                                        value="{{ old('paid_amount') ?? 0 }}" min="0" required>
                                    @if ($errors->has('paid_amount'))
                                        <span class="text-danger">{{ $errors->first('paid_amount') }}</span>
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <label for="payment_detail"
                                        class="form-label">{{ localize('Payment Details') }}</label>
                                    <textarea class="form-control" type="text" id="payment_detail" placeholder="{{ localize('Type payment detail') }}"
                                        name="payment_detail" value="{{ old('payment_detail') }}"></textarea>
                                    @if ($errors->has('payment_detail'))
                                        <span class="text-danger">{{ $errors->first('payment_detail') }}</span>
                                    @endif
                                </div>
                                <div class="mb-4">
                                    <div class="form-check tt-checkbox">
                                        <label for="carry_forward" class="form-check-label fw-medium ">
                                            <input class="form-check-input cursor-pointer" type="checkbox"
                                                id="carry_forward" name="carry_forward"
                                                {{ getSetting('carry_forward') == 1 ? 'checked' : '' }}>
                                            <strong>{{ localize('Balance Carry Forward: ') }}</strong>
                                            {{ localize('Remaining balance from active package(only for active) will be added to next package balance.') }}

                                        </label>
                                        {{ localize('This service is applicable for same package - Lifetime to Lifetime, Yearly to Yearly, Monthly to Monthly and Prepaid to Prepaid pacakge.') }}

                                        <div class="badge bg-soft-primary rounded-pill">
                                            {{ localize('Your global setting for Balance Carry Forward is:') }}
                                            @if (getSetting('carry_forward') == 1)
                                                <span class="badge bg-success">{{ localize('Enabled') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ localize('Disabled') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $oldPackageHistory = activePackageHistory($user->id);
                                @endphp

                                @if (!empty($oldPackageHistory) && $oldPackageHistory->subscription_status == 1)
                                    <div class="mb-4">
                                        <div class="form-check tt-checkbox">
                                            <label for="active_now" class="form-check-label fw-medium ">
                                                <input class="form-check-input cursor-pointer" type="checkbox"
                                                    id="active_now" name="active_now">
                                                <strong>{{ localize('Active This Package Now ') }}</strong>
                                            </label>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <!--basic information end-->

                        <!-- submit button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i> {{ localize('Save Changes') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- submit button end -->

                    </form>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar d-none d-xl-block">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Customer Information') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('Basic Information') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


@section('scripts')
    <script>
        $('.payment_method').on('change', function() {
            let value = this.value;
            if (value == "offline") {
                $('.offline_payment_method_wrapper').removeClass('d-none');
                $('.offline_payment_method_id').prop('required', true);
            } else {
                $('.offline_payment_method_wrapper').addClass('d-none');
                $('.offline_payment_method_id').prop('required', false);
            }
        });

        $('.package_select').on('change', function() {
            let price = $(this).find('option:selected').data('price');
            $('#paid_amount').val(price);
        });

        $(document).ready(function() {
            let price = $('.package_select').find('option:selected').data('price');
            $('#paid_amount').val(price);
        });
    </script>
@endsection
