@extends('layouts.auth')

@section('title')
    {{ localize('Update Password') }}
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
            <form action="{{ route('forgotPw.update') }}" method="POST" id="login-form" class="mt-4 register-form">
                @csrf

                <div class="row">
                    <div class="col-sm-12 mb-3">
                        <label for="verification_code" class="mb-1">{{ localize('Verification Code') }}<span
                                class="text-danger">
                                *</span></label>
                        <div class="input-group">
                            <input type="text" id="verification_code" name="verification_code" class="form-control"
                                placeholder="{{ localize('Enter your verification code') }}" class="theme-input"required>
                        </div>

                    </div>

                    <div class="col-sm-12 mb-3">
                        <label for="password" class="mb-1">{{ localize('Password') }}<span class="text-danger">
                                *</span></label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" id="password"
                                placeholder="{{ localize('Password') }}"
                                class="theme-input @error('password') is-invalid @enderror" required>
                        </div>
                    </div>


                    <div class="col-sm-12 mb-3">
                        <label for="password_confirmation" class="mb-1">{{ localize('Confirm Password') }}<span
                                class="text-danger">
                                *</span></label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" class="form-control"
                                id="password_confirmation" placeholder="{{ localize('Confirm Password') }}"
                                class="theme-input @error('password') is-invalid @enderror" required>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary mt-3 d-block w-100 sign-in-btn"
                            onclick="handleSubmit()">{{ localize('Update Password') }}</button>
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

        // disable login button
        function handleSubmit() {
            $('#login-form').on('submit', function(e) {
                $('.sign-in-btn').prop('disabled', true);
            });
        }
    </script>
@endsection
