@extends('backend.layouts.master')

@section('title')
    {{ localize('Update Question') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Question Edit') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="#">{{ localize('Question Edit') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Update') }}</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ url('dashboard/sub-question-update/'.$subcategquestionidedit->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $subcategquestionidedit->id }}">
                        <!--basic information start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>


                                <div class="mb-4">
                                    <label for="question" class="form-label">{{ localize('Question') }}</label>
                                    <div id="question-wrapper">
                                        <div class="input-group mb-2">
                                            <input class="form-control" type="text" name="question" value="{{$subcategquestionidedit->question}}" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="status" class="form-label">Status <span class="text-danger ms-1">*</span></label>
                                    <select class="form-select select2" id="status" name="status" required>
                                        <option value="1" {{ $subcategquestionidedit->status == '1' ? 'selected' : '' }}>
                                            Active
                                        </option>
                                        <option value="0" {{ $subcategquestionidedit->status == '0' ? 'selected' : '' }}>
                                            Deactive
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!--basic information end-->

                        <!-- submit button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i> {{ localize('Save Changes') }}
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
