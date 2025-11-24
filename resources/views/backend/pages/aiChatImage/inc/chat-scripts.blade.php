        <script>
            "use strict";
            // runs when the document is ready --> for media files


            // show hide templates optional field
            $(".tt-advance-options-wrapper").hide();
            $(".tt-advance-options").on("click", function(e) {
                $(".tt-advance-options-wrapper").slideToggle(300);
            });


            initScrollToChatBottom();

            // get conversations
            function getConversations($this, ai_chat_category_id) {
                let hasActiveClass = $($this).hasClass('active');
                if (hasActiveClass) {
                    return;
                }

                if (TT.eventSource) {
                    TT.eventSource.close();
                }

                $('.list-and-messages-wrapper').addClass('d-none');
                $('.list-and-messages-wrapper-loader').removeClass('d-none');

                $($this).closest('.expert-list').find('a.active').removeClass('active');
                $($this).addClass('active');

                let data = {
                    ai_chat_category_id: ai_chat_category_id
                };

                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    method: 'POST',
                    url: '{{ route('chat.getConversations') }}',
                    data: data,
                    beforeSend: function() {},
                    complete: function() {
                        setTimeout(() => {
                            $('.list-and-messages-wrapper-loader').addClass('d-none');
                            $('.list-and-messages-wrapper').removeClass('d-none');
                            initScrollToChatBottom();
                        }, 300);
                    },
                    success: function(data) {
                        if (data.status == 200) {
                            $('.list-and-messages-wrapper').empty();
                            $('.list-and-messages-wrapper').html(data.chatRight);
                            initFeather();
                            initEditUpdate();
                            initMsgForm();
                            initCopyMsg();
                            initPromptLibrary();
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
            }

            // new conversation
            function startNewConversation(expertId) {


                let data = {
                    ai_chat_category_id: expertId
                };
                if (TT.eventSource) {
                    TT.eventSource.close();
                }
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    method: 'POST',
                    url: '{{ route('chat.store') }}',
                    data: data,
                    beforeSend: function() {
                        $('.new-conversation-btn').prop('disabled', true);
                    },
                    complete: function() {
                        $('.new-conversation-btn').prop('disabled', false);
                    },
                    success: function(data) {
                        if (data.status == 200) {
                            $('.ai-chat-list').empty();
                            $('.messages-container').empty();
                            $('.ai-chat-list').html(data.chatList);
                            $('.messages-container').html(data.messagesContainer);
                            initFeather();
                            initEditUpdate();
                            initMsgForm();
                            initCopyMsg();
                            initPromptLibrary();
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
            }

            // edit - update chat
            function initEditUpdate() {
                $(".tt_editable").each(function() {
                    var $this = this;
                    $($this).on("click", async function() {
                        var name = $this.dataset.name;
                        $(".tt_update_text[data-name='" + name + "']").attr("contenteditable", "true")
                            .focus();

                    });
                });

                $(".tt_update_text").each(function() {
                    var $this = this;
                    $($this).on("focusout", async function() {
                        var chatId = $($this).data('id');
                        var value = $this.innerHTML;
                        var data = {
                            chatId,
                            value
                        }
                        var response = await updateChat(data);
                    });
                });
            }
            initEditUpdate();
            // update chat
            async function updateChat(data) {
                let result = $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    method: 'POST',
                    url: '{{ route('chat.update') }}',
                    data: data,
                    success: function(response) {
                        // do nothing
                    },
                    error: function(data) {
                        notifyMe('error', '{{ localize('Something went wrong') }}');
                    }
                });

            }
            // get messages of conversation
            function getMessagesOfConversation($this, chatId) {
                let hasActiveClass = $($this).hasClass('active');
                if (hasActiveClass) {
                    return;
                }

                $('.messages-container').addClass('d-none');
                $('.messages-container-loader').removeClass('d-none');

                $($this).closest('.tt-chat-history-list').find('a.active').removeClass('active');
                $($this).addClass('active');

                let data = {
                    chatId: chatId
                };
                if (TT.eventSource) {
                    TT.eventSource.close();
                }
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    method: 'POST',
                    url: '{{ route('imageChat.getMessages') }}',
                    data: data,
                    beforeSend: function() {},
                    complete: function() {
                        setTimeout(() => {
                            $('.messages-container-loader').addClass('d-none');
                            $('.messages-container').removeClass('d-none');
                            initScrollToChatBottom();
                        }, 300);
                    },
                    success: function(data) {
                        if (data.status == 200) {
                            $('.messages-container').empty();
                            $('.messages-container').html(data.messagesContainer);
                            initFeather();
                            initMsgForm();
                            initCopyMsg();
                            initPromptLibrary();
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
            }

            // send new message
            function initMsgForm() {
                $("#prompt").keypress(function(e) {
                    if (e.which === 13 && !e.shiftKey) {
                        e.preventDefault();
                        $('#image_chat').submit();
                    }
                });

                $(document).on('submit', '#image_chat', function(e) {
                    e.preventDefault();


                    let form = $(this);
                    let prompt = form.find('#prompt').val();

                    if (prompt == '') {
                        notifyMe('warning', 'Please Write here somethings');
                        return;
                    }

                    let userName = '{{ auth()->user()->name }}';
                    let userAvatar =
                        '{{ auth()->user()->avatar ? asset('/public/' . auth()->user()->profileImage->media_file) : staticAsset('/backend/assets/img/avatar/1.jpg') }}';
                    let newMsg = `<div class="d-flex justify-content-end mb-4 tt-message-wrap tt-message-me">
                                <div class="d-flex flex-column align-items-end">
                                <div class="d-flex align-items-start">
                                <div class="p-3 me-3 rounded-3 mw-450 tt-message-text tt-message-text-category-wise">
                                    ${prompt}
                                    </div>
                                    <div class="avatar avatar-md flex-shrink-0">
                                    <img class="rounded-circle" src="${userAvatar}" alt=" avatar" />
                                    </div>
                                </div>
                                </div>
                            </div>`;
                    $('.messages-wrapper').append(newMsg);

                    initScrollToChatBottom();


                    var data = form.serialize();
                    let chatId = $('body').find('.ai-chat-list a.active').data('id');
                    let categoryId = $(this).data('category_id');

                    data += `&chat_id=${chatId}&category_id=${categoryId}`;
                    $('#prompt').val('');
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        method: 'get',
                        url: '{{ route('imageChat.newMessage') }}',
                        data: data,
                        beforeSend: function() {
                            $('.msg-send-btn').prop('disabled', true);
                            cloneLoader();
                            $('.messages-wrapper .new-msg-loader').last().removeClass('d-none');
                            setTimeout(() => {
                                initScrollToChatBottom();
                            }, 300);

                        },
                        complete: function() {
                            $('#prompt').val('');
                        },
                        success: function(data) {
                            setTimeout(() => {
                                initScrollToChatBottom();
                            }, 300);
                            if (data.response.status == 200) {
                                var image = '<img src="' + data.response.file_path + '"  width="256" alt="">';
                                $('.messages-wrapper .new-msg-loader:last').find('.tt-message-text').html(
                                image);
                                
                            } else {
                                if (data.response.message) {
                                    notifyMe('error', data.response.message);
                                } else {
                                    notifyMe('error', '{{ localize('Something went wrong') }}');
                                }
                                $('.messages-wrapper .new-msg-loader:last').find('.tt-message-text').html(null);
                            } 
                            $('.msg-send-btn').prop('disabled', false);
                            initScrollToChatBottom();
                        }
                    });
                });
            }
            initMsgForm();

            // show loader
            function cloneLoader() {
                let cloned = $(".single-chat-wrapper-copy").clone().removeClass('single-chat-wrapper-copy');

                $('.messages-wrapper').append(cloned);
                // initCopyMsg();
            }
            // Forcefully stop generating content


            function resetGenerateButton() {
                $('.new-msg-loader').first().removeClass('new-msg-loader');
                $('.msg-send-btn').prop('disabled', false);
                $('.btn-stop-content').prop('disabled', true);
                initFeather();
            }
            // copy-msg-btn
            function initCopyMsg() {

                // download  contents

                // copy chat
                $(".copyChat").on("click", function() {
                    downloadChatHistory('copyChat');

                });

            }
            initCopyMsg();


            $('.chatTrainTestBtn').on('click', function() {
                $('.chat_training_data').val(`[
                    {
                "role": "system",
                "content": "You are an helpful assistant"
            },
            {
                "role": "user",
                "content": "Who won the world cup football in 2022?"
            },
            {
                "role": "assistant",
                "content": "Argentina won the world cup football in 2022. It was played againt France in the final. The score was 3-3 in the regular time. And then it was penalty shoot out."
            },
            {
                "role": "user",
                "content": "Where was it played?"
            },
            {
                "role": "assistant",
                "content": "It was played in Qatar."
            }
        ]`, -1)
            })

            $('.promptTestBtn').on('click', function() {
                $('.prompt').val(`Write a blog about [blog title]`, -1)
            })

            function initPromptLibrary() {
                $('.promptBtn').on('click', function() {
                    let prompt = $(this).data('prompt');
                    $('#prompt').val(prompt);
                    $('#promptModal').modal('hide');
                })
            }
            // download/copy chat history
            function downloadChatHistory(type = 'html') {
                let chatId = $('body').find('.ai-chat-list a.active').data('id');
                let categoryId = $('body').find('.tt-chat-user-list a.active').data('category_id');
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    method: 'GET',
                    url: '{{ route('chat.download') }}',
                    data: {
                        chatId: chatId,
                        categoryId: categoryId,
                        type: type
                    },
                    success: function(data) {
                        var html = $('#downloadChat').html(data);
                        var msg = '{{ localize('Chat has been copied successfully') }}';
                        let copyText = $("#downloadChat").html();
                        copyText = clearFormatData(copyText);
                        navigator.clipboard.writeText(copyText);
                        notifyMe('success', msg);
                    },
                    error: function(data) {

                    }

                })
            }

            function clearFormatData(copyText) {

                copyText = copyText.replaceAll(/(?:\r\n|\r|\n)/g, '');
                copyText = copyText.replaceAll('                        ', ' ');
                copyText = copyText.replaceAll('     ', ' ');
                copyText = copyText.replaceAll('    ', '');
                copyText = copyText.replaceAll('<br>', '\n');
                copyText = copyText.replaceAll('<span>', '');
                copyText = copyText.replaceAll('</span>', '');


                return copyText;
            }

            function formatText(text) {
                return text.replace(/(?:\r\n|\r|\n)/g, '<br>');

            }

            initPromptLibrary();
            var mediaRecorder;
            let audioBlob;
            var chunks = [];
            var stream_;

            var prompt_images = [];
            $(document).on('click', '#recordButton', function(e) {
                chunks = [];
                navigator.mediaDevices
                    .getUserMedia({
                        audio: true
                    })
                    .then(function(stream) {
                        stream_ = stream;
                        mediaRecorder = new MediaRecorder(stream);
                        $('#recordButton').addClass('d-none');
                        $('#stopButton').removeClass('d-none');
                        var isRecord = true;
                        mediaRecorder.ondataavailable = function(e) {
                            chunks.push(e.data);
                        };
                        mediaRecorder.start();
                    })
                    .catch(function(err) {
                        console.log('The following error occurred: ' + err);
                        toastr.warning('Audio is not allowed');
                    });


                $(document).on('click', '#stopButton', function(e) {
                    e.preventDefault();
                    $('#recordButton').removeClass('d-none');
                    $('#stopButton').addClass('d-none');
                    var isRecord = false;
                    mediaRecorder.onstop = function(e) {
                        var blob = new Blob(chunks, {
                            type: 'audio/mp3'
                        });

                        var formData = new FormData();
                        var fileOfBlob = new File([blob], 'audio.mp3');
                        formData.append('file', fileOfBlob);

                        chunks = [];

                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            url: '{{ route('imageChat.recordVoiceToText') }}',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(data) {
                                if (data.text.length >= 5) {
                                    $('#prompt').val(data.text);
                                }
                            },
                            error: function(error) {
                                console.log(error);
                                // Handle the error response
                            },
                        });
                    };
                    mediaRecorder.stop();
                    stream_
                        .getTracks() // get all tracks from the MediaStream
                        .forEach(track => track.stop()); // stop each of them
                });
            });
        </script>
