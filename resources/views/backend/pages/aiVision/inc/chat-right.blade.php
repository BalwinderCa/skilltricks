<div class="tt-chat-history d-flex flex-column d-none d-lg-flex">
    <!-- chat start -->
    <div class="tt-search-box px-2 py-3 border-bottom  d-none d-lg-block">
        <form action="">
            <div class="input-group">
                <span class="position-absolute top-50 start-0 translate-middle-y ms-2"><i
                        data-feather="search"></i></span>
                <input class="form-control-sm rounded-pill w-100 bg-secondary-subtle" type="text" name="search"
                    placeholder="{{ localize('Type & hit enter') }}..."
                    @isset($searchKey)
                    value="{{ $searchKey }}"
                    @endisset>
            </div>
        </form>
    </div>

    <div class="tt-history-list-wrap tt-custom-scrollbar ai-chat-list  d-none d-lg-block ">
        @include('backend.pages.aiVision.inc.chat-list')
    </div>
    <!-- chat end -->

    <div class="mt-auto text-center py-3 d-none d-lg-block">
        <button class="btn fw-medium tt-custom-link-btn rounded-pill px-3 py-2 new-conversation-btn"
            onclick="startNewConversation({{$chatExpert->id}})">
            {{ localize('New Conversation') }}<i data-feather="plus" class="icon-14 ms-1"></i>
        </button>
    </div>
</div>

<!-- messages -->
<div class="w-100 d-flex flex-column messages-container h-100">
    @include('backend.pages.aiVision.inc.messages-container')
</div>
<!-- messages -->

<!-- messages preloader start -->
<div class="w-100 d-flex h-100 align-items-center justify-content-center messages-container-loader d-none">
    <div class="tt-text-preloader">
        <span></span>
        <span></span>
        <span></span>
    </div>
</div>
<!-- messages preloader end -->
