@extends('backend.layouts.master')

@section('title')
    {{ localize('Subscriptions Report') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Subscriptions Report') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Subscriptions Report') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card mb-4" id="section-1">
                        <form class="app-search" action="{{ Request::fullUrl() }}" method="GET">
                            <div class="card-header border-bottom-0">
                                <div class="row justify-content-between g-3">

                                    <div class="col-4">
                                        <div class="input-group">
                                            @php
                                                $start_date = date('m/d/Y', strtotime('7 days ago'));
                                                $end_date = date('m/d/Y', strtotime('today'));
                                                if (isset($date_var)) {
                                                    $start_date = date('m/d/Y', strtotime($date_var[0]));
                                                    $end_date = date('m/d/Y', strtotime($date_var[1]));
                                                }
                                            @endphp

                                            <input class="form-control date-range-picker date-range" type="text"
                                                placeholder="{{ localize('Start date - End date') }}" name="date_range"
                                                data-startdate="'{{ $start_date }}'"
                                                data-enddate="'{{ $end_date }}'">
                                        </div>
                                    </div>
                                    @if (auth()->user()->user_type != 'customer')
                                    <div class="col-auto">
                                        <div class="input-group">
                                            <select class="form-select select2" name="user_id">
                                                <option value="">{{ localize('Select User') }}</option>
                                                @foreach ($users as $user)                                                
                                                    <option value="{{$user->id}}" {{isset($user_id) ? ($user_id ==  $user->id ? 'selected':''):''}}> {{$user->name}}
                                                       </option>
                                                @endforeach

                                            </select>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-auto">
                                        <div class="input-group">
                                            <select class="form-select select2" name="package_id">
                                                <option value="">{{ localize('Select Package') }}</option>
                                                @foreach ($packages as $package)                                                
                                                    <option value="{{$package->id}}" {{isset($package_id) ? ($package_id ==  $package->id ? 'selected':''):''}}> {{$package->title}}
                                                       </option>
                                                @endforeach

                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary">
                                            <i data-feather="search" width="18"></i>
                                            {{ localize('Search') }}
                                        </button>
                                    </div>
                                    <div class="col-auto flex-grow-1"></div>
                                    <div class="col-auto text-end">
                                        <span class="fs-sm">
                                            {{ localize('Total Amount') }}
                                        </span>
                                        <div class="fw-bold text-primary">
                                            {{ formatPrice($totalPrice) }}
                                        </div>
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
                                    <th data-breakpoints="xs sm">{{ localize('Price') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Subscribed On') }}</th>
                                    <th data-breakpoints="xs sm" class="text-end">{{ localize('Payment Method') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($histories as $key => $history)
                                    <tr>
                                        <td class="text-center fs-sm">
                                            {{ $key + 1 + ($histories->currentPage() - 1) * $histories->perPage() }}
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

                                        <td>
                                            <span
                                                class="fs-sm">{{ date('d M, Y', strtotime($history->created_at)) }}</span>
                                        </td>


                                        <td class="text-end">

                                            <span class="badge bg-soft-primary rounded-pill text-capitalize">
                                                {{ $history->payment_method }}
                                            </span>
                                        </td>


                                    </tr>
                                @endforeach
                            </tbody>
                        </table>


                        <!--pagination start-->
                        <div class="d-flex align-items-center justify-content-between px-4 pb-4">
                            <span>{{ localize('Showing') }}
                                {{ $histories->firstItem() ?? 0 }}-{{ $histories->lastItem() ?? 0 }}
                                {{ localize('of') }}
                                {{ $histories->total() }} {{ localize('results') }}</span>
                            <nav>
                                {{ $histories->appends(request()->input())->links() }}
                            </nav>
                        </div>
                        <!--pagination end-->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
