<div class="tt-chat-history flex-column d-lg-flex">
    <div class="tt-history-list-wrap tt-custom-scrollbar">
        <ul class="tt-chat-history-list list-unstyled pdfChatLi">

            @include("backend.pages.pdfChat.pdf-chat-li")
        </ul>
    </div>
    <div class="mt-auto text-center py-3">
        <button
            type="button"
            class="tt-custom-link-btn rounded-pill pdfNewChat px-3 py-2 bg-transparent border-0">
            {{ localize("New Conversation") }} <i data-feather="plus" class="icon-14 ms-1"></i>
        </button>
    </div>
</div>
