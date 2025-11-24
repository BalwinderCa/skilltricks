<script src="https://unpkg.com/typed.js@2.0.16/dist/typed.umd.js"></script>
<script>
    "use strict";

    var streamline = '{{ getSetting('enable_streamline') }}';

    // init hljs
    function initHljs() {
        hljs.highlightAll();
        hljs.initLineNumbersOnLoad();
    }
    // show hide templates optional field
    $(document).ready(function() {


        $(".tt-advance-options-wrapper").hide();
        $(".tt-advance-options").on("click", function(e) {
            $(".tt-advance-options-wrapper").slideToggle(300);
        });
        initHljs();
        let projectEditRoute = '{{ Route::is('projects.edit') }}';
        if (projectEditRoute != 1) {
            $('.editor').summernote('disable');
        }
    });
   
    // showSaveToFolderModal
    function showSaveToFolderModal() {

        let project_id = $('.project_id').val();

        if (project_id == null || project_id == '') {
            notifyMe('error', '{{ localize('Please generate AI contents first') }}');
            return;
        }

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'POST',
            url: '{{ route('projects.moveToFolderModal') }}',
            data: {
                project_id
            },
            beforeSend: function() {
                $('.move_to_folder_btn').prop('disabled', true);
            },
            complete: function() {
                $('.move_to_folder_btn').prop('disabled', false);
            },
            success: function(data) {
                if (data.status == 200) {
                    $('.move-to-folder-contents').html(data.contents);
                    $('.modalSelect2').select2({
                        dropdownParent: $(('.modalParentSelect2'))
                    });
                    $('#saveToFolder').modal('show');
                    moveToFolderFormInit();
                } else {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            },
            error: function(data) {
                notifyMe('error', '{{ localize('Something went wrong') }}');
            }
        });
    }

    // move-to-folder-form
    function moveToFolderFormInit() {
        $('.move-to-folder-form').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('projects.moveToFolder') }}',
                data: form.serialize(),
                beforeSend: function() {
                    $('.move-project-btn').prop('disabled', true);
                },
                complete: function() {
                    $('.move-project-btn').prop('disabled', false);
                },
                success: function(data) {
                    if (data.status == 200) {
                        $('#saveToFolder').modal('hide');
                        notifyMe('success', '{{ localize('Project moved successfully') }}');
                    } else {
                        notifyMe('error', '{{ localize('Something went wrong') }}');
                    }
                },
                error: function(data) {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            });
        });
    }

    function initJqueryEvents() {

        // contents start 


        // copy contents
        $(".copyBtn").on("click", function() {
            var type = $(this).data('type');
            if (type && type == "code") {
                var html = document.querySelector('#codetext');
                var msg = '{{ localize('Code has been copied successfully') }}';
            } else {
                var html = document.querySelector('.note-editable');
                var msg = '{{ localize('Content has been copied successfully') }}';
            }
            const selection = window.getSelection();
            const range = document.createRange();
            range.selectNodeContents(html);
            selection.removeAllRanges();
            selection.addRange(range);
            document.execCommand('copy');
            window.getSelection().removeAllRanges()
            notifyMe('success', msg);
        });

        // create contents ajax call
        $('.generate-contents-form').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let url = $(this).data('url');

            $('.note-editable').empty();
            $('.note-editing-area > .typed-cursor').remove();
            $('.editor').summernote('disable');

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: url,
                data: form.serialize(),
                beforeSend: function() {
                    $('.btn-create-text').html(TT.localize.pleaseWait);
                    $('.btn-create-content').prop('disabled', true);
                    $('.btn-create-content .tt-text-preloader').removeClass('d-none');
                },
                success: function(data) {
                    let urlEvent = '{{ route('templates.processContents') }}'
                    TT.eventSource = new EventSource(`${urlEvent}`, {
                        withCredentials: true
                    });

                    if (data.status == 200) {
                        $('.generate-contents-form .btn-stop-content').prop('disabled', false);
                        $('.project-title').val(data.title);
                        $('.project_id').val(data.project_id);

                        TT.eventSource.onmessage = eventOnSuccess;
                        TT.eventSource.onerror = eventOnError;
                    } else {
                        $('.btn-create-content').prop('disabled', false);
                        if (data.message) {
                            notifyMe('error', data.message);
                        } else {
                            notifyMe('error', '{{ localize('Something went wrong') }}');
                        }
                    }
                },
                error: function(data) {
                    $('.btn-create-content').prop('disabled', false);
                    if (data.status == 400 && data.message) {
                        notifyMe('error', data.message);
                    } else {
                        notifyMe('error', '{{ localize('Something went wrong') }}');
                    }
                }
            });
        });

        // Forcefully stop generating content
        $('.generate-contents-form .btn-stop-content').on('click', function(e) {
            e.preventDefault();
            TT.eventSource.close();
            resetGenerateButton();
            notifyMe('info', '{{ localize('Contents generation has been stopped.') }}');
            updatBalanceAfterStopGeneration();
        });

        function updatBalanceAfterStopGeneration() {
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('templates.update_balance') }}',
                success: function(data) {},
                error: function() {}
            })
        }


        function eventOnSuccess(e) {
            $('.editor').summernote('enable');

            if (e.data.indexOf("[DONE]") !== -1) {
                resetGenerateButton();
                notifyMe('success', '{{ localize('Contents generated successfully') }}');
                TT.eventSource.close();
            } else {
                let txt = undefined;
                try {
                    txt = JSON.parse(e.data).choices[0].delta.content;
                    if (txt !== undefined) {
                        let oldValue = '';
                        if ($('.new-msg-loader:first .tt-message-text').find('.tt-text-preloader').length !== 0) {
                            $('.new-msg-loader:first .tt-message-text').empty();
                            $('.new-msg-loader').first().removeClass('d-none');
                        } else {
                            oldValue += $('.note-editable').html();
                        }

                        let value = oldValue + txt;

                        value = value.replace(/\*\*(.*?)\*\*/g, '<h3 class="mb-0">$1</h3>');
                        let finalText = value.replace(/(?:\r\n|\r|\n)/g, '<br>');

                        $('.new-msg-loader:first .tt-message-text').html(finalText);
                        $('.note-editable').html(finalText);
                    }
                } catch (e) {}
            }
        }

        function eventOnError(e) {
            TT.eventSource.close();
            resetGenerateButton();
            notifyMe('error', '{{ localize('Something wrong happened. Please try again.') }}');
        }

        function resetGenerateButton() {
            $('.btn-create-text').html(TT.localize.createContent);
            $('.btn-create-content .tt-text-preloader').addClass('d-none');
            $('.btn-create-content').prop('disabled', false);
            $('.generate-contents-form .btn-stop-content').prop('disabled', true);
        }

        // content-form submit -- update contents
        $('.content-form').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);

            let project_id = $('.project_id').val();
            let title = $('.project-title').val();
            let contents = $('.note-editable').html();

            // let formInfos = form.serialize() + $('.note-editable').html();

            if (project_id == null || project_id == '') {
                notifyMe('error', '{{ localize('Please generate AI contents first') }}');
                return;
            }

            let data = {
                project_id,
                title,
                contents,
            }

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('projects.update') }}',
                data: data,
                beforeSend: function() {
                    $('.content-form-submit').prop('disabled', true);
                },
                complete: function() {
                    $('.content-form-submit').prop('disabled', false);
                },
                success: function(data) {
                    if (data.status == 200) {
                        notifyMe('success', '{{ localize('Contents updated successfully') }}');
                    } else {
                        notifyMe('error', '{{ localize('Something went wrong') }}');
                    }
                },
                error: function(data) {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            });
        });

        // favorite template
        $(".favorite-template").each(function(el) {
            var $this = $(this);
            let templateId = $this.data('template');
            $this.on('click', function() {
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    method: 'POST',
                    url: '{{ route('templates.updateFavorite') }}',
                    data: {
                        templateId: templateId
                    },
                    success: function(data) {
                        $($this).find('i').toggleClass('lar').toggleClass('las')
                            .toggleClass(
                                'text-success');
                        notifyMe('success', data['message']);
                    },
                    error: function(data) {
                        notifyMe('error', '{{ localize('Something went wrong') }}');
                    }
                });
            })
        });

        // create images ajax call
        $('.generate-images-form').on('submit', function(e) {
            e.preventDefault();
            var $this = this;
            var form = $('.generate-images-form')[0]; // You need to use standard javascript object here
            var formData = new FormData(form);
            let engine = $($this).data('engine');
            let url = engine == "sd" ? '{{ route('sdImages.generate') }}' : '{{ route('images.generate') }}';
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: url,
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('.btn-create-content').html(TT.localize.pleaseWait);
                    $('.btn-create-content').prop('disabled', true);
                },
                complete: function() {
                    $('.btn-create-content').prop('disabled', false);
                    $('.btn-create-content').html(TT.localize.generateImage);
                },
                success: function(data) {
                    if (data.status == 200) {
                        $('.used-words-percentage').empty();
                        $('.ai-images-wrapper').empty();

                        $('.used-words-percentage').append(data.usedPercentage);
                        $('.ai-images-wrapper').append(data.images);

                        $('[data-bs-toggle="tooltip"]').tooltip();
                        $(".confirm-delete").click(function(e) {
                            e.preventDefault();
                            var url = $($this).data("href");
                            $("#delete-modal").modal("show");
                            $("#delete-link").attr("href", url);
                        });

                        magnifyPopup();
                        initFeather();
                        notifyMe('success', '{{ localize('Image generated successfully') }}');
                    } else {
                        if (data.message) {
                            notifyMe('error', data.message);
                        } else {
                            notifyMe('error', '{{ localize('Something went wrong') }}');
                        }
                    }
                },
                error: function(data) {
                    if (data.status == 400 && data.message) {
                        notifyMe('error', data.message);
                    } else {
                        notifyMe('error', '{{ localize('Something went wrong') }}');
                    }
                }
            });
        });

        // create code ajax call
        $('.generate-codes-form').on('submit', async function(e) {
            e.preventDefault();
            let form = $(this);

            await $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('codes.generate') }}',
                data: form.serialize(),
                beforeSend: function() {
                    $('.btn-create-text').html(TT.localize.pleaseWait);
                    $('.btn-create-content').prop('disabled', true);
                    $('.btn-create-content .tt-text-preloader').removeClass('d-none');
                },
                complete: function() {
                    $('.btn-create-text').html(TT.localize.generateCode);
                    $('.btn-create-content').prop('disabled', false);
                    $('.btn-create-content .tt-text-preloader').addClass('d-none');
                },
                success: function(data) {
                    let urlEvent = '{{ route('templates.processContents') }}'
                    TT.eventSource = new EventSource(`${urlEvent}`, {
                        withCredentials: true
                    });
                    if (data.status == 200) {
                        $('.used-words-percentage').empty();
                        $('.content-code-card').empty()

                        $('.content-code-card').html(data['output']);

                        initJqueryEvents();

                        initHljs();

                        initFeather();

                        $('.used-words-percentage').append(data.usedPercentage);
                        notifyMe('success', '{{ localize('Code generated successfully') }}');
                    } else {
                        if (data.message) {
                            notifyMe('error', data.message);
                        } else {
                            notifyMe('error', '{{ localize('Something went wrong') }}');
                        }
                    }
                },
                error: function(data) {
                    if (data.status == 400 && data.message) {
                        notifyMe('error', data.message);
                    } else {
                        notifyMe('error', '{{ localize('Something went wrong') }}');
                    }
                }
            });
        });

        // create s2t ajax call
        $('.generate-s2t-form').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('s2t.generate') }}',
                dataType: "JSON",
                data: new FormData(this),
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('.btn-create-text').html(TT.localize.pleaseWait);
                    $('.btn-create-content').prop('disabled', true);
                    $('.btn-create-content .tt-text-preloader').removeClass('d-none');
                },
                complete: function() {
                    $('.btn-create-text').html(TT.localize.createContent);
                    $('.btn-create-content').prop('disabled', false);
                    $('.btn-create-content .tt-text-preloader').addClass('d-none');
                    $('.editor').summernote('enable');
                },
                success: function(data) {
                    if (data.status == 200) {
                        $('.used-words-percentage').empty();

                        if (parseInt(streamline) == 1) {
                            new Typed('.note-editable', {
                                strings: [data['output']],
                                typeSpeed: 10,
                            });
                        } else {
                            $('.note-editable').html(data['output']);
                        }

                        $('.project-title').val(data.title);
                        $('.project_id').val(data.project_id);
                        $('.used-words-percentage').append(data.usedPercentage);
                        notifyMe('success', '{{ localize('Contents generated successfully') }}');
                    } else {
                        if (data.message) {
                            notifyMe('error', data.message);
                        } else {
                            notifyMe('error', '{{ localize('Something went wrong') }}');
                        }
                    }
                },
                error: function(data) {
                    if (data.status == 400 && data.message) {
                        notifyMe('error', data.message);
                    } else {
                        notifyMe('error', '{{ localize('Something went wrong') }}');
                    }
                }
            });
        });
    }
    initJqueryEvents();


   
</script>
