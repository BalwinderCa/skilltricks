@extends('layouts.auth')

@section('title')
    {{ localize('Login') }}
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

            <div class="text-center mb-5">
                <h2 class="h4 fw-bold">{{ systemSettingsLocalization('login_rightbar_title') }}</h2>
                <p class="text-muted">{{ systemSettingsLocalization('login_rightbar_sub_title') }}</p>
            </div>

            <!--social login-->
            @include('auth.inc.social')
            <!--social login-->

            <!--form login-->
            <form action="{{ route('login') }}" method="POST" id="login-form" class="mt-4 register-form">
                @csrf
                @if (getSetting('enable_recaptcha') == 1)
                    {!! RecaptchaV3::field('recaptcha_token') !!}
                @endif
                <input type="hidden" name="login_with" class="login_with" value="email">
                <div class="row">
                    <div class="col-sm-12">
                        <span class="login-email @if (old('login_with') == 'phone') d-none @endif">
                            <label for="email" class="mb-1">{{ localize('Email') }}<span class="text-danger">
                                    *</span></label>
                            <div class="input-group">
                                <input type="email" class="form-control" placeholder="{{ localize('Enter your email') }}"
                                    id="email" required aria-label="email" name="email" value="{{ old('email') }}">
                            </div>
                            <div class="text-end">
                                <small class="">
                                    <a href="javascript:void(0);" class="fs-sm login-with-phone-btn"
                                        onclick="handleLoginWithPhone()">
                                        {{ localize('Login with phone?') }}</a>
                                </small>
                            </div>
                        </span>

                        <span class="login-phone @if (old('login_with') == 'email' || old('login_with') == '') d-none @endif">
                            <label for="phone" class="mb-1">{{ localize('Phone') }}<span class="text-danger">
                                    *</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="+xxxxxxxxxx" id="phone"
                                    aria-label="phone" name="phone" value="{{ old('phone') }}">
                            </div>
                            <div class="text-end">
                                <small class="">
                                    <a href="javascript:void(0);" class="fs-sm login-with-email-btn"
                                        onclick="handleLoginWithEmail()">
                                        {{ localize('Login with email?') }}</a>
                                </small>
                            </div>
                        </span>
                    </div>

                    <div class="col-sm-12">
                        <label for="password" class="mb-1">{{ localize('Password') }} <span
                                class="text-danger">*</span></label>
                        <div class="input-group mb-3">
                            <input type="password" class="form-control" name="password" id="password"
                                placeholder="{{ localize('Enter your password') }}" aria-label="Password" required>
                        </div>
                    </div>

                    <!--demo credentials-->
                    @if (env('DEMO_MODE') == 'On')
                        <div class="row my-3">
                            <div class="col-12">
                                <label class="fw-bold">Admin Access</label>
                                <div class="d-flex flex-wrap align-items-center justify-content-between border-bottom pb-3">
                                    <small>admin@themetags.com</small>
                                    <small>123456</small>
                                    <button class="btn btn-sm btn-secondary py-0 px-2" type="button"
                                        onclick="copyAdmin()">Copy</button>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <label class="fw-bold">Customer Access</label>
                                <div class="d-flex flex-wrap align-items-center justify-content-between">
                                    <small>customer@themetags.com</small>
                                    <small>123456</small>

                                    <button class="btn btn-sm btn-secondary py-0 px-2" type="button"
                                        onclick="copyCustomer()">Copy</button>
                                </div>
                            </div>
                        </div>
                    @endif
                    <!--demo credentials-->


                    <div class="col-12">
                        <button type="submit" class="btn btn-primary mt-3 d-block w-100 sign-in-btn"
                            onclick="handleSubmit()">{{ localize('Sign In') }}</button>
                    </div>
                </div>

                <p class="font-monospace fw-medium text-center text-muted mt-3 pt-4 mb-0">
                    {{ localize("Don't have an Account?") }} <a href="{{ route('register') }}"
                        class="text-decoration-none">{{ localize('Sign Up') }}</a>
                    <br>
                    <a href="{{ route('password.request') }}"
                        class="text-decoration-none">{{ localize('Forgot Password') }}</a>
                </p>
            </form>
            <!--form login-->
        </div>
    </section>
    <!--login registration section end-->
@endsection


@section('scripts')
    <script>
        "use strict";

        // copyAdmin
        function copyAdmin() {
            $('#email').val('admin@themetags.com');
            $('#password').val('123456');
        }

        // copyCustomer
        function copyCustomer() {
            $('#email').val('customer@themetags.com');
            $('#password').val('123456');
        }

        // change input to phone
        function handleLoginWithPhone() {
            $('.login_with').val('phone');

            $('.login-email').addClass('d-none');
            $('.login-email input').prop('required', false);

            $('.login-phone').removeClass('d-none');
            $('.login-phone input').prop('required', true);
        }

        // change input to email
        function handleLoginWithEmail() {
            $('.login_with').val('email');
            $('.login-email').removeClass('d-none');
            $('.login-email input').prop('required', true);

            $('.login-phone').addClass('d-none');
            $('.login-phone input').prop('required', false);
        }


        // disable login button
        function handleSubmit() {
            $('#login-form').on('submit', function(e) {
                $('.sign-in-btn').prop('disabled', true);
            });
        }
    </script>
@endsection
