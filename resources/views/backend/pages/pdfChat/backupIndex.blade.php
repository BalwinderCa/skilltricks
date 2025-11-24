@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Chat') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-flex flex-wrap align-items-center justify-content-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI Chat') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AI Chat') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <div class="d-flex align-items-center">
                                    @if (!isCustomer())
                                        <a href="{{ route('chat.prompts') }}" class="btn btn-accent btn-sm py-2 me-2">
                                            {{ localize('Chat Prompts') }}</a>
                                    @endif

                                    <a href="{{ route('chat.experts') }}" class="btn btn-primary btn-sm py-2">
                                        {{ localize('Browse Experts') }}</a>

                                </div>
                            </div>
                        </div>

                        <div class="d-block d-lg-none mt-3">
                            <button type="button"
                                    class="tt-advance-options cursor-pointer form-label cursor-pointer mb-0 btn btn-light shadow-sm btn-sm rounded-pill"><span
                                    class="fw-bold tt-promot-number fw-bold me-1"></span>{{ localize('Show History') }}
                                <span><i data-feather="plus" class="icon-16 text-primary ms-2"></i></span></button>
                            <div class="tt-advance-options-wrapper">
                                <div class="tt-chat-history flex-column d-flex">
                                    <!-- ai chat history search start -->
                                    <form action="">
                                        <div class="tt-search-box px-2 py-3 border-bottom">
                                            <div class="input-group">
                                                <span class="position-absolute top-50 start-0 translate-middle-y ms-2"><i
                                                        data-feather="search"></i></span>
                                                <input class="form-control-sm rounded-pill w-100 bg-secondary-subtle"
                                                       name="search" placeholder="{{ localize('Type & hit enter') }}..."
                                                       @isset($searchKey)
                                                           value="{{ $searchKey }}"
                                                    @endisset>
                                            </div>
                                        </div>
                                    </form>
                                    <!-- ai chat history search end -->

                                    <div class="tt-history-list-wrap tt-custom-scrollbar ai-chat-list">
                                    </div>
                                    <div class="mt-auto text-center py-3">
                                        <button
                                            class="tt-custom-link-btn rounded-pill px-3 py-2 bg-transparent border-0 new-conversation-btn"
                                            onclick="startNewConversation()">
                                            {{ localize('New Conversation') }}<i data-feather="plus"
                                                                                 class="icon-14 ms-1"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body p-0">
                            <div id="tt-ai-chat" class="d-flex" style="height: 65vh;">


                                <!-- chat right with preloader start -->
                                <div class="tt-chat-right d-flex w-100 d-none list-and-messages-wrapper-loader">
                                    <div class="tt-text-preloader tt-preloader-center">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </div>

                                <div class="tt-chat-right tt-custom-scrollbar d-flex w-100 list-and-messages-wrapper">
                                    <!-- messages Start -->
                                    <div class="w-100 d-flex flex-column messages-container h-100">
                                        <div class="mt-auto text-center justify-content-between align-items-end">
                                            <div class="pdfChatConversationArea w-100"></div>
                                        </div>
                                    </div>
                                    <!-- messages End-->

                                    <!-- Form Start -->
                                    <div class="w-100 d-flex flex-column messages-container">
                                        <div class="mt-auto text-center justify-content-between align-items-end">
                                            <div class="pdfChatConversationArea w-100"></div>

                                        </div>

                                        <div class="mt-auto text-center border-top justify-content-between align-items-end">
                                            <form class="p-3 d-block w-100" id="pdfChatForm" enctype="multipart/form-data">
                                                <textarea class="form-control border-0" rows="2" name="prompt" id="prompt"
                                                          placeholder="{{ localize('Type your message') }}.."></textarea>
                                                <div class="tt-product-thumb mt-2 text-start tt-vision-thumb" id="vision_image">

                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap flex-lg-nowrap gap-2">
                                                    <div class="d-flex align-items-center">
                                                        <span class="">{{ localize('Upload Image') }}
                                                            <input type="file"
                                                                   name="pdfFile"
                                                                   id="pdfFile"
                                                                   accept=".pdf"
                                                                   class="form-control" />
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <button class="btn btn-primary btn-sm rounded-pill tt-send-btn msg-send-btn" type="submit">
                                                            {{ localize('Send') }}<i data-feather="send" class="ms-1"></i>
                                                        </button>

                                                        <button class="btn rounded-pill btn-secondary btn-sm py-2 btn-stop-content ms-3" disabled>
                                                            <span>{{ localize('Stop') }} <i data-feather="stop-circle" class="ms-1"></i></span>
                                                        </button>
                                                    </div>

                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <!-- Form End -->
                                </div>
                                <!-- chat right box end -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
@endsection


