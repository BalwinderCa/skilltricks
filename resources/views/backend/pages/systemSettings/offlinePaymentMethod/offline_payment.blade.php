@extends('backend.layouts.master')

@section('title')
    {{ localize('Offline Payment') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Offline Payment Method') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Offline Payment Method') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-4" id="section-1">
                                <!--search start-->
                                <form class="app-search" action="{{ Request::fullUrl() }}" method="GET">
                                    <x-name-search />
                                </form>
                                <!--search start-->
                                <table class="table tt-footable border-top" data-use-parent-width="true">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="7%">{{ localize('S/L') }}</th>
                                            <th>{{ localize('Name') }}</th>
                                            <th>{{ localize('Description') }}</th>
                                            <th>{{ localize('Status') }}</th>
                                            <th data-breakpoints="xs sm" class="text-end">{{ localize('Action') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($offline_payment_methods as $key => $offlinePayment)
                                            <tr>
                                                <td class="text-center">
                                                    {{ $key + 1 + ($offline_payment_methods->currentPage() - 1) * $offline_payment_methods->perPage() }}
                                                </td>

                                                <td class="fw-semibold">
                                                    {{ $offlinePayment->name }}
                                                </td>

                                                <td> {!! nl2br(e($offlinePayment->description)) !!}
                                                </td>

                                                <td>
                                                    <x-status-change :modelid="$offlinePayment->id" :table="$offlinePayment->getTable()"
                                                        :status="$offlinePayment->is_active" />
                                                </td>
                                                <td class="text-end">
                                                    <div class="dropdown tt-tb-dropdown">
                                                        <button type="button" class="btn p-0" data-bs-toggle="dropdown"
                                                            aria-expanded="false">
                                                            <i data-feather="more-vertical"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-end shadow">

                                                            @can('edit_offline_payment_methods')
                                                                <a class="dropdown-item"
                                                                    href="{{ route('admin.offline-payment.edit', ['id' => $offlinePayment->id, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}">
                                                                    <i data-feather="edit-3"
                                                                        class="me-2"></i>{{ localize('Edit') }}
                                                                </a>
                                                            @endcan

                                                            @can('delete_offline_payment_methods')
                                                                <a href="#" class="dropdown-item confirm-delete"
                                                                    data-href="{{ route('admin.offline-payment.delete', $offlinePayment->id) }}"
                                                                    title="{{ localize('Delete') }}">
                                                                    <i data-feather="trash-2" class="me-2"></i>
                                                                    {{ localize('Delete') }}
                                                                </a>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!--pagination start-->
                                <x-pagination-component :list="$offline_payment_methods" />
                                <!--pagination end-->
                            </div>
                        </div>

                        @can('add_offline_payment_methods')
                            <form action="{{ route('admin.offline-payment.store') }}" class="pb-650" method="POST">
                                @csrf
                                <!-- tag info start-->
                                <div class="card mb-4" id="section-2">
                                    <div class="card-body">
                                        <h5 class="mb-4">{{ localize('Add New Offline Payment Method') }}</h5>

                                        <div class="mb-4">
                                            <label for="name" class="form-label">{{ localize('Name') }} <span
                                                    class="text-danger ms-1">*</span></label>
                                            <input class="form-control" type="text" id="name" name="name"
                                                placeholder="{{ localize('Type name') }}" value="{{ old('name') }}"
                                                required>

                                            @if ($errors->has('name'))
                                                <span class="text-danger">{{ $errors->first('name') }}</span>
                                            @endif
                                        </div>
                                        <div class="mb-4">
                                            <label for="description" class="form-label">{{ localize('Description') }} <span
                                                    class="text-danger ms-1">*</span></label>
                                            <textarea name="description" class="form-control" id="description" cols="30" rows="10" required></textarea>

                                            @if ($errors->has('description'))
                                                <span class="text-danger">{{ $errors->first('description') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <!-- tag info end-->

                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-4">
                                            <button class="btn btn-primary" type="submit">
                                                <i data-feather="save" class="me-1"></i>
                                                {{ localize('Save Offline Payment Method') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        @endcan
                    </div>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Offline Payment Method Information') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1"
                                            class="active">{{ localize('All Offline Payment Method') }}</a>
                                    </li>
                                    @can('add_offline_payment_methods')
                                        <li>
                                            <a href="#section-2">{{ localize('Add Offline Payment Method') }}</a>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
