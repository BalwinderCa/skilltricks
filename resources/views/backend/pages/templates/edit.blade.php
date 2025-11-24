@extends('backend.layouts.master')

@section('title')
    {{ localize('Update Template') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4 g-3">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Update Template') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('templates.index') }}">{{ localize('Templates') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Update') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <x-change-language :langkey="$lang_key" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-xl-12 col-lg-12 pe-xl-4">
                    <div class="tt-template-field flex-grow-1">
                        <div class="row justify-content-center">
                            <div class="col-12">
                                <form action="{{ route('templates.update') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $template->id }}">
                                    <input type="hidden" name="lang_key" value="{{ $lang_key }}">
                                    <!--basic information start-->
                                    <div class="card mb-4" id="section-1">
                                        <div class="card-body">
                                            <h5 class="mb-4">{{ localize('Basic Information') }}</h5>

                                            <div class="mb-4">
                                                <label for="name" class="form-label">{{ localize('Template Name') }}
                                                    <small class="ms-1 text-danger">*</small> </label>
                                                <input type="text" name="name" id="name"
                                                    placeholder="{{ localize('Type template name') }}" class="form-control"
                                                    required
                                                    value="{{ $template->collectLocalization('name', $lang_key) }}">
                                            </div>

                                            <div class="mb-4">
                                                <label for="template_group_id"
                                                    class="form-label">{{ localize('Category') }}</label>
                                                <select class="form-control select2" name="template_group_id"
                                                    data-toggle="select2">
                                                    <option value="">{{ localize('Select a category') }}</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}"
                                                            @if ($template->template_group_id == $category->id) selected @endif>
                                                            {{ localize($category->name) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label">{{ localize('Description') }}</label>
                                                <textarea class="form-control" name="description" id="description"
                                                    placeholder="{{ localize('Type short description') }}">{{ $template->collectLocalization('description', $lang_key) }}</textarea>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label">{{ localize('Icon') }}</label>
                                                <div class="tt-image-drop rounded">
                                                    <span class="fw-semibold">{{ localize('Choose Template Icon') }}</span>
                                                    <!-- choose media -->
                                                    <div class="tt-product-thumb show-selected-files mt-3">
                                                        <div class="avatar avatar-xl cursor-pointer choose-media"
                                                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                            onclick="showMediaManager(this)" data-selection="single">
                                                            <input type="hidden" name="icon"
                                                                value="{{ $template->icon }}">
                                                            <div class="no-avatar rounded-circle">
                                                                <span><i data-feather="plus"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- choose media -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!--basic information end-->

                                    <!-- submit button -->
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="mb-4">
                                                <button class="btn btn-primary" type="submit">
                                                    <i data-feather="save" class="me-1"></i>
                                                    {{ localize('Save Template') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- submit button end -->

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        "use strict";
        // runs when the document is ready --> for media files
        $(document).ready(function() {
            getChosenFilesCount();
            showSelectedFilePreviewOnLoad();
        });
    </script>
@endsection
