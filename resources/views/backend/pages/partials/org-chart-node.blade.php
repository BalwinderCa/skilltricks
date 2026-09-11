@php
    // Recursive: a node draws its person, then includes this same file for each
    // of their reports. Depth comes from the data, not from a fixed nesting.
    $person = $node['user'];
    $isHead = isset($head) && $head && (int) $head->id === (int) $person->id;
@endphp

<li>
    <div class="tt-chart-card {{ $isHead ? 'tt-chart-head' : '' }} {{ $isHead && $chosen ? 'is-chosen' : '' }}"
         data-member-id="{{ $person->id }}"
         data-drop-manager="{{ $person->id }}"
         @if($isOwner)
             data-member-edit
             data-id="{{ $person->id }}"
             data-name="{{ $person->name }}"
             data-email="{{ $person->email }}"
             data-rank="{{ $person->hierarchy_rank }}"
             data-department="{{ $person->department_id }}"
         @endif>
        <span class="tt-chart-avatar" style="background: {{ $color }}">{{ $initials($person->name) }}</span>
        <span class="tt-chart-who">
            <strong>{{ $person->name }}</strong>
            <small class="text-muted">
                {{ $person->orgRole?->name ?? localize('Role not set') }}
                @if($isHead && ! $chosen)
                    <span class="tt-chart-auto">{{ localize('auto') }}</span>
                @endif
            </small>
        </span>
    </div>

    @if(count($node['children']))
        <ul class="tt-chart-reports">
            @foreach($node['children'] as $child)
                {{-- The gap above each card is where you drop to place someone
                     before it; the last card also carries one below. --}}
                <li class="tt-chart-gap" data-drop-before="{{ $child['user']->id }}" aria-hidden="true"></li>
                @include('backend.pages.partials.org-chart-node', ['node' => $child, 'head' => null])
                @if($loop->last)
                    <li class="tt-chart-gap" data-drop-after="{{ $child['user']->id }}" aria-hidden="true"></li>
                @endif
            @endforeach
        </ul>
    @endif
</li>
