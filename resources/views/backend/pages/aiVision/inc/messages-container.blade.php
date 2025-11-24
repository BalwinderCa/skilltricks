@if (!is_null($conversation))

    <!-- chat header top start -->
    <div class="tt-chat-header p-3 border-bottom d-flex align-items-center  gap-3 flex-wrap justify-content-between">

        <div class="col-auto d-flex align-items-center">
            <div class="avatar avatar-md">
                @if ($conversation->category->avatar == null)
                    <img class="rounded-circle" src="{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}"
                        alt="avatar" />
                @else
                    <img class="rounded-circle"
                        src="{{ (int) $conversation->category->avatar == 0 ? staticAsset($conversation->category->avatar) : uploadedAsset($conversation->category->avatar) }}"
                        alt="avatar" />
                @endif
            </div>
            <div class="ms-2 lh-1">
                <h6 class="mb-0 lh-1">{{ $conversation->category->name }}</h6>
                <span class="text-muted fst-italic fs-sm text-capitalize">{{ $conversation->category->role }}</span>
            </div>
        </div>
        <div class="tt-chat-action d-flex align-items-center">

            <div class="dropdown tt-tb-dropdown">
                <button type="button" class="btn p-0 me-3" data-bs-toggle="modal" data-bs-target="#exampleModal"
                    title="Send in Email"><i data-feather="send"></i></button>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{ localize('Send Chat to Email') }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form action="{{ route('chat.sendInEmail') }}" method="POST">
                            @csrf
                            <div class="modal-body">  
                                <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                                <div class="form-group">
                                    <label class="form-label">{{ localize('Type Email') }}</label>
                                    <input type="email" class="form-control" name="email"
                                        placeholder="{{ localize('Type an email') }}" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">{{ localize('Close') }}</button>
                                <button type="submit" class="btn btn-primary">{{ localize('Send') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="dropdown tt-tb-dropdown">
                <button type="button" class="btn p-0 me-3 copyChat" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="Copy Chat"><i data-feather="copy"></i></button>
            </div>

            <div class="dropdown tt-tb-dropdown">
                <button type="button" class="btn p-0 me-2" data-bs-title="Download" id="downloadDropdown"
                    href="#!" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    aria-haspopup="true" aria-expanded="true"><i data-feather="download"></i></button>

                <div class="dropdown-menu dropdown-menu-end shadow" style="">

                    <a href="{{ route('chat.download', ['chatId' => $conversation->id, 'type' => 'pdf']) }}"
                        class="dropdown-item downloadChatBtn" target="_blank" data-doc-type="pdf"
                        data-doc-name="{{ isset($conversation) ? $conversation->title : '' }}">
                        <i data-feather="file-text" class="icon-18"></i> {{ localize('PDF') }}
                    </a>

                    <a href="{{ route('chat.download', ['chatId' => $conversation->id, 'type' => 'html']) }}"
                        class="dropdown-item downloadChatBtn" data-doc-type="html"
                        data-doc-name="{{ isset($conversation) ? $conversation->title : '' }}">
                        <svg class="shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 4l-2 14.5l-6 2l-6 -2l-2 -14.5z"></path>
                            <path d="M15.5 8h-7l.5 4h6l-.5 3.5l-2.5 .75l-2.5 -.75l-.1 -.5"></path>
                        </svg> {{ localize('HTML') }}
                    </a>

                    <a href="{{ route('chat.download', ['chatId' => $conversation->id, 'type' => 'word']) }}"
                        class="dropdown-item downloadChatBtn" data-doc-type="doc"
                        data-doc-name="{{ isset($conversation) ? $conversation->title : '' }}">
                        <svg class="shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 18h9v-12l-5 2v5l-4 2v-8l9 -4l7 2v13l-7 3z"></path>
                        </svg> {{ localize('MS Word') }}
                    </a>
                </div>
            </div>

            <div class="dropdown tt-tb-dropdown">
                <button type="button" class="btn p-0">
                    <a href="#" class="dropdown-item" onclick="confirmDelete(this)"
                        data-href="{{ route('chat.delete', $conversation->id) }}" title="{{ localize('Delete') }}">
                        <i data-feather="trash-2" class="me-2"></i>
                    </a>
                </button>
            </div>


        </div>
    </div>
    <!-- chat header top end -->

    <!-- chat conversation start -->
    <div class="tt-conversation p-3 tt-custom-scrollbar">
        @php
            $messages = $conversation->messages;
        @endphp

        <div class="messages-wrapper">
            @foreach ($messages as $message)
                <!-- single chat expert start -->
                <div
                    class="d-flex {{ $message->prompt != null ? 'tt-message-end justify-content-end' : 'justify-content-start' }} mb-4 tt-message-wrap {{ $message->prompt != null ? 'tt-message-me' : '' }}">
                    <div
                        class="d-flex flex-column {{ $message->prompt != null ? 'align-items-end' : 'align-items-start' }}">
                        <div class="d-flex align-items-start">
                            @if ($message->prompt == null)
                                <div class="avatar avatar-md flex-shrink-0">
                                    @if ($conversation->category->avatar == null)
                                        <img class="rounded-circle"
                                            src="{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}"
                                            alt="avatar" />
                                    @else
                                        <img class="rounded-circle"
                                            src="{{ (int) $conversation->category->avatar == 0 ? staticAsset($conversation->category->avatar) : uploadedAsset($conversation->category->avatar) }}"
                                            alt="avatar" />
                                    @endif
                                </div>
                            @endif

                            <div class="msg-wrapper">
                                @php
                                    $result = $message->result;
                                    $result = preg_replace('/\*\*(.*?)\*\*/', '<h3 class="mb-0">$1</h3>', $result);
                                    $result = preg_replace('/<input[^>]*>|<textarea[^>]*>/', '', $result);

                                @endphp
                                <div
                                    class="p-3  {{ $message->prompt != null ? 'me-3' : 'ms-3' }}  rounded-3 {{ $message->prompt != null ? 'mw-450' : 'text-start  mw-650' }} tt-message-text tt-message-text-category-wise">
                                    {!! $result !!}
                                    @php
                                        $images = json_decode($message->images)
                                    @endphp
                                    @if($images && $message->prompt != null)
                                        @foreach ($images as $item)
                                            <div>
                                                <img src="{{$item->image_url->url}}" alt="">
                                            </div>
                                        @endforeach                                        
                                    @endif
                                   

                                </div>

                                @if ($message->prompt == null)
                                    <button type="button"
                                        class="border-0 btn btn-icon btn-soft-primary rounded-circle txt-copy-btn d-none shadow-sm copy-msg-btn">
                                        <i data-feather="copy"></i>
                                    </button>
                                @endif
                            </div>

                            @if ($message->prompt != null)
                                <div class="avatar avatar-md flex-shrink-0">
                                    <img class="rounded-circle"
                                        src="{{ $conversation->user->avatar ? asset('public/' . $conversation->user->profileImage->media_file) : staticAsset('/backend/assets/img/avatar/1.jpg') }}"
                                        alt="avatar" />
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
                <!-- single chat expert end -->
            @endforeach
        </div>

        <!-- single chat expert start -->
        <div class="d-flex justify-content-start mb-4 tt-message-wrap new-msg-loader d-none">
            <div class="d-flex flex-column align-items-start">
                <div class="d-flex align-items-start">
                    <div class="avatar avatar-md  flex-shrink-0">
                        @if ($conversation->category->avatar == null)
                            <img class="rounded-circle" src="{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}"
                                alt="avatar" />
                        @else
                            <img class="rounded-circle"
                                src="{{ (int) $conversation->category->avatar == 0 ? staticAsset($conversation->category->avatar) : uploadedAsset($conversation->category->avatar) }}"
                                alt="avatar" />
                        @endif
                    </div>

                    <div class="msg-wrapper">
                        <div class="p-2 ms-3  rounded-3 text-start mw-650 tt-message-text">
                            <!-- text preloader start -->
                            <div class="tt-text-preloader">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <!-- text preloader end -->
                        </div>
                        <button type="button"
                            class="border-0 btn btn-icon btn-soft-primary rounded-circle txt-copy-btn d-none shadow-sm copy-msg-btn">
                            <i data-feather="copy"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>
        <!-- single chat expert end -->

    </div>

    <div class="mt-auto text-center border-top d-flex justify-content-between align-items-end">
        <form class="p-3 d-block w-100" id="vision_form" data-category_id="{{$chatExpert->id}}">

            <textarea class="form-control border-0" rows="2" name="prompt" id="prompt" required
                placeholder="{{ localize('Type your message') }}.."></textarea>
                <div class="tt-product-thumb mt-2 text-start tt-vision-thumb" id="vision_image">
                    
                  </div>

            <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap flex-lg-nowrap gap-2">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-icon rounded-circle bg-secondary me-2" type="button" id="recordButton"><i
                        data-feather="mic"></i></button>
                    <button class="btn btn-sm btn-icon rounded-circle bg-secondary d-none me-2" type="button" id="stopButton"><i
                        data-feather="pause" ></i></button>
                    <a class="btn rounded-pill btn-secondary btn-sm py-2 align-items-center d-flex" href="#test-popup"
                        data-bs-toggle="modal" data-bs-target="#promptModal"><i data-feather="file-text"
                            class="icon-14 me-1"></i><span
                            class="d-none d-lg-block lh-base">{{ localize('Prompt Library') }}</span></a>
                        
                        <div id="metaImage" data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                                class="btn rounded-pill btn-secondary btn-sm py-2 ms-3" data-type="vision" onclick="showMediaManagerForVision(this)">
                                <input type="hidden" name="vision_images" id="vision_images">
                                <i data-feather="image"
                                class="icon-14"></i>
                                <span
                                class="">{{ localize('Upload Image') }}</span>
                            </div>
                </div>
                <div class="d-flex align-items-center">
                    <button class="btn btn-primary btn-sm rounded-pill tt-send-btn msg-send-btn" type="submit">
                        {{ localize('Send') }}<i data-feather="send" class="ms-1"></i>
                    </button>
                    <button class="btn rounded-pill btn-secondary btn-sm py-2 btn-stop-content ms-3" disabled>
                        <span>{{ localize('Stop') }} <i data-feather="stop-circle" class="ms-1"></i></span>
                    </button>
                </div>

            </div>
        </form>
       
    </div>

    <!-- chat right box end -->

    <!-- prompt modal start -->
    <div class="modal fade" id="promptModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="promptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="promptModalLabel">
                        {{ localize('Predefined Prompts Library') }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body tt-prompt-body tt-custom-scrollbar bg-body-secondary rounded-bottom-3 p-0">
                    <div class="row g-0">
                        <div class="col-xl-4">
                            <div class="nav flex-column nav-pills p-4 tt-custom-scrollbar tt-prompt-tab"
                                id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <div class="tt-prompt-group">
                                    @foreach ($promptGroups as $grpKey => $group)
                                        <button class="nav-link  {{ $grpKey == 0 ? 'active' : '' }}"
                                            id="v-pills-group-{{ $group->id }}-tab" data-bs-toggle="pill"
                                            data-bs-target="#v-pills-group-{{ $group->id }}" type="button"
                                            role="tab" aria-controls="v-pills-group-{{ $group->id }}"
                                            aria-selected="true">{{ $group->name }}</button>
                                    @endforeach

                                </div>
                            </div>
                        </div>
                        <div class="col-xl-8">
                            <div class="tab-content tt-custom-scrollbar tt-prompt-tab-content p-4"
                                id="v-pills-tabContent">
                                @foreach ($promptGroups as $key => $group)
                                    <div class="tab-pane fade {{ $key == 0 ? 'show active' : '' }}"
                                        id="v-pills-group-{{ $group->id }}" role="tabpanel"
                                        aria-labelledby="v-pills-group-{{ $group->id }}-tab" tabindex="0">
                                        <div class="d-flex flex-column gap-3 ">
                                            @foreach ($prompts as $prompt)
                                                @if ($prompt->ai_chat_prompt_group_id == $group->id)
                                                    <div class="tt-prompt-single-content p-3 rounded shadow-sm card text-start promptBtn"
                                                        data-prompt="{!! $prompt->prompt !!}">
                                                        <h3 class="h6 mb-1">{{ $prompt->title }}
                                                        </h3>
                                                        <p class="fs-md">{!! $prompt->prompt !!}</p>
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
        </div>
    </div>
    <!-- prompt modal end -->

    <div class="downloadChat" id="downloadChat">

    </div>
@else
    <div class="d-flex h-100 align-items-center justify-content-center">
        {{ localize('Open a new conversation to chat with Ai') }}
    </div>
@endif
