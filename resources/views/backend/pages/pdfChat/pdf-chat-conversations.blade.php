@php
    $conversations = !empty($myLastPdfChat) ? $myLastPdfChat->conversations : [];
@endphp

@forelse($conversations as $pdfChatConversation)

    <!-- User Input Prompt Start -->
    <div class="d-flex justify-content-end mt-4 tt-message-wrap tt-message-me">
        <div class="d-flex flex-column align-items-end">
            <div class="d-flex align-items-start">
                <div class="me-3 p-3 rounded-3 text-end mw-450 tt-message-text">
                    {{ $pdfChatConversation->prompt }}

                    @if(!empty($pdfChatConversation->pdf_file))
                        <a href="{{ asset($pdfChatConversation->pdf_file) }}" class="d-block" target="_blank">
                            <img
                                src="{{ staticAsset('/backend/pdfChat/pdf-icon.svg') }}"
                                loading="lazy"
                                alt="Icon Not Found" />
                            @php
                                $explodeFileName = explode("public/uploads/pdfChats/",$pdfChatConversation->pdf_file);
                            @endphp
                            {{ isset($explodeFileName[1]) ? $explodeFileName[1] :  null }}
                        </a>
                    @endif

                </div>
                <div class="avatar avatar-md flex-shrink-0">

                    <img class="rounded-circle"
                         src="{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}"
                         alt="Icon Not Found"/>
                </div>
            </div>
        </div>
    </div>
    <!-- User Input Prompt End -->

    @if(!empty($pdfChatConversation->ai_response))
        <!-- Ai Response Start  -->
        <div class="d-flex justify-content-start mt-4 tt-message-wrap">
            <div class="d-flex flex-column align-items-start">
                <div class="d-flex align-items-start">
                    <div class="avatar avatar-md flex-shrink-0">
                        <img class="rounded-circle"
                             src="{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}"
                             loading="lazy"
                             alt="avatar"/>
                    </div>
                    <div
                        class="ms-3 p-3 rounded-3 text-start mw-650 tt-message-text">
                        {{ $pdfChatConversation->ai_response }}
                    </div>
                </div>

            </div>
        </div>
        <!-- Ai Response End  -->
    @endif
@empty

@endforelse
