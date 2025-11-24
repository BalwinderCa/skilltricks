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
                <div class="col-12">
                    <div class="card mb-4" id="section-1">
                        <form class="app-search" action="{{ Request::fullUrl() }}" method="GET">
                            <div class="card-header border-bottom-0">
                                <div class="row justify-content-between g-3">
                                    <div class="col-auto flex-grow-1">
                                        <div class="tt-search-box">
                                            <div class="input-group">
                                                <span class="position-absolute top-50 start-0 translate-middle-y ms-2">
                                                    <i data-feather="search"></i></span>
                                                <input class="form-control rounded-start w-100" type="text"
                                                    id="search" name="search" placeholder="{{ localize('Search') }}..."
                                                    @isset($searchKey)
                                                value="{{ $searchKey }}"
                                            @endisset>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="input-group">
                                            <select class="form-select select2" name="status"
                                                data-minimum-results-for-search="Infinity">
                                                <option value="">{{ localize('Select status') }}</option>
                                                <option value="2"
                                                    @isset($status)
                                                     @if ($status == 2) selected @endif
                                                    @endisset>
                                                    {{ localize('Pending') }}</option>
                                                <option value="3"
                                                    @isset($status)
                                                     @if ($status == 3) selected @endif
                                                    @endisset>
                                                    {{ localize('Reject') }}</option>
                                                <option value="4"
                                                    @isset($status)
                                                     @if ($status == 4) selected @endif
                                                    @endisset>
                                                    {{ localize('Incomplete') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary">
                                            <i data-feather="search" width="18"></i>
                                            {{ localize('Search') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>


                        <table class="table tt-footable border-top align-middle" data-use-parent-width="true">
                            <thead>
                                <tr>
                                    <th class="text-center">{{ localize('S/L') }}</th>
                                    @if (auth()->user()->user_type != 'customer')
                                        <th>{{ localize('User') }}</th>
                                    @endif
                                    <th>{{ localize('Package') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Package Price') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Paid Amount') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Created Date') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Status') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Payment Method') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payment_requests as $key => $history)
                                    <tr>
                                        <td class="text-center fs-sm">
                                            {{ $key + 1 + ($payment_requests->currentPage() - 1) * $payment_requests->perPage() }}
                                        </td>

                                        @if (auth()->user()->user_type != 'customer')
                                            <td>
                                                <a href="javascript:void(0);" class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm">
                                                        <img class="rounded-circle"
                                                            src="{{ uploadedAsset($history->user->avatar) }}"
                                                            alt=""
                                                            onerror="this.onerror=null;this.src='{{ staticAsset('backend/assets/img/placeholder-thumb.png') }}';" />
                                                    </div>
                                                    <h6 class="fs-sm mb-0 ms-2">{{ $history->user->name }}
                                                    </h6>
                                                </a>
                                            </td>
                                        @endif


                                        <td class="text-capitalize fw-sm">
                                            {{ $history->subscriptionPackage->title }}/{{ $history->subscriptionPackage->package_type == 'starter' ? localize('Monthly') : $history->subscriptionPackage->package_type }}
                                        </td>

                                        <td class="text-capitalize fw-sm">
                                            {{ $history->subscriptionPackage->price > 0 ? formatPrice($history->subscriptionPackage->price) : localize('Free') }}
                                        </td>
                                        <td class="text-capitalize fw-sm">
                                            {{ $history->price > 0 ? formatPrice($history->price) : localize('Free') }}
                                        </td>

                                        <td>
                                            <span
                                                class="fs-sm">{{ date('d M, Y', strtotime($history->created_at)) }}</span>
                                        </td>

                                        <td>
                                            <span class="badge bg-soft-primary rounded-pill text-capitalize">
                                                @if ($history->payment_status == 1)
                                                    {{ localize('Approved') }}
                                                @elseif($history->payment_status == 2)
                                                    {{ localize('Pending') }}
                                                @elseif($history->payment_status == 3)
                                                    {{ localize('Reject') }}
                                                @elseif($history->payment_status == 4)
                                                    <span class="text-danger"> {{ localize('Incomplete') }}</span>
                                                @endif
                                            </span>
                                        </td>

                                        <td>

                                            <span class="badge bg-soft-primary rounded-pill text-capitalize">
                                                {{ $history->offlinePaymentMethod->name }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <x-action-drop-down>

                                                @can('approve_payment_request')
                                                    <a href="#" class="dropdown-item confirm-approve"
                                                        data-href="{{ route('admin.payment-request.approve', $history->id) }}"
                                                        title="{{ localize('Approve') }}">
                                                        <i data-feather="check" class="me-2"></i>
                                                        {{ localize('Approve') }}
                                                    </a>
                                                @endcan

                                                <a href="{{ route('admin.payment-request.view', $history->id) }}"
                                                    class="dropdown-item" title="{{ localize('View') }}">
                                                    <i data-feather="eye" class="me-2"></i>{{ localize('View') }}
                                                </a>

                                                @can('reject_payment_request')
                                                    <a href="#" class="dropdown-item confirm-reject"
                                                        data-href="{{ route('admin.payment-request.reject', $history->id) }}"
                                                        title="{{ localize('Reject') }}">
                                                        <i data-feather="x" class="me-2"></i>
                                                        {{ localize('Reject') }}
                                                    </a>
                                                @endcan

                                                @can('resubmit_payment_request')
                                                    <a href="#" class="dropdown-item" data-href=""
                                                        title="{{ localize('Add Note') }}"
                                                        onclick="showaddNoteModal({{ $history->id }})">
                                                        <i data-feather="file-text" class="me-2"></i>
                                                        {{ localize('Add Note') }}
                                                    </a>
                                                @endcan
                                            </x-action-drop-down>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>


                        <!--pagination start-->
                        <x-pagination-component :list="$payment_requests" />
                        <!--pagination end-->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('modals-common')
    <!-- Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNoteModalLabel">{{ localize('Write Note') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">

                    <form action="{{ route('admin.payment-request.feedback-note') }}" method="POST"
                        class="payment-method-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="history_id" value="" class="history_id">

                        <!-- notet -->


                        <div class="mb-4">
                            <label class="form-label">{{ localize('Note') }}</label>
                            <textarea class="form-control" name="note" id="offline_note" rows="3"
                                placeholder="{{ localize('Type your Note') }}"></textarea>
                            @if ($errors->has('note'))
                                <span class="text-danger">{{ $errors->first('note') }}</span>
                            @endif
                        </div>

                        <div class="justify-content-center pb-3 text-center">
                            <button id="delete-link" class="btn btn-danger mt-2">{{ localize('Proceed') }}</button>
                            <button type="button" class="btn btn-secondary mt-2"
                                data-bs-dismiss="modal">{{ localize('Cancel') }}</button>
                        </div>

                        <!-- End Offline  payment -->
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('scripts-common')
    <script>
        "use strict";

        // show Add Note modal
        function showaddNoteModal(id) {
            $('.history_id').val(id);
            $('#addNoteModal').modal('show');
        }
    </script>
@endsection
