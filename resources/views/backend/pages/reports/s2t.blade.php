@extends('backend.layouts.master')

@section('title')
    {{ localize('Speech to Text Report') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Speech to Text Report') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Speech to Text Report') }}</li>
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
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary">
                                            <i data-feather="search" width="18"></i>
                                            {{ localize('Search') }}
                                        </button>
                                    </div>
                                    <div class="col-auto flex-grow-1"></div>
                                    <div class="col-auto text-end">
                                        <span class="fs-sm">
                                            {{ localize('Total Images Generated') }}
                                        </span>
                                        <div class="fw-bold text-primary">
                                            {{ formatWords($totalWordsGenerated) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <table class="table tt-footable border-top align-middle" data-use-parent-width="true">
                            <thead>
                                <tr>
                                    <th class="text-center">{{ localize('S/L') }}
                                    </th>
                                    <th>{{ localize('Title') }}</th>
                                    <th>{{ localize('User') }}</th>
                                    <th>{{ localize('Generated On') }}</th>
                                    <th data-breakpoints="xs" class="text-end">{{ localize('Size') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($usage as $key => $use)
                                    <tr>
                                        <td class="text-center">
                                            {{ $key + 1 + ($usage->currentPage() - 1) * $usage->perPage() }}</td>

                                        <td>
                                            <h6 class="fs-sm mb-0 ms-2">{{ $use->title }}</h6>
                                        </td>
                                        <td>
                                            <span class="fw-bold">
                                                {{ @$use->user->name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fs-sm">{{ date('d M, Y', strtotime($use->created_at)) }}</span>
                                        </td>

                                        <td class="text-end">
                                            <span class="fw-bold">{{ $use->words }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <!--pagination start-->
                        <div class="d-flex align-items-center justify-content-between px-4 pb-4">
                            <span>{{ localize('Showing') }}
                                {{ $usage->firstItem() }}-{{ $usage->lastItem() }} {{ localize('of') }}
                                {{ $usage->total() }} {{ localize('results') }}</span>
                            <nav>
                                {{ $usage->appends(request()->input())->links() }}
                            </nav>
                        </div>
                        <!--pagination end-->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
