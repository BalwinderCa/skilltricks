@extends('backend.layouts.master')

@section('title')
    {{ localize('Dashboard') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    @php
        $user = auth()->user();
        $totalText = \App\Models\SubscriptionHistory::sum('total_used_words');

        $totalImages = \App\Models\Project::where('content_type', 'image')->count();
        $totalCode = \App\Models\Project::where('content_type', 'code')->count();
        $totalS2T = \App\Models\Project::where('content_type', 'speech_to_text')->count();
        
        $recentProjectIds = \App\Models\Project::latest()
            ->where('user_id', $user->id)
            ->take(30)
            ->pluck('id');
        
        $recentProjects = \App\Models\Project::latest()
            ->whereIn('id', $recentProjectIds)
            ->paginate(10);
        
        if (isCustomer()) {
            $totalText = \App\Models\SubscriptionHistory::where('user_id', $user->id)->sum('total_used_words');        
            $totalImages = \App\Models\SubscriptionHistory::where('user_id', $user->id)->sum('total_used_images');
        
            $totalCode = \App\Models\Project::where('user_id', $user->id)
                ->where('content_type', 'code')
                ->count();            
            $totalS2T = \App\Models\SubscriptionHistory::where('user_id', $user->id)->sum('total_used_s2t');
        } else {
            $totalText += \App\Models\AiBlogWizard::whereNull('subscription_history_id')->sum('total_words');
        }
       
    @endphp

    <section class="tt-section pt-4">

        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header ">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Dashboard') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a></li>
                                    <li class="breadcrumb-item">{{ localize('Overview') }}</li>
                                </ol>
                            </div>
                            <!-- <div class="tt-action d-flex gap-2 flex-wrap">
                                <a href="{{ route('templates.index') }}" class="btn btn-secondary"><i
                                        data-feather="file-plus" class="me-2"></i> {{ localize('Create Content') }}</a>
                                @if (auth()->user()->user_type != 'customer')
                                    <a href="{{ route('subscriptions.index') }}" class="btn btn-primary"><i
                                            data-feather="zap"
                                            class="me-2"></i>{{ localize('Subscription Packages') }}</a>
                                @endif

                            </div> -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3 g-3">

                @if (isCustomer())
                    <div class="col-xl-7">
                        <div class="card">
                            <div class="card-body pb-0">
                                <div class="d-flex flex-wrap">
                                    <div class="me-3">
                                        <div class="avatar avatar-2xl">
                                            <img class="rounded" src="{{ uploadedAsset($user->avatar) }}" alt="avatar"
                                                onerror="this.onerror=null;this.src='{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}';" />
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h2 class="h5 mb-2">{{ $user->name }}</h2>
                                        <ul class="d-flex flex-wrap list-unstyled">
                                            <li class="me-3"><i data-feather="mail"
                                                    class="me-1 text-muted icon-14"></i>{{ $user->email }}</li>
                                            @if ($user->phone)
                                                <li class="me-3"><i data-feather="phone"
                                                        class="me-1 text-muted icon-14"></i>{{ $user->phone }}</li>
                                            @endif

                                        </ul>
                                        <div class="d-flex align-items-center flex-column mt-4">

                                            <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                                                @php
                                                    
                                                    $latestPackage = activePackageHistory();
                                                    $totalWords = $latestPackage != null ? $latestPackage->this_month_available_words + $latestPackage->this_month_used_words : 0;
                                                @endphp
                                                 @if($latestPackage)
                                                    @if($latestPackage->new_word_balance != -1)
                                                    <span class="fs-base"><strong>
                                                            @if ($latestPackage != null)
                                                                {{ $latestPackage->this_month_used_words > $totalWords ? $totalWords : $latestPackage->this_month_used_words }}
                                                            @else
                                                                0
                                                            @endif
                                                        </strong>
                                                        {{ localize('Used out of') }}
                                                        <strong>{{ $totalWords }}</strong>
                                                        {{ localize('words.') }}</span>
                                                    <span class="fs-base fw-bold">{{ getUsedWordsPercentage() }}%</span>
                                                    @endif
                                                 @endif
                                            </div>
                                            @if($latestPackage)
                                                @if($latestPackage->new_word_balance != -1)
                                                <div class="progress mb-2 w-100" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" role="progressbar"
                                                        style="width:  {{ getUsedWordsPercentage() }}%"
                                                        aria-valuenow="{{ getUsedWordsPercentage() }}" aria-valuemin="0"
                                                        aria-valuemax="100"></div>
                                                </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <ul class="nav nav-line-tab fw-semibold">
                                    <li class="nav-item">
                                        <a href="{{ route('writebot.dashboard') }}" class="nav-link active">
                                            {{ localize('Overview') }}
                                        </a>
                                    </li>
                                    <!-- <li class="nav-item">
                                        <a href="{{ route('subscriptions.index') }}" class="nav-link">
                                            {{ localize('Subscriptions') }}
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('subscriptions.histories.index') }}" class="nav-link">
                                            {{ localize('Histories') }}
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('dashboard.profile') }}" class="nav-link">
                                            {{ localize('Profile') }}
                                        </a>
                                    </li> -->
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif              

                <div class="{{ isCustomer() ? 'col-xl-5' : 'col-xl-12' }}">
                    <div class="row g-3">
                        {{-- Total words generated --}}
                        <div class="{{ isCustomer() ? 'col-6' : 'col-md-3' }}">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-2 flex-shrink-0">
                                            <div class="text-center rounded bg-soft-primary">
                                                <span><i data-feather="file-text"></i></span>
                                            </div>
                                        </div>

                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ $totalText }}</h6>
                                            <!-- <span class="fs-sm">{{ localize('Total words generated') }}</span> -->
                                            <span class="fs-sm">{{ localize('Total Users') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Total Image generated --}}
                        @if (getSetting('enable_ai_images') != '0')
                            <div class="{{ isCustomer() ? 'col-6' : 'col-md-3' }}">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-2 flex-shrink-0">
                                                <div class="text-center rounded bg-soft-accent">
                                                    <span><i data-feather="image"></i></span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0">{{ $totalImages }}</h6>
                                                <!-- <span class="fs-sm">{{ localize('Total Image generated') }}</span> -->
                                                <span class="fs-sm">{{ localize('Pageviews') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        {{-- Total code generated --}}
                        @if (getSetting('enable_ai_code') != '0')
                            <div class="{{ isCustomer() ? 'col-6' : 'col-md-3' }}">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-2 flex-shrink-0">
                                                <div class="text-center rounded bg-soft-info">
                                                    <span><i data-feather="code"></i></span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0">{{ $totalCode }}</h6>
                                                <span class="fs-sm">{{ localize('Total code generated') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        {{-- Total speech to text --}}
                        @if (getSetting('enable_speech_to_text') != '0')
                        <div class="{{ isCustomer() ? 'col-6' : 'col-md-3' }}">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-2 flex-shrink-0">
                                            <div class="text-center rounded bg-soft-danger">
                                                <span><i data-feather="mic"></i></span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ $totalS2T }}</h6>
                                            <span class="fs-sm">{{ localize('Total speech to text') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                {!! adSense_dashboard_profile_bottom_section() !!}
                
            </div>
            @if (getSetting('enable_built_in_templates') != '0')
            <div class="row g-3 mb-3">
                <div class="{{ isCustomer() ? 'col-12' : 'col-md-8' }}">
                    <div class="card h-100 flex-column">
                        <div class="card-body d-flex flex-column h-100">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted">{{ localize('Total Words Generated with templates') }}</span>
                                <div class="dropdown tt-tb-dropdown fs-sm">
                                    <a class="dropdown-toggle text-muted" href="#" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        @if (isset($timelineText))
                                            {{ $timelineText }}
                                        @else
                                            {{ localize('Last 7 days') }}
                                        @endif
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end shadow">
                                        <a class="dropdown-item"
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Last 7 days') }}</a>
                                        <a class="dropdown-item"
                                            href="{{ route('writebot.dashboard') }}?&timeline=30">{{ localize('Last 30 days') }}</a>
                                        <a class="dropdown-item"
                                            href="{{ route('writebot.dashboard') }}?&timeline=90">{{ localize('Last 3 months') }}</a>
                                    </div>
                                </div>
                            </div>
                            <h4 class="fw-bold">{{ formatWords($totalWordsData->totalWords) }}
                                {{ localize('Words') }}</h4>
                        </div>

                        <div id="chart"></div>
                    </div>
                </div>
                @if (auth()->user()->user_type != 'customer')
                    <div class="col-xl-4">
                        <div class="card h-100 flex-column">
                            <div class="card-body d-flex flex-column h-100">
                                <span class="text-muted">{{ localize('Top 5 Templates') }}</span>
                                <h4 class="fw-bold">
                                    {{ getSetting('truncate_price') == 1 ? formatWords($totalTemplateWordsData->totalTemplateWordsCount) : $totalTemplateWordsData->totalTemplateWordsCount }}
                                    {{ localize('Words') }}</h4>
                                <div id="donut"></div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            @endif
            {!! adSense_recent_project_top_section() !!}
            {{--<!-- <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-bottom-0">
                            <div class="row justify-content-between align-items-center g-3">
                                <div class="col-auto flex-grow-1">
                                    <h5 class="mb-lg-0">{{ localize('Recent Projects') }}</h5>
                                </div>
                            </div>
                        </div>
                        @include('backend.pages.projects.inc.projectTable', [
                            'projects' => $recentProjects,
                            'newTab' => false,
                        ])

                        <div class="d-flex align-items-center justify-content-between px-4 pb-4">
                            <span>{{ localize('Showing') }}
                                {{ $recentProjects->firstItem() ?? 0 }}-{{ $recentProjects->lastItem() ?? 0 }}
                                {{ localize('of') }}
                                {{ $recentProjects->total() }} {{ localize('results') }}</span>
                            <nav>
                                {{ $recentProjects->appends(request()->input())->links() }}
                            </nav>
                        </div>
                    </div>
                </div>
            </div> -->--}}

        </div>
    </section>
@endsection


@section('scripts')
    <script>
        "use strict";
        
        // total earning chart
        var totalSales = {
            chart: {
                type: "area",
                height: 80,
                sparkline: {
                    enabled: true,
                },
            },
            stroke: {
                curve: "smooth",
                width: 2,
            },
            fill: {
                opacity: 1,
            },
            series: [{
                name: '{{ localize('Words') }}',
                data: [{!! $totalWordsData->words !!}],
            }, ],
            labels: [{!! $totalWordsData->labels !!}],
            xaxis: {
                type: "datetime",
            },
            yaxis: {
                min: 0,
            },
            colors: ["#9333ea"],
        };
        new ApexCharts(document.querySelector("#chart"), totalSales).render();

        if ('{{ auth()->user()->user_type }}' != 'customer') {
            //pie chart top five
            var optionsTopFive = {
                chart: {
                    type: "donut",
                    height: 100,
                    offsetY: 15,
                    offsetX: -20,
                },
                series: {!! $totalTemplateWordsData->series !!},
                labels: [{!! $totalTemplateWordsData->labels !!}],
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200,
                        },
                        legend: {
                            position: "bottom",
                            show: false,
                        },
                        dataLabels: {
                            enabled: false,
                        },
                    },
                }, ],
            };

            var chartTopFive = new ApexCharts(
                document.querySelector("#donut"),
                optionsTopFive
            );
            chartTopFive?.render();
        }
    </script>
@endsection
