{{-- Notion drift badge: $badge = StrategyOverview::badge(). --}}
@php
    $badgeColours = [
        'green' => ['#1e7e34', '#e6f4ea'],
        'yellow' => ['#9a5a12', '#fbf2ea'],
        'red' => ['#b42318', '#fdecea'],
        'none' => ['#5f6b7a', '#eef1f4'],
    ][$badge['level']];
@endphp
<span title="{{ implode(' · ', $badge['reasons']) }}"
    style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;color:{{ $badgeColours[0] }};background:{{ $badgeColours[1] }}">{{ $badge['label'] }}</span>
