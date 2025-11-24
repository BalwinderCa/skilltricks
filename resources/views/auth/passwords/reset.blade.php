@extends('layouts.auth')

@section('title')
    {{ localize('Reset Password') }}
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
                <h2 class="h4 fw-bold">{{ getSetting('login_rightbar_title') }}</h2>
                <p class="text-muted">{{ getSetting('login_rightbar_sub_title') }}</p>
            </div>


            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <!--form login-->
            <form action="{{ route('password.update') }}" method="POST" id="login-form" class="mt-4 register-form">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="row">
                    <div class="col-sm-12">
                        <label for="email" class="mb-1">{{ localize('Email') }}<span class="text-danger">
                                *</span></label>
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="{{ localize('Enter your email') }}"
                                id="email" aria-label="email" name="email" @error('email') is-invalid @enderror
                                value="{{ $email ?? old('email') }}" required>
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <label for="password" class="mb-1">{{ localize('Password') }} <span
                                class="text-danger">*</span></label>
                        <div class="input-group mb-3">
                            <input type="password" class="form-control" name="password" id="password"
                                placeholder="{{ localize('Enter your password') }}" aria-label="Password" required>
                        </div>
                    </div>


                    <div class="col-sm-12">
                        <label for="password_confirmation" class="mb-1">{{ localize('Confirm Password') }} <span
                                class="text-danger">*</span></label>
                        <div class="input-group mb-3">
                            <input type="password" class="form-control" name="password_confirmation"
                                id="password_confirmation" placeholder="{{ localize('Confirm password') }}"
                                aria-label="password_confirmation" required>
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
