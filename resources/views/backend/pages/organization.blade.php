@extends('backend.layouts.master')

@section('title')
    {{ localize('Organization') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="mb-1">{{ $org?->name ?: localize('Your organization') }}</h4>
                    @if($org)
                        <p class="text-muted small mb-0">
                            {{ localize('Members are grouped by verified email domain') }}: <code>{{ $org->domain }}</code>
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

                {{-- Who currently governs the strategic context --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-2">{{ localize('Active strategic context') }}</h5>
                        @if($activeContext)
                            <p class="text-muted small">
                                {{ localize('Set by the highest-ranking member who has completed calibration. This is what the platform tailors its intelligence to.') }}
                            </p>
                            <dl class="row mb-0">
                                <dt class="col-sm-3">{{ localize('Declared by') }}</dt>
                                <dd class="col-sm-9">{{ $activeContext->profile['role'] ?? '—' }}</dd>

                                @if(! empty($activeContext->profile['scale']))
                                    <dt class="col-sm-3">{{ localize('Scale') }}</dt>
                                    <dd class="col-sm-9">{{ $activeContext->profile['scale'] }}</dd>
                                @endif

                                @if(! empty($activeContext->profile['governance']))
                                    <dt class="col-sm-3">{{ localize('Governance') }}</dt>
                                    <dd class="col-sm-9">{{ $activeContext->profile['governance'] }}</dd>
                                @endif

                                <dt class="col-sm-3">{{ localize('Recorded') }}</dt>
                                <dd class="col-sm-9">{{ optional($activeContext->created_at)->diffForHumans() ?: '—' }}</dd>
                            </dl>
                        @else
                            <p class="mb-0 text-muted">
                                {{ localize('No active context yet. It is set when a member completes calibration.') }}
                            </p>
                        @endif
                    </div>
                </div>

                @include('backend.pages.partials.org-members')

            @endif

        </div>
    </section>
@endsection
