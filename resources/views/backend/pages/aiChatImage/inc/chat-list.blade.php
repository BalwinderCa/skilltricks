<ul class="tt-chat-history-list list-unstyled">
    @forelse ($chatList as $chat)
        <li>
            <a href="javascript:void(0);" class="d-flex {{ $loop->iteration == 1 ? 'active' : '' }}"
                onclick="getMessagesOfConversation(this, {{ $chat->id }})" data-id="{{ $chat->id }}">
                <span><i data-feather="message-square" class="icon-16 me-2 text-muted"></i></span>
                <span>
                    <p class="mb-0 tt_update_text" data-id={{ $chat->id }} data-name="chat-title-{{ $chat->id }}">
                        {{ $chat->title }}</p>
                    <small class="fst-italic text-muted">{{ $chat->updated_at->diffForHumans() }}</small>
                </span>
            </a>
            <!-- edit and delete -->
            <div class="tt-history-action position-absolute">
                <button type="button" class="border-0 bg-light rounded-circle tt_editable"
                    data-name="chat-title-{{ $chat->id }}">
                    <i data-feather="edit-3" width="14"></i>
                </button>

                <button type="button" class="border-0 bg-soft-danger rounded-circle" onclick="confirmDelete(this)"
                    data-href="{{ route('chat.delete', $chat->id) }}">
                    <i data-feather="trash" width="14"></i>
                </button>
            </div>
            <!-- edit and delete -->
        </li>
    @empty
        <li>
            <div class="text-center pt-5">
                <span class="badge bg-soft-danger rounded-pill p-2 px-3">{{ localize('No conversation found') }}</span>
            </div>
        </li>
    @endforelse
</ul>
