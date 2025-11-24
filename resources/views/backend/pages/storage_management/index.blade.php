@extends('backend.layouts.master')

@section('title')
    {{ localize('Storage Manager') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">


            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Storage Manager') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Storage Manager') }}</li>
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
                    @if (count($storages) > 0)
                        <!--default lang info start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Active Storage') }}</h5>
                                <div class="mb-4">

                                    <select id="active_storage" class="form-control country-flag-select"
                                        name="active_storage" data-toggle="select2"
                                        onchange="handleEnableActiveStorageSubmit(this)">

                                        @foreach ($storages as $key => $list)
                                            <option value="{{ $list->type }}"
                                                {{ getSetting('active_storage') == $list->type ? 'selected' : '' }}>
                                                {{ strtoupper($list->type) }} {{ localize('Storage') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endif
                    <!--default lang info end-->

                    <!--aws storage info start-->
                    <form action="{{ route('admin.storage-management.update') }}" method="POST"
                        enctype="multipart/form-data" class="">
                        @csrf
                        <input type="hidden" name="type" value="aws">
                        <!--AWS Storage Manager-->
                        <div class="card mb-4" id="section-2">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="mb-4">{{ localize('Amazon Web Services') }}</h5>
                                    <div class="">
                                        <div class="form-check form-switch">
                                            <label for="secret_key" class="form-label">{{ localize('Is Active') }} <span
                                                    class="text-danger ms-1">*</span></label> <br>
                                            <input type="checkbox" class="form-check-input" name="is_active"
                                                @if ($awsS3->is_active == 1) checked @endif>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="access_key" class="form-label">{{ localize('AWS Access Key') }}
                                                <span class="text-danger ms-1">*</span></label>
                                            <input type="hidden" name="access_key" value="access_key">
                                            <input type="text" id="access_key" name="access_key" class="form-control"
                                                required value="{{ $awsS3->access_key }}">
                                            @if ($errors->has('access_key'))
                                                <span class="text-danger">{{ $errors->first('access_key') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="secret_key"
                                                class="form-label">{{ localize('AWS Secret Access Key') }} <span
                                                    class="text-danger ms-1">*</span></label>
                                            <input type="hidden" name="secret_key" value="secret_key">
                                            <input type="text" id="secret_key" name="secret_key" class="form-control"
                                                value="{{ $awsS3->secret_key }}" required>
                                            @if ($errors->has('secret_key'))
                                                <span class="text-danger">{{ $errors->first('secret_key') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="bucket_name"
                                                class="form-label">{{ localize('AWS S3 Bucket Name') }} <span
                                                    class="text-danger ms-1">*</span></label>
                                            <input type="hidden" name="bucket_name" value="bucket_name">
                                            <input type="text" id="bucket_name" name="bucket_name" class="form-control"
                                                value="{{ $awsS3->bucket }}" required>
                                            @if ($errors->has('bucket_name'))
                                                <span class="text-danger">{{ $errors->first('bucket_name') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="region" class="form-label">{{ localize('AWS Region') }} <span
                                                    class="text-danger ms-1">*</span></label>
                                            <input type="hidden" name="region" value="">
                                            <input type="text" id="region" name="region" class="form-control"
                                                value="{{ $awsS3->region }}" required>
                                            @if ($errors->has('region'))
                                                <span class="text-danger">{{ $errors->first('region') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-6">

                                    </div>
                                </div>




                            </div>
                        </div>
                        <!--AWS Storage Manager-->
                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save AWS Configuration') }}
                            </button>
                        </div>

                    </form>
                    <!--aws storage info end-->

                    <!--gcs info start-->

                    <!--gcs storage info end-->

                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Configure Storage Manager') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('Active Storage') }}</a>
                                        <a href="#section-2" class="">{{ localize('Amazon Web Services') }}</a>

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
