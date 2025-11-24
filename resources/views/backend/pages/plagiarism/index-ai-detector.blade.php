@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Content Detection') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI Content Detection') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AI Content Detection') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action"> 
                                @if(auth()->user()->user_type =='customer')
                                <a href="{{ route('admin.content.detector.create') }}" class="btn btn-primary"><i
                                    data-feather="plus"></i> {{ localize('Scan Content') }}</a>
                                @else
                                    @can('add_content_detectors')
                                        <a href="{{ route('admin.content.detector.create') }}" class="btn btn-primary"><i
                                                data-feather="plus"></i> {{ localize('Scan Content') }}</a>
                                    @endcan
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row g-4">
                <div class="col-12">
                    <div class="card mb-4" id="section-1">


                        <table class="table tt-footable border-top" data-use-parent-width="true">
                            <thead>
                                <tr>
                                    <th class="text-center">{{ localize('S/L') }}</th>
                                    <th>{{ localize('Title') }}</th>
                                    <th data-breakpoints="xs sm md">{{ localize('Words') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Human Score') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('AI Score') }}</th>
                                    <th data-breakpoints="xs sm" class="text-end">{{ localize('Action') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($content_detectors as $key => $content_detector)
                                    <tr>
                                        <td class="text-center">
                                            {{ $key + 1 }}</td>
                                        <td>
                                            {{ $content_detector->title }}
                                        </td>

                                        <td>
                                            <strong>{{ @$content_detector->response->length }}</strong>
                                        </td>

                                        <td>
                                            {{ number_format($content_detector->human) }}
                                        </td>

                                        <td>
                                            {{ number_format($content_detector->ai) }}
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown tt-tb-dropdown">
                                                <button type="button" class="btn p-0" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i data-feather="more-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end shadow">
                                                    
                                                    <a class="dropdown-item"
                                                        href="{{ route('admin.content.detector.show', [$content_detector->id]) }}"
                                                        target="_blank">
                                                        <i data-feather="eye" class="me-2"></i>{{ localize('View') }}
                                                    </a>
                                                    <a href="#" class="dropdown-item confirm-delete"
                                                    data-href="{{ route('admin.content.detector.delete', [$content_detector->id]) }}"
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
                                {{ $content_detectors->firstItem() }}-{{ $content_detectors->lastItem() }}
                                {{ localize('of') }}
                                {{ $content_detectors->total() }} {{ localize('results') }}</span>
                            <nav>
                                {{ $content_detectors->appends(request()->input())->links() }}
                            </nav>
                        </div>
                        <!--pagination end-->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
