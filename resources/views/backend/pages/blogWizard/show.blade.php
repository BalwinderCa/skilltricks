@extends('backend.layouts.master')

@section('title')
    {{ localize('Blog Details') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Blog Details') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('blog.wizard') }}">{{ localize('Ai Blog Articles') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Blog Details') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <a href="{{ route('blog.wizard.edit', $article->aiBlogWizard->uuid) }}"
                                    class="btn btn-sm btn-soft-primary"><i data-feather="edit-3" class="me-1"></i>
                                    {{ localize('Edit Blog Article') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-8 mx-auto">
                    <div class="card card-body article-data mb-5 p-4">
                        @include('backend.pages.blogWizard.stepsData.article', [
                            'article' => $article,
                        ])
                    </div>
                </div>
            </div>


        </div>
    </section>
@endsection

@section('scripts')
    @include('backend.pages.blogWizard.inc.initScripts')
    @include('backend.pages.blogWizard.inc.scripts')
    <script>
        setTimeout(() => {
            populateArticleData({{ $article->id }})
        }, 300);
    </script>
@endsection
