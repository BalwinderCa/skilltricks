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
                                        <th>{{ localize('Committed') }}</th>
                                        <th>{{ localize('Drift') }}</th>
                                        <th>{{ localize('Obstacles') }}</th>
                                        <th>{{ localize('Not viable') }}</th>
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
                                            <td>{{ $row['alignment']['committed'] }} of {{ $row['alignment']['people'] }}
                                                @if ($row['alignment']['rate'] !== null)<span class="text-muted small">({{ $row['alignment']['rate'] }}%)</span>@endif
                                            </td>
                                            <td>{{ $row['drift'] }}</td>
                                            <td>{{ $row['obstacles'] }}</td>
                                            <td>{{ $row['not_viable'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
