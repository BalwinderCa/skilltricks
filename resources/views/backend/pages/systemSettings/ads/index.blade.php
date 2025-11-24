@extends('backend.layouts.master')

@section('title')
    {{ localize('Google Ads') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ getSetting('dashboard_adSense') ? localize('Google Ads for Free User') : localize('Google Ads') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{  localize('Google Ads') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                               
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
                                    <th>{{ localize('Name') }}</th>
                                    <th>{{ localize('Size') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Is Active ?') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Action') }}</th>
                                   
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ads as $key => $ad)
                                    <tr>
                                        <td class="text-center">
                                            {{ $key + 1 + ($ads->currentPage() - 1) * $ads->perPage() }}
                                        </td>
                                       
                                        <td>
                                            {{ $ad->name }}
                                        </td>
                                        <td>
                                            {{ $ad->size }}
                                        </td>
                                        
                                        <td>
                                            <x-status-change :modelid="$ad->id" :table="$ad->getTable()"
                                                :status="$ad->is_active" />
                                        </td>
                                      
                                        <td class="text-end">
                                            <div class="dropdown tt-tb-dropdown">
                                                <button type="button" class="btn p-0" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i data-feather="more-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end shadow">


                                                    <a class="dropdown-item"
                                                        href="{{ route('admin.settings.adsense.edit', $ad->id) }}">
                                                        <i data-feather="edit-3" class="me-2"></i>{{ localize('Edit') }}
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
                                {{ $ads->firstItem() }}-{{ $ads->lastItem() }} {{ localize('of') }}
                                {{ $ads->total() }} {{ localize('results') }}</span>
                            <nav>
                                {{ $ads->appends(request()->input())->links() }}
                            </nav>
                        </div>
                        <!--pagination end-->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


