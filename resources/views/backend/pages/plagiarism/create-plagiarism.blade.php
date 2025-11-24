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
                @if ($msg)
                    <div class="col-lg-12">
                        <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                            {{ $msg }}
                        </div>
                    </div>
                @endif
                <div class="col-xl-7 col-lg-7 mt-4 mt-md-0">
                    <div class="tt-template-field flex-grow-1">
                        <div class="row justify-content-center">
                            <div class="col-12">
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

                                    <form action="{{ route('admin.content.plagiarism.store') }}" method="POST" class="scan-contents-form"
                                        id="scanNowform">
                                        @csrf

                                        <div class="mb-4">
                                            <label for="title" class="form-label"><span
                                                    class="fw-bold tt-promot-number fw-bold fs-4 me-2"></span>{{ localize('Title') }}<span
                                                    class="text-danger ms-1">*</span> </label>
                                            <input class="form-control" name="title" id="title" value="{{old('title', @$content_detector->title)}}"
                                                placeholder="{{ localize('Type here') }}">
                                                <x-error :name="'title'"/>
                                           
                                        </div>
                                        <div class="mb-4">

                                            <label for="input-textarea" class="form-label"><span
                                                    class="fw-bold tt-promot-number fw-bold fs-4 me-2"></span>{{ localize('Input your content ') }}<span
                                                    class="text-danger ms-1">*</span> <span class="ms-1 cursor-pointer"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    data-bs-title="{{ localize('The text to scan. Minimum 300 characters. Texts under 600 characters may produce unreliable results and should be avoided. Maximum 100 000 characters per request') }}"><i
                                                        data-feather="help-circle" class="icon-14"></i></span></label>
                                            <textarea class="form-control" name="text" id="input-textarea" rows="14" placeholder="{{ localize('Type here') }}">{{old('text', @$content_detector->content)}}</textarea>
                                            <x-error :name="'text'"/>
                                            <div class="d-flex justify-content-between">
                                                <p class="fs-md">{{localize('Total word')}} : <strong id="charac-count">{{@$content_detector->length ?? 0}}</strong></p>
                                               
                                            </div>
                                        </div>



                                        <button class="btn btn-primary scanNow" id="scanNow">
                                            <span class="me-2 btn-create-text">{{ localize('Scan Content') }}</span>
                                            <!-- text preloader start -->
                                            <span class="tt-text-preloader d-none">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </span>
                                            <!-- text preloader end -->
                                        </button>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5 col-lg-5 pe-xl-4 pt-6">
                    <div class="tt-generate-text"  id="renderChat">
                       @include('backend.pages.plagiarism.settings.inc._donut-chat')
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@php
    $series = isset($content_detector) ? [(int)number_format($content_detector->human), (int)number_format($content_detector->ai)] : [100, 0];
@endphp
@section('scripts')

    <script>

        var options = {
            chart: {
                width: 380,
                type: "donut"
            },
            dataLabels: {
                enabled: false
            },
            series:@json($series),
            labels: ["{{ localize('Human Writing') }}", "{{ localize('AI Writing') }}"],

        };

        var plag = new ApexCharts(
            document.querySelector("#donutChat"),
            options
        );
        plag.render();


        let inputTextArea = document.getElementById("input-textarea");
        let characCount = document.getElementById("charac-count");

        inputTextArea.addEventListener("input", () => {
            let textLenght = inputTextArea.value.length;
            characCount.textContent = textLenght;

        });
        @if($msg != null)
            $(document).on('click', '#scanNow', function(e){
                
                e.preventDefault();
                notifyMe('error', '{{ $msg }}');
                    return;
                    
            })
        @endif
    </script>
@endsection