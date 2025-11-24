<!DOCTYPE html>
<html >
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
@if($type == 'pdf')
    <script>
        var is_chrome = function () { return Boolean(window.chrome); }
        if(is_chrome){
            window.print(); 
        }else{
            window.print();
        }
    </script>
@endif
<body  @if($type == 'pdf') onLoad="loadHandler();" @endif id="downloadChat">

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
</body>
@if($type == 'pdf')
<script>
    function chatPrint() {
        window.print();
    }
</script>
@endif
</html>
