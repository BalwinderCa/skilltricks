@extends('backend.layouts.master')

@section('title')
    {{ localize('Executive view') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    @php
        $decisionLabels = ['act_on_it' => localize('Act on it'), 'review_in_detail' => localize('Review'), 'not_viable' => localize('Not viable')];
    @endphp
    <section class="tt-section pt-4">
        <div class="container">
            <a href="{{ route('strategies.index') }}" class="small" style="color:#2c6d82">&larr; {{ localize('Executive view') }}</a>
            <h4 class="mt-2 mb-1">{{ $company_goal }}</h4>
            <p class="text-muted small">
                {{ $chat->selected_strategy }} · {{ localize('Published by') }} {{ $chat->publisher?->name }}
                {{ optional($chat->published_at)->toFormattedDateString() }} · {{ localize('Drift') }}: {{ $drift }}
            </p>

            <div class="card mb-3"><div class="card-body">
                <h6>{{ localize('Alignment') }}: {{ $alignment['overall']['committed'] }} of {{ $alignment['overall']['people'] }}
                    @if ($alignment['overall']['rate'] !== null)({{ $alignment['overall']['rate'] }}%)@endif</h6>
                @if (! empty($alignment['departments']))
                    <table class="table table-sm mb-0">
                        <thead><tr><th>{{ localize('Department') }}</th><th>{{ localize('Committed') }}</th><th>{{ localize('Rate') }}</th></tr></thead>
                        <tbody>
                            @foreach ($alignment['departments'] as $dept)
                                <tr><td>{{ $dept['name'] }}</td><td>{{ $dept['committed'] }} / {{ $dept['people'] }}</td><td>{{ $dept['rate'] === null ? '—' : $dept['rate'].'%' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h6>{{ localize('Goals') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>{{ localize('Role') }}</th><th>{{ localize('Goal') }}</th><th>{{ localize('People') }}</th><th>{{ localize('Committed') }}</th><th>{{ localize('Responses') }}</th><th>{{ localize('Drift') }}</th></tr></thead>
                        <tbody>
                            @foreach ($goals as $row)
                                <tr>
                                    <td>{{ $row['role'] }}</td>
                                    <td>{{ $row['goal']->recommended_action }}</td>
                                    <td>{{ $row['holders'] }}</td>
                                    <td>{{ $row['committed'] }}</td>
                                    <td class="small">
                                        @foreach ($row['decisions'] as $decision => $count)
                                            {{ $decisionLabels[$decision] }}: {{ $count }}@if (! $loop->last), @endif
                                        @endforeach
                                    </td>
                                    <td>{{ $row['drift'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h6>{{ localize('Reported obstacles') }}</h6>
                @forelse ($obstacles as $obstacle)
                    <div class="small mb-1" style="border-left:3px solid #ec883f;padding-left:8px">
                        {{ $obstacle->body }}
                        <span class="text-muted">— {{ $obstacle->user?->name }}, {{ $obstacle->goal->orgRole->name ?? $obstacle->goal->role }}, {{ optional($obstacle->created_at)->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">{{ localize('Nothing reported.') }}</p>
                @endforelse
            </div></div>

            <div class="card mb-4"><div class="card-body">
                <h6>{{ localize('Goal changes') }}</h6>
                @forelse ($revisions as $revision)
                    <div class="small mb-2">
                        <strong>{{ $revision->goal->orgRole->name ?? $revision->goal->role }}</strong>:
                        "{{ $revision->old_text }}" &rarr; "{{ $revision->new_text }}"
                        <span class="text-muted">— {{ $revision->user?->name }}, {{ optional($revision->created_at)->diffForHumans() }}</span>
                        @if ($revision->reason)<div class="text-muted">{{ $revision->reason }}</div>@endif
                    </div>
                @empty
                    <p class="text-muted small mb-0">{{ localize('No changes yet.') }}</p>
                @endforelse
            </div></div>
        </div>
    </section>
@endsection
