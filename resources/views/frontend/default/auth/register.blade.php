@extends('layouts.auth')


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
        "use strict";

        // disable login button
        function handleSubmit() {
            $('#login-form').on('submit', function(e) {
                $('.sign-in-btn').prop('disabled', true);
            });
        }
    </script>
@endsection
