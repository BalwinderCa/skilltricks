@extends('backend.layouts.master')

@section('title')
    {{ localize('Most Used Templates') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Most Used Templates') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Most Used Templates') }}</li>
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
                                    <div class="col-auto flex-grow-1">
                                        <div class="tt-search-box">
                                            <div class="input-group">
                                                <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i
                                                        data-feather="search"></i></span>
                                                <input class="form-control rounded-start w-100" type="text"
                                                    id="search" name="search" placeholder="{{ localize('Search') }}"
                                                    @isset($searchKey)
                                                value="{{ $searchKey }}"
                                                @endisset>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-auto">
                                        <div class="input-group">
                                            <select class="form-select select2" name="order"
                                                data-minimum-results-for-search="Infinity">
                                                <option value="DESC"
                                                    @isset($order)
                                                         @if ($order == 'DESC') selected @endif
                                                        @endisset>
                                                    {{ localize('High ⟶ Low') }}</option>

                                                <option value="ASC"
                                                    @isset($order)
                                                         @if ($order == 'ASC') selected @endif
                                                        @endisset>
                                                    {{ localize('Low ⟶ High') }}</option>
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

                        <table class="table tt-footable border-top" data-use-parent-width="true">
                            <thead>
                                <tr>
                                    <th class="text-center">{{ localize('S/L') }}
                                    </th>
                                    <th>{{ localize('Template Name') }}</th>
                                    <th class="text-end">{{ localize('Total Words') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($usage as $key => $use)
                                    <tr>
                                        <td class="text-center">
                                            {{ $key + 1 + ($usage->currentPage() - 1) * $usage->perPage() }}</td>
                                        <td>
                                            <h6 class="fs-sm mb-0 ms-2">{{ localize($use->name) }}
                                            </h6>
                                        </td>

                                        <td class="text-end">
                                            {{ $use->total_words_generated }}
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
