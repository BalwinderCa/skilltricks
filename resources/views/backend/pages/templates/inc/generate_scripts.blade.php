<script>
    const voicesData = <?php echo json_encode($languages_voices); ?>;
    $(document).on('submit', '.generate-voice-form', function(e) {
        e.preventDefault();
        let status = $('#status').val();
        if (!status) {
            notifyMe('warning', '{{ localize('Please Enable Voice Over from Voice Settings') }}');
            return;
        }
        document.getElementById("generate_speech_button").disabled = true;
        document.getElementById("generate_speech_button").innerHTML = "Please Wait...";

        var formData = new FormData();
        var speechData = [];
        formData.append('title', $('#title').val());
        formData.append('voice', $('#voice').val());
        formData.append('lang', $('#languages').val());
        formData.append('speed', $('#speed').val());
        formData.append('b_reak', $('#break').val());
        var data = {
            content: $('.defaultcontent').val()
        }
        speechData.push(data);
        $('.speeches .speech').each(function() {
            var data = {
                content: $(this).find('textarea').val()
            };
            speechData.push(data);
        });

        var jsonData = JSON.stringify(speechData);
        formData.append('speeches', jsonData);
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            type: "post",
            url: '{{ route('t2s.generate') }}',
            data: formData,
            contentType: false,
            processData: false,
            success: function(data) {
                clearData();
                notifyMe(data.response.status ? 'success' : 'warning', data.response.message);
                if (data.response.status === true) {
                    $("#voice_list_table").html('');
                    $("#voice_list_table").html(data.response.view);
                    initFeather();
                    initJqueryEvents();
                    $("table.tt-footable").footable({
                        on: {
                            "ready.ft.table": function(e, ft) {
                                $(".confirm-delete").click(function(e) {
                                    e.preventDefault();
                                    var url = $(this).data("href");
                                    $("#delete-modal").modal("show");
                                    $("#delete-link").attr("href", url);
                                });
                            },
                        },
                    });
                }
                document.getElementById("generate_speech_button").disabled = false;
                document.getElementById("generate_speech_button").innerHTML =
                    '{{ localize('Generate Speech') }}';
            },
            error: function(data) {
                var err = data.responseJSON ? data.responseJSON.errors : data.responseText.errors;
                $.each(err, function(index, value) {
                    toastr.error(value);
                });
                document.getElementById("generate_speech_button").disabled = false;
                document.getElementById("generate_speech_button").innerHTML = "Save";
            }
        });

    })

    function clearData() {
        $('.speeches .speech').remove();
        $('.defaultcontent').val('');
        $('#title').val('');
    }
    $(document).ready(function() {
        "use strict";

        populateVoiceSelect();

        $("#languages").on("change", function() {
            populateVoiceSelect();
        });

        function populateVoiceSelect() {
            const selectedLanguage = $("#languages").val();
            const selectedOptions = voicesData[selectedLanguage];
            const voiceSelect = $("#voice");

            voiceSelect.empty();

            if (selectedOptions) {
                selectedOptions.forEach(option => {
                    $("<option></option>")
                        .val(option.value)
                        .text(option.label)
                        .appendTo(voiceSelect);
                });
            }
        }

        $('.add-new-text').click(function() {

            var speechContent = `
                                
                <div class="speech mb-4">
                    <div class= "row align-items-center justify-content-between">
                                <div class="mb-2 col-10 col-lg-4">
                                    <select class="form-select select2 say-as">
                                        <option value="0" selected>{{__('say-as')}}</option>
                                        <option value="currency">{{__('currency')}}</option>
                                        <option value="telephone">{{__('telephone')}}</option>
                                        <option value="verbatim">{{__('verbatim')}}</option>
                                        <option value="date">{{__('date')}}</option>
                                        <option value="characters">{{__('characters')}}</option>
                                        <option value="cardinal">{{__('cardinal')}}</option>
                                        <option value="ordinal">{{__('ordinal')}}</option>
                                        <option value="fraction">{{__('fraction')}}</option>
                                        <option value="bleep">{{__('bleep')}}</option>
                                        <option value="unit">{{__('unit')}}</option>
                                        <option value="unit">{{__('time')}}</option>
                                    </select>
                                </div>
                                <div class="col-2">
                                    <a class="float-end mb-1 delete-speech cursor-pointer"><i data-feather="trash-2"
                                                                class="me-2 icon-14 text-danger">delete</i></a></div>
                        
                    </div>
                    <textarea class="form-control content" name="content[]" id="text" rows="4"
                    placeholder="{{ localize('Type your text') }}"></textarea>
                </div>
            `;

            $('.speeches').append(speechContent);
            $(".select2").select2();
            initFeather();
        });

        $(document).on('click', '.delete-speech', function() {
            $(this).closest('.speech').remove();
        });
        $(document).on('change', '.say-as', function() {
            var selectedValue = $(this).val();
            if ( selectedValue === 'currency' ){
                selectedValue = "<say-as interpret-as='currency' language='en-US'>$42.01</say-as>";
            } else if ( selectedValue === 'telephone' ){
                selectedValue = "<say-as interpret-as='telephone' google:style='zero-as-zero'>1800-202-1212</say-as>";
            } else if ( selectedValue === 'verbatim' ){
                selectedValue = "<say-as interpret-as='verbatim'>abcdefg</say-as>";
            } else if ( selectedValue === 'date' ){
                selectedValue = "<say-as interpret-as='date' format='yyyymmdd' detail='1'>1960-09-10</say-as>";
            } else if ( selectedValue === 'characters' ){
                selectedValue = "<say-as interpret-as='characters'>can</say-as>";
            } else if ( selectedValue === 'cardinal' ){
                selectedValue = "<say-as interpret-as='cardinal'>12345</say-as>";
            } else if ( selectedValue === 'ordinal' ){
                selectedValue = "<say-as interpret-as='ordinal'>1</say-as>";
            } else if ( selectedValue === 'fraction' ){
                selectedValue = "<say-as interpret-as='fraction'>5+1/2</say-as>";
            } else if ( selectedValue === 'bleep' ){
                selectedValue = "<say-as interpret-as='expletive'>censor this</say-as>";
            } else if ( selectedValue === 'unit' ){
                selectedValue = "<say-as interpret-as='unit'>10 foot</say-as>";
            } else if ( selectedValue === 'time' ){
                selectedValue = "<say-as interpret-as='time' format='hms12'>2:30pm</say-as>";
            }
            var textarea = $(this).closest('.speech').find('textarea');
            var existingValue = textarea.val();
            textarea.val(existingValue + selectedValue);
            $(this).val('0');
        });

    });
</script>
