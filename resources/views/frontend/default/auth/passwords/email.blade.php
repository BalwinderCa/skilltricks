@extends('layouts.auth')

@section('title')
    {{ localize('Forgot Password') }}
@endsection

@section('contents')
    <!--login registration section start-->
    <section class="tt-login-registration min-vh-100 d-flex overflow-hidden bg-dark bg-image-hero align-items-center">
        @include('auth.inc.loginSidebar')
        <!--right bar content-->
        <div class="tt-login-registration-form-wrap max-w-30 bg-secondary-subtle p-4 p-lg-5 min-vh-100">
            <a href="{{ route('home') }}" class="navbar-brand d-flex justify-content-center mb-5 text-decoration-none">
                <img src="{{ uploadedAsset(getSetting('navbar_logo_dark')) }}" alt="logo" class="img-fluid logo-color" />
            </a>

            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <!--form login-->
            <form action="{{ route('password.email') }}" method="POST" id="login-form" class="mt-4 register-form">
                @csrf

                <input type="hidden" name="reset_with" class="reset_with" value="email">
                <div class="row">
                    <div class="col-sm-12">
                        <span class="reset-email @if (old('reset_with') == 'phone') d-none @endif">
                            <label for="email" class="mb-1">{{ localize('Email') }}<span class="text-danger">
                                    *</span></label>
                            <div class="input-group">
                                <input type="email" class="form-control" placeholder="{{ localize('Enter your email') }}"
                                    id="email" @if (old('reset_with') != 'phone') required @endif aria-label="email"
                                    name="email" value="{{ old('email') }}">
                            </div>
                            <div class="text-end">
                                <small class="">
                                    <a href="javascript:void(0);" class="fs-sm login-with-phone-btn"
                                        onclick="handleResetWithPhone()">
                                        {{ localize('Reset with phone?') }}</a>
                                </small>
                            </div>
                        </span>

                        <span class="reset-phone @if (old('reset_with') == 'email' || old('reset_with') == '') d-none @endif">
                            <label for="phone" class="mb-1">{{ localize('Phone') }}<span class="text-danger">
                                    *</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="+xxxxxxxxxx" id="phone"
                                    aria-label="phone" name="phone" value="{{ old('phone') }}">
                            </div>
                            <div class="text-end">
                                <small class="">
                                    <a href="javascript:void(0);" class="fs-sm login-with-email-btn"
                                        onclick="handleResetWithEmail()">
                                        {{ localize('Reset with email?') }}</a>
                                </small>
                            </div>
                        </span>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary mt-3 d-block w-100 sign-in-btn"
                            onclick="handleSubmit()">{{ localize('Reset Password') }}</button>
                    </div>
                </div>


            </form>
            <!--form login-->
        </div>
    </section>
    <!--login registration section end-->
@endsection



@section('scripts')
    <script>
        "use strict";


        // change input to phone
        function handleResetWithPhone() {
            $('.reset_with').val('phone');

            $('.reset-email').addClass('d-none');
            $('.reset-email input').prop('required', false);

            $('.reset-phone').removeClass('d-none');
            $('.reset-phone input').prop('required', true);
        }

        // change input to email
        function handleResetWithEmail() {
            $('.reset_with').val('email');
            $('.reset-email').removeClass('d-none');
            $('.reset-email input').prop('required', true);

            $('.reset-phone').addClass('d-none');
            $('.reset-phone input').prop('required', false);
        }

        // disable login button
        function handleSubmit() {
            $('#login-form').on('submit', function(e) {
                $('.sign-in-btn').prop('disabled', true);
            });
        }
    </script>
@endsection
