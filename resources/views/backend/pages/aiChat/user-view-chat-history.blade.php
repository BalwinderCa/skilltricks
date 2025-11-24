@extends('backend.layouts.master')



@section('title')

    {{ localize('View Chat') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}

@endsection



@section('contents')

    <section class="tt-section pt-4">

        <div class="container">

            <div class="row mb-4">

                <div class="col-12">

                    <div class="tt-page-header">

                        <div class="d-lg-flex align-items-center justify-content-lg-between">

                            <div class="tt-page-title mb-3 mb-lg-0">

                                <h1 class="h4 mb-lg-1">{{ localize('View Chat') }}</h1>

                                <ol class="breadcrumb breadcrumb-angle text-muted">

                                    <li class="breadcrumb-item"><a

                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>

                                    </li>

                                    <li class="breadcrumb-item">{{ localize('View Chat') }}</li>

                                </ol>

                            </div>

                            <div class="tt-action">

                                <div class="mb-3">

                                    <a class="btn btn-primary" href="{{url('dashboard/userchathistory')}}">

                                        <i data-feather="save" class="me-1"></i> {{ localize('Back') }}

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>





            <div class="row mb-4 g-4">



                <!--left sidebar-->

                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">

                        <div class="card mb-4" id="section-1">

                            <div class="card-body">

                                <!-- <h5 class="mb-4">{{ localize('Search Goals You Are Looking For By Answering Few Questions !!') }}</h5> -->

                                <div class="mb-3">
                                    <label class="form-label">{{ localize('View Chat Questions & Answers') }}</label>
                                </div>

                            @php
                                $savedAnswers = collect(json_decode(optional($userhistoryview)->answers ?? '[]', true));
                            @endphp

                            @foreach($savedAnswers as $index => $answer)
                                @php
                                    $questionId = $answer['question_id'];

                                    // Get the question text from subcategory_menu_question table
                                    $questionmenulist = DB::table('subcategory_menu_question')
                                        ->where('id', $questionId)
                                        ->where('status', 1)
                                        ->first();
                                @endphp

                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            {{ $questionmenulist->question ?? 'Question not found' }}
                                            <input type="hidden" name="question[]" value="{{ $questionId }}">
                                        </label>
                                    </div>

                                    <div class="col-md-6">
                                        <textarea name="answers[{{ $questionId }}]" class="form-control" rows="2" placeholder="Your answer here..." required readonly>{{ $answer['answer'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            @endforeach





                            </div>

                        </div>

                        <!--basic information end-->







                        <!-- submit button -->
<!-- 
                        <div class="row">

                            <div class="col-12">

                                <div class="mb-3">

                                    <button class="btn btn-primary" type="submit">

                                        <i data-feather="save" class="me-1"></i> {{ localize('Next') }}

                                    </button>

                                </div>

                            </div>

                        </div>
 -->
                        <!-- submit button end -->



                    </form>

                </div>



                <!--right sidebar-->

                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">

                    <div class="card tt-sticky-sidebar d-none d-xl-block">

                        <div class="card-body">

                            <h5 class="mb-4">{{ localize('View Chat Information') }}</h5>

                             <div class="tt-vertical-step">

                                <ul class="list-unstyled">

                                    <li>

                                        <a href="#section-1" class="active">{{ localize('View Chat Information') }}</a>

                                    </li>

                                </ul>

                            </div> 

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

@endsection





@section('scripts')

@endsection

