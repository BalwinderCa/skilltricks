@extends('backend.layouts.master')


@section('title')
    {{ localize('Templates') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('All Templates') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Templates') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row mb-3 g-3">
                <div class="col-xl-12">
                    <div class="card h-100 bg-secondary-subtle">
                        <div class="card-header sticky-top-card bg-secondary py-lg-5 py-4">
                            <!-- template search -->
                            <form action="{{ Request::fullUrl() }}" method="GET">
                                <div class="row justify-content-center">
                                    <div class="col-lg-6 col-md-8">
                                        <div class="input-group">
                                            <input type="search"
                                                placeholder="{{ localize('Search template that you are looking for') }}..."
                                                name="search"
                                                @isset($searchKey)
                                        value="{{ $searchKey }}"
                                    @endisset
                                                class="form-control border border-2 border-primary rounded-pill rounded-end">
                                            <div class="input-group-append">
                                                <button type="submit"
                                                    class="btn btn-link bg-primary border border-2 border-primary text-light rounded-pill rounded-start"><i
                                                        class="flaticon-search translate-middle-y"></i>{{ localize('Search Now') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="tt-advance-options cursor-pointer d-block d-lg-none text-center mt-3">
                                <label for="tt-advance-options"
                                    class="form-label cursor-pointer mb-0 btn btn-light shadow-sm btn-sm rounded-pill"><span
                                        class="fw-bold tt-promot-number fw-bold me-1"></span>{{ localize('Show Category') }}
                                    <span><i data-feather="plus" class="icon-16 text-primary ms-2"></i></span></label>
                                <div class="tt-advance-options-wrapper" id="tt-advance-options">
                                    <ul class="nav nav-pills d-flex justify-content-center tt-horizontal-tab mt-3"
                                        id="pills-tab" role="tablist">

                                        <li class="nav-item mb-2" role="presentation">
                                            <a class="nav-link rounded-pill active" id="tabId-all"data-bs-toggle="pill"
                                                data-bs-target="#all-tab" type="button" role="tab"
                                                aria-controls="all-tab" aria-selected="true" href="#">
                                                <i data-feather="book" class="me-1 icon-16"></i>
                                                {{ localize('All Templates') }}
                                            </a>
                                        </li>

                                        @foreach ($templateGroups as $group)
                                            <li class="nav-item mb-2" role="presentation">
                                                <a class="nav-link rounded-pill" id="tabId-{{ $group->slug }}"
                                                    data-bs-toggle="pill" data-bs-target="#tab-{{ $group->slug }}"
                                                    type="button" role="tab" aria-controls="tab-{{ $group->slug }}"
                                                    aria-selected="true" href="#">
                                                    <i data-feather="{{ $group->icon }}" class="me-1 icon-16"></i>
                                                    {{ localize($group->name) }}</a>
                                            </li>
                                        @endforeach
                                    </ul>

                                </div>
                            </div>



                            <div class="d-none d-lg-block">
                                <ul class="nav nav-pills d-flex justify-content-center tt-horizontal-tab mt-3"
                                    id="pills-tab" role="tablist">

                                    <li class="nav-item mb-2" role="presentation">
                                        <a class="nav-link rounded-pill active" id="tabId-all"data-bs-toggle="pill"
                                            data-bs-target="#all-tab" type="button" role="tab" aria-controls="all-tab"
                                            aria-selected="true" href="#">
                                            <i data-feather="book" class="me-1 icon-16"></i>
                                            {{ localize('All Templates') }}
                                        </a>
                                    </li>

                                    @foreach ($templateGroups as $group)
                                        <li class="nav-item mb-2" role="presentation">
                                            <a class="nav-link rounded-pill" id="tabId-{{ $group->slug }}"
                                                data-bs-toggle="pill" data-bs-target="#tab-{{ $group->slug }}"
                                                type="button" role="tab" aria-controls="tab-{{ $group->slug }}"
                                                aria-selected="true" href="#">
                                                <i data-feather="{{ $group->icon }}" class="me-1 icon-16"></i>
                                                {{ localize($group->name) }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>





                        @php
                            $user = auth()->user();
                            $subscriptionTemplates = [];
                        @endphp

                        <div class="tab-content card-body" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="all-tab" role="tabpanel"
                                aria-labelledby="tabId-all" tabindex="0">
                                <div class="row g-3">

                                    @if (isCustomer())
                                        @php
                                            $package = optional(activePackageHistory())->subscriptionPackage ?? new \App\Models\SubscriptionPackage();
                                            // subscription package template based on template
                                            $subscriptionTemplates = \App\Models\SubscriptionPackageTemplate::where('subscription_package_id', $package->id)
                                                ->pluck('template_id')
                                                ->toArray();
                                        @endphp
                                    @endif

                                    @foreach ($templates as $template)
                                        <div class="col-lg-3 col-sm-6">
                                            @include('backend.pages.templates.inc.template-card', [
                                                'template' => $template,
                                                'subscriptionTemplates' => $subscriptionTemplates,
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                            </div>


                            <!-- loop -->
                            @foreach ($templateGroups as $group)
                                <div class="tab-pane fade" role="tabpanel" aria-labelledby="tabId-{{ $group->slug }}"
                                    id="tab-{{ $group->slug }}">
                                    <div class="row g-3">
                                        @foreach ($templates as $template)
                                            @if ($template->template_group_id == $group->id)
                                                <div class="col-lg-3 col-sm-6">
                                                    @include('backend.pages.templates.inc.template-card', [
                                                        'template' => $template,
                                                        'subscriptionTemplates' => $subscriptionTemplates,
                                                    ])
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@section('scripts')
    @include('backend.pages.templates.inc.template-scripts')
@endsection
