{{-- "My goal" card (Features spec, phase 3): this user's goals from the
     organization's published strategies. --}}
@php
    $decisionLabels = [
        'act_on_it' => localize('Act on it'),
        'review_in_detail' => localize('Review in detail'),
        'not_viable' => localize('Not viable'),
    ];
    $currency = config('custom.default_currency_symbol') ?: '$';
@endphp
<style>
    #my-goals .mg-goal { border: 1px solid #36839b; border-radius: 10px; padding: 14px; margin-top: 12px; background: #e7f3f7; }
    #my-goals .mg-label { font-size: 12px; color: #2c6d82; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 2px; }
    #my-goals .mg-action { font-weight: 600; }
    #my-goals .mg-btn { border: 1px solid #36839b; color: #2c6d82; background: #fff; }
    #my-goals .mg-btn.active { background: #36839b; color: #fff; }
    /* The theme leaves these inputs content-box, so width:100% plus padding overflowed the card. */
    #my-goals .form-control { box-sizing: border-box; }
    #my-goals .mg-obstacle { font-size: 12px; background: #fbf2ea; border-left: 3px solid #ec883f; padding: 4px 8px; margin-top: 4px; border-radius: 4px; }
</style>
<div class="card mb-4" id="my-goals">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline">
            <h5 class="mb-1">{{ localize('Your goals') }}</h5>
            @if ($isLeader ?? false)
                <a href="{{ route('strategies.index') }}" class="small" style="color:#2c6d82">{{ localize('Executive view') }} &rarr;</a>
            @endif
        </div>
        @if (! $user->org_role_id)
            <p class="text-muted small mb-0">{{ localize('You will see goals here once your organization gives you a role.') }}</p>
        @elseif ($myGoals->isEmpty())
            <p class="text-muted small mb-0">{{ localize('No published strategy has a goal for your role yet.') }}</p>
        @else
            @foreach ($myGoals as $card)
                <div class="mg-goal">
                    <div class="mg-label">{{ localize('Company goal') }}</div>
                    <div class="mb-2">{{ $card['company_goal'] }}</div>
                    <div class="small text-muted mb-2">
                        {{ $card['strategy']->selected_strategy }}@if ($card['strategy']->selected_scenario) · {{ $card['strategy']->selected_scenario }}@endif
                    </div>

                    <div class="mg-label">{{ localize('Your goal') }} ({{ $card['role_name'] }})</div>
                    <div class="mg-action mb-2">{{ $card['goal']->recommended_action }}</div>
                    @if ($card['goal']->success_metric || $card['goal']->target_date)
                        <div class="small mb-2">
                            @if ($card['goal']->success_metric){{ localize('Measure') }}: {{ $card['goal']->success_metric }}@endif
                            @if ($card['goal']->target_value) ({{ $card['goal']->target_value }})@endif
                            @if ($card['goal']->target_date) · {{ localize('By') }} {{ \Illuminate\Support\Carbon::parse($card['goal']->target_date)->toFormattedDateString() }}@endif
                        </div>
                    @endif

                    @if ($card['resources'])
                        <div class="mg-label">{{ localize('Your team\'s resources') }} ({{ $card['resources']->department_name }})</div>
                        <div class="small mb-2">
                            {{ $card['resources']->budget !== null ? $currency.number_format((float) $card['resources']->budget) : '—' }}
                            · {{ $card['resources']->fte !== null ? (float) $card['resources']->fte.' FTE' : '—' }}
                            @if ($card['resources']->tools) · {{ $card['resources']->tools }}@endif
                        </div>
                    @endif

                    @if ($card['waiting_on'])
                        <div class="small mb-1"><strong>{{ localize('Waiting on') }}:</strong> {{ $card['waiting_on']->orgRole?->name ?? $card['waiting_on']->role }} — {{ $card['waiting_on']->recommended_action }}</div>
                    @endif
                    @foreach ($card['waiting_on_you'] as $dependent)
                        <div class="small mb-1"><strong>{{ localize('Waiting on you') }}:</strong> {{ $dependent->orgRole?->name ?? $dependent->role }} — {{ $dependent->recommended_action }}</div>
                    @endforeach

                    <form method="POST" action="{{ route('my-goals.decide') }}" class="d-flex flex-wrap gap-2 mt-2">
                        @csrf
                        <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                        @foreach ($decisionLabels as $value => $label)
                            <button type="submit" name="decision" value="{{ $value }}"
                                class="btn btn-sm mg-btn {{ $card['response']?->decision === $value ? 'active' : '' }}">{{ $label }}</button>
                        @endforeach
                    </form>

                    @if ($card['response']?->decision === 'act_on_it')
                        <div class="mg-label mt-3">{{ localize('Where to begin') }}</div>
                        @if (! empty($card['goal']->starting_options))
                            <form method="POST" action="{{ route('my-goals.commit') }}">
                                @csrf
                                <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                                @foreach ($card['goal']->starting_options as $i => $option)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="option" value="{{ $i }}"
                                            id="start-{{ $card['goal']->id }}-{{ $i }}" required
                                            @checked($card['response']->starting_point === $option)>
                                        <label class="form-check-label small" for="start-{{ $card['goal']->id }}-{{ $i }}">{{ $option }}</label>
                                    </div>
                                @endforeach
                                <button type="submit" class="btn btn-sm mg-btn mt-1">
                                    {{ $card['response']->starting_point ? localize('Change my starting point') : localize('Commit') }}
                                </button>
                            </form>
                            @error('option')
                                <div class="small text-danger mt-1">{{ $message }}</div>
                            @enderror
                        @else
                            <form method="POST" action="{{ route('my-goals.suggest') }}">
                                @csrf
                                <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                                <button type="submit" class="btn btn-sm mg-btn">{{ localize('Suggest starting points') }}</button>
                            </form>
                        @endif
                        @if ($card['response']->starting_point)
                            <div class="small mt-1">{{ localize('Committed') }}: <strong>{{ $card['response']->starting_point }}</strong>
                                @if ($card['last_revised_at'] && $card['response']->committed_at && $card['response']->committed_at->lt($card['last_revised_at']))
                                    <span class="text-muted">({{ localize('made before this goal changed') }})</span>
                                @endif
                            </div>
                        @endif
                    @endif

                    @if ($isLeader ?? false)
                        <details class="mt-2">
                            <summary class="small" style="color:#2c6d82;cursor:pointer">{{ localize('Refine this goal') }}</summary>
                            <form method="POST" action="{{ route('my-goals.revise') }}" class="mt-2">
                                @csrf
                                <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                                <textarea name="text" rows="2" maxlength="500" required class="form-control form-control-sm mb-1">{{ $card['goal']->recommended_action }}</textarea>
                                <input type="text" name="reason" maxlength="500" class="form-control form-control-sm mb-1" placeholder="{{ localize('Why? (optional)') }}">
                                <button type="submit" class="btn btn-sm mg-btn">{{ localize('Save and notify') }}</button>
                            </form>
                        </details>
                    @endif
                    <form method="POST" action="{{ route('my-goals.obstacle') }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                        <div class="d-flex gap-2">
                            <input type="text" name="body" maxlength="2000" required class="form-control form-control-sm"
                                placeholder="{{ localize('Anything in the way? e.g. the tool keeps timing out') }}">
                            <button type="submit" class="btn btn-sm mg-btn">{{ localize('Report') }}</button>
                        </div>
                    </form>
                    @foreach ($card['obstacles'] as $obstacle)
                        <div class="mg-obstacle">{{ $obstacle->body }} <span class="text-muted">· {{ optional($obstacle->created_at)->diffForHumans() }}</span></div>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>
</div>
