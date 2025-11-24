@extends('backend.layouts.master')

@section('title')
    {{ localize('AI PDF Chat') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-flex flex-wrap align-items-center justify-content-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI PDF Chat') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a href="#">  {{ localize('Home') }} </a></li>
                                    <li class="breadcrumb-item">{{ localize('AI PDF Chat') }}</li>
                                </ol>
                            </div>
                        </div>

                        <div class="d-block d-lg-none mt-3">
                            <button
                                class="form-label tt-advance-options cursor-pointer mb-0 btn btn-light shadow-sm btn-sm rounded-pill">
                                <span class="fw-bold tt-promot-number fw-bold me-1"></span>
                                {{ localize('Show History') }}
                                <span>
                                    <i data-feather="plus" class="icon-16 text-primary ms-2"></i>
                                </span>
                            </button>

                            <div id="tt-advance-options">
                                @include("backend.pages.pdfChat.pdfChatLeftSidebar")
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
                                <!-- chat right box start -->
                                <div class="tt-chat-right d-flex w-100 rip123">
                                    <div class="desktopSidebar">
                                        @include("backend.pages.pdfChat.pdfChatLeftSidebar")
                                    </div>

                                    <div class="w-100 d-flex flex-column">
                                        @include("backend.pages.pdfChat.chat-top")

                                        <!-- chat conversation start -->
                                        <div class="tt-conversation p-4 tt-custom-scrollbar convo123">
                                            @include("backend.pages.pdfChat.pdf-chat-conversations")
                                        </div>

                                        @include("backend.pages.pdfChat.form-pdf")
                                    </div>

                                    <!-- for preloader -->
                                    <div class="tt-chat-right d-flex w-100 d-none">
                                        <div class="tt-content-placeholder bg-secondary-subtle">
                                            <div class="tt-preloader-wrap">
                                                <span class="tt-preloader-bar"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- chat right box end -->
                                </div>
                                <!-- chat right box end -->

                                <!-- chat right with preloader start -->
                                <div class="tt-chat-right d-flex w-100 d-none">
                                    <!-- text preloader start -->
                                    <div class="tt-text-preloader tt-preloader-center">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                    <!-- text preloader end -->
                                </div>
                                <!-- chat right with preloader end -->
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PDF Chat Delete Modal --}}
                @include("backend.pages.pdfChat.pdf-chat-delete")

            </div>
        </div>
    </section>
@endsection

@section("css")
    <style>
        .desktopSidebar {
            overflow: hidden;
            overflow-y: scroll;
            display: flex;
        }
    </style>
@endsection


