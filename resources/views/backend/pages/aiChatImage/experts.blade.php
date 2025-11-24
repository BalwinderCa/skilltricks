@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Chat Experts') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI Chat Experts') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AI Chat Experts') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action d-flex">
                                @if (auth()->user()->user_type != "customer")
                                <a href="{{ route('chat.prompts') }}" class="btn btn-sm btn-success me-2" target="__blank"><i
                                    data-feather="plus"></i> {{ localize('Add Chat Prompts') }}</a>
                                    <a href="{{ route('chat.createExpert') }}" class="btn btn-sm btn-primary"><i
                                        data-feather="plus"></i> {{ localize('Add Chat Expert') }}</a>
                                        
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div class="card flex-column h-100">
                        <div class="card-header p-3 p-md-4 p-lg-5">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-lg-8 col-md-9 col-12">
                                    <!-- image generate form -->
                                    <form action="" class="header-search-form">
                                        <!-- image generate form -->
                                        <div class="input-group">
                                            <input type="search" name="search"
                                                placeholder="{{ localize('Search expertise you are looking for') }}..."
                                                class="form-control border border-2 border-primary rounded-pill rounded-end"
                                                @isset($searchKey)
                                                value="{{ $searchKey }}"
                                                @endisset>
                                            <div class="input-group-append">
                                                <button type="submit"
                                                    class="btn btn-link bg-primary border border-2 border-primary text-light rounded-pill rounded-start"><i
                                                        class="flaticon-search translate-middle-y"></i>{{ localize('Search Experts') }}</button>
                                                        
                                                    
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column h-100">
                            <div class="row g-3">

                                @foreach ($chatExperts as $expert)
                                    <div class="col-lg-3 col-md-6">
                                        <div class="tt-single-expert  p-3 rounded-3">
                                            <a href="{{ route('chat.index') }}?expert={{ $expert->id }}"
                                                class="d-flex align-content-center">
                                                <div class="avatar avatar-md">
                                                    @if($expert->avatar == null)
                                                    <img class="rounded-circle" src="{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}"
                                                    alt="avatar" />
                                                    @else
                                                    <img class="rounded-circle" src="{{ (int)$expert->avatar == 0 ? staticAsset($expert->avatar) : uploadedAsset($expert->avatar) }}"
                                                    alt="avatar" />
                                                    @endif
                                                    
                                                </div>
                                                <div class="tt-expert-info ms-2">
                                                    <h6 class="mb-0">{{ $expert->name }}</h6>
                                                    <p class="text-muted mb-0 small text-capitalize">{{ $expert->role }}
                                                    </p>
                                                </div>
                                            </a>
                                            @if (auth()->user()->user_type != "customer")
                                                <div class="tt-expert-chat position-absolute">
                                                    <div class="d-flex align-items-center">
                                                        <div class="d-flex align-items-center"> 
                                                            <div class="form-check form-switch me-2">
                                                                <input type="checkbox" class="form-check-input cursor-pointer tt_editable" onchange="updateChat({{ $expert->id }})"
                                                                    id="is_active-{{ $expert->id }}" data-name="is_active-{{ $expert->id }}"
                                                                @if ($expert->is_active == 1)
                                                                    checked
                                                                @endif    
                                                                >
                                                            </div> 
                                                        </div>  

                                                        <a href="{{ route('chat.editExpert', $expert->slug) }}">
                                                            <span>
                                                                <i data-feather="edit-3" class="icon-16"></i>
                                                            </span>
                                                        </a> 
                                                    </div>  
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </section>
@endsection


@section('scripts')
    @include('backend.pages.aiChat.inc.chat-scripts')

    <script>
        "use strict";

        // update chat
        function updateChat(id) {
            let result = $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('chat.updateStatus') }}',
                data: {
                    id:id
                },
                success: function(response) {
                    notifyMe('success', '{{ localize('Status updated successfully') }}');

                },
                error: function(data) {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            }); 
        }
    </script>
@endsection
