@extends('backend.layouts.master')

@section('title')
    {{ localize('Invoice Settings') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')

    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Invoice Settings') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Invoice Settings') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ route('admin.invoice-settings.update') }}" method="POST"
                        enctype="multipart/form-data" class="pb-650">
                        @csrf
                        <!--Invoice  settings-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">

                              

                                <div class="mb-3">
                                    <label for="order_code_prefix"
                                        class="form-label">{{ localize('Code Prefix') }}<span
                                            class="text-danger ms-1">*</span> </label>
                                    <input type="hidden" name="types[]" value="order_code_prefix">
                                    <input type="text" id="order_code_prefix" name="order_code_prefix"
                                        class="form-control" value="{{ getSetting('order_code_prefix') }}"
                                        min="-1">
                                </div>
                                <div class="mb-3">
                                    <label for="order_code_start"
                                        class="form-label">{{ localize('Code Prefix Start') }}<span
                                            class="text-danger ms-1">*</span> </label>
                                    <input type="hidden" name="types[]" value="order_code_start">
                                    <input type="text" id="order_code_start" name="order_code_start"
                                        class="form-control" value="{{ getSetting('order_code_start') }}"
                                        min="-1">
                                </div>
                               

                                <div class="mb-3">
                                    <label for="invoice_thanksgiving" class="form-label">{{ localize('Thanks Message') }}<span
                                            class="text-danger ms-1">*</span> </label>
                                    <input type="hidden" name="types[]" value="invoice_thanksgiving">
                                    <textarea type="number" id="invoice_thanksgiving" name="invoice_thanksgiving" class="form-control">{{ getSetting('invoice_thanksgiving') }}</textarea>
                                </div>


                            </div>
                        </div>
                        <!--Invoice settings-->


                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save Configuration') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Configure Settings') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('General Information') }}</a>
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
