@extends('backend.layouts.master')

@section('title')
    {{ localize('Update Customer') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Update Customer') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('admin.customers.index') }}">{{ localize('Customers') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Update Customer') }}</li>
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
                    <form action="{{ route('admin.customers.update') }}" method="POST" class="pb-650">
                        @csrf
                        <!--basic information start-->
                        <input type="hidden" name="id" value="{{ $user->id }}">
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>

                                <div class="mb-4">
                                    <label for="name" class="form-label">{{ localize('Customer Name') }}
                                        <span class="text-danger ms-1">*</span></label>
                                    <input class="form-control" type="text" id="name"
                                        placeholder="{{ localize('Type Customer name') }}" name="name"
                                        value="{{ $user->name }}" required>
                                    @if ($errors->has('name'))
                                        <span class="text-danger">{{ $errors->first('name') }}</span>
                                    @endif
                                </div>


                                <div class="mb-4">
                                    <label for="email" class="form-label">{{ localize('Customer Email') }}<span
                                            class="text-danger ms-1">*</span></label>
                                    <input class="form-control" type="email" id="email"
                                        placeholder="{{ localize('Type Customer email') }}" name="email"
                                        value="{{ $user->email }}" required>
                                    @if ($errors->has('email'))
                                        <span class="text-danger">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <label for="phone" class="form-label">{{ localize('Customer Phone') }}<span
                                            class="text-danger ms-1">*</span></label>
                                    <input class="form-control" type="text" id="phone"
                                        placeholder="{{ localize('Type Customer phone') }}" name="phone"
                                        value="{{ $user->phone }}" required>
                                    @if ($errors->has('phone'))
                                        <span class="text-danger">{{ $errors->first('phone') }}</span>
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label">{{ localize('Password') }}</label>
                                    <input class="form-control" type="password" id="password"
                                        placeholder="{{ localize('Type password') }}" name="password">
                                    @if ($errors->has('password'))
                                        <span class="text-danger">{{ $errors->first('password') }}</span>
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
    </script>
@endsection
