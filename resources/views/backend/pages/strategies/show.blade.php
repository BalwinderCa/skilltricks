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
            <div class="mb-2">
                @include('backend.pages.strategies.badge', ['badge' => $badge])
                <span class="small text-muted ms-1">{{ implode(' · ', $badge['reasons']) }}</span>
            </div>
            @php $money = config('custom.default_currency_symbol') ?: '$'; @endphp
            <div class="row g-3 mb-3">
                <div class="col-md-4"><div class="card h-100"><div class="card-body">
                    <div class="small text-muted">{{ localize('Teams involved') }}</div>
                    <div class="fs-5 fw-bold">{{ $command['teams_involved'] }} / {{ $command['teams_total'] }} {{ localize('departments') }}</div>
                    <div class="small text-muted">{{ $command['contributors'] }} {{ \Illuminate\Support\Str::plural('active contributor', $command['contributors']) }}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card h-100"><div class="card-body">
                    <div class="small text-muted">{{ localize('Alignment effort saved (estimate)') }}</div>
                    <div class="fs-5 fw-bold">{{ $money }}{{ number_format($command['savings']['saved']) }}</div>
                    <div class="small text-muted">{{ rtrim(rtrim(number_format($command['savings']['hours_saved'], 1), '0'), '.') }} {{ localize('meeting hours saved') }}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card h-100"><div class="card-body">
                    <div class="small text-muted">{{ localize('Drift index') }}</div>
                    <div class="fs-5 fw-bold">@include('backend.pages.strategies.drift-pill', ['index' => $command['drift_index'], 'level' => $command['drift_level']])</div>
                    <div class="small text-muted">
                        @if ($command['projected']){{ localize('Projected completion') }}: {{ $command['projected']->toFormattedDateString() }} ({{ localize('at current pace') }})@else{{ localize('Not enough progress reported to project yet') }}@endif
                    </div>
                </div></div></div>
            </div>

            @if ($command['drift_level'] === 'red')
                <div class="card mb-3" style="border:1px solid #b42318;background:#fdecea"><div class="card-body">
                    <h6 style="color:#b42318">⚠ {{ localize('Severe drift') }}: {{ $command['drift_index'] }}% {{ localize('behind baseline') }}</h6>
                    @if (! empty($recourse['options']))
                        <ul class="mb-2">
                            @foreach ($recourse['options'] as $option)
                                <li><strong>{{ $option['action'] }}</strong> <span class="text-muted">— {{ $option['why'] }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                    <form method="POST" action="{{ route('strategies.recourse', $chat->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background:#b42318;color:#fff">{{ empty($recourse['options']) ? localize('Suggest recourse options') : localize('Refresh recourse options') }}</button>
                    </form>
                </div></div>
            @endif

            @if ($supporting->isNotEmpty())
                <div class="card mb-3"><div class="card-body">
                    <h6>{{ localize('Supporting initiatives') }}</h6>
                    @foreach ($supporting as $child)
                        <div class="small mb-2">
                            <a href="{{ route('strategies.show', $child['chat']->id) }}" style="color:#2c6d82">{{ $child['company_goal'] }}</a>
                            <span class="text-muted">— {{ $child['owner'] }}</span>
                            @include('backend.pages.strategies.badge', ['badge' => $child['badge']])
                            @include('backend.pages.strategies.drift-pill', ['index' => $child['drift_index'], 'level' => $child['drift_level']])
                            <span class="text-muted">{{ $child['alignment']['committed'] }} of {{ $child['alignment']['people'] }} {{ localize('committed') }}</span>
                        </div>
                    @endforeach
                </div></div>
            @endif

            <div class="card mb-3"><div class="card-body">
                <h6>{{ localize('Departmental progress & deliverables') }}</h6>
                @php $statusText = ['completed' => '✅ '.localize('Completed'), 'in_progress' => '⏳ '.localize('In progress'), 'blocked' => '⛔ '.localize('Blocked'), 'not_started' => localize('Not started')]; @endphp
                @foreach ($deliverables as $item)
                    <div class="small mb-1">
                        <strong>{{ $item['role'] }}</strong>: {{ $item['note'] ?? $item['goal']->recommended_action }}
                        <span class="ms-1">[{{ $statusText[$item['status']] }}]</span>
                        @if ($item['cascaded']) <span class="text-muted">· {{ $item['cascaded']['total'] }} {{ \Illuminate\Support\Str::plural('sub-goal', $item['cascaded']['total']) }} cascaded ({{ $item['cascaded']['completed'] }} completed)</span>@endif
                        @if ($item['days_behind']) <span style="color:#b42318">— {{ $item['days_behind'] }} {{ \Illuminate\Support\Str::plural('day', $item['days_behind']) }} behind baseline</span>@endif
                    </div>
                @endforeach
                <div class="small mt-2 p-2" style="background:{{ $blockers->isNotEmpty() ? '#fdecea' : ($obstacles->isNotEmpty() ? '#fbf2ea' : '#e6f4ea') }};border-radius:6px">
                    @if ($blockers->isEmpty() && $obstacles->isEmpty())
                        {{ localize('No goals are marked Blocked and no obstacles have been reported.') }}
                    @elseif ($blockers->isEmpty())
                        {{ localize('No goals are marked Blocked, but') }} {{ $obstacles->count() }} {{ \Illuminate\Support\Str::plural('obstacle', $obstacles->count()) }} {{ $obstacles->count() === 1 ? localize('has') : localize('have') }} {{ localize('been reported (see Reported obstacles below).') }}
                    @else
                        ⚠ {{ $blockers->count() }} {{ \Illuminate\Support\Str::plural('upstream blocker', $blockers->count()) }}: {{ $blockers->implode(', ') }}
                    @endif
                </div>
            </div></div>
            <p class="text-muted small">
                {{ $chat->selected_strategy }} · {{ localize('Published by') }} {{ $chat->publisher?->name }}
                {{ optional($chat->published_at)->toFormattedDateString() }} · {{ localize('OI drift') }}: {{ $drift }}
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
                        <thead><tr><th>{{ localize('Role') }}</th><th>{{ localize('Goal') }}</th><th>{{ localize('Impact') }}</th><th>{{ localize('People') }}</th><th>{{ localize('Committed') }}</th><th>{{ localize('Responses') }}</th><th>{{ localize('Target vs projected') }}</th><th>{{ localize('OI drift') }}</th></tr></thead>
                        <tbody>
                            @foreach ($goals as $row)
                                <tr>
                                    <td>{{ $row['role'] }}</td>
                                    <td>{{ $row['goal']->recommended_action }}</td>
                                    <td class="small">@if ($row['goal']->impact_score)<strong title="{{ $row['goal']->impact_reason }}">{{ $row['goal']->impact_score }}/10</strong><br>@endif weight {{ $row['goal']->weight ?? 1 }}</td>
                                    <td>{{ $row['holders'] }}</td>
                                    <td>{{ $row['committed'] }}</td>
                                    <td class="small">
                                        @foreach ($row['decisions'] as $decision => $count)
                                            {{ $decisionLabels[$decision] }}: {{ $count }}@if (! $loop->last), @endif
                                        @endforeach
                                    </td>
                                    <td class="small">
                                        @if (($row['metrics']['projected_value'] ?? null) !== null)
                                            {{ localize('Target') }} {{ $row['goal']->target_value }} vs {{ localize('projected') }} {{ rtrim(rtrim(number_format($row['metrics']['projected_value'], 1), '0'), '.') }} <span class="text-muted">({{ localize('at current pace') }})</span>
                                        @else — @endif
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
