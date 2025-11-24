@extends('backend.layouts.master')

@section('title')
    {{ localize('Project Details') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 col-xl-9 mx-auto">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Project Details') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('projects.index') }}">{{ localize('Projects') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Project Details') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-5 g-4">

                <div class="col-xl-9 mx-auto">
                    <div class="tt-generate-text">
                        <div class="row">
                            <div class="col-12">
                                <div class="card flex-column h-100" id="section-1">
                                    @if ($project->content_type == 'code')
                                        @include('backend.pages.templates.inc.contentCode')
                                    @else
                                        @include('backend.pages.templates.inc.content')
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


@section('modals')
    @include('backend.pages.templates.inc.saveToFolderModal')
@endsection

@section('scripts')
    @include('backend.pages.templates.inc.template-scripts')
    @include('backend.pages.templates.inc.rewrite-scripts')

@endsection
