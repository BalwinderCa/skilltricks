@extends('backend.layouts.master')

@section('title')
    {{ localize('Custom Template Categories') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Custom Template Categories') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Custom Template Categories') }}</li>
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

                                <table class="table tt-footable border-top align-middle" data-use-parent-width="true">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="7%">{{ localize('S/L') }}</th>
                                            <th>{{ localize('Name') }}</th>
                                            <th data-breakpoints="xs sm" class="text-end">{{ localize('Action') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($categories as $key => $category)
                                            <tr>
                                                <td class="text-center">
                                                    {{ $key + 1 + ($categories->currentPage() - 1) * $categories->perPage() }}
                                                </td>

                                                <td class="">
                                                    <div class="fw-semibold d-flex align-items-center">
                                                        <div class="cat-icon">
                                                            {!! $category->icon !!}
                                                        </div>
                                                        <div class="ms-1">{{ $category->collectLocalization('name') }}
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="text-end">
                                                    @if (auth()->user()->user_type != 'customer')
                                                        <div class="dropdown tt-tb-dropdown">
                                                            <button type="button" class="btn p-0" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                <i data-feather="more-vertical"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-end shadow">
                                                                <a class="dropdown-item"
                                                                    href="{{ route('custom.templateCategories.edit', ['id' => $category->id, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}">
                                                                    <i data-feather="edit-3"
                                                                        class="me-2"></i>{{ localize('Edit') }}
                                                                </a>

                                                                <a href="#" class="dropdown-item confirm-delete"
                                                                    data-href="{{ route('custom.templateCategories.delete', $category->id) }}"
                                                                    title="{{ localize('Delete') }}">
                                                                    <i data-feather="trash-2" class="me-2"></i>
                                                                    {{ localize('Delete') }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @else
                                                        @if ((int) $category->user_id == auth()->user()->id)
                                                            <div class="dropdown tt-tb-dropdown">
                                                                <button type="button" class="btn p-0"
                                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                                    <i data-feather="more-vertical"></i>
                                                                </button>
                                                                <div class="dropdown-menu dropdown-menu-end shadow">
                                                                    <a class="dropdown-item"
                                                                        href="{{ route('custom.templateCategories.edit', ['id' => $category->id, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}">
                                                                        <i data-feather="edit-3"
                                                                            class="me-2"></i>{{ localize('Edit') }}
                                                                    </a>

                                                                    <a href="#" class="dropdown-item confirm-delete"
                                                                        data-href="{{ route('custom.templateCategories.delete', $category->id) }}"
                                                                        title="{{ localize('Delete') }}">
                                                                        <i data-feather="trash-2" class="me-2"></i>
                                                                        {{ localize('Delete') }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!--pagination start-->
                                <div class="d-flex align-items-center justify-content-between px-4 pb-4">
                                    <span>{{ localize('Showing') }}
                                        {{ $categories->firstItem() }}-{{ $categories->lastItem() }} {{ localize('of') }}
                                        {{ $categories->total() }} {{ localize('results') }}</span>
                                    <nav>
                                        {{ $categories->appends(request()->input())->links() }}
                                    </nav>
                                </div>
                                <!--pagination end-->
                            </div>
                        </div>

                        <form action="{{ route('custom.templateCategories.store') }}" class="pb-650" method="POST">
                            @csrf
                            <!-- Category info start-->
                            <div class="card mb-4" id="section-2">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('Add New Category') }}</h5>

                                    <div class="mb-4">
                                        <label for="name" class="form-label">{{ localize('Category Name') }}<span
                                                class="text-danger ms-1">*</span></label>
                                        <input class="form-control" type="text" id="name" name="name"
                                            placeholder="{{ localize('Type category name') }}"
                                            value="{{ old('name') }}" required>
                                        @if ($errors->has('name'))
                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                        @endif
                                    </div>

                                    <div class="mb-4">
                                        <label for="icon" class="form-label">{{ localize('Icon') }}<a class="ms-1"
                                                href="https://icons8.com/line-awesome" target="_blank"
                                                rel="noopener noreferrer"><i data-feather="info"
                                                    class="icon-16"></i></a></label>
                                        <input class="form-control" type="text" id="icon" name="icon"
                                            placeholder='<i class="las la-info-circle"></i>'>
                                    </div>
                                </div>
                            </div>
                            <!-- Category info end-->

                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-4">
                                        <button class="btn btn-primary" type="submit">
                                            <i data-feather="save" class="me-1"></i> {{ localize('Save Category') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Category Information') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('All Categories') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-2">{{ localize('Add New Category') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
