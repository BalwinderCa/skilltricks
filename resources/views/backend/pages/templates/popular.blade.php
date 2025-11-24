@extends('backend.layouts.master')


@section('title')
    {{ localize('Popular Templates') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Popular Templates') }}</h1>
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
                <!-- templates  -->
                <div class="col-xl-12">
                    <div class="card h-100 bg-transparent border-0">
                        <div class="row g-3 mt-2">
                            @php
                                $user = auth()->user();
                                $subscriptionTemplates = [];
                            @endphp
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
                                <div class="col-lg-4 col-md-6">
                                    @include('backend.pages.templates.inc.template-card', [
                                        'template' => $template,
                                        'subscriptionTemplates' => $subscriptionTemplates,
                                    ])
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
