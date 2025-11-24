<script>
    "use strict";
    // runs when the document is ready --> for media files
    var newMsgWithImage = '';
    $(document).ready(function() {
        getChosenFilesCount();
        showSelectedFilePreviewOnLoad();
    });

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

        if(TT.eventSource) {
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
        if(TT.eventSource) {
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
        if(TT.eventSource) {
            TT.eventSource.close();
        }
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'POST',
            url: '{{ route('vision.getMessages') }}',
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
                $('#vision_form').submit();
            }
        });

        $(document).on('submit', '#vision_form', function(e) {
            e.preventDefault();


            let form = $(this);
            let prompt = form.find('#prompt').val();
            let images = form.find('#vision_images').val();
            console.log(images);
            if(prompt == '') {
                notifyMe('warning', 'Please Write here somethings');
                return;
            }
            if(vision_images == '') {
                notifyMe('warning', 'Please select Images');
                return;
            }
            let userName = '{{ auth()->user()->name }}';
            let userAvatar ='{{userAvatar()}}';


            initScrollToChatBottom();


            var data = form.serialize();
            let chatId = $('body').find('.ai-chat-list a.active').data('id');
            let categoryId = $(this).data('category_id');


            let newMsg = getFilePathNewMsg(data, chatId, categoryId, images, prompt);
        });
    }
    initMsgForm();


     function getFilePathNewMsg(promtResultData, promtResultChatId, promtResultCategoryId, images, prompt)
    {
            console.log('function start:')
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'GET',
                url: '{{ route('vision.newMessageWithFile') }}',
                data: {images:images, prompt:prompt},

                success: function(data) {
                    if (data.status == 200) {
                         $('.messages-wrapper').append(data.mediaFiles);
                         initScrollToChatBottom();
                         promtResult(promtResultData, promtResultChatId, promtResultCategoryId);
                    console.log('funstion success:');
                    } else if(data.status == 404) {
                        notifyMe('error', data.message);
                    }
                }
            });

    }
    // prompt result
    function promtResult(data, chatId, categoryId){
        data += `&chat_id=${chatId}&category_id=${categoryId}`;
            console.log('before result promt:');
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('vision.newMessage') }}',
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
                    $('#vision_image').empty();
                },
                success: function(data) {
                    console.log('before result promt:', data.status);
                    if (data.status == 200) {

                        let urlEvent = '{{ route('vision.process') }}'
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
                                        if ($('.new-msg-loader:first .tt-message-text').find('.tt-text-preloader').length !== 0) {
                                            $('.new-msg-loader:first .tt-message-text').empty();
                                            $('.new-msg-loader').first().removeClass('d-none');
                                        } else {
                                            oldValue += $('.new-msg-loader:first .tt-message-text').html();
                                        }
                                        let value = oldValue + txt;
                                        let finalText = formatText(value);
                                        $('.new-msg-loader:first .tt-message-text').html(finalText);
                                    }
                                } catch (e) {}
                            }

                            initScrollToChatBottom();
                        };

                        TT.eventSource.onerror = function(e) {
                           TT.eventSource.close();
                        };
                    } else {
                        notifyMe('error', data.message);
                        $('.new-msg-loader').first().remove();
                    }
                }
            });
    }
      // show loader
    function cloneLoader() {
        let cloned = $(".new-msg-loader").clone();
        $('.messages-wrapper').append(cloned);
        initCopyMsg();
    }
    // Forcefully stop generating content
    $(document).on('click', '.btn-stop-content', function(e) {
        e.preventDefault();
        if(TT.eventSource) {
            TT.eventSource.close();
        }
        resetGenerateButton();
        notifyMe('info',
            '{{ localize('Articale generation has been stopped.') }}');
        updatBalanceAfterStopGeneration();

    });
    function updatBalanceAfterStopGeneration(){
        $.ajax({
            headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'POST',
            url: '{{ route('vision.update_balance') }}',
            success: function(data) {},
            error:function() {}
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
                var copyText = $(this).parent().closest('.msg-wrapper').find('.tt-message-text').html();
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
            data: {chatId:chatId, categoryId:categoryId, type:type},
            success:function(data){
                var html = $('#downloadChat').html(data);
                var msg = '{{ localize('Chat has been copied successfully') }}';
                let copyText = $("#downloadChat").html();
                copyText = clearFormatData(copyText);
                navigator.clipboard.writeText(copyText);
                notifyMe('success', msg);
            },
            error:function(data){

            }

        })
    }
    function clearFormatData(copyText)
    {

            copyText = copyText.replaceAll(/(?:\r\n|\r|\n)/g, '');
            copyText = copyText.replaceAll('                        ', ' ');
            copyText = copyText.replaceAll('     ', ' ');
            copyText = copyText.replaceAll('    ', '');
            copyText = copyText.replaceAll('<br>', '\n');
            copyText = copyText.replaceAll('<span>', '');
            copyText = copyText.replaceAll('</span>', '');


            return copyText;
    }
    function formatText(text)
    {
        return text.replace(/(?:\r\n|\r|\n)/g, '<br>');

    }

    initPromptLibrary();
</script>
