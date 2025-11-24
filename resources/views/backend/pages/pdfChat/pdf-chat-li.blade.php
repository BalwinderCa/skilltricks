@forelse($pdfChats as $key=>$pdfChat)

    <li>
        <a href="#" class="d-flex {{ $loop->first ? 'active' : '' }}" onclick="loadPdfChat({{ $pdfChat->id }})">
            <span><i data-feather="message-square" class="icon-16 me-2 text-muted"></i></span>
            <span>
                  <p class="mb-0 tt_update_text" data-no="title-edit">
                    {{ localize("Chat ID : ") }} : {{ $pdfChat->chat_code}}
                  </p>
                  <small class="fst-italic text-muted">{{ $pdfChat->created_at->diffForHumans() }}</small>
            </span>
        </a>
        <!-- edit and delete -->
        <div class="tt-history-action position-absolute">
            <button type="button"
                    onclick="deleteModalShow({{ $pdfChat->id }})"
                    class="border-0  bg-soft-danger rounded-circle">
                <i data-feather="trash" width="14"></i>
            </button>
        </div>
        <!-- edit and delete -->
    </li>
@empty
@endforelse
