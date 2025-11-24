@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Content plagiarism') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4 g-3">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI Content plagiarism') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>

                                    <li class="breadcrumb-item">{{ localize('AI Content plagiarism') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-5">

                <div class="col-xl-12 col-lg-12 mt-4 mt-md-0">
                    <div class="tt-template-field flex-grow-1">
                        <div class="row justify-content-center">

                            <div class="col-10">
                                <div class="tt-article-generate">

                                    @if (isCustomer())
                                        @php
                                            $latestPackage = activePackageHistory(auth()->user()->id);
                                        @endphp
                                        @if ($latestPackage)
                                            @if ($latestPackage->new_word_balance != -1)
                                                <div class="card mb-4">
                                                    <div class="card-body">
                                                        <div
                                                            class="d-flex align-items-center flex-column used-words-percentage">
                                                            @include('backend.pages.templates.inc.used-words-percentage')
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    @endif



                                    <div class="">
                                        <label for="title" class="form-label"><span
                                                class="fw-bold tt-promot-number fw-bold fs-4 me-2"></span>{{ $content_detector->title }}
                                        </label>
                                    </div>
                                    <div class="mb-4">

                                        <p>{{ $content_detector->content }}</p>


                                    </div>




                                </div>
                            </div>
                            <hr>

                            <div class="col-10 mt-4">
                                <div class="tt-article-generate">
                                     <h4>{{ localize('total number of matching websites found by the Plagiarism API') }}</h4>


                                    @isset($content_detector)
                                        @foreach ($content_detector->response->results as $key => $item)
                                            <div class="mb-4">
                                                <label for="title" class="form-label"> {{ $key + 1 }} .<span
                                                        class="fw-bold tt-promot-number fw-bold fs-4 me-2"></span>{{ @$item->title }}<span
                                                        class="text-danger ms-1">*</span> </label>
                                                <p><a href="{{ @$item->url }}">{{ @$item->url }}</a></p>
                                                @foreach ($item->excerpts as $data)
                                                    <p>{!! @$data !!}</p>
                                                @endforeach
                                                <p>{{ @$item->date }}</p>

                                            </div>
                                        @endforeach
                                    @endisset





                                </div>
                            </div>

                        </div>
                    </div>
                </div>


            </div>
        </div>
    </section>
@endsection
