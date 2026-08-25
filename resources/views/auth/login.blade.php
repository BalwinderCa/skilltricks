@extends('layouts.auth')

@section('title')
    {{ localize('Login') }}
@endsection

@section('contents')


@include('auth.inc.authStyles')
  
    <!--   <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/> -->
    

  

    <div class="login-page">
        <div class="container-fluid p-0">
            <div class="row">
                {{-- Column stretches to the row height (set by the image panel), so the
                     form can center against it rather than hanging off the logo. --}}
                <div class="col-lg-6 mt-3 d-flex flex-column">
                    <div class="img-wrap d-flex">
                     <a href="{{ route('home') }}" class="navbar-brand d-flex justify-content-center mb-2 text-decoration-none">
                        <img src="{{ staticAsset('newfronted/Assets/logo-wordmark.png') }}" alt="Skilltricks">
                      </a>
                    </div>
                    <div class="flex-grow-1 d-flex align-items-center w-md-50">
                        <div class="w-100 px-4 text-center text-extra-small w-lg-50">
                            <div class="container-tight">
                                
                                <h1 class="ttl">Sign in</h1>
                                <div class="row gx-3">
                                    <div class="col-lg-12">
                                         @include('auth.inc.social')
                                    </div>
                                </div>
                                <div class="my-3 d-flex align-items-center gap-3 text-black opacity-60 dark-text-white dark-opacity-60">
                                    <span class="d-inline-block flex-grow-1 bg-secondary opacity-10" style="height: 1px;"></span>
    
                                    or
                                    <span class="d-inline-block flex-grow-1 bg-secondary opacity-10" style="height: 1px;"></span>
                                </div>
                        <form action="{{ route('login') }}" method="POST" id="login-form" class="mt-4 register-form"> 
                            @csrf
                            @if (getSetting('enable_recaptcha') == 1)
                                {!! RecaptchaV3::field('recaptcha_token') !!}
                            @endif
                            <input type="hidden" name="login_with" class="login_with" value="email">        
                                    <div class="form-group position-relative w-100 mb-1">
                                        <label>Email Address</label>
                                         <input type="email" class="form-control" placeholder="{{ localize('Enter your email') }}"
                                    id="email" required aria-label="email" name="email" value="{{ old('email') }}">
                                    </div>
                                    <div class="form-group position-relative w-100">
                                        <div class="position-relative">
                                            <label>Password</label>
                                            <div class="position-relative">
                                                <input type="password" class="form-control" name="password" id="password" placeholder="{{ localize('Enter your password') }}" aria-label="Password" required>
                                                <span class="toggle-password" onclick="togglePassword()">
                                                    <i id="eye-icon" class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="my-2 d-flex justify-content-between w-100">
                                        <div class="grow">
                                            <div class="lqd-input-container relative">
                                                <label class="lqd-input-label d-flex align-items-center" for="remember">
                                                    <input id="remember" class="lform-control" name="remember" type="checkbox">
                                                    <span class="lqd-input-label-txt mx-1"> Remember me</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="ms-auto text-end">
                                            <a class="text-indigo-600" href="{{ route('password.request') }}">Forgot Password?</a>
                                        </div>
                                    </div>
                                    <button class="btn btn-md btn-blue mt-2" type="submit" 
                            onclick="handleSubmit()"> Sign in</button>
                                </form>
                            </div>
                            <div class="text-muted mt-4 mb-2 lqd-input-label-txt ">
                                Don't have account yet?
                                <a class="font-medium text-indigo-600 underline fs-12" href="{{ route('register') }}">
                                    Sign up
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            
                <div class="col-lg-6">
                    <div class="hiddenbg" style="background-image: url('{{ asset('public/images/bg-auth.jpg') }}');">
                        <img class="translate-x-[27%]" src="{{ asset('public/images/dash-mockup.jpg') }}" alt="" />
                    </div>
                </div>
            </div>
            
        </div>
    </div>



@endsection






@section('scripts')

    <script>
        function togglePassword() {
            var passwordInput = document.getElementById("password");
            var eyeIcon = document.getElementById("eye-icon");
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }
    </script>
    <script>
        "use strict";

        // copyAdmin
        function copyAdmin() {
            $('#email').val('admin@example.com');
            $('#password').val('123456');
        }

        // copyCustomer
        function copyCustomer() {
            $('#email').val('customer@example.com');
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













{{--@extends('layouts.auth')

@section('title')
    {{ localize('Login') }}
@endsection

@section('contents')
    <!--login registration section start-->
<!--     <section class="tt-login-registration min-vh-100 d-flex overflow-hidden bg-dark bg-image-hero align-items-center">

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
                    @if (config('custom.demo_mode') == 'On')
                        <div class="row my-3">
                            <div class="col-12">
                                <label class="fw-bold">Admin Access</label>
                                <div class="d-flex flex-wrap align-items-center justify-content-between border-bottom pb-3">
                                    <small>admin@example.com</small>
                                    <small>123456</small>
                                    <button class="btn btn-sm btn-secondary py-0 px-2" type="button"
                                        onclick="copyAdmin()">Copy</button>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <label class="fw-bold">Customer Access</label>
                                <div class="d-flex flex-wrap align-items-center justify-content-between">
                                    <small>customer@example.com</small>
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
    </section> -->
    <!--login registration section end-->
@endsection


@section('scripts')
    <script>
        // "use strict";

        // // copyAdmin
        // function copyAdmin() {
        //     $('#email').val('admin@example.com');
        //     $('#password').val('123456');
        // }

        // // copyCustomer
        // function copyCustomer() {
        //     $('#email').val('customer@example.com');
        //     $('#password').val('123456');
        // }

        // // change input to phone
        // function handleLoginWithPhone() {
        //     $('.login_with').val('phone');

        //     $('.login-email').addClass('d-none');
        //     $('.login-email input').prop('required', false);

        //     $('.login-phone').removeClass('d-none');
        //     $('.login-phone input').prop('required', true);
        // }

        // // change input to email
        // function handleLoginWithEmail() {
        //     $('.login_with').val('email');
        //     $('.login-email').removeClass('d-none');
        //     $('.login-email input').prop('required', true);

        //     $('.login-phone').addClass('d-none');
        //     $('.login-phone input').prop('required', false);
        // }


        // // disable login button
        // function handleSubmit() {
        //     $('#login-form').on('submit', function(e) {
        //         $('.sign-in-btn').prop('disabled', true);
        //     });
        // }
    </script>
@endsection--}}