@section('scripts')

    <script>
        "use strict";

        let width = window.innerWidth;

        $(()=>{
            if(width <= 991){
                $(".desktopSidebar").hide();
            }
        })

        // show hide templates optional field
        $("#tt-advance-options").hide();

        $(".tt-advance-options").on("click", function(e) {

            $("#tt-advance-options").slideToggle(300);
        });

        let basePath = "{{ url('/') }}";
        let messageContainer = $(".convo123");
        let loaderMessage = $(".mrLoader");
        let outputDiv = $('.convo123');
        let sentence = '';
        let form = document.getElementById("pdfChatForm");


        $(()=>{
            initScrollToChatBottom();
        });

        $("#pdf-delete-modal").on("hidden.bs.modal", function () {
            $(".erase").attr("data-id", null);
        });

        $(".pdfNewChat").click(async function () {

            $('#vision_image').html(null);
            $("#tt-advance-options").slideToggle(300);
            await $.ajax({
               "url": "{{ route('pdfChat.index') }}?newChat=true",
               "type": "GET",
               "success": function (response) {
                   console.log("Success  is : ",response);
                   $(".pdfChatLi").html(null);

                   $(".pdfChatLi").html(response.data);

                   $(".convo123").html("");

                   initFeather();

                   makeFormEmpty();
               },
               "error": function (XHR, textStatus, errorThrown) {
                   console.log("Error is : ",XHR);
               }
            });
        })

        function makeFormEmpty(){
            $("#prompt").val(null);
            $("#pdfFile").val(null);
        }

        function deleteModalShow(id){
            $("#pdf-delete-modal").modal("show");
            $(".erase").attr("data-id",id);
        }

        $(document).on("click", ".erase", function () {
            console.log("Clicked on Erase Button.");
            let id = $(this).attr("data-id");
           deletePdfChat(id);
        });

        async function deletePdfChat(id){

            $('#vision_image').html(null);

            await $.ajax({
                "type": "POST",
                "url": "{{ route('pdfChat.destroy') }}",
                "data" : {
                    _token : "{{ csrf_token() }}",
                    pdf_chat_id : id
                },
                "success": function (response) {
                    console.log("Delete Success  is : ",response);
                    $(".pdfChatLi").html(response.data);
                    $("#pdf-delete-modal").modal("hide");
                },
                "error": function (XHR, textStatus, errorThrown) {

                    console.log("Error is : ",XHR);
                    const error = XHR.responseJSON;

                    notifyMe("error",error.message);
                }
            });

            $("#pdf-delete-modal").modal("hide");

            initFeather();
        }


        async function loadPdfChat(id){
            $('#vision_image').html(null);

            await $.ajax({
                "type": "GET",
                "url": "{{ route('pdfChat.index') }}?load_pdf_chat="+id,
                "success": function (response) {
                    console.log("Conversation is : ",response);
                    $(".convo123").html(response.data);
                },
                "error": function (XHR, textStatus, errorThrown) {
                    console.log("Error is : ",XHR);
                }
            });
        }

        $(document).on('submit', '#pdfChatForm', function (e) {
            e.preventDefault();

            let formData = new FormData(form);
            let promptValue = $('#prompt').val();
            let pdfFile = $('#pdfFile')[0].files[0];

            if(promptValue == '') {
                notifyMe("error",'Text Field Empty .Please write something');
                return;
            }
            if($('#pdfFile')[0].files.length === 0) {
                notifyMe("error",'Please upload pdf');
                return;
            }
            formData.append('prompt', promptValue);
            formData.append('pdfFile', pdfFile);
            formData.append('_token', "{{ csrf_token() }}");

           

            $('#vision_image').html(null);

            try {
                $.ajax({
                    url: "{{ route('pdfChat.pdfChatEmbedding') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                         $('.msg-send-btn').prop('disabled', true);
                    },
                    success: function (response) {
                        $(".msg-send-btn").prop("disabled", false);

                        $('#prompt').val(null);

                        makeFormEmpty();


                        console.log("Server Response is : ", response);

                        try{
                            let promptData = userInputDataMaker(promptValue, response.data);

                            // Loader Assign.
                            messageContainer.append(userHtmltSection(promptData));
                            messageContainer.append(loadingLoader);
                            initScrollToChatBottom();
                            // Loader Assign.
                            messageContainer.prepend(loadingLoader);
                            chatCompletionEventSource();
                          

                        }
                        catch (e) {
                            $('.msg-send-btn').prop('disabled', false);
                            console.log("Embedding Error is Here : ",e);
                        }

                    }, // Success Close Here
                    error: function (XHR, textStatus, error) {
                        hideLoader();
                        $('.msg-send-btn').prop('disabled', false);
                        const jsonData = JSON.parse(XHR.responseText);

                        console.log("Error Json Data : ", jsonData);

                        notifyMe("error", jsonData.message);

                        console.log("XHR Response is : ", XHR);
                        console.log("Error Status Response is : ", textStatus);
                        console.log("XHR Response is : ", XHR);
                        // Handle the error response here

                        const jsonResponse = jsonData;

                        // Display errors for all properties in the data object
                        displayErrors(jsonResponse.data);
                    }
                });
            } catch (e) {
                $('.msg-send-btn').prop('disabled', false);
                console.log("Catch Error : ", e)
            }

        });

        function hideLoader(){
            $(".mrLoader").addClass("d-none");
        }


        // Function to dynamically iterate over properties and display errors
        function displayErrors(data) {
            if (typeof data === 'object' && data !== null) {
                for (const key in data) {
                    const errors = data[key];
                    if (Array.isArray(errors) && errors.length > 0) {
                        console.log(`${key} Errors:`);
                        errors.forEach(error => {
                            console.log(error);
                            notifyMe('error', "Validation Error : "+error);
                        });
                    } else if (typeof data[key] === 'object') {
                        displayErrors(data[key]);
                    }
                }
            }
        }


        // Forcefully stop generating content
        $(document).on('click', '.btn-stop-content', function(e) {
            e.preventDefault();
            if (TT.eventSource) {
                TT.eventSource.close();
            }
            resetGenerateButton();
            notifyMe('info', '{{ localize('Ai Pdf Chat has been stopped.') }}');
        });

        function resetGenerateButton() {
            $('.new-msg-loader').first().removeClass('new-msg-loader');
            $('.msg-send-btn').prop('disabled', false);
            $('.btn-stop-content').prop('disabled', true);
            initFeather();
        }


        function chatCompletionEventSource() {
            /*
             * Pdf Chat Completion Start With Event Source
             * */

            let urlEvent = "{{ route('pdfChat.pdfChatCompletion') }}";

            console.log("urlEvent : ", urlEvent);

            TT.eventSource = new EventSource(`${urlEvent}`, {
                withCredentials: true
            });

            let finalText = '';

            hideLoader();

            messageContainer.append(outputHtmlSection(finalText));

            TT.eventSource.onmessage = function (e) {
                //  $(".mrLoader").addClass("d-none");
                console.log("Response From Chat Completion", e);

                if (e.data.indexOf("[DONE]") !== -1) {
                    // $('.new-msg-loader').first().removeClass('new-msg-loader');
                    $('.msg-send-btn').prop('disabled', false);
                    $('.btn-stop-content').prop('disabled', true);
                    TT.eventSource.close();
                }
                else {

                    $('.btn-stop-content').prop('disabled', false);
                    let txt = undefined;
                    try {
                        txt = JSON.parse(e.data).choices[0].delta.content ?  JSON.parse(e.data).choices[0].delta.content : undefined;
                        if (txt !== undefined) {
                            console.log("Streaming Text is : ", txt);
                            let oldValue = '';

                            let value = oldValue + txt;
                            finalText = formatText(value);

                            initScrollToChatBottom();

                            sentence += finalText;

                           // let finalSentence = decodeHtmlEntities(sentence);
                            let finalSentence = sentence;

                            $(".outputText:last").html(finalSentence);

                            console.log("Output data Data Prepending", sentence);
                            initScrollToChatBottom();

                        }
                    } catch (e) {

                        // notifyMe('error', "Catch Error : "+e);

                     //   notifyMe("error",e);
                        console.log("Failed to access Content : ", e);
                    }

                }

                initScrollToChatBottom();
            };

            TT.eventSource.onerror = function (e) {
                TT.eventSource.close();
            };

            /*
            * Pdf Chat Completion Start With Event Source
            * */
        }


        function decodeHtmlEntities(input) {
            var doc = new DOMParser().parseFromString(input, "text/html");
            return doc.documentElement.textContent;
        }

        function formatText(text) {
            return text.replace(/(?:\r\n|\r|\n)/g, '<br>');
        }


        document.getElementById('pdfFile').addEventListener('change', function () {

            // Clear any previous content in the vision_image element
            let fileNameDisplay = document.getElementById('vision_image');
            fileNameDisplay.innerHTML = '';

            // Get the selected file
            let file = this.files[0];

            // Update the path to the PDF icon accordingly
            let pdfSvg = "{{ asset('public/backend/pdfChat/pdf-icon.svg') }}";

            // Check if a file is selected
            if (file) {
                // Display the selected PDF file's name along with an icon
                fileNameDisplay.innerHTML = 'Selected PDF: ' + file.name + ' <img src="' + pdfSvg + '" loading="lazy" alt="Icon Not Found." />';
            } else {
                // Display a message if no PDF is selected
                fileNameDisplay.innerHTML = 'No PDF selected';
            }
        });

        let userAvatar = '{{ userAvatar() }}';

        function userHtmltSection(inputText) {

            console.log("Avatar is : ", userAvatar);
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
                                    src="${userAvatar}"
                                    alt=" avatar" />
                            </div>
                          </div>
                        </div>
                    </div>`;
        }

        function outputHtmlSection(outputText) {
            return `<div class="d-flex justify-content-start mt-4 tt-message-wrap outputConversationSection">
                            <div class="d-flex flex-column align-items-start">
                              <div class="d-flex align-items-start">
                                <div class="avatar avatar-md flex-shrink-0">
                                  <img
                                    loading="lazy"
                                    class="rounded-circle"
                                    src="{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}"
                                    alt="avatar" />
                                </div>
                                <div class="ms-3 p-3 rounded-3 text-start mw-650 tt-message-text outputText">
                                    ${outputText}
                                </div>
                              </div>

                            </div>
                          </div>`;
        }


        const loadingLoader = `<div class="d-flex justify-content-start mt-4 tt-message-wrap mrLoader">
                            <div class="d-flex flex-column align-items-start">
                              <div class="d-flex align-items-start">
                                <div class="avatar avatar-md flex-shrink-0">
                                  <img
                                    class="rounded-circle"
                                    loading="lazy"
                                    src="{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}"
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


        function userInputDataMaker(prompt, data) {
            //let pdfName = data.pdf_file ? data.pdf_file.split("/")[2] :  data.data.pdf_file.split("/")[2];
            let pdfName = data.pdf_file.split("public/uploads/pdfChats/")[1];

            console.log("PDF Name : ", pdfName);

            let pdfLink = `${basePath}/${data.pdf_file}`;

            let pdfSvg = `${basePath}/public/backend/pdfChat/pdf-icon.svg`;

            return `${prompt} </br>
                    <a href='${pdfLink}' target="_blank">
                        <img src="${pdfSvg}" alt="XYZ Online" />
                        ${pdfName}
                    </a>`;
        }
    </script>

@endsection
