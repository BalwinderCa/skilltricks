@extends('backend.layouts.master')


@section('title')
    {{ localize('Add API Key') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('API Keys') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('admin.settings.openAi') }}">{{ localize('AI Settings') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        {{ isset($editApiKey) ? localize('Update') : localize('API Keys') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                                <a href="{{ route('admin.multiOpenAi.index') }}" class="btn btn-primary">
                                    {{ localize('Api Key List') }}</a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row mb-4 g-4">

                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    @include('backend.pages.systemSettings.openAi._form')
                </div>

            </div>
        </div>
    </section>
@endsection
