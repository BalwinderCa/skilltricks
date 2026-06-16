<div class="tt-single-template d-flex flex-column h-100 position-relative">
    <div
        class="card flex-column h-100 tt-template-card tt-corner-shape border-0 @if (Auth::check() && isCustomer() && !in_array($template->id, $subscriptionTemplates)) tt-is-inactive @endif">

        @auth
            @php
                $user = auth()->user();
            @endphp
            @if (isCustomer())
                @if (!in_array($template->id, $subscriptionTemplates))
                    <span class="text-danger tt-inactive-plan" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="{{ localize('This template not included in your subscription plan') }}"><i
                            data-feather="info" class="icon-14"></i></span>
                @endif
            @endif
        @endauth

        <a @if (Auth::check() && isCustomer() && !in_array($template->id, $subscriptionTemplates)) href="javascript::void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-title="{{ localize('This template is not included in your subscription plan') }}" @else href="{{ route('templates.show', $template->code) }}" @endif
            class="card-body d-flex flex-column h-100">
            <div class="tt-card-info mb-4">
                <div class="tt-template-icon mb-3">
                    @if ($template->icon == null)
                        <img src="{{ staticAsset('backend/assets/img/templates/' . $template->code . '.png') }}"
                            alt="" class="img-fluid">
                    @else
                        <img src="{{ uploadedAsset($template->icon) }}" alt="" class="img-fluid">
                    @endif
                </div>
                <h3 class="h6">{{ $template->collectLocalization('name') }}</h3>
                <p class="mb-0">{{ $template->collectLocalization('description') ?? '' }}
                </p>
            </div>
            <div class="mt-auto">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fs-md text-muted d-block">
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
                </div>
            </div>
        </a>

        <div class="d-flex align-items-center justify-content-end template-actions">
            @auth
                <button
                    class="border-0 bg-transparent tt-template-edit position-absolute d-flex align-items-center favorite-template"
                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ localize('Favorite') }}"
                    data-template="{{ $template->id }}">
                    <i
                        class="{{ in_array($template->id, $favoritesArray) ? 'las' : 'lar' }}  la-heart fs-lg {{ in_array($template->id, $favoritesArray) ? 'text-success' : '' }}"></i>
                </button>

                @if (auth()->user()->user_type != 'customer')
                    <a href="{{ route('templates.edit', ['id' => $template->id, 'lang_key' => config('custom.default_language')]) }}"
                        class="border-0 px-1 bg-transparent tt-template-edit position-absolute d-flex align-items-center edit-template"
                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ localize('Edit') }}">
                        <i data-feather="edit-3" class="icon-14 text-primary"></i>
                    </a>

                    <a href="javascript:void(0);"
                        class="border-0 px-1 bg-transparent tt-template-edit position-absolute d-flex align-items-center delete-template"
                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ localize('Delete') }}"
                        data-template="{{ $template->id }}" data-href="{{ route('templates.delete', $template->id) }}"
                        onclick="confirmDelete(this)">
                        <i data-feather="trash" class="icon-14 text-danger"></i>
                    </a>
                @endif
            @endauth
            @guest
                <a class="border-0 bg-transparent tt-template-edit position-absolute d-flex align-items-center"
                    href="{{ route('login') }}">
                    <i class="lar la-heart fs-lg"></i>
                </a>
            @endguest
        </div>
    </div>
</div>
