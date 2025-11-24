@extends('backend.layouts.master')

@section('title')
    {{ localize('Social Media Link Configuration') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Social Media Link') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Social Media Link') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <x-change-language :langkey="$lang_key" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data"
                        class="pb-650">
                        @csrf
                        <input type="hidden" name="language_key" id="language_id" value="{{ $lang_key }}">
                        {{-- 1 --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Social Media Link') }}</h5>

                                <div class="mb-3">
                                    <label for="facebook_link"
                                        class="form-label">{{ localize('Facebook Link') }}</label>
                                    <input type="hidden" name="types[]" value="facebook_link">
                                    <input type="text" id="facebook_link" name="facebook_link"
                                        class="form-control" placeholder="www.facebook.com"
                                        value="{{ getSetting('facebook_link') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="instagram_link"
                                        class="form-label">{{ localize('Instagram Link') }}</label>
                                    <input type="hidden" name="types[]" value="instagram_link">
                                    <input type="text" id="instagram_link" name="instagram_link"
                                        class="form-control" placeholder="www.instagram.com"
                                        value="{{ getSetting('instagram_link') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="twitter_link"
                                        class="form-label">{{ localize('Twitter Link') }}</label>
                                    <input type="hidden" name="types[]" value="twitter_link">
                                    <input type="text" id="twitter_link" name="twitter_link"
                                        class="form-control" placeholder="www.twitter.com"
                                        value="{{ getSetting('twitter_link') }}">
                                </div>

                               
                            </div>
                        </div>

                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Homepage Configuration') }}</h5>
                            <div class="tt-vertical-step-link">
                                <ul class="list-unstyled">
                                    @include('backend.pages.appearance.homepage.inc.rightSidebar')
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection
