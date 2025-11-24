@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Vision') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-flex flex-wrap align-items-center justify-content-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI Vision') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AI Vision') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <div class="d-flex align-items-center">
                                    @if (auth()->user()->user_type != 'customer')
                                        <a href="{{ route('chat.prompts') }}" class="btn btn-accent btn-sm py-2 me-2">
                                            {{ localize('Chat Prompts') }}</a>
                                    @endif

                                    <a href="{{ route('chat.experts') }}" class="btn btn-primary btn-sm py-2">
                                        {{ localize('Browse Experts') }}</a>

                                </div>
                            </div>
                        </div>

                        <div class="d-block d-lg-none mt-3">
                            <button type="button"
                                class="tt-advance-options cursor-pointer form-label cursor-pointer mb-0 btn btn-light shadow-sm btn-sm rounded-pill"><span
                                    class="fw-bold tt-promot-number fw-bold me-1"></span>{{ localize('Show History') }}
                                <span><i data-feather="plus" class="icon-16 text-primary ms-2"></i></span></button>
                            <div class="tt-advance-options-wrapper">
                                <div class="tt-chat-history flex-column d-flex">
                                    <!-- ai Vision history search start -->
                                    <form action="">
                                        <div class="tt-search-box px-2 py-3 border-bottom">
                                            <div class="input-group">
                                                <span class="position-absolute top-50 start-0 translate-middle-y ms-2"><i
                                                        data-feather="search"></i></span>
                                                <input class="form-control-sm rounded-pill w-100 bg-secondary-subtle"
                                                    name="search" placeholder="{{ localize('Type & hit enter') }}..."
                                                    @isset($searchKey)
                                                value="{{ $searchKey }}"
                                                @endisset>
                                            </div>
                                        </div>
                                    </form>
                                    <!-- ai Vision history search end -->

                                    <div class="tt-history-list-wrap tt-custom-scrollbar ai-chat-list">
                                        @include('backend.pages.aiVision.inc.chat-list')
                                    </div>
                                    <div class="mt-auto text-center py-3">
                                        <button
                                            class="tt-custom-link-btn rounded-pill px-3 py-2 bg-transparent border-0 new-conversation-btn"
                                            onclick="startNewConversation({{$chatExpert->id}})">
                                            {{ localize('New Conversation') }}<i data-feather="plus"
                                                class="icon-14 ms-1"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <x-open-ai-error-message/>
                @if($supportedModelMessage)
                    <div class="col-lg-12 mt-2">
                        <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                            {{$supportedModelMessage}}                          
                        </div>
                    </div>
                @endif
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body p-0">
                            <div id="tt-ai-chat" class="d-flex" style="height: 65vh;">
                                

                                <!-- chat right box start -->

                                <!-- chat right with preloader start -->
                                <div class="tt-chat-right d-flex w-100 d-none list-and-messages-wrapper-loader">
                                    <div class="tt-text-preloader tt-preloader-center">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </div>

                                <div class="tt-chat-right tt-custom-scrollbar d-flex w-100 list-and-messages-wrapper">
                                    @include('backend.pages.aiVision.inc.chat-right')
                                </div>
                                <!-- chat right box end -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
@endsection


@section('scripts')
    @include('backend.pages.aiVision.inc.chat-scripts')

@endsection
