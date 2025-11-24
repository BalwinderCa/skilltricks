@extends('backend.layouts.master')

@section('title')
    {{ localize('Folders') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('All Folders') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Folders') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-4" id="section-1">
                                <form class="app-search" action="{{ Request::fullUrl() }}" method="GET">
                                    <div class="card-header border-bottom-0">
                                        <div class="row justify-content-between g-3">
                                            <div class="col-auto flex-grow-1">
                                                <div class="tt-search-box">
                                                    <div class="input-group">
                                                        <span
                                                            class="position-absolute top-50 start-0 translate-middle-y ms-2">
                                                            <i data-feather="search"></i></span>
                                                        <input class="form-control rounded-start w-100" type="text"
                                                            id="search" name="search"
                                                            placeholder="{{ localize('Search') }}..."
                                                            @isset($searchKey)
                                                value="{{ $searchKey }}"
                                            @endisset>
                                                    </div>
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
                                <div class="card-body">
                                    <div class="row g-3">
                                        @foreach ($folders as $key => $folder)
                                            <div class="col-12 col-lg-4">
                                                <div
                                                    class="d-flex align-items-center justify-content-between border rounded tt-single-folder">

                                                    <a href="{{ route('folders.show', $folder->slug) }}"
                                                        class="col-auto flex-grow-1 p-3 tt-folder-title d-flex align-items-center">
                                                        <span class="tt-line-clamp fw-medium tt-clamp-1"><i
                                                                data-feather="folder" class="icon-16 me-1"></i>
                                                            <span class="mt-1">{{ $folder->name }}</span>
                                                        </span>
                                                    </a>
                                                    <div class="col-auto dropdown tt-tb-dropdown pe-2">
                                                        <span class="cursor-pointer tt-dropdown-icon" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false"><i
                                                                data-feather="more-vertical" class="icon-16"></i></span>
                                                        <div class="dropdown-menu dropdown-menu-end shadow">
                                                            <a class="dropdown-item"
                                                                href="{{ route('folders.edit', ['slug' => $folder->slug, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}">
                                                                <i data-feather="edit-3"
                                                                    class="me-2"></i>{{ localize('Rename') }}
                                                            </a>
                                                            <a href="#" onclick="confirmDelete(this)"
                                                                class="dropdown-item confirm-delete"
                                                                data-href="{{ route('folders.delete', $folder->id) }}"
                                                                title="{{ localize('Delete') }}">
                                                                <i data-feather="trash" class="me-2"></i>{{ localize('Delete') }}
                                                            </a>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!--pagination start-->                               
                                <x-pagination-component :list="$folders"/>
                                <!--pagination end-->
                            </div>
                        </div>
                    </div>

                    @if (isCustomer())
                        @include('backend.pages.folders.inc.folders-form')
                    @endif
                    @can('add_folders')
                        @include('backend.pages.folders.inc.folders-form')
                    @endcan
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Folder Information') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('All Folders') }}</a>
                                    </li>
                                    @can('add_folders')
                                        <li>
                                            <a href="#section-2">{{ localize('Add New Folder') }}</a>
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
