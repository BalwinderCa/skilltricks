@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Chat') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-flex flex-wrap align-items-center justify-content-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">YOUR AI MODEL (Fine Tune)</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                                    <li class="breadcrumb-item">Fine-Tune</li>
                                </ol>
                            </div>

                            <div class="tt-action">
                                <a href="{{ route('fine-tunes.create') }}"
                                   class="btn btn-sm btn-primary">
                                    <i data-feather="plus"></i> {{ localize('New Fine Tune') }}
                                </a>
                            </div>

                        </div>

                        <div class="d-block d-lg-none mt-3">

                            <button
                                class="form-label tt-advance-options cursor-pointer mb-0 btn btn-light shadow-sm btn-sm rounded-pill">
                                <span class="fw-bold tt-promot-number fw-bold me-1"></span>
                                Show History
                                <span>
                                    <i data-feather="plus" class="icon-16 text-primary ms-2"></i>
                                </span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>SL</th>
                                            <th>Purpose</th>
                                            <th>Trained Engine</th>
                                            <th>FT-Your Model</th>
                                            <th>Status</th>
                                            <th>Status Details</th>
                                            <th>Contents</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse($fineTunes as $key => $fineTune)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $fineTune->title }}</td>
                                                <td>{{ $fineTune->model_engine }}</td>
                                                <td>{{ $fineTune->ft_model_id }}</td>
                                                <td>{{ $fineTune->status }}</td>
                                                <td>{{ $fineTune->status_details }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-success">
                                                        <i data-feather="file-text" class="icon-16"></i>
                                                    </button>
                                                </td>
                                                <td>
                                                    <a href="" class="btn btn-sm btn-danger">
                                                        <i data-feather="delete"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                        @endforelse
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </section>

@endsection


@section("css")
    <style>
        .jsonFile{
            margin-top: 28px;
        }
    </style>
@endsection
