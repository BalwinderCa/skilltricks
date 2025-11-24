@extends('layouts.auth')


@section('title')
    {{ localize('Sign Up') }}
@endsection


@section('contents')
<style>
    .form-group input:focus ~ label, .form-group textarea:focus ~ label, .form-group input:not(:placeholder-shown) ~ label, .form-group textarea:not(:placeholder-shown) ~ label {
  transform: translateY(0px);
  font-size: 14px;
 color: #7b8396;
}
body{background:transparent !important}
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'DM Sans', 'Space Grotesk', sans-serif;
}
html {
    scrollbar-width: none;
    -ms-overflow-style: none; 
}

html::-webkit-scrollbar {
    display: none;
}
.preloader-wrap{width: 150px;max-width: 100%;margin:auto;}

/* default CSS */
.text-primary{color: #fff  !important;}

/* Login page CSS */
.login-page .container {
    position: relative;
    width: 70vw;
    height: 80vh;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.3), 0 6px 20px 0 rgba(0, 0, 0, 0.3);
    overflow: hidden;
}
.login-page .container::before {
    content: "";
    position: absolute;
    top: 0;
    left: -50%;
    width: 100%;
    height: 101%;
    /* background: linear-gradient(-45deg, #df4adf, #520852); */
    background: linear-gradient(-45deg, #037ba0, #520852);
    z-index: 6;
    transform: translateX(100%);
    transition: 1s ease-in-out;
}
.login-page .signin-signup {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-around;
    z-index: 5;
}
.login-page form {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    width: 100%;
    min-width: 100%;
    padding: 0 10px;
}
.login-page form.sign-in-form {
    opacity: 1;
    transition: 0.5s ease-in-out;
    transition-delay: 1s;
}
.login-page form.sign-up-form {
    opacity: 0;
    transition: 0.5s ease-in-out;
    transition-delay: 1s;
}
.login-page .title {
    font-size: 35px;
    color: #1882ae;
    margin-bottom: 10px;
}
.login-page .input-field {
    width: 100%;
    height: 50px;
    background: #f0f0f0;
    margin: 10px 0;
    border: 2px solid #1882ae;
    border-radius: 50px;
    display: flex;
    align-items: center;
}
.login-page .input-field i {
    flex: 1;
    text-align: center;
    color: #666;
    font-size: 18px;
}
.login-page .input-field .form-control {
    flex: 5;
    background: none;
    border: none;
    outline: none;
    width: 100%;
    font-size: 16px;
    font-weight: 600;
    color: #444;
}
.login-page .input-field .form-control:focus{box-shadow: none}
.login-page .ttl{font-weight: 700;font-size: 36px;margin-bottom: 20px;font-family: "DM Sans";}
/* .login-page .btn {
    width: 150px;
    height: 50px;
    border: none;
    border-radius: 50px;
    background: #1882ae;
    color: #fff;
    font-weight: 600;
    margin: 10px 0;
    text-transform: uppercase;
    cursor: pointer;
}
.login-page .btn:hover {
    background: #000;
} */
.login-page .social-text {
    margin: 10px 0;
    font-size: 16px;
}
.login-page .social-media {
    display: flex;
    justify-content: center;
}
.login-page .content .img-wrap img{width: 150px;margin-bottom: 15px;}
.login-page .social-icon {
    height: 45px;
    width: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #444;
    border: 1px solid #444;
    border-radius: 50px;
    margin: 0 5px;
}
.login-page a {
    text-decoration: none;
}
.login-page .btn-outline-primary{border:1px solid #ddd;box-shadow: 0 0 1px rgba(0,0,0,0.2);}
.login-page .btn-outline-primary:hover{background: transparent;
  color: #000 !important;
  box-shadow: 0 0 4px rgba(0,0,0,0.2);
  border:
1px solid #ddd;}
.login-page .social-icon:hover {
    color: #1882ae;
    border-color:#1882ae;
}
.login-page .panels-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-around;
}
.login-page .panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-around;
    width: 35%;
    min-width: 238px;
    padding: 0 10px;
    text-align: center;
    z-index: 6;
}
.login-page .left-panel {
    pointer-events: none;
}
.login-page .content {
    color: #fff;
    transition: 1.1s ease-in-out;
    transition-delay: 0.5s;
}
.login-page .panel h3 {
    font-size: 24px;
    font-weight: 600;
}
.login-page .panel p {
    font-size: 15px;
    padding: 10px 0;
}
.login-page .image {
    width: 100%;
    transition: 1.1s ease-in-out;
    transition-delay: 0.4s;
}
.login-page .left-panel .image,
.login-page .left-panel .content {
    transform: translateX(-200%);
}
.login-page .right-panel .image,
.login-page .right-panel .content {
    transform: translateX(0);
}
.login-page .account-text {
    display: none;
}
/*Animation*/
.login-page .container.sign-up-mode::before {
    transform: translateX(0);
}
.login-page .container.sign-up-mode .right-panel .image,
.login-page .container.sign-up-mode .right-panel .content {
    transform: translateX(200%);
}
.login-page .container.sign-up-mode .left-panel .image,
.login-page .container.sign-up-mode .left-panel .content {
    transform: translateX(0);
}
.login-page .container.sign-up-mode form.sign-in-form {
    opacity: 0;
}
.login-page .container.sign-up-mode form.sign-up-form {
    opacity: 1;
}
.login-page .container.sign-up-mode .right-panel {
    pointer-events: none;
}
.login-page .container.sign-up-mode .left-panel {
    pointer-events: all;
}
/* ./Login page css */
.login-page .hiddenbg{padding: 150px 0px 150px 10px;overflow: hidden;}
.login-page .hiddenbg img{height: 500px;
  transform: translateX(27%);
  overflow:
hidden;
  border-radius:
35px;}
.login-page .img-wrap img{width: 150px;padding:10px 24px 10px;}


.hiddenbg{padding: 150px 200px;overflow: hidden;}
.hiddenbg img{box-shadow:0 24px 88px rgba(0, 0, 0, .55);}

.form-section .form-group{width: 100%;margin-bottom: 10px;}
.form-group label{text-align: left;width: 100%;color: #7b8396;font-size: 14px;position: initial;}
.login-page .container-tight{padding: 0px 130px;}
.form-group input:not([type="checkbox"]), .form-group textarea{padding: 10px 10px !important;}
.form-group input:not([type="checkbox"]), .form-group textarea {
    width: 100%;
    border: 1px solid #bbb8b8 !important;
    color: #000000 !important;
    font-size: 13px !important;
}

.login-page .btn-outline-primary{/*! box-shadow:0 20px 25px -5px rgb(0 0 0 / .1), 0 8px 10px -6px rgb(0 0 0 / .1); */ transition-property: all;
    transition-timing-function: cubic-bezier(.4,0,.2,1);
    transition-duration: .15s;background: transparent;
    color: #000;
    font-size: 13px;}

.login-page .form-section .form-control::placeholder {  font-size: 12px;}
.login-page .form-section .form-control{border-radius: 10px;font-size: 14px;
    height: 36.7px;}
.login-page .btn-blue{background:#3e8da6;width:100%;color: #fff;border-radius: 50px;}
.login-page .btn-blue:hover{opacity: 0.8;background:#3e8da6 !important;color:#fff !important}
.login-page .fs-12{font-size:12px}

.login-page .lqd-input-label-txt{color: #7b8396;font-size: 14px;}
.login-page .text-indigo-600{font-size:14px}


.login-page .form-group .toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
}
.login-page .dark-text-white{color: #828282 !important;}
.login-page .bg-secondary{background-color: #bebdbd !important;}
.login-page .form-group .toggle-password{color: #828282;font-size: 12px;}


.bg-secondary-subtle{background: white !important;}
/*Responsive*/

@media (max-width:779px) {
    .login-page .container-tight {padding:0px 10px;}
    .hiddenbg{display:none}
    .login-page .container-tight {padding:0px 10px;}

}
@media (max-width:779px) {
   .login-page .container {
        width: 100vw;
        height: 100vh;
    }
}
@media (max-width:635px) {
   .login-page .container::before {
        display: none;
    }
    .login-page form {
        width: 80%;
    }
    .login-page form.sign-up-form {
        display: none;
    }
    .login-page .container.sign-up-mode2 form.sign-up-form {
        display: flex;
        opacity: 1;
    }
    .login-page .container.sign-up-mode2 form.sign-in-form {
        display: none;
    }
    .login-page .panels-container {
        display: none;
    }
    .login-page .account-text {
        display: initial;
        margin-top: 30px;
    }
}
@media (max-width:320px) {
    .login-page form {
        width: 90%;
    }
}
</style>
    <!--login registration section start-->
  

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&amp;family=Space+Grotesk:wght@500;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <!-- <link rel="stylesheet" href="assets/css/admin_custom.css"/>
     -->

    @section('title')
        {{ localize('Sign Up') }}
    @endsection
    <div class="login-page">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-lg-6">
                    <div class="img-wrap d-flex">
                      <a href="{{ route('home') }}" class="navbar-brand d-flex justify-content-center mb-3 text-decoration-none">
                        <img src="{{ uploadedAsset(getSetting('navbar_logo_dark')) }}" alt="">
                      </a>
                    </div>
                    <div class="flex-grow d-flex flex-md-column align-items-md-center justify-content-md-center w-md-50">
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
                            {!! RecaptchaV3::field('recaptcha_token') !!}
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
                                                <label>Do you have a company?</label>
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
















