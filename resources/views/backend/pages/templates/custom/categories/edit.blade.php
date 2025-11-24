@extends('backend.layouts.master')

@section('title')
    {{ localize('Update Template Category') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Update Template Category') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('custom.templateCategories.index') }}">{{ localize('Template Categories') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Update') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <select id="language"
                                    class="w-100 min-w-150 form-control text-capitalize country-flag-select"
                                    data-toggle="select2" onchange="localizeData(this.value)">
                                    @foreach (\App\Models\Language::isActive()->get() as $key => $language)
                                        <option value="{{ $language->code }}"
                                            {{ $lang_key == $language->code ? 'selected' : '' }}
                                            data-flag="{{ staticAsset('backend/assets/img/flags/' . $language->flag . '.png') }}">
                                            {{ $language->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-4">

                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ route('custom.templateCategories.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $category->id }}">
                        <input type="hidden" name="lang_key" value="{{ $lang_key }}">
                        <!--basic information start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>
                                <div class="mb-4">
                                    <label for="name" class="form-label">{{ localize('Category Name') }}</label>
                                    <input class="form-control" type="text" id="name" name="name"
                                        placeholder="{{ localize('Type category name') }}" required
                                        value="{{ $category->collectLocalization('name', $lang_key) }}">
                                    @if ($errors->has('name'))
                                        <span class="text-danger">{{ $errors->first('name') }}</span>
                                    @endif
                                </div>

                                @if (env('DEFAULT_LANGUAGE') == $lang_key)
                                    <div class="mb-4">
                                        <label for="icon" class="form-label">{{ localize('Icon') }}<a class="ms-1"
                                                href="https://icons8.com/line-awesome" target="_blank"
                                                rel="noopener noreferrer"><i data-feather="info"
                                                    class="icon-16"></i></a></label>
                                        <input class="form-control" type="text" id="icon" name="icon"
                                            placeholder='<i class="las la-info-circle"></i>' value="{{ $category->icon }}">
                                    </div>
                                @endif

                            </div>
                        </div>
                        <!--basic information end-->

                        <!-- submit button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i> {{ localize('Save Changes') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- submit button end -->

                    </form>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar d-none d-xl-block">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Category Information') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('Basic Information') }}</a>
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
