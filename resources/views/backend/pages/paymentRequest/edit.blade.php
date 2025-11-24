@extends('backend.layouts.master')


@section('title')
    {{ localize('Payment Request') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section py-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Payment Request') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Payment Request') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-4">
                    <div class="card mb-4" id="section-1">
                        @include('backend.pages.paymentRequest._package_deatils', [
                            'package' => $package,
                        ])
                    </div>
                </div>
                <div class="col-8 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ route('admin.re-submit-request.store') }}" method="POST" class="pb-650"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="history_id" value="{{ $history->id }}">
                        <!--basic information start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Admin Required') }} <span class="text-danger ms-1">*</span>
                                </h5>
                                <p>[ {{ $history->feedback_note }} ]</p>
                                <hr class="mb-4">
                                <div class="offline_payment " id="offline_payment">
                                    <div class="mb-4">
                                        <label for="payment_method" class="form-label">{{ localize('Payment Method') }}
                                            <span class="text-danger ms-1">*</span></label>
                                        <select class="form-control select2" id="offline_payment_method"
                                            name="offline_payment_method" data-toggle="select2">
                                            <option value="">{{ localize('Select Offline Payment Method') }}</option>
                                            @foreach ($offlinePaymentMethods as $offlinePaymentMethod)
                                                <option value="{{ $offlinePaymentMethod->id }}"
                                                    {{ $history->offline_payment_id == $offlinePaymentMethod->id ? 'selected' : '' }}>
                                                    {{ $offlinePaymentMethod->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label for="name" class="form-label text-center">{{ localize('Description') }}
                                            <span class="text-danger ms-1">*</span></label>
                                        @foreach ($offlinePaymentMethods as $offlinePaymentMethod)
                                            <p id="description_{{ $offlinePaymentMethod->id }}"
                                                class="all-description d-none">
                                                {{ $offlinePaymentMethod->description }}</p>
                                        @endforeach
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">{{ localize('Payment Details') }} <span
                                                class="text-danger ms-1">*</span></label>
                                        <textarea class="form-control" name="payment_detail" id="offline_payment_detail" rows="2"
                                            placeholder="{{ localize('Type your Payment Details') }}">{{ $history->payment_details ? json_decode($history->payment_details) : null }}</textarea>
                                        @if ($errors->has('payment_detail'))
                                            <span class="text-danger">{{ $errors->first('payment_detail') }}</span>
                                        @endif
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">{{ localize('Note') }}</label>
                                        <textarea class="form-control" name="note" id="offline_note" rows="1"
                                            placeholder="{{ localize('Type your Note') }}">{{ $history->note }}</textarea>
                                        @if ($errors->has('note'))
                                            <span class="text-danger">{{ $errors->first('note') }}</span>
                                        @endif
                                    </div>
                                    <div class="mb-3">
                                        <label for="default_creativity" class="form-label">{{ localize('File') }}
                                        </label>
                                        <br>
                                        @if ($history->file)
                                            <img src="{{ asset($history->file) }}" alt="" class="mb-2 img-fluid">
                                        @endif
                                        <div class="file-drop-area file-upload text-center rounded-3">
                                            <input type="file" class="file-drop-input" name="file"
                                                id="offline_file" />
                                            <div class="file-drop-icon ci-cloud-upload">
                                                <i data-feather="image"></i>
                                            </div>
                                            <p class="text-dark fw-bold mb-2 mt-3">
                                                {{ localize('Drop your files here or') }}
                                                <a href="javascript::void(0);"
                                                    class="text-primary">{{ localize('Browse') }}</a>
                                            </p>
                                            <p class="mb-0 file-name text-muted">

                                                <small>* {{ localize('Allowed file types: jpg,png,jpeg') }}
                                                </small>


                                            </p>
                                        </div>
                                        @if ($errors->has('file'))
                                            <span class="text-danger">{{ $errors->first('file') }}</span>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!--basic information end-->


                        <!-- submit button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i> {{ localize('Update') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- submit button end -->

                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('scripts-common')
    <script>
        "use strict";

        $(document).on('change', '#offline_payment_method', function(e) {
            let id = $(this).val();
            if (id) {
                $('.all-description').addClass('d-none');
                $('#description_' + id).removeClass('d-none');
            } else {
                $('.all-description').addClass('d-none');
            }


        })
    </script>
@endsection
