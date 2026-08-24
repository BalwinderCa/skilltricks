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
                    {{ localize('You are not part of an organization yet. Finish your calibration to set one up.') }}
                    <a href="{{ route('onboarding.index') }}">{{ localize('Start calibration') }}</a>
                </div>
            @else
                @include('backend.pages.partials.org-members')
            @endif

        </div>
    </section>
@endsection
