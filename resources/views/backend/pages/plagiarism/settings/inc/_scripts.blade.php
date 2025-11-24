<script>
    var options = {
        chart: {
            width: 380,
            type: "donut"
        },
        dataLabels: {
            enabled: false
        },
        series: [90, 10],
        labels: ['Human Writing', 'AI Writing']

    };

    var plag = new ApexCharts(
        document.querySelector("#donutChat"),
        options
    );
    plag.render();

    let inputTextArea = document.getElementById("input-textarea");
    let characCount = document.getElementById("charac-count");

    inputTextArea.addEventListener("input", () => {
        let textLenght = inputTextArea.value.length;

        if (textLenght > 2500) {
            notifyMe('error', '{{ localize('Content exceeds limit') }}')
        }
        characCount.textContent = textLenght;


    });
</script>