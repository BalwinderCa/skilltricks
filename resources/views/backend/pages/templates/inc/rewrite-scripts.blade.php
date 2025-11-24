<script>
    "use strict";

    function aiAssigntent(type, text, range) {
        let language = $('#content-language').val();
       if(!text) {
        notifyMe('error', 'Please provide the text');
        return;
       }
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'POST',
            url: '{{ route('ai.rewrite') }}',
            dataType: "JSON",
            data: {
                type: type,
                text: text,
                language: language
            },
            beforeSend: function() {
                notifyMe('warning', 'Please wait. Content Genrating..');

                },
            success: function(data) {
                if (data.status == 200) {
                    range.pasteHTML(data.response);
                    notifyMe('success', '{{ localize('Generate Successfully, dont forget to save') }}');
                } else {
                    if (data.response) {
                        notifyMe('error', data.response);
                    } else {
                        notifyMe('error', '{{ localize('Something went wrong') }}');
                    }
                }
            },
            error: function(data) {
                if (data.status == 400 && data.response) {
                    notifyMe('error', data.response);
                } else {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            }
        })
    }

    var myEditor = $('.editor');
    $(myEditor).summernote({        
        height: 420,
        fontSizes: ['8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '36', '48' , '64'],
        toolbar: [
                    ["font", ["bold", "underline", "italic", "clear"]],
                    ['fontname', ['fontname']],
                    ["para", ["ul", "ol", "paragraph"]],
                    ["style", ["style"]],
                    ['fontsize', ['fontsize']],
                    ["view", ["undo", "redo"]],
        ],
        callbacks: {
            onInit: function() {

                let customDropdown = `<div class="note-btn-group note-view"> <select class="form-select px-3 py-1 rounded-pill dChange cursor-pointer" aria-label="Default select example">
            @foreach (appStatic()::rewrite_types as $key=>$type)
            <option value="{{ $key }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select></div>`;

                $(customDropdown).appendTo($('.note-toolbar'));
                $(document).on("change", ".dChange", function(e) {
                    var range = $(".editor").summernote('createRange');
                    let type = $(this).val();
                    let txt = range.toString();
                    aiAssigntent(type, txt, range)
                })

            }
        }
    });
</script>
