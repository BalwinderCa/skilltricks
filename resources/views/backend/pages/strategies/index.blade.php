@extends('backend.layouts.master')

@section('title')
    {{ localize('Executive view') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <h4 class="mb-1">{{ localize('Executive view') }}</h4>
            <p class="text-muted small">{{ localize('Every published strategy in your organization.') }}</p>
            <div class="card">
                <div class="card-body">
                    @if ($strategies->isEmpty())
                        <p class="text-muted mb-0">{{ localize('No strategy has been published yet.') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ localize('Company goal') }}</th>
                                        <th>{{ localize('Published') }}</th>
                                        <th>{{ localize('Status') }}</th>
                                        <th>{{ localize('Drift index') }}</th>
                                        <th>{{ localize('Committed') }}</th>
                                        <th>{{ localize('OI drift') }}</th>
                                        <th>{{ localize('Obstacles') }}</th>
                                        <th>{{ localize('Not viable') }}</th>
                                        <th>{{ localize('Saved (est.)') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($strategies as $row)
                                        <tr>
                                            <td>
                                                <a href="{{ route('strategies.show', $row['chat']->id) }}" style="color:#2c6d82">{{ $row['company_goal'] }}</a>
                                                <div class="small text-muted">{{ $row['chat']->selected_strategy }}</div>
                                            </td>
                                            <td class="small">{{ $row['chat']->publisher?->name }}<br>{{ optional($row['chat']->published_at)->toFormattedDateString() }}</td>
                                            <td>@include('backend.pages.strategies.badge', ['badge' => $row['badge']])</td>
                                            <td>@include('backend.pages.strategies.drift-pill', ['index' => $row['drift_index'], 'level' => $row['drift_level']])</td>
                                            <td>{{ $row['alignment']['committed'] }} of {{ $row['alignment']['people'] }}
                                                @if ($row['alignment']['rate'] !== null)<span class="text-muted small">({{ $row['alignment']['rate'] }}%)</span>@endif
                                            </td>
                                            <td>{{ $row['drift'] }}</td>
                                            <td>{{ $row['obstacles'] }}</td>
                                            <td>{{ $row['not_viable'] }}</td>
                                            <td>{{ config('custom.default_currency_symbol') ?: '$' }}{{ number_format($row['savings']['saved']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @if ($isOwner)
                <details class="mt-3">
                    <summary class="small" style="color:#2c6d82;cursor:pointer">{{ localize('Savings assumptions (estimate)') }}</summary>
                    <form method="POST" action="{{ route('strategies.settings') }}" class="row g-2 mt-2" style="max-width:720px">
                        @csrf
                        @foreach (['hourly_rate' => 'Blended hourly rate', 'manual_hours' => 'Manual alignment hours per participant', 'oi_minutes' => 'OI minutes per participant', 'token_cost' => 'Cost per 1,000 AI tokens'] as $key => $label)
                            <div class="col-md-6">
                                <label class="small">{{ localize($label) }}</label>
                                <input type="number" step="any" min="0" name="{{ $key }}" value="{{ $settings[$key] }}" required class="form-control form-control-sm" style="box-sizing:border-box">
                            </div>
                        @endforeach
                        <div class="col-12"><button type="submit" class="btn btn-sm" style="background:#36839b;color:#fff">{{ localize('Save assumptions') }}</button></div>
                    </form>
                </details>
            @endif
        </div>
    </section>
@endsection
