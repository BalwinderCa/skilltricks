@extends('backend.layouts.master')


@section('title')
    {{ localize('Add New Prompt') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Add New Prompt') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('chat.prompts') }}">{{ localize('Prompts') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Add Prompt') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                                @if (auth()->user()->user_type != 'customer')
                                    <a href="javascript:void(0);" class="btn btn-sm btn-accent me-2"
                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasForm"><i
                                            data-feather="plus"></i>
                                        {{ localize('Add Prompt Group') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ route('chat.storePrompt') }}" method="POST" class="pb-650">
                        @csrf
                        <!--basic information start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>

                                <div class="mb-3">
                                    <label for="title" class="form-label">{{ localize('Prompt Title') }} <span
                                            class="text-danger">*</span> </label>
                                    <input type="text" name="title" id="title"
                                        placeholder="{{ localize('Type prompt title') }}" class="form-control" required>
                                </div>

                                <div class="mb-4">
                                    <label for="ai_chat_prompt_group_id" class="form-label">{{ localize('Group') }} <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control select2" name="ai_chat_prompt_group_id"
                                        data-toggle="select2" required>
                                        <option value="">{{ localize('Select a group') }}</option>
                                        @foreach ($promptGroups as $group)
                                            <option value="{{ $group->id }}">
                                                {{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <label class="form-label me-2">{{ localize('Prompt') }} <span
                                                class="text-danger">*</span></label>
                                        <a href="javascript:void(0);"
                                            class="btn btn-primary rounded-pill btn-sm py-1 promptTestBtn">
                                            {{ localize('Generate Test Prompt') }}</a>
                                    </div>

                                    <textarea class="form-control prompt" name="prompt" id="prompt" rows="4"
                                        placeholder="{{ localize('Write a blog about [blog title]') }}" required></textarea>
                                    <small>*{{ localize('Put your variables inside third bracket: Write a blog about [blog title]') }}</small>
                                </div>

                            </div>
                        </div>
                        <!--basic information end-->


                        <!-- submit button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i> {{ localize('Save Prompt') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- submit button end -->

                    </form>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar d-none d-xl-block">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Prompt Information') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('Basic Information') }}</a>
                                    </li>
                                </ul>
                            </div>
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

@section('scripts')
    @include('backend.pages.aiChat.inc.chat-scripts')
@endsection
