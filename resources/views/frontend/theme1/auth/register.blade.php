@extends('frontend.theme1.auth.auth_master')

@section('title')
    {{ localize('Home') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('auth-content')
    <!-- Custom Cursor  -->
    <div class="custom-cursor">
        <div>
            <span></span>
            <svg class="custom-cursor__img" width="119" height="45" viewBox="0 0 119 45" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_1130_4828)">
                    <path
                        d="M10.9627 22.9931L12.2909 16.1687C12.603 14.5633 14.1573 13.0107 15.7632 12.7011L21.5757 11.5778C23.181 11.2681 23.2911 10.4355 21.8214 9.719L2.95694 0.527836C1.48667 -0.188687 0.764833 0.470432 1.34397 1.99934L9.34853 23.1305C9.92766 24.6594 10.6501 24.5984 10.9627 22.9931Z"
                        fill="url(#paint0_linear_1130_4828)" />
                </g>
                <rect x="16" y="16.2383" width="103" height="28" rx="4" fill="url(#paint1_linear_1130_4828)" />
                <defs>
                    <linearGradient id="paint0_linear_1130_4828" x1="-58.0002" y1="-33.2617" x2="25.4638" y2="2.23919"
                        gradientUnits="userSpaceOnUse">
                        <stop offset="1" stop-color="#F49959" />
                    </linearGradient>
                    <linearGradient id="paint1_linear_1130_4828" x1="-53.2743" y1="34.7382" x2="110.992" y2="27.6664"
                        gradientUnits="userSpaceOnUse">
                        <stop offset="1" stop-color="#F49959" />
                    </linearGradient>
                    <clipPath id="clip0_1130_4828">
                        <rect width="24" height="24" fill="white" transform="translate(0 0.238281)" />
                    </clipPath>
                </defs>
            </svg>
        </div>
        <div></div>
    </div>
    <!-- /Custom Cursor  -->
    <!-- Login Section -->
    <div class="login-page">
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-xxl-7 col-xl-6 d-none d-xl-block">
                    <div class="radar-main-wrapper">
                        <div class="radar-main-circle-one"></div>
                        <div class="radar-main-circle-two"></div>
                        <div class="radar-main-circle-three"></div>
                        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/login-shape-circle-text.png"
                            alt="image" class="img-fluid radar-main-shape-text">
                        <div class="radar-logo-wrapper">
                            <div class="radar">
                                <div class="animated-text-wrapper">
                                    <p class="cd-headline slide mb-0">
                                        <span class="cd-words-wrapper">
                                            <b class="is-hidden">WriteBot AI</b>
                                            <b class="is-hidden">WriteBot AI</b>
                                            <b class="is-visible">WriteBot AI</b>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="logo-wrapper d-flex justify-content-center align-items-center">
                                <img src="{{ uploadedAsset(getSetting('favicon')) ?? asset('public/frontend/theme1/assets/img/hero-7-logo.png') }}" alt="image"
                                    class="img-fluid hero-7-logo">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-5 col-xl-6 col-lg-8 col-md-10">

                    <div class="gradient-card py-sm-12 py-8 px-sm-8 px-5 rounded-5">
                        {{-- <p class="fs-24 fw-medium clr-neutral-80 mb-5">Welcome !</p> --}}
                        <h4 class="h4 mb-2 clr-neutral-90 fw-bold">{{ getSetting('login_rightbar_sub_title') }} </h4>
                        <p class="clr-neutral-80">{{ getSetting('login_rightbar_title') }}</p>

                        @if (getSetting('google_login') == '1' || getSetting('facebook_login') == '1')
                            <div class="d-flex flex-wrap gap-xl-6 gap-4 align-items-center justify-content-center mt-6">
                                @if (getSetting('google_login') == '1')
                                    <a href="{{ route('social.login', ['provider' => 'facebook']) }}"
                                        class="link d-inline-flex align-items-center justify-content-center gap-4 py-3 px-8 rounded-2 bg-neutral-6 login-with-btn">
                                        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/login-facebook.svg"
                                            alt="image" class="img-fluid">
                                        <span class="d-inline-block clr-neutral-80 fs-14">{{ localize('Facebook') }}</span>
                                    </a>
                                @endif
                                @if (getSetting('google_login') == '1')
                                    <a href="{{ route('social.login', ['provider' => 'google']) }}"
                                        class="link d-inline-flex align-items-center justify-content-center gap-4 py-3 px-8 rounded-2 bg-neutral-6 login-with-btn">
                                        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/login-google.svg"
                                            alt="image" class="img-fluid">
                                        <span class="d-inline-block clr-neutral-80 fs-14">{{ localize('Google') }}</span>
                                    </a>
                                @endif
                            </div>
                        @endif
                        {!! Form::open([
                            'route' => 'register',
                            'method' => 'POST',
                            'id' => 'login-form',
                            'class' => 'mt-4 register-form',
                        ]) !!}
                        <input type="hidden" name="login_with" class="login_with" value="email">
                        {!! RecaptchaV3::field('recaptcha_token') !!}
                        <div class="mt-8">
                            <label class="clr-neutral-80 mb-2">{{ localize('Full Name') }} <span
                                    class="text-danger">*</span></label>
                            {!! Form::text('name', old('name'), [
                                'class' =>
                                    'form-control border border-neutral-17 clr-neutral-90 :focus-clr-current rounded-2 py-4 px-4 bg-neutral-4 placeholder-50 focus-bg-none',
                                'id' => 'name',
                                'placeholder' => localize('Type full name'),
                                'aria-label' => 'name',
                                'required' => true,
                            ]) !!}
                        </div>
                        <div class="mt-8">
                            <label class="clr-neutral-80 mb-2">{{ localize('Email') }} <span
                                    class="text-danger">*</span></label>
                            {!! Form::email('email', old('email'), [
                                'class' =>
                                    'form-control border border-neutral-17 clr-neutral-90 :focus-clr-current rounded-2 py-4 px-4 bg-neutral-4 placeholder-50 focus-bg-none',
                                'name' => 'email',
                                'id' => 'email',
                                'placeholder' => localize('Type your email'),
                                'aria-label' => 'email',
                                'required' => true,
                            ]) !!}
                        </div>
                        <div class="mt-8">
                            <label class="clr-neutral-80 mb-2">{{ localize('Phone') }} @if (getSetting('registration_with') == 'email_and_phone')
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            @php
                                $required = getSetting('registration_with') == 'email_and_phone' ? true : false;
                            @endphp

                            {!! Form::text('phone', old('phone'), [
                                'class' =>
                                    'form-control border border-neutral-17 clr-neutral-90 :focus-clr-current rounded-2 py-4 px-4 bg-neutral-4 placeholder-50 focus-bg-none',
                                'name' => 'phone',
                                'id' => 'phone',
                                'placeholder' => localize('+880xxxxxxxxxx'),
                                'aria-label' => 'phone',
                                'required' => $required,
                            ]) !!}
                        </div>
                        <div class="mt-8">
                            <label class="clr-neutral-80 mb-2">{{ localize('Password') }} <span
                                    class="text-danger">*</span></label>
                            <div class="pass-field-area">
                                <input type="password" name="password"
                                    class="form-control border border-neutral-17 clr-neutral-90 :focus-clr-current rounded-2 py-4 px-4 bg-neutral-4 placeholder-50 focus-bg-none"
                                    placeholder="{{ localize('Enter your password') }}">
                                <button type="button" class="bg-transparent border-0 pass-eye">
                                    <i class="bi bi-eye-slash-fill eye-off"></i>
                                    <i class="bi bi-eye-fill eye-on"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mt-8">
                            <label class="clr-neutral-80 mb-2">{{ localize('Confirm Password') }} <span
                                    class="text-danger">*</span></label>
                            <div class="pass-field-area">
                                <input type="password_confirmation" type="password" name="password_confirmation"
                                    class="form-control border border-neutral-17 clr-neutral-90 :focus-clr-current rounded-2 py-4 px-4 bg-neutral-4 placeholder-50 focus-bg-none"
                                    placeholder="{{ localize('Confirm Password') }}">
                                <button type="button" class="bg-transparent border-0 pass-eye">
                                    <i class="bi bi-eye-slash-fill eye-off"></i>
                                    <i class="bi bi-eye-fill eye-on"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between mt-8">
                            <div class="form-check check-box check-box check-box-neutral-30 gap-1">
                                <input class="form-check-input check-box__input clr-white my-auto border-0 bg-neutral-17"
                                    type="checkbox" id="n30dash" required>
                                <label class="form-check-label clr-neutral-80 fs-12 ps-1" for="n30dash">
                                    {{ localize('By continuing, you are stating that you accept the') }} <a href="{{route('terms-of-conditions')}}" target="_blank" rel="noopener noreferrer">{{localize('Terms & Conditions')}}</a> {{localize('have read the')}}
                                    <a href="{{route('privacy-policy')}}" target="_blank" rel="noopener noreferrer">{{localize('Privacy Policy')}}</a>
                                </label>
                            </div>
                          
                        </div>
                        <button type="submit"
                            class="link d-inline-flex justify-content-center align-items-center gap-2 py-4 px-6 border border-primary-key bg-primary-key rounded-1 fw-bold clr-white border-0 w-100 mt-8 :arrow-btn">
                            <span>{{ localize('Register') }}</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                        <p class="mb-0 clr-neutral-80 text-center mt-8">{{ localize('Do you have an Account?') }} <a
                                href="{{ route('login') }}"
                                class="link clr-primary-key fw-semibold">{{ localize('Login now') }}</a></p>
                        <div class="text-center mt-6">
                            <a href="{{ route('home') }}"
                                class="link d-inline-flex justify-content-center align-items-center gap-2 py-2 px-4 border border-primary-key bg-primary-key :bg-primary-30 rounded-pill fs-14 fw-bold text-center clr-white">
                                <span class="d-block"> {{ localize('Back to Home') }} </span>
                                <span class="d-block fs-10">
                                    <i class="bi bi-arrow-up-right"></i>
                                </span>
                            </a>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
            @include('frontend.theme1.auth.inc.auth-footer')
        </div>
        <!-- /Login Section -->
    @endsection
