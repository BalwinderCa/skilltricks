<script>
    
// stability
var stability_slider = document.getElementById("stability");
    var stability_output = document.getElementById("stability__value");

    stability_output.innerHTML = stability_slider.value;

    // This function input current value in span and add progress colour in range
    stability_slider.oninput = function() {

        stability_output.innerHTML = this.value;

        var value = (this.value - this.min) / (this.max - this.min) * 100;
        console.log(value);
        this.style.background = 'linear-gradient(to right, #9333ea  0%, #7a16d4  ' + value + '%, #d7dcdf ' + value +
            '%, #d7dcdf 100%)'
    }
    //  similarity boost
    var similarity_boost_slider = document.getElementById("similarity_boost");
    var similarity_boost_output = document.getElementById("similarity_boost__value");

    similarity_boost_output.innerHTML = similarity_boost_slider.value;

    // This function input current value in span and add progress colour in range
    similarity_boost_slider.oninput = function() {

        similarity_boost_output.innerHTML = this.value;

        var value = (this.value - this.min) / (this.max - this.min) * 100;

        this.style.background = 'linear-gradient(to right, #9333ea  0%, #7a16d4  ' + value + '%, #d7dcdf ' + value +
            '%, #d7dcdf 100%)'
    }

    // style
    var style_slider = document.getElementById("style");
    var style_output = document.getElementById("style__value");

    style_output.innerHTML = style_slider.value;

    // This function input current value in span and add progress colour in range
    style_slider.oninput = function() {

        style_output.innerHTML = this.value;

        var value = (this.value - this.min) / (this.max - this.min) * 100;

        this.style.background = 'linear-gradient(to right, #9333ea  0%, #7a16d4  ' + value + '%, #d7dcdf ' + value +
            '%, #d7dcdf 100%)'
    }

    // sts

    let inputTextArea = document.getElementById("input-textarea");
    let characCount = document.getElementById("charac-count");

    inputTextArea.addEventListener("input", () => {
        let textLenght = inputTextArea.value.length;

        if(textLenght > 2500) {
            notifyMe('error', '{{ localize('Content exceeds limit') }}')
        }
        characCount.textContent = textLenght;
        

    });

</script>
