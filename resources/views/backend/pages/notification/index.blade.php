@extends('backend.layouts.master')

@section('title')
    {{ localize('Notification') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Notification') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Notification') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <button type="button" class="tt-remove btn btn-soft-danger d-flex align-items-center"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-title="{{ localize('Remove all data') }}"
                                    data-href="{{ route('admin.notifications.deleteAll') }}" onclick="confirmAllDelete(this)"><i
                                        data-feather="trash-2" class="icon-12 btn-icon"></i>
                                    <span class="ms-1">{{ localize('Delete All') }}</span></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card mb-4" id="section-1">

                        <table class="table tt-footable align-middle" data-use-parent-width="true">
                            <thead>
                                <tr>
                                    <th class="text-center">{{ localize('S/L') }}</th>
                                    <th>{{ localize('Title') }}</th>
                                    <th data-breakpoints="xs sm">{{localize('URL')}}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Date') }}</th>                                   
                                    <th data-breakpoints="xs sm">{{ localize('Is read') }}</th>                                   
                                    <th data-breakpoints="xs sm" class="text-end">{{ localize('Action') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notifications as $key => $notification)
                                    <tr class="{{ $notification->is_read == 0 ? 'fw-bold' : 'fw-light' }}">
                                        <td class="text-center">
                                            {{ $key + 1 + ($notifications->currentPage() - 1) * $notifications->perPage() }}
                                        </td>

                                        <td> {{ $notification->title }} </td>

                                        <td>
                                            @if($notification->url)
                                                <a href="{{URL::to('/'.$notification->url)}}" target="_blank" rel="noopener noreferrer">{{localize('Click')}}</a>
                                            @else
                                                {{ localize('n/a') }}
                                            @endif
                                        </td>

                                        <td>
                                            {{date('d-M-Y h:i:s A', strtotime($notification->created_at))}}
                                           
                                        </td>
                                        <td>
                                            @if($notification->is_read)
                                                {{localize('Yes')}}
                                            @else
                                            {{localize('No')}}
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown tt-tb-dropdown">
                                                <button type="button" class="btn p-0" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i data-feather="more-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end shadow">
                                                   
                                                    <a class="dropdown-item"
                                                        href="{{ route('admin.notifications.index', ['id' => $notification->id, 'is_read' =>1]) }}">
                                                        <i data-feather="check"
                                                            class="me-2"></i>{{ $notification->is_read == 0 ? localize('Mark As Read') : localize('Mark As Unread') }}
                                                    </a>
                                                  

                                                    <a href="#" class="dropdown-item confirm-delete"
                                                        data-href="{{ route('admin.notifications.delete', ['id'=> $notification->id]) }}"
                                                        title="{{ localize('Delete') }}">
                                                        <i data-feather="trash-2" class="me-2"></i>
                                                        {{ localize('Delete') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <!--pagination start-->
                        <div class="d-flex align-items-center justify-content-between px-4 pb-4">
                            <span>{{ localize('Showing') }}
                                {{ $notifications->firstItem() }}-{{ $notifications->lastItem() }} {{ localize('of') }}
                                {{ $notifications->total() }} {{ localize('results') }}</span>
                            <nav>
                                {{ $notifications->appends(request()->input())->links() }}
                            </nav>
                        </div>
                        <!--pagination end-->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


@section('scripts')
    <script>
        "use strict";

        function updateBanStatus(el) {
            if (el.checked) {
                var status = 1;
            } else {
                var status = 0;
            }
            $.post('{{ route('admin.customers.updateBanStatus') }}', {
                    _token: '{{ csrf_token() }}',
                    id: el.value,
                    status: status
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
