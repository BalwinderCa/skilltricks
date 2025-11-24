@extends('backend.layouts.master')

@section('title')
    {{ localize('Projects') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ $folder->name }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>

                                    <li class="breadcrumb-item">
                                        <a href="{{ route('folders.index') }}">{{ localize('Folders') }}</a>
                                    </li>

                                    <li class="breadcrumb-item">{{ localize('Projects') }}</li>
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
                                            <select class="form-select select2" name="content_type"
                                                data-minimum-results-for-search="Infinity">
                                                <option value=""
                                                    @isset($content_type)
                                                     @if ($content_type == 'all') selected @endif
                                                    @endisset>
                                                    {{ localize('All') }}</option>
                                                <option value="content"
                                                    @isset($content_type)
                                                     @if ($content_type == 'content') selected @endif
                                                    @endisset>
                                                    {{ localize('Content') }}</option>
                                                <option value="code"
                                                    @isset($content_type)
                                                     @if ($content_type == 'code') selected @endif
                                                    @endisset>
                                                    {{ localize('Code') }}</option>
                                                <option value="image"
                                                    @isset($content_type)
                                                     @if ($content_type == 'image') selected @endif
                                                    @endisset>
                                                    {{ localize('Image') }}</option>
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

                        <!--project table-->
                        @include('backend.pages.projects.inc.projectTable', [
                            'projects' => $projects,
                            'newTab' => true,
                        ])

                        <!--pagination start-->
                        <div class="d-flex align-items-center justify-content-between px-4 pb-4">
                            <span>{{ localize('Showing') }}
                                {{ $projects->firstItem() ?? 0 }}-{{ $projects->lastItem() ?? 0 }}
                                {{ localize('of') }}
                                {{ $projects->total() }} {{ localize('results') }}</span>
                            <nav>
                                {{ $projects->appends(request()->input())->links() }}
                            </nav>
                        </div>
                        <!--pagination end-->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
