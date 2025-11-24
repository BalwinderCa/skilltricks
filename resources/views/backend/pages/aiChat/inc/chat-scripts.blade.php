<script>

    "use strict";



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

    function startNewConversation() {

        let expertId = $('.expert-list a.active').data('category_id');



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

            url: '{{ route('chat.getMessages') }}',

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

                $('#chat_form').submit();

            }

        });



        $('#chat_form').on('submit', function(e) {

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

            let categoryId = $('body').find('.tt-chat-user-list a.active').data('category_id');



            data += `&chat_id=${chatId}&category_id=${categoryId}`;



            $.ajax({

                headers: {

                    'X-CSRF-TOKEN': '{{ csrf_token() }}'

                },

                method: 'POST',

                url: '{{ route('chat.newMessage') }}',

                data: data,

                beforeSend: function() {

                    $('.msg-send-btn').prop('disabled', true);

                    cloneLoader();

                    $('.new-msg-loader').first().removeClass('d-none');

                    setTimeout(() => {

                        initScrollToChatBottom();

                    }, 300);



                },

                complete: function() {

                    $('#prompt').val('');

                },

                success: function(data) {



                    if (data.status == 200) {



                        let urlEvent = '{{ route('chat.process') }}'

                        TT.eventSource = new EventSource(`${urlEvent}`, {

                            withCredentials: true

                        });



                        // TT.eventSource.onmessage = function(e) {

                        TT.eventSource.onmessage = function(e) {

                            

                            if (e.data.indexOf("[DONE]") !== -1) {

                                $('.new-msg-loader').first().removeClass('new-msg-loader');

                                $('.msg-send-btn').prop('disabled', false);

                                $('.btn-stop-content').prop('disabled', true);

                                TT.eventSource.close();

                            } else {

                                $('.btn-stop-content').prop('disabled', false);

                                let txt = undefined;

                                try {

                                    txt = JSON.parse(e.data).choices[0].delta.content;

                                    if (txt !== undefined) {

                                        let oldValue = '';

                                        if ($('.new-msg-loader:first .tt-message-text').find(

                                                '.tt-text-preloader').length !== 0) {

                                            $('.new-msg-loader:first .tt-message-text').empty();

                                            $('.new-msg-loader').first().removeClass('d-none');

                                        } else {

                                            oldValue += $(

                                                    '.new-msg-loader:first .tt-message-text')

                                                .html();

                                        }

                                        let value = oldValue + txt;

                                        let finalText = formatText(value);

                                        $('.new-msg-loader:first .tt-message-text').html(

                                            finalText);

                                    }

                                } catch (e) {

                                    console.log('error '+ e);

                                }

                            }



                            initScrollToChatBottom();

                        };



                        TT.eventSource.onerror = function(e) {

                            console.log(e);

                            TT.eventSource.close();

                            notifyMe('error', 'something went wrong !! .You exceeded your current quota, please check your plan and billing details. For more information on this error, read the docs: https://platform.openai.com/docs/guides/error-codes/api-errors.');

                            $('.new-msg-loader').first().remove();

                        };

                    } else {

                        notifyMe('error', data.message);

                        $('.new-msg-loader').first().remove();

                    }

                }

            });

        });

    }

    initMsgForm();



    // show loader

    function cloneLoader() {

        let cloned = $(".new-msg-loader").clone();

        $('.messages-wrapper').append(cloned);

        initCopyMsg();

    }



    // Forcefully stop generating content

    $(document).on('click', '.btn-stop-content', function(e) {

        e.preventDefault();

        if (TT.eventSource) {

            TT.eventSource.close();

        }

        resetGenerateButton();

        notifyMe('info',

            '{{ localize('Articale generation has been stopped.') }}');

        updatBalanceAfterStopGeneration();



    });



    function updatBalanceAfterStopGeneration() {

        $.ajax({

            headers: {

                'X-CSRF-TOKEN': '{{ csrf_token() }}'

            },

            method: 'POST',

            url: '{{ route('chat.update_balance') }}',

            success: function(data) {},

            error: function() {}

        })

    }



    function resetGenerateButton() {

        $('.new-msg-loader').first().removeClass('new-msg-loader');

        $('.msg-send-btn').prop('disabled', false);

        $('.btn-stop-content').prop('disabled', true);

        initFeather();

    }

    // copy-msg-btn

    function initCopyMsg() {

        $(".copy-msg-btn").each(function() {

            var $this = this;

            $($this).on("click", async function() {

                var type = $(this).data('type');

                var msg = '{{ localize('Message has been copied successfully') }}';

                var copyText = $(this).parent().closest('.msg-wrapper').find('.tt-message-text')

                    .html();

                copyText = clearFormatData(copyText);



                navigator.clipboard.writeText(copyText);

                notifyMe('success', msg);

            });

        });



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





    $(document).on('change', '#realTimeData', function(e){

        let settings = "{{getSetting('serper_api_key')}}"

        if(!settings){

            notifyMe('warning', 'Please enable serper api key from AI settings for real time data');

            $(this).prop('checked', false);

            return;

        }

        if($(this).is(':checked')) {

           $('#realTimeChatValue').val(1)

        }else{

            $('#realTimeChatValue').val(0)

        }

    })

</script>

