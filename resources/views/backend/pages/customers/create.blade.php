@extends('backend.layouts.master')

@section('title')
    {{ localize('Add New Customer') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Add New Customer') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('admin.customers.index') }}">{{ localize('Customers') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('New Customer') }}</li>
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
                    <form action="{{ route('admin.customers.store') }}" method="POST" class="pb-650">
                        @csrf
                        <!--basic information start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>

                                <div class="mb-4">
                                    <label for="name" class="form-label">{{ localize('Customer Name') }}
                                        <span class="text-danger ms-1">*</span></label>
                                    <input class="form-control" type="text" id="name"
                                        placeholder="{{ localize('Type Customer name') }}" name="name"
                                        value="{{ old('name') }}">
                                    @if ($errors->has('name'))
                                        <span class="text-danger">{{ $errors->first('name') }}</span>
                                    @endif
                                </div>


                                <div class="mb-4">
                                    <label for="email" class="form-label">{{ localize('Customer Email') }}<span
                                            class="text-danger ms-1">*</span></label>
                                    <input class="form-control" type="email" id="email"
                                        placeholder="{{ localize('Type Customer email') }}" name="email"
                                        value="{{ old('email') }}" required>
                                    @if ($errors->has('email'))
                                        <span class="text-danger">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <label for="phone" class="form-label">{{ localize('Customer Phone') }}<span
                                            class="text-danger ms-1">{{ @getSetting('registration_with') == 'email_and_phone' ? '*' : '' }}</span></label>
                                    <input class="form-control" type="text" id="phone"
                                        placeholder="{{ localize('Type Customer phone') }}" name="phone"
                                        value="{{ old('phone') }}"
                                        {{ @getSetting('registration_with') == 'email_and_phone' ? 'required' : '' }}>
                                    @if ($errors->has('phone'))
                                        <span class="text-danger">{{ $errors->first('phone') }}</span>
                                    @endif
                                </div>


                                <div class="mb-4">
                                    <label for="password" class="form-label">{{ localize('Password') }}<span
                                            class="text-danger ms-1">*</span></label>
                                    <input class="form-control" type="password" id="password"
                                        placeholder="{{ localize('Type password') }}" name="password">
                                    @if ($errors->has('password'))
                                        <span class="text-danger">{{ $errors->first('password') }}</span>
                                    @endif
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
                                                {{ '[' . ($package->price == 0 || !$package->price ? 'Free' : 'Price(' . ($package->discount_status == 1 && $package->discount ? $package->discount_price : $package->price) . ')') . ']' }}
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
                                        name="payment_method">
                                        <option value="">{{ localize('Select Payment Method') }}</option>
                                        @include('backend.pages.customers.inc.pg_options')
                                    </select>
                                    @if ($errors->has('payment_method'))
                                        <span class="text-danger">{{ $errors->first('payment_method') }}</span>
                                    @endif
                                </div>

                                <div
                                    class="mb-4 offline_payment_method_wrapper {{ old('payment_method') == 'offline' ? '' : 'd-none' }}">
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
                                        value="{{ old('paid_amount', 0) }}" min="0" required>
                                    @if ($errors->has('paid_amount'))
                                        <span class="text-danger">{{ $errors->first('paid_amount') }}</span>
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <label for="payment_detail"
                                        class="form-label">{{ localize('Payment Details') }}</label>
                                    <textarea class="form-control" type="text" id="payment_detail"
                                        placeholder="{{ localize('Type payment detail') }}" name="payment_detail" value="{{ old('payment_detail') }}"></textarea>
                                    @if ($errors->has('payment_detail'))
                                        <span class="text-danger">{{ $errors->first('payment_detail') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!--basic information end-->

                        <!-- submit button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i> {{ localize('Save Customer') }}
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
