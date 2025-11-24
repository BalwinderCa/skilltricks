@php
    $messages = $array['messages'];
@endphp
@foreach ($messages as $message)
    <span>[</span>{{ $message->created_at }}<span>]</span>
    @if ($message->prompt == null)
        {{ $conversation->category->name }}:
    @else
        {{ $conversation->user->name }}:
    @endif
    {!! $message->result !!}
    <br>
    <br>
@endforeach
