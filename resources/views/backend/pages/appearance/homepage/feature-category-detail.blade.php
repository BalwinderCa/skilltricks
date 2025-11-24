@extends('backend.layouts.master')

@section('title')
    {{ localize('Website Homepage Configuration') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Feature Category Detail') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Feature Category Detail') }}</li>
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
                                @include('backend.pages.appearance.homepage.inc._feature-category-detail-list')
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.appearance.homepage.store-feature-category-detail') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="language_key" id="language_id" value="{{ @$lang_key }}">
                        <!--slider info start-->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Add Feature Category Detail') }}</h5>

                                <div class="mb-3">
                                    <label for="title" class="form-label">{{ localize('Title') }} <x-required-star/> </label>
                                    <input type="text" name="title" id="title"
                                        placeholder="{{ localize('Type title') }}" class="form-control" required>
                                        <x-error :name="'title'"/>
                                </div>

                                <div class="mb-3">
                                    <label for="icon" class="form-label">{{ localize('Icon') }}  <span><a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener noreferrer">{{localize('Get Icons')}}</a></span></label>
                                    <input type="text" name="icon" id="icon"
                                        placeholder="bi bi-stars" class="form-control">
                                        <x-error :name="'icon'"/>
                                </div>


                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Feature Category') }} <x-required-star/></label>
                                    <select class="select2 form-control" name="feature_category_id"
                                        data-minimum-results-for-search="Infinity">
                                        @foreach ($featureCategories as $item)
                                            <option value="{{$item->id}}">{{$item->collectLocalization('name')}}</option>
                                        @endforeach

                                    </select>
                                    <x-error :name="'feature_category_id'"/>

                                </div>


                                <div class="mb-3">
                                    <label for="short_description" class="form-label">{{ localize('Short Description') }} <x-required-star/> </label>
                                    <textarea name="short_description" id="short_description" placeholder="{{ localize('Type short description') }}" class="form-control" required></textarea>
                                    <x-error :name="'short_description'"/>
                                </div>


                                <div class="mb-3">
                                    <label class="form-label">{{ localize(' Image') }}</label>
                                    <div class="tt-image-drop rounded">
                                        <span class="fw-semibold">{{ localize('Choose  Image') }}</span>
                                        <!-- choose media -->
                                        <div class="tt-product-thumb show-selected-files mt-3">
                                            <div class="avatar avatar-xl cursor-pointer choose-media"
                                                data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                                onclick="showMediaManager(this)" data-selection="single">
                                                <input type="hidden" name="image">
                                                <div class="no-avatar rounded-circle">
                                                    <span><i data-feather="plus"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- choose media -->
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ localize('Is Active ?') }} <x-required-star /></label>
                                    <select class="select2 form-control" name="is_active"
                                        data-minimum-results-for-search="Infinity">

                                        <option value="1">{{ localize('Yes') }}</option>
                                        <option value="0">{{ localize('No') }}</option>

                                    </select>

                                </div>
                            </div>
                        </div>
                        <!--slider info end-->

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i> {{ localize('Save') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Homepage Configuration') }}</h5>
                            <div class="tt-vertical-step-link">
                                <ul class="list-unstyled">
                                    @include('backend.pages.appearance.homepage.inc.rightSidebar')
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection
