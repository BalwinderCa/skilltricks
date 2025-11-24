@extends('backend.layouts.master')

@section('title')
    {{ localize('Social Login Configurations') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Social Login Configurations') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Social Login Configurations') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data"
                        class="pb-650">
                        @csrf

                        <!--google settings-->
                        <div class="card mb-4" id="section-1">

                            <div class="card-body">

                                <h5 class="mb-4">{{ localize('Google Login') }}</h5>
                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Google Client ID') }}</label>
                                    <input type="hidden" name="types[]" value="GOOGLE_CLIENT_ID">
                                    <input type="text" class="form-control" name="GOOGLE_CLIENT_ID"
                                        value="{{ env('GOOGLE_CLIENT_ID') }}"
                                        placeholder="{{ localize('Google Client ID') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Google Client Secret') }}</label>
                                    <input type="hidden" name="types[]" value="GOOGLE_CLIENT_SECRET">

                                    <input type="text" class="form-control" name="GOOGLE_CLIENT_SECRET"
                                        value="{{ env('GOOGLE_CLIENT_SECRET') }}"
                                        placeholder="{{ localize('Google Client Secret') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Google Redirect Url') }}</label>
                                    <input type="text" class="form-control"
                                        value="{{ url('/social-login/redirect/google') }}"
                                        placeholder="{{ localize('Google Redirect Url') }}" disabled>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Google Callback Url') }}</label>
                                    <input type="text" class="form-control"
                                        value="{{ url('/social-login/google/callback') }}"
                                        placeholder="{{ localize('Google Callback Url') }}" disabled>
                                </div>


                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Is Active?') }}</label>

                                    <input type="hidden" name="types[]" value="google_login">
                                    <select id="google_login" class="form-control select2" name="google_login"
                                        data-toggle="select2">
                                        <option value="0" {{ getSetting('google_login') == '0' ? 'selected' : '' }}>
                                            {{ localize('Disable') }}</option>
                                        <option value="1" {{ getSetting('google_login') == '1' ? 'selected' : '' }}>
                                            {{ localize('Enable') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!--google settings-->


                        <!--facebook settings-->
                        <div class="card mb-4" id="section-2">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Facebook Login') }}</h5>
                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Facebook App ID') }}</label>
                                    <input type="hidden" name="types[]" value="FACEBOOK_APP_ID">
                                    <input type="text" class="form-control" name="FACEBOOK_APP_ID"
                                        value="{{ env('FACEBOOK_APP_ID') }}"
                                        placeholder="{{ localize('Facebook App ID') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Facebook App Secret') }}</label>
                                    <input type="hidden" name="types[]" value="FACEBOOK_APP_SECRET">

                                    <input type="text" class="form-control" name="FACEBOOK_APP_SECRET"
                                        value="{{ env('FACEBOOK_APP_SECRET') }}"
                                        placeholder="{{ localize('Facebook App Secret') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Facebook Redirect Url') }}</label>
                                    <input type="text" class="form-control"
                                        value="{{ url('/social-login/redirect/facebook') }}"
                                        placeholder="{{ localize('Facebook Redirect Url') }}" disabled>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Facebook Callback Url') }}</label>
                                    <input type="text" class="form-control"
                                        value="{{ url('/social-login/facebook/callback') }}"
                                        placeholder="{{ localize('Facebook Callback Url') }}" disabled>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Is Active?') }}</label>

                                    <input type="hidden" name="types[]" value="facebook_login">
                                    <select id="facebook_login" class="form-control select2" name="facebook_login"
                                        data-toggle="select2">
                                        <option value="0"
                                            {{ getSetting('facebook_login') == '0' ? 'selected' : '' }}>
                                            {{ localize('Disable') }}</option>
                                        <option value="1"
                                            {{ getSetting('facebook_login') == '1' ? 'selected' : '' }}>
                                            {{ localize('Enable') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!--facebook settings-->



                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save Configuration') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Social Login Configurations') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('Google Login') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-2">{{ localize('Faccebook Login') }}</a>
                                    </li>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
