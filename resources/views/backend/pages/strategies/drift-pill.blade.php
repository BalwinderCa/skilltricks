{{-- Notion drift index: green < 5%, yellow 5–15%, red > 15%. --}}
@php
    [$fg, $bg] = ['green' => ['#1e7e34', '#e6f4ea'], 'yellow' => ['#9a5a12', '#fbf2ea'], 'red' => ['#b42318', '#fdecea']][$level] ?? ['#5f6b7a', '#eef1f4'];
@endphp
<span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;color:{{ $fg }};background:{{ $bg }}">
    {{ $index === null ? localize('Not measured') : rtrim(rtrim(number_format((float) $index, 1), '0'), '.').'%' }}
</span>
