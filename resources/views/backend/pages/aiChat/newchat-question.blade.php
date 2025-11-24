@extends('backend.layouts.master')



@section('title')

    {{ localize('Question') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}

@endsection



@section('contents')

    <section class="tt-section pt-4">

        <div class="container">

            <div class="row mb-4">

                <div class="col-12">

                    <div class="tt-page-header">

                        <div class="d-lg-flex align-items-center justify-content-lg-between">

                            <div class="tt-page-title mb-3 mb-lg-0">

                                <h1 class="h4 mb-lg-1">{{ localize('Question') }}</h1>

                                <ol class="breadcrumb breadcrumb-angle text-muted">

                                    <li class="breadcrumb-item"><a

                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>

                                    </li>

                                    <li class="breadcrumb-item">{{ localize('Question') }}</li>

                                </ol>

                            </div>

                            <div class="tt-action">

                            </div>

                        </div>

                    </div>

                </div>

            </div>





            <div class="row mb-4 g-4">



                <!--left sidebar-->

                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">

                    <form action="{{url('dashboard/chat-question-store')}}" method="POST">

                        @csrf

                        <input type="hidden" name="id" value="{{ $user->id }}">
                        <input type="hidden" name="chat_role_categories" value="{{ $requestall['chat_role_categories'] }}">
                        <input type="hidden" name="categories" value="{{ $requestall['categories'] }}">
                        <input type="hidden" name="subcategories" value="{{ $requestall['subcategories'] ?? '' }}">
                        <input type="hidden" name="questionmenuid" value="{{ $questionmenu->id ?? '' }}">

                        <!--basic information start-->

                        <div class="card mb-4" id="section-1">

                            <div class="card-body">

                                <!-- <h5 class="mb-4">{{ localize('Search Goals You Are Looking For By Answering Few Questions !!') }}</h5> -->

                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Questions & Answers') }}</label>
                                </div>

                            @php
                                $savedAnswers = collect(json_decode(optional($useranswerdata)->answers ?? '[]', true));
                            @endphp

                            @foreach($questionmenulist as $index => $question)
                                @php
                                    $answer = $savedAnswers->firstWhere('question_id', $question->id);
                                @endphp

                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            {{ $question->question }}
                                            <input type="hidden" name="question[]" value="{{ $question->id }}">
                                        </label>
                                    </div>

                                    <div class="col-md-6">
                                        <textarea name="answers[{{ $question->id }}]" class="form-control" rows="2" placeholder="Your answer here..." required>{{ $answer['answer'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            @endforeach


                            {{--<!-- @foreach($questionmenulist as $index => $question)

                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            {{ $question->question }}
                                            <input type="hidden" name="question[]" value="{{ $question->id }}">
                                        </label>
                                    </div>

                                    <div class="col-md-6">
                                        <textarea name="answers[{{ $question->id }}]" class="form-control" rows="2" placeholder="Your answer here..." required></textarea>
                                    </div>
                                </div>
                            @endforeach --> --}}




                            </div>

                        </div>

                        <!--basic information end-->







                        <!-- submit button -->

                        <div class="row">

                            <div class="col-12">

                                <div class="mb-3">

                                    <button class="btn btn-primary" type="submit">

                                        <i data-feather="save" class="me-1"></i> {{ localize('Next') }}

                                    </button>

                                </div>

                            </div>

                        </div>

                        <!-- submit button end -->



                    </form>

                </div>



                <!--right sidebar-->

                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">

                    <div class="card tt-sticky-sidebar d-none d-xl-block">

                        <div class="card-body">

                            <h5 class="mb-4">{{ localize('Question Information') }}</h5>

                            <div class="tt-vertical-step">

                                <ul class="list-unstyled">

                                    <li>

                                        <a href="#section-1" class="active">{{ localize('Basic Information') }}</a>

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

 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $('#category').on('change', function () {
        let categoryId = $(this).val();

        if (categoryId) {
            $.ajax({
                url: "{{ url('dashboard/getsubcategories') }}", // this is the route you’ll create
                type: "GET",
                data: { category_id: categoryId },
                success: function (data) {
                    $('#subcategories').empty().append('<option value="">Select</option>');
                    $.each(data, function (key, subcategory) {
                        $('#subcategories').append('<option value="' + subcategory.id + '">' + subcategory.sub_category + '</option>');
                    });
                }
            });
        } else {
            $('#subcategories').empty().append('<option value="">Select</option>');
        }
    });
</script>

@endsection

