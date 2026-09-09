@extends('backend.layouts.master')

@section('title')
    {{ localize('Teams') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="mb-1">{{ localize('Teams') }}</h4>
                    @if($org)
                        <p class="text-muted small mb-0">
                            {{ localize('Everyone in your organization, grouped by verified email domain') }}:
                            <code>{{ $org->domain }}</code>
                        </p>
                    @endif
                </div>
            </div>

            @if(! $org)
                <div class="alert alert-info">
                    {{ localize('You are not part of an organization yet.') }}
                    <a href="{{ route('dashboard.profile') }}">{{ localize('Add your company information') }}</a>
                </div>
            @else
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link {{ $view === 'members' ? 'active' : '' }}"
                           href="{{ route('organization.index') }}">{{ localize('Members') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $view === 'chart' ? 'active' : '' }}"
                           href="{{ route('organization.index', ['view' => 'chart']) }}">{{ localize('Organization chart') }}</a>
                    </li>
                </ul>

                @if($view === 'chart')
                    @include('backend.pages.partials.org-chart')
                @else
                    @include('backend.pages.partials.org-members')
                @endif
            @endif

        </div>
    </section>
@endsection
