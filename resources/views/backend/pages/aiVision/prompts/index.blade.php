@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Chat Prompts') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI Chat Prompts') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AI Chat Prompts') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                @if (auth()->user()->user_type != 'customer')
                                    <a href="javascript:void(0);" class="btn btn-sm btn-accent me-2"
                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasForm"><i
                                            data-feather="plus"></i>
                                        {{ localize('Add Prompt Group') }}</a>
                                    <a href="{{ route('chat.createPrompt') }}" class="btn btn-sm btn-primary"><i
                                            data-feather="plus"></i> {{ localize('Add Chat Prompts') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3 g-3">
                <div class="col-xl-12">
                    <div class="card h-100 bg-secondary-subtle">
                        <div class="card-header sticky-top-card bg-secondary py-lg-5 py-4">
                            <!-- template search -->
                            <form action="{{ Request::fullUrl() }}" method="GET">
                                <div class="row justify-content-center">
                                    <div class="col-lg-6 col-md-8">
                                        <div class="input-group">
                                            <input type="search"
                                                placeholder="{{ localize('Search prompt that you are looking for') }}..."
                                                name="search"
                                                @isset($searchKey)
                                        value="{{ $searchKey }}"
                                    @endisset
                                                class="form-control border border-2 border-primary rounded-pill rounded-end">
                                            <div class="input-group-append">
                                                <button type="submit"
                                                    class="btn btn-link bg-primary border border-2 border-primary text-light rounded-pill rounded-start"><i
                                                        class="flaticon-search translate-middle-y"></i>{{ localize('Search Now') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="d-block">
                                <ul class="nav nav-pills d-flex align-items-center justify-content-center tt-horizontal-tab tt-prompt-group-list mt-3 gap-2 nav-tabs-dropdown"
                                    id="pills-tab" role="tablist">

                                    <li class="nav-item d-flex align-items-center" role="presentation">
                                        <a class="nav-link active" id="tabId-all"data-bs-toggle="pill"
                                            data-bs-target="#all-tab" type="button" role="tab" aria-controls="all-tab"
                                            aria-selected="true" href="#">
                                            {{ localize('All Prompts') }}
                                        </a>
                                    </li>

                                    @foreach ($promptGroups as $group)
                                        <li class="nav-item d-flex align-items-center" role="presentation">
                                            <a class="nav-link" id="tabId-{{ $group->slug }}" data-bs-toggle="pill"
                                                data-bs-target="#tab-{{ $group->slug }}" type="button" role="tab"
                                                aria-controls="tab-{{ $group->slug }}" aria-selected="true"
                                                href="#">
                                                {{ localize($group->name) }}</a>

                                            <div class="d-flex align-items-center tt-prompt-action gap-2">
                                                <div class="tt-prompt-action-item"
                                                    onclick="handleEditGroup({{ $group->id }})"><i data-feather="edit-3"
                                                        class="text-primary icon-14"></i>
                                                </div>
                                                <a class="tt-prompt-action-item border-0"
                                                    data-href="{{ route('chat.deletePromptGroup', $group->id) }}"
                                                    onclick="confirmDelete(this)"><i data-feather="trash"
                                                        class="text-danger icon-14"></i>
                                                </a>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="tab-content card-body" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="all-tab" role="tabpanel"
                                aria-labelledby="tabId-all" tabindex="0">
                                <div class="row g-3">

                                    @foreach ($prompts as $prompt)
                                        <div class="col-lg-3 col-sm-6">
                                            @include('backend.pages.aiChat.prompts.inc.prompt-card', [
                                                'prompt' => $prompt,
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                            </div>


                            <!-- loop -->
                            @foreach ($promptGroups as $group)
                                <div class="tab-pane fade" role="tabpanel" aria-labelledby="tabId-{{ $group->slug }}"
                                    id="tab-{{ $group->slug }}">
                                    <div class="row g-3">
                                        @foreach ($prompts as $prompt)
                                            @if ($prompt->ai_chat_prompt_group_id == $group->id)
                                                <div class="col-lg-3 col-sm-6">
                                                    @include(
                                                        'backend.pages.aiChat.prompts.inc.prompt-card',
                                                        [
                                                            'prompt' => $prompt,
                                                        ]
                                                    )
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- Offcanvas -->
        <div class="offcanvas offcanvas-end" id="offcanvasForm" tabindex="-1">
            <div class="group-form">
                @include('backend.pages.aiChat.prompts.inc.group-form')
            </div>
        </div>
    </section>
@endsection
