@extends('backend.layouts.master')

@section('title')
    {{ localize('AWS ') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">


            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AWS Credentials Setup') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AWS Credentials Setup') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 pb-650">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">

                    <!--default lang info end-->

                    <!--aws storage info start-->
                    <form action="{{ route('admin.awsSettings.update') }}" method="POST" enctype="multipart/form-data" class="">
                        @csrf
                        <!--AWS AWS Credentials Setup-->
                        <div class="card mb-4" id="section-2">
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="AWS_ACCESS_KEY_ID" class="form-label">{{ localize('AWS Access Key') }}
                                                <span class="text-danger ms-1">*</span></label>
                                            <input type="text" id="AWS_ACCESS_KEY_ID" name="types[AWS_ACCESS_KEY_ID]" class="form-control"
                                                required value="{{ getSetting('AWS_ACCESS_KEY_ID') }}" required>

                                            @if ($errors->has('AWS_ACCESS_KEY_ID'))
                                                <span class="text-danger">{{ $errors->first('AWS_ACCESS_KEY_ID') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="AWS_SECRET_ACCESS_KEY"
                                                class="form-label">{{ localize('AWS Secret Access Key') }} <span
                                                    class="text-danger ms-1">*</span></label>
                                            <input type="text" id="AWS_SECRET_ACCESS_KEY" name="types[AWS_SECRET_ACCESS_KEY]" class="form-control"
                                                value="{{ getSetting('AWS_SECRET_ACCESS_KEY') }}" required>
                                            @if ($errors->has('AWS_SECRET_ACCESS_KEY'))
                                                <span class="text-danger">{{ $errors->first('AWS_SECRET_ACCESS_KEY') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="AWS_DEFAULT_REGION" class="form-label">{{ localize('AWS Region') }} <span
                                                    class="text-danger ms-1">*</span></label>
                                            <input type="text" id="AWS_DEFAULT_REGION" name="types[AWS_DEFAULT_REGION]" class="form-control"
                                                value="{{ getSetting('AWS_DEFAULT_REGION') }}" required>
                                            @if ($errors->has('AWS_DEFAULT_REGION'))
                                                <span class="text-danger">{{ $errors->first('AWS_DEFAULT_REGION') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--AWS AWS Credentials Setup-->
                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save AWS Configuration') }}
                            </button>
                        </div>

                    </form>


                </div>

            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        "use strict"

        function handleEnableActiveStorageSubmit(el) {
            $.post('{{ route('admin.storage-management.active-storage') }}', {
                    _token: '{{ csrf_token() }}',
                    method: el.value
                },
                function(data) {
                    notifyMe(data.status, data.message);
                });
        }
    </script>
@endsection
