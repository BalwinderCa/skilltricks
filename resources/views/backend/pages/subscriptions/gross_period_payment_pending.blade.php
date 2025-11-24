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
                        <table class="table tt-footable border-top align-middle" data-use-parent-width="true">
                            <thead>
                                <tr>
                                    <th class="text-center">{{ localize('S/L') }}</th>
                                    @if (auth()->user()->user_type != 'customer')
                                        <th>{{ localize('User') }}</th>
                                    @endif
                                 
                                    <th data-breakpoints="xs sm">{{ localize('Order ID') }}</th>                            
                                    <th data-breakpoints="xs sm">{{ localize('Transaction ID') }}</th>                            
                                    <th data-breakpoints="xs sm">{{ localize('VA Number') }}</th>                            
                                    <th data-breakpoints="xs sm">{{ localize('Package') }}</th>                            
                                    <th data-breakpoints="xs sm">{{ localize('Package Price') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Expire time') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Payment Method') }}</th>
                                    <!--<th data-breakpoints="xs sm">{{ localize('Actions') }}</th>-->
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
                                            {{ $history->response->order_id }}
                                        </td>
                                        <td class="text-capitalize fw-sm">
                                            {{ $history->response->transaction_id }}
                                        </td>
                                        <td class="text-capitalize fw-sm">
                                            {{ property_exists($history->response, 'va_numbers') ? $history->response->va_numbers[0]->bank .'['. $history->response->va_numbers[0]->va_number .']' : null }}
                                        </td>
                                        <td class="text-capitalize fw-sm">
                                            {{ $history->subscriptionPackage->title }}/{{ $history->subscriptionPackage->package_type == 'starter' ? localize('Monthly') : $history->subscriptionPackage->package_type }}
                                        </td>

                                        <td class="text-capitalize fw-sm">
                                            {{ $history->subscriptionPackage->price > 0 ? formatPrice($history->subscriptionPackage->price) : localize('Free') }}
                                        </td>
                                       

                                       

                                        <td>
                                            <span class="badge bg-soft-primary rounded-pill text-capitalize">
                                               {{$history->response->expiry_time}}
                                               
                                                
                                            </span>
                                        </td>

                                        <td>

                                            
                                               {{$history->response->payment_type}} <span class="badge bg-soft-primary rounded-pill text-capitalize"> {{ $history->gateway }}</span>
                                            
                                        </td>
                                        <!--<td class="text-end">-->
                                        <!--    <x-action-drop-down>-->

                                               
                                        <!--    </x-action-drop-down>-->
                                        <!--</td>-->

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
