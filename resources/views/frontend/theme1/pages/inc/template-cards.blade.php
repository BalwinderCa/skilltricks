<div class="col-sm-6 col-lg-4 col-xxl-3">
    <div class="ai-template-card text-center text-xl-start d-flex flex-column h-100">
        <div class="ai-template-card-icon">
            <i class="bi bi-stars"></i>
        </div>
        <h4 class="clr-neutral-90 fs-18 mt-6">
            {{ $template->collectLocalization('name') }}</h4>
        <p class="clr-neutral-80 fs-14">
            {{ $template->collectLocalization('description') ?? '' }}</p>
        <div
            class="d-flex align-items-center justify-content-between flex-wrap gap-4 mt-auto position-relative z-1">
            <span class="d-block clr-neutral-70 fs-12"> 
                @auth
                    @if (auth()->user()->user_type != 'customer')
                        {{ formatWords($template->total_words_generated) }}
                    @else
                        {{ formatWords($template->templateUsage()->where('user_id', auth()->user()->id)->sum('total_used_words')) }}
                    @endif
                    {{ localize('Words Generated') }}

                @endauth
                @guest
                    {{ formatWords($template->total_words_generated) }}
                    {{ localize('Words Generated') }}
                @endguest
            </span>
                @auth
                <div class="d-flex align-items-center justify-content-md-end gap-2">
                    @php
                        $iconClass = in_array($template->id, $favoritesArray)
                            ? 'bi bi-heart-fill'
                            : 'bi bi-heart';
                    @endphp
                    <button type="button" class="btn ai-card-btn favorite-template" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ localize('Favorite') }}"
                    data-template="{{ $template->id }}">
                        <i class="{{ $iconClass }}"></i>
                    </button>
                    
                    @if (auth()->user()->user_type != 'customer')
                        <a href="{{ route('templates.edit', ['id' => $template->id, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}"
                            class="btn ai-card-btn"
                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ localize('Edit') }}">
                            <i class="bi bi-pencil-fill"></i>
                        </a>

                        <a href="javascript:void(0);"
                            class="btn ai-card-btn"
                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ localize('Delete') }}"
                            data-template="{{ $template->id }}" data-href="{{ route('templates.delete', $template->id) }}"
                            onclick="confirmDelete(this)">
                            <i class="bi bi-eye-fill"></i>
                        </a>
                    @endif
                </div>
                @endauth
            </div>
    </div>
</div>