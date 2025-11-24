<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streaming Example</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <button id="startBtn" class="btn btn-primary">Start Writing</button>
    <button id="stopBtn" class="btn btn-danger">Stop</button>
    <div id="outputContent" class="mt-3"></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    $(document).ready(function () {
        let source = null;
        let wordCount = 0;

        $('#startBtn').on('click', function () {
            source = new EventSource('/stream');

            source.onmessage = function (event) {
                let data = JSON.parse(event.data);
                $('#outputContent').append(data.content);
                wordCount += data.wordCount;
            };
        });

        $('#stopBtn').on('click', function () {
            if (source !== null) {
                source.close();
                alert('Total Words Streamed: ' + wordCount);
            }
        });
    });
</script>

</body>
</html>
