<div class="tt-single-template d-flex flex-column h-100 position-relative">
    <div class="card flex-column h-100 tt-template-card tt-corner-shape border-0">

        <div class="card-body d-flex flex-column h-100">
            <div class="tt-card-info mb-4">
                <h3 class="h6">{{ $prompt->title }}</h3>
                <p class="mb-0">{{ $prompt->prompt ?? '' }}
                </p>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-end template-actions">
            @auth
                @if (auth()->user()->user_type != 'customer')
                    <a href="{{ route('chat.editPrompt', ['id' => $prompt->id, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}"
                        class="border-0 px-1 bg-transparent tt-template-edit position-absolute d-flex align-items-center edit-template"
                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ localize('Edit') }}">
                        <i data-feather="edit-3" class="icon-14 text-primary"></i>
                    </a>

                    <a href="javascript:void(0);"
                        class="border-0 px-1 bg-transparent tt-template-edit position-absolute d-flex align-items-center delete-template"
                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ localize('Delete') }}"
                        data-template="{{ $prompt->id }}" data-href="{{ route('chat.deletePrompt', $prompt->id) }}"
                        onclick="confirmDelete(this)">
                        <i data-feather="trash" class="icon-14 text-danger"></i>
                    </a>
                @endif
            @endauth
        </div>
    </div>
</div>
