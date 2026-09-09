@php
    $rankLabels = \App\Services\OrganizationService::RANK_LABELS;

    // Initials rather than avatars: the avatar column holds a media id, and a
    // roster built by CSV import has none of them.
    $initials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $letters = array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

        return implode('', $letters) ?: '?';
    };
@endphp

    <div class="tt-chart-root">
        <div class="tt-chart-card">
            <span class="tt-chart-avatar">{{ $initials($chart['root']->name) }}</span>
            <span class="tt-chart-who">
                <strong>{{ $chart['root']->name }}</strong>
                <small class="text-muted">
                    {{ $chart['root']->hierarchy_rank ? localize($rankLabels[(int) $chart['root']->hierarchy_rank] ?? '') : localize('Owner') }}
                </small>
            </span>
        </div>
    </div>

    @if(count($chart['branches']))
        <div class="tt-chart-branches">
            @foreach($chart['branches'] as $branch)
                @php $color = $branch['department']->color ?? '#94A3B8'; @endphp
                <div class="tt-chart-branch" data-drop-department="{{ $branch['department']->id ?? '' }}">
                    <button type="button" class="tt-chart-band" style="background: {{ $color }}"
                            data-branch-toggle
                            aria-expanded="true"
                            title="{{ localize('Collapse or expand') }}">
                        <span class="tt-chart-caret" aria-hidden="true">&#9662;</span>
                        <span>{{ $branch['department']->name ?? localize('No department') }}</span>
                        <span class="tt-chart-band-count">{{ $branch['count'] }}</span>
                    </button>

                    @if(! $branch['head'])
                        {{-- An empty department still needs a body, so there is
                             something to drag its first person onto. --}}
                        <div class="tt-chart-empty">{{ localize('Nobody yet') }}</div>
                    @else
                        <ul class="tt-chart-reports is-roots">
                            @foreach($branch['nodes'] as $node)
                                <li class="tt-chart-gap" data-drop-before="{{ $node['user']->id }}" aria-hidden="true"></li>
                                @include('backend.pages.partials.org-chart-node', [
                                    'node' => $node,
                                    'head' => $branch['head'],
                                    'chosen' => $branch['chosen'],
                                    'color' => $color,
                                ])
                                @if($loop->last)
                                    <li class="tt-chart-gap" data-drop-after="{{ $node['user']->id }}" aria-hidden="true"></li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
