@extends('backend.layouts.master')

@section('title')
    {{ localize('Settings') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">


            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Settings') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Settings') }}</li>
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


                    <form action="{{ route('admin.envKey.update') }}" method="POST" enctype="multipart/form-data"
                        class="mt-5">
                        @csrf
                        <!--google analytics-->
                        <div class="card mb-4" id="section-9">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Subscription Settings') }}</h5>



                                <div class="mb-3">
                                    <div class="form-check tt-checkbox">
                                        <label for="next_subscription" class="form-check-label fw-medium ">
                                            <input class="form-check-input cursor-pointer subcriptionSettings"
                                                data-type="next_subscription" onchange="updateStatus(this)" type="checkbox"
                                                id="next_subscription"
                                                {{ getSetting('next_subscription') == 1 ? 'checked' : '' }}>
                                            {{ localize('Start Automatically Your Next Subscription plan if Current Subscription plan balance is Zero(0)') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--google analytics-->

                    </form>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Configure General Settings') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">

                                    <li>
                                        <a href="#section-9">{{ localize('Subscription Settings') }}</a>
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
        "use strict";

        // runs when the document is ready --> for media files
        $(document).ready(function() {
            getChosenFilesCount();
            showSelectedFilePreviewOnLoad();
        });

        function updateStatus(el) {
            let type = $(el).data('type');
            if (el.checked) {
                var is_active = 1;
            } else {
                var is_active = 0;
            }

            $.post('{{ route('admin.subscription-settings.store') }}', {
                    _token: '{{ csrf_token() }}',
                    is_active: is_active,
                    type: type,

                },
                function(data) {
                    if (data == 1) {
                        notifyMe('success', '{{ localize('Status updated successfully') }}');
                    } else {
                        notifyMe('danger', '{{ localize('Something went wrong') }}');
                    }
                });
        }
    </script>
@endsection
