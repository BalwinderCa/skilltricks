@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Fine Tune Jobs') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-flex flex-wrap align-items-center justify-content-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Your Ai Model (Fine Tune)') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a href="#">{{ localize('Home') }}</a></li>
                                    <li class="breadcrumb-item">{{ localize('Fine-Tune') }}</li>
                                </ol>
                            </div>

                            <div class="tt-action">
                                <a href="{{ route('fine-tunes.create') }}" class="btn btn-sm btn-primary">
                                    <i data-feather="plus"></i> {{ localize('New Fine Tune') }}
                                </a>
                            </div>

                        </div>

                        <div class="d-block d-lg-none mt-3">

                            <button
                                class="form-label tt-advance-options cursor-pointer mb-0 btn btn-light shadow-sm btn-sm rounded-pill">
                                <span class="fw-bold tt-promot-number fw-bold me-1"></span>
                                {{ localize('Show History') }}
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
                                            <th>{{ localize('SL') }}</th>
                                            <th>{{ localize('FT. Job') }}s</th>
                                            <th>{{ localize('Model') }}</th>
                                            <th>{{ localize('Organization ID') }}</th>
                                            <th>{{ localize('Status') }}</th>
                                            <th>{{ localize('Training File') }}</th>
                                            <th>{{ localize('Errors') }}</th>
                                            <th>{{ localize('Action') }}</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @isset($jobs['data'])
                                            @forelse($jobs["data"] as $key => $job)
                                                <tr>
                                                    <td> {{ $loop->iteration }} </td>
                                                    <td> {{ $job['id'] }} </td>
                                                    <td> {{ $job['model'] }} </td>
                                                    <td> {{ $job['organization_id'] }} </td>
                                                    <td> {{ $job['status'] }} </td>
                                                    <td> {{ $job['training_file'] }} </td>
                                                    <td>
                                                        @if (isset($job['error']))
                                                            <ul>
                                                                @forelse($job["error"] as $key => $error)
                                                                    <li>{{ $error }}</li>
                                                                @empty
                                                                @endforelse
                                                            </ul>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">

                                                        <div class="dropdown tt-tb-dropdown">
                                                            <button type="button" class="btn p-0" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                <i data-feather="more-vertical"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-end shadow" style="">

                                                                <button type="button" data-type="1"
                                                                    data-job-url="{{ route('fine-tunes.getByFineTuneJobid', $job['id']) }}"
                                                                    class="dropdown-item  jobBtn">
                                                                    <i data-feather="eye" class="icon-14"></i>
                                                                    {{ localize('View Contents') }}
                                                                </button>

                                                                <a href="{{ route('fine-tunes.cancelFineTuneJobByJobId', $job['id']) }}"
                                                                    class="dropdown-item  m-1">
                                                                    <i data-feather="x-circle" class="fa-2x"></i>
                                                                    {{ localize('Cancel Fine Tune') }}
                                                                </a>

                                                                <a href="{{ route('fine-tunes.deleteFineTuneJobByJobId', $job['id']) }}"
                                                                    class="dropdown-item  m-1">
                                                                    <i data-feather="trash" class="fa-2x"></i>
                                                                    {{ localize('Delete Fine Tune') }}
                                                                </a>


                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                            @endforelse
                                        @endisset

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </section>


    <!-- The Modal -->
    <div class="modal" id="jobModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title"></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">

                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>

    @endsection


    @section('css')
        <style>
            .jsonFile {
                margin-top: 28px;
            }
        </style>
    @endsection


    @section('scripts')
        <script>
            "use strict";

            $(document).on("click", ".jobBtn", async function(e) {

                let jobUrl = $(this).data("job-url");

                console.log("Job URL : ", jobUrl);

                $("#jobModal").modal("show");

                let type = $(this).data("type");

                $("#jobModal .modal-title").html(`<p> Loading... </p> `);

                if (type == 1) {
                    $("#jobModal .modal-title").html("Fine-Tune Job Details");
                }

                $(".modal-body").html(` <p> Loading... </p> `);

                await $.ajax({
                    url: jobUrl,
                    type: "GET",
                    success: function(response) {
                        console.log("Open AI Server Response : ", response);

                        let ulList = `<ul class="pl-2">
                            <li> <p> <strong>Fine Tune Job ID : </strong> ${response.id} </p>  </li>
                            <li> <p> <strong>Model Name : </strong> ${response.model} </p>  </li>
                            <li> <p> <strong>Organization ID : </strong> ${response.organization_id} </p>  </li>
                            <li> <p> <strong>Training ID : </strong> ${response.training_file} </p>  </li>
                            <li> <p> <strong>Fine Tuned Model : </strong> ${response.fine_tuned_model} </p>  </li>
                            <li> <p> <strong>Trained Tokens : </strong> ${response.trained_tokens ?? 0} </p>  </li>
                     </ul>`;

                        $(".modal-body").html(ulList);
                    },
                    error: function(error) {
                        console.log("Error : ", error);
                    }
                })

            });
        </script>
    @endsection
