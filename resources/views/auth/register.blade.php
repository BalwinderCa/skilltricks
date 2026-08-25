@extends('layouts.auth')


@section('title')
    {{ localize('Sign Up') }}
@endsection


@section('contents')
@include('auth.inc.authStyles')
    <!--login registration section start-->
  

    <!-- <link rel="stylesheet" href="assets/css/admin_custom.css"/>
     -->

    @section('title')
        {{ localize('Sign Up') }}
    @endsection
    <div class="login-page">
        <div class="container-fluid p-0">
            <div class="row">
                {{-- Column stretches to the row height (set by the image panel), so the
                     form can center against it rather than hanging off the logo. --}}
                <div class="col-lg-6 mt-3 d-flex flex-column">
                    <div class="img-wrap d-flex">
                      <a href="{{ route('home') }}" class="navbar-brand d-flex justify-content-center mb-3 text-decoration-none">
                        <img src="{{ staticAsset('newfronted/Assets/logo-wordmark.png') }}" alt="Skilltricks">
                      </a>
                    </div>
                    <div class="flex-grow-1 d-flex flex-md-column align-items-md-center justify-content-md-center w-md-50">
                        <div class="w-100 px-4 text-center text-extra-small w-lg-50">
                            <div class="container-tight">
                                
                                <h1 class="ttl">Sign up</h1>
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

                            {!! Form::open(['route'=>'register', 'method'=>'POST' , 'id'=>"login-form", 'class'=>"w-100 form-section"]) !!}
                            <input type="hidden" name="login_with" class="login_with" value="email">
                            @if (getSetting('enable_recaptcha') == 1)
                                {!! RecaptchaV3::field('recaptcha_token') !!}
                            @endif
                                    <div class="row gx-2">
                                        <div class="col-md-12">
                                            <div class="form-group position-relative">
                                                <label>Full Name</label>
                                             {!! Form::text('name', old('name'), ['class'=>"form-control", 'id'=>"name",
                                             'placeholder'=>localize('Type full name'), 'aria-label'=>"name", 'required'=>true]) !!}
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group position-relative">
                                                <label>Mobile Number</label>
                                                @php
                                                    $required = getSetting('registration_with') == 'email_and_phone' ? true :false;
                                                @endphp
                                                 {!! Form::text('phone', old('phone'), ['class'=>"form-control", 'name'=>"phone", 'id'=>"phone",
                                                    'placeholder'=>localize('+880xxxxxxxxxx'), 'aria-label'=>"phone", 'required'=>$required]) !!}
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group position-relative">
                                                <label>Are you representing a company?</label>
                                                <div class="d-flex align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <input type="radio" id="yes" name="company" value="yes" required>&nbsp;
                                                        <label for="yes">Yes</label>
                                                    </div> &nbsp;
                                                    <div class="d-flex align-items-center mx-2">
                                                        <input type="radio" id="no" name="company" value="no" required>&nbsp;
                                                        <label for="no">No</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group position-relative">
                                                <label>Email Address</label>
                                                {!! Form::email('email', old('email'), ['class'=>"form-control", 'name'=>"email", 'id'=>"email",
                                                 'placeholder'=>localize('Type your email'), 'aria-label'=>"email", 'required'=>true]) !!}
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <div class="form-group position-relative">
                                                <label>Password</label>
                                                <div class="position-relative">
                                                    {!! Form::password('password', ['class'=>"form-control", 'id'=>"password",
                                                    'placeholder'=>localize('Enter your password'), 'aria-label'=>"Password", 'required'=>true]) !!}
                                                    <span class="toggle-password" onclick="togglePassword('password', 'eye-icon1')">
                                                        <i id="eye-icon1" class="fas fa-eye"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group position-relative">
                                                <label>Confirm Your Password</label>
                                                <div class="position-relative">
                                                    {!! Form::password('password_confirmation', ['class'=>"form-control", 'name'=>"password_confirmation",
                                                    'id'=>"password_confirmation", 'placeholder'=>localize('Confirm password'),
                                                    'aria-label'=>"password_confirmation", 'required'=>true]) !!}
                                                    <span class="toggle-password" onclick="togglePassword('password_confirmation', 'eye-icon2')">
                                                        <i id="eye-icon2" class="fas fa-eye"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <button
                                                class="btn btn-md btn-blue mt-2"
                                                id="RegisterFormButton"
                                                type="submit"
                                             onclick="handleSubmit()">
                                                Sign up
                                            </button>
                                        </div>
                                    </div>
                            {!! Form::close() !!}
                            </div>
                            <div class="text-muted mt-4 mb-2">
                                Have an account?
                                <a class="font-medium text-indigo-600 underline fs-12" href="{{ route('login') }}">
                                    Sign in
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
        "use strict";

        // disable login button
        function handleSubmit() {
            $('#login-form').on('submit', function(e) {
                $('.sign-in-btn').prop('disabled', true);
            });
        }
    </script>
    <script>
       function togglePassword(inputId, eyeIconId) {
        var passwordInput = document.getElementById(inputId);
        var eyeIcon = document.getElementById(eyeIconId);

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
@endsection









































{{--@extends('layouts.auth')


@section('title')
    {{ localize('Sign Up') }}
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

            <!--social login-->
            @include('auth.inc.social')
            <!--social login-->

            <!--form login-->
          
                {!! Form::open(['route'=>'register', 'method'=>'POST' , 'id'=>"login-form", 'class'=>"mt-4 register-form"]) !!}
                <input type="hidden" name="login_with" class="login_with" value="email">
                <div class="row">
                    {!! RecaptchaV3::field('recaptcha_token') !!}
                    <div class="col-sm-12">
                        <label for="name" class="mb-1">{{ localize('Full Name') }} <span
                                class="text-danger">*</span></label>
                        <div class="input-group mb-3">                           
                                {!! Form::text('name', old('name'), ['class'=>"form-control", 'id'=>"name",
                                'placeholder'=>localize('Type full name'), 'aria-label'=>"name", 'required'=>true]) !!}
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <label for="email" class="mb-1">{{ localize('Email') }} <span
                                class="text-danger">*</span></label>
                        <div class="input-group mb-3">
                          
                                {!! Form::email('email', old('email'), ['class'=>"form-control", 'name'=>"email", 'id'=>"email",
                                'placeholder'=>localize('Type your email'), 'aria-label'=>"email", 'required'=>true]) !!}
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <label for="phone" class="mb-1">{{ localize('Phone') }}
                            @if (getSetting('registration_with') == 'email_and_phone')
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        @php
                            $required = getSetting('registration_with') == 'email_and_phone' ? true :false;
                        @endphp
                        <div class="input-group mb-3">                            
                                {!! Form::text('phone', old('phone'), ['class'=>"form-control", 'name'=>"phone", 'id'=>"phone",
                                'placeholder'=>localize('+880xxxxxxxxxx'), 'aria-label'=>"phone", 'required'=>$required]) !!}
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <label for="password" class="mb-1">{{ localize('Password') }} <span
                                class="text-danger">*</span></label>
                        <div class="input-group mb-3">                         
                                {!! Form::password('password', ['class'=>"form-control", 'id'=>"password",
                                'placeholder'=>localize('Enter your password'), 'aria-label'=>"Password", 'required'=>true]) !!}
                        </div>
                    </div>


                    <div class="col-sm-12">
                        <label for="password_confirmation" class="mb-1">{{ localize('Confirm Password') }} <span
                                class="text-danger">*</span></label>
                        <div class="input-group mb-3">
                          
                                {!! Form::password('password_confirmation', ['class'=>"form-control", 'name'=>"password_confirmation",
                                'id'=>"password_confirmation", 'placeholder'=>localize('Confirm password'),
                                'aria-label'=>"password_confirmation", 'required'=>true]) !!}
                        </div>
                    </div>
                    

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary mt-3 d-block w-100 sign-in-btn"
                            onclick="handleSubmit()">{{ localize('Sign Up') }}</button>
                    </div>
                </div>

                <p class="font-monospace fw-medium text-center text-muted mt-3 pt-4 mb-0">
                    {{ localize('Already have an Account?') }} <a href="{{ route('login') }}"
                        class="text-decoration-none">{{ localize('Sign In') }}</a>
                </p>
            {!! Form::close() !!}
            <!--form login-->
        </div>
    </section>
    <!--login registration section end-->
@endsection

@section('scripts')
    <script>
       /* "use strict";

        // disable login button
        function handleSubmit() {
            $('#login-form').on('submit', function(e) {
                $('.sign-in-btn').prop('disabled', true);
            });
        }*/
    </script>
@endsection --}}
















