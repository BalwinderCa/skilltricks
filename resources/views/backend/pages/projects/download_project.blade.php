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

    {!! $content !!}
    <br>
    <br>

</body>
@if($type == 'pdf')
<script>
    function contentPrint() {
        window.print();
    }
</script>
@endif
</html>