@section('scripts')

    <script>

        "use strict";

        // $(()=>{
        //     $(".messages-container").prepend(
        //         userHtmltSection("User HTMLTemporary Text")+
        //         outputHtmlSection("Output Text")+
        //         loadingLoader()
        //     );
        // })

        let basePath = "{{ url('/') }}";
        let messageContainer = $(".pdfChatConversationArea");
        let loaderMessage = $(".mrLoader");
        let outputDiv = $('.pdfChatConversationArea');
        let sentence = '';

        $(document).on('submit', '#pdfChatForm', function(e) {
            e.preventDefault();

            var formData    = new FormData();
            var promptValue = $('#prompt').val();
            var pdfFile     = $('#pdfFile')[0].files[0];

            formData.append('prompt', promptValue);
            formData.append('pdfFile', pdfFile);
            formData.append('_token', "{{ csrf_token() }}");

            // Loader Assign.
            messageContainer.prepend(loadingLoader());

            try{
                $.ajax({
                    url: "{{ route('pdfChat.pdfChatEmbedding') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {


                        console.log("Server Response is : ",response);

                        let promptData = userInputDataMaker(promptValue, response.data);
                        // Loader Assign.
                        messageContainer.append(userHtmltSection(promptData))

                        chatCompletionEventSource();

                    }, // Success Close Here
                    error: function(XHR, textStatus, error) {
                        const jsonData = JSON.parse(XHR.responseText);

                        console.log("Error Json Data : ", jsonData);

                        console.log("XHR Response is : ", XHR);
                        console.log("Error Status Response is : ", textStatus);
                        console.log("XHR Response is : ", XHR);
                        // Handle the error response here

                        notifyMe('error', jsonData);

                        $('.new-msg-loader').first().remove();
                    }
                });
            }
            catch (e) {
                console.log("Catch Error : ",e)
            }

        });


        function chatCompletionEventSource(){
            /*
             * Pdf Chat Completion Start With Event Source
             * */

            let urlEvent = "{{ route('pdfChat.pdfChatCompletion') }}";

            TT.eventSource = new EventSource(`${urlEvent}`, {
                withCredentials: true
            });

            let finalText = '';

            messageContainer.prepend(outputHtmlSection(finalText));

            TT.eventSource.onmessage = function(e) {
                $(".mrLoader").addClass("d-none");

                console.log("Response From Chat Completion", e);

                if (e.data.indexOf("[DONE]") !== -1) {
                    // $('.new-msg-loader').first().removeClass('new-msg-loader');
                    $('.msg-send-btn').prop('disabled', false);
                    $('.btn-stop-content').prop('disabled', true);
                    TT.eventSource.close();
                } else {
                    $('.btn-stop-content').prop('disabled', false);
                    let txt = undefined;
                    try {
                        txt = JSON.parse(e.data).choices[0].delta.content;
                        if (txt !== undefined) {
                            console.log("Streaming Text is : ",txt);
                            let oldValue = '';

                            let value = oldValue + txt;
                            finalText = formatText(value);

                            sentence+=finalText;

                            $(".outputConversationSection:last").text(sentence);

                            console.log("Output data Data Prepending", sentence);

                            // $(".outputConversationSection:last").prepend(finalText);

                            //$('.new-msg-loader:first .tt-message-text').html(finalText);
                        }
                    } catch (e) {

                        console.log("Failed to access Content : ",e);
                    }
                }

                initScrollToChatBottom();
            };

            TT.eventSource.onerror = function(e) {
                TT.eventSource.close();
            };

            /*
            * Pdf Chat Completion Start With Event Source
            * */
        }


        function formatText(text) {
            return text.replace(/(?:\r\n|\r|\n)/g, '<br>');
        }

        function userHtmltSection(inputText) {
            return `
                    <div class="d-flex justify-content-end mt-4 tt-message-wrap tt-message-me">
                        <div class="d-flex flex-column align-items-end">
                          <div class="d-flex align-items-start">
                            <div class="me-3 p-3 rounded-3 text-end mw-450 tt-message-text">
                                ${inputText}
                            </div>
                            <div class="avatar avatar-md flex-shrink-0">
                              <img
                                    loading="lazy"
                                    class="rounded-circle"
                                    src="assets/img/favicon.png"
                                    alt=" avatar" />
                            </div>
                          </div>
                        </div>
                    </div>`;
        }

        function outputHtmlSection(outputText){
            return `<div class="d-flex justify-content-start mt-4 tt-message-wrap outputConversationSection">
                            <div class="d-flex flex-column align-items-start">
                              <div class="d-flex align-items-start">
                                <div class="avatar avatar-md flex-shrink-0">
                                  <img
                                    loading="lazy"
                                    class="rounded-circle"
                                    src="assets/img/avatar/1.jpg"
                                    alt="avatar" />
                                </div>
                                <div class="ms-3 p-3 rounded-3 text-start mw-650 tt-message-text">
                                    ${outputText}
                                </div>
                              </div>

                            </div>
                          </div>`;
        }


        function loadingLoader(){
            return `<div class="d-flex justify-content-start mt-4 tt-message-wrap mrLoader">
                            <div class="d-flex flex-column align-items-start">
                              <div class="d-flex align-items-start">
                                <div class="avatar avatar-md flex-shrink-0">
                                  <img
                                    class="rounded-circle"
                                    loading="lazy"
                                    src="assets/img/avatar/1.jpg"
                                    alt="avatar" />
                                </div>
                                <div class="ms-3 p-2 rounded-3 text-start mw-450 tt-message-text">
                                  <!-- text preloader start -->
                                  <div class="tt-text-preloader">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                  </div>
                                  <!-- text preloader end -->
                                </div>
                              </div>
                            </div>
                          </div>`;
        }


        function userInputDataMaker(prompt, data){
            let pdfName = data.split("/")[2];

            let pdfLink = `${basePath}/${data}`;

            return `${prompt} </br>
                    <a href='${pdfLink}' target="_blank">
                        <i data-feather="file"></i>
                        ${pdfName}
                    </a>`;
        }
    </script>

@endsection
