@extends('layouts.auth')

@section('title')
    {{ localize('Verify') }}
@endsection

@section('contents')
@include('auth.inc.authStyles')

    <div class="login-page">
        <div class="container-fluid p-0">
            <div class="row">
                {{-- Column stretches to the row height (set by the image panel), so the
                     short verify copy can center against it instead of stacking under the logo. --}}
                <div class="col-lg-6 mt-3 d-flex flex-column">
                    <div class="img-wrap d-flex">
                        <a href="{{ route('home') }}" class="navbar-brand d-flex justify-content-center mb-2 text-decoration-none">
                            <img src="{{ staticAsset('newfronted/Assets/logo-wordmark.png') }}" alt="Skilltricks">
                        </a>
                    </div>
                    <div class="flex-grow-1 d-flex align-items-center w-md-50">
                        <div class="w-100 px-4 text-center text-extra-small w-lg-50">
                            <div class="container-tight">

                                <h1 class="ttl">{{ localize('Confirm your email') }}</h1>

                                <p class="lqd-input-label-txt mb-2">
                                    {{ localize('We sent a verification link to') }}
                                    <strong class="text-dark">{{ auth()->user()->email }}</strong>.
                                    {{ localize('Open it and you are in — this is the last step before SkillTricks starts calibrating around your role.') }}
                                </p>

                                <p class="lqd-input-label-txt fs-12 mb-0">
                                    {{ localize('Nothing there yet? Give it a minute, then check spam and promotions before sending a fresh one.') }}
                                </p>

                                @if (session('resent'))
                                    <div class="alert alert-success mt-3 mb-0 py-2 fs-12" role="alert">
                                        {{ localize('Done — a new link is on its way.') }}
                                    </div>
                                @endif

                                <form action="{{ route('verification.resend') }}" method="POST" id="login-form" class="w-100 form-section mt-3">
                                    @csrf
                                    <button class="btn btn-md btn-blue mt-2 sign-in-btn" type="submit" onclick="handleSubmit()">
                                        {{ localize('Send a new link') }}
                                    </button>
                                </form>

                            </div>
                            <div class="text-muted mt-4 mb-2 lqd-input-label-txt">
                                {{ localize('Wrong address?') }}
                                <a class="font-medium text-indigo-600 underline fs-12" href="{{ route('logout') }}">
                                    {{ localize('Sign out and register again') }}
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

        // disable the resend button once it has been pressed
        function handleSubmit() {
            $('#login-form').on('submit', function(e) {
                $('.sign-in-btn').prop('disabled', true);
            });
        }
    </script>
@endsection
