@extends('backend.layouts.master')


@section('title')
    {{ localize('New Blog Article') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            @php
                $aiBlogWizardKeyWord = null;
                $aiBlogWizardTitle = null;
                $aiBlogWizardOutlines = null;
                $aiBlogWizardArticle = null;
                
                $hasBlogWizard = 0;
                //
                $aiBlogWizardId = null;
                $aiBlogWizardCompletedStep = null;
                
                //
                $generatedKeywords = null;
                $generatedTitles = null;
                $generatedImages = null;
                $generatedOutlines = null;
                
                $keywords = null;
                $title = null;
                $topic = null;
                if ($aiBlogWizard != null) {
                    $aiBlogWizardKeyWord = $aiBlogWizard->aiBlogWizardKeyword;
                    $aiBlogWizardTitle = $aiBlogWizard->aiBlogWizardTitle;
                    $aiBlogWizardOutlines = $aiBlogWizard->aiBlogWizardOutlines;
                    $aiBlogWizardArticle = $aiBlogWizard->aiBlogWizardArticle;
                
                    $hasBlogWizard = 1;
                    //
                    $aiBlogWizardId = $aiBlogWizard->id;
                    $aiBlogWizardCompletedStep = $aiBlogWizard->completed_step;
                
                    //
                    $generatedKeywords = $aiBlogWizardKeyWord->values ?? null;
                    $generatedTitles = $aiBlogWizardTitle->values ?? null;
                    $generatedImages = $aiBlogWizard->aiBlogWizardImages()->pluck('id');
                    $generatedOutlines = $aiBlogWizardOutlines;
                    $generatedArticle = $aiBlogWizardArticle ? $aiBlogWizardArticle->id : null;
                
                    $latestOutline = $aiBlogWizard
                        ->aiBlogWizardOutlines()
                        ->latest()
                        ->first();
                    $keywords = $latestOutline ? $latestOutline->keywords ?? ($aiBlogWizardTitle->keywords ?? '') : '';
                    $title = $latestOutline ? $latestOutline->title ?? ($aiBlogWizardTitle->topic ?? '') : '';
                    $topic = $aiBlogWizardTitle->topic ?? '';
                    $outlines = @$aiBlogWizardArticle->outlines;
                }
            @endphp
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Add New Blog Article') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('blog.wizard') }}">{{ localize('AI Blog Wizard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('New Blog Article') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                {{-- @if (isCustomer())
                                    <div class="card card-body py-2">
                                        <div class="d-flex align-items-center flex-column used-words-percentage">
                                            @include('backend.pages.templates.inc.used-words-percentage')
                                        </div>
                                    </div>
                                @endif --}}
                                @if (!$aiBlogWizard)
                                    <a href="#" class="btn btn-sm btn-soft-danger" onclick="resetBlogData()"><i
                                            data-feather="refresh-cw" class="me-1"></i> {{ localize('Reset Data') }}</a>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-between mb-5">
                <div class="col-xl-5 col-lg-6 mb-4">
                    <div class="card tt-sticky-sidebar">
                        {{-- steps heading --}}
                        <div class="card-header">
                            @include('backend.pages.blogWizard.stepsHeader.headings')
                        </div>

                        {{-- steps form --}}
                        <div class="card-body tt-widget-wrapper">
                            @include('backend.pages.blogWizard.stepsForm.stepKeyword')
                            @include('backend.pages.blogWizard.stepsForm.stepTitle')
                            @if(getSetting('generate_image') == 1)
                                 @include('backend.pages.blogWizard.stepsForm.stepImage')
                            @endif
                            @include('backend.pages.blogWizard.stepsForm.stepOutline')
                            @include('backend.pages.blogWizard.stepsForm.stepArticle')
                        </div>
                    </div>
                </div>

                {{-- steps data --}}
                <div class="col-xl-7 col-lg-6 ps-xl-5">
                    <div class="tt-fieldset-data is-active card mb-5">
                        <div class="card-header">
                            <h4 class="mb-0">{{ localize('Generated Keywords') }}</h4>
                        </div>
                        <div class="card-body keywords-data px-0 pt-0">
                            @include('backend.pages.blogWizard.stepsData.keywords', [
                                'keywords' => [],
                            ])
                        </div>
                    </div>

                    <div class="tt-fieldset-data mb-5">
                        <h4 class="mb-4">{{ localize('Generated Titles') }}</h4>
                        <div class="titles-data">
                            @include('backend.pages.blogWizard.stepsData.titles', [
                                'titles' => [],
                            ])
                        </div>
                    </div>
                    @if(getSetting('generate_image') == 1)
                    <div class="tt-fieldset-data mb-5">
                        <h4 class="mb-4">{{ localize('Generated Images') }}</h4>
                        <div class="images-data">
                            @include('backend.pages.blogWizard.stepsData.images', [
                                'images' => [],
                            ])
                        </div>
                    </div>
                    @endif
                    <div class="tt-fieldset-data mb-5">
                        <h4 class="mb-4">{{ localize('Generated Outlines') }}</h4>
                        <div class="outlines-data">
                            @include('backend.pages.blogWizard.stepsData.outlines', [
                                'outlines' => [],
                            ])
                        </div>
                    </div>

                    <div class="tt-fieldset-data mb-5">
                        <div class="article-data">
                            @include('backend.pages.blogWizard.stepsData.article', [
                                'article' => null,
                            ])
                        </div>
                    </div>

                    <!-- go next button start -->
                    <div class="position-relative d-flex justify-content-center d-none data-next-btn">
                        <div class="d-grid g-3 tt-next-step">
                            <button class="btn btn-primary btn-next-step" type="button">{{ localize('Next') }}</button>
                        </div>
                    </div>
                    <!-- go next button end -->
                </div>
                {{-- steps data --}}
            </div>
        </div>
    </section>
@endsection
@section('scripts')
    @include('backend.pages.blogWizard.inc.initScripts')

    @if ($hasBlogWizard)
        <script>
            "use strict";
            var hasBlogWizard = {{ $hasBlogWizard }};
            if (hasBlogWizard) {
                wizardFormData = {
                    activeStep: {{ $aiBlogWizardCompletedStep }},
                    aiBlogWizardId: {{ $aiBlogWizardId }},
                    keywords: {
                        for: 'keywords',
                        data: {
                            topic: '{{ $topic }}',
                            keywords: '{{ $keywords }}',
                            generatedKeywords: {!! $generatedKeywords ?? '[]' !!}
                        }
                    },
                    titles: {
                        for: 'titles',
                        data: {
                            topic: '{{ $topic }}',
                            keywords: '{{ $keywords }}',
                            title: '{{ $title }}',
                            generatedTitles: {!! $generatedTitles ?? '[]' !!}
                        }
                    },
                    images: {
                        for: 'images',
                        data: {
                            title: '{{ $title }}',
                            generatedImages: {!! $generatedImages ?? '[]' !!}
                        }
                    },
                    outlines: {
                        for: 'outlines',
                        data: {
                            title: '{{ $title }}',
                            keywords: '{{ $keywords }}',
                            generatedOutlines: {!! $generatedOutlines ?? '[]' !!}
                        }
                    },
                    article: {
                        for: 'article',
                        data: {
                            title: '{{ $title }}',
                            image: '',
                            outlines: {!! $outlines ?? '[]' !!},
                            keywords: '{{ $keywords }}',
                            langIndex: 0,
                            generatedArticle: {{ $generatedArticle != null ? $generatedArticle : 0 }}
                        }
                    }
                };

                setLocalWizardData();
            }
        </script>
    @endif


    @include('backend.pages.blogWizard.inc.scripts')
@endsection
