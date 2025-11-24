@extends('layouts.auth')

@section('title')
    {{ localize('Verify Phone Number') }}
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

            <!--form login-->
            <form action="{{ route('phone.verification.confirmation') }}" method="POST" id="login-form"
                class="mt-4 register-form">
                @csrf

                <div class="row">
                    <div class="col-sm-12 mb-3">
                        <label for="phone" class="mb-1">{{ localize('Phone') }}<span class="text-danger">
                                *</span></label>
                        <div class="input-group">
                            <input type="text" id="phone" name="phone" class="form-control"
                                placeholder="{{ localize('Enter phone number with country code') }}"
                                value="{{ $user->phone }}" class="theme-input" required disabled>
                        </div>
                    </div>

                    <div class="col-sm-12 mb-3">
                        <label for="verification_code" class="mb-1">{{ localize('Verification Code') }}<span
                                class="text-danger">
                                *</span></label>
                        <div class="input-group">
                            <input type="text" id="verification_code" name="verification_code" class="form-control"
                                placeholder="{{ localize('Enter your verification code') }}" class="theme-input"required>
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <button type="submit" class="btn btn-primary mt-3 d-block w-100 sign-in-btn"
                            onclick="handleSubmit()">{{ localize('Verify') }}</button>
                    </div>
                    <p class="mb-0">{{ localize("Don't have get any code?") }} <a
                            href="">{{ localize('Resend') }}</a></p>
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

        // disable login button
        function handleSubmit() {
            $('#login-form').on('submit', function(e) {
                $('.sign-in-btn').prop('disabled', true);
            });
        }
    </script>
@endsection
