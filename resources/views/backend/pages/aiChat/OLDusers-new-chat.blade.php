@extends('backend.layouts.master')



@section('title')

    {{ localize('Chat') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}

@endsection

<style>
    .template-actions {
        top: 70% !important;
    }
    .btn svg {
        width: 30px !important;
        height: 30px !important;
    }
    button.btn.btn-sm.text-success.me-2.copy-btn {
        margin-top: -10px;
        position: absolute;
        left: -25px;
    }
</style>


@section('contents')

    <section class="tt-section pt-4">

        <div class="container">

            <div class="row mb-4">

                <div class="col-12">

                    <div class="tt-page-header">

                        <div class="d-lg-flex align-items-center justify-content-lg-between">

                            <div class="tt-page-title mb-3 mb-lg-0">

                                <h1 class="h4 mb-lg-1">{{ localize('Chat') }}</h1>

                                <ol class="breadcrumb breadcrumb-angle text-muted">

                                    <li class="breadcrumb-item"><a

                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>

                                    </li>

                                    <li class="breadcrumb-item">{{ localize('Chat') }}</li>

                                </ol>

                            </div>

                            <div class="tt-action">


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

                            <form id="ask-form">
                                <div class="row justify-content-center">
                                    <div class="col-lg-6 col-md-8">
                                        <input type="hidden" name="user_id" value="{{ $user->id }}">

                                        <div class="input-group">
                                            <input type="search"
                                                   name="search"
                                                   id="question"
                                                   placeholder="{{ localize('Search prompt that you are looking for') }}..."
                                                   @isset($searchKey) value="{{ $searchKey }}" @endisset
                                                   class="form-control border border-2 border-primary rounded-pill rounded-end">

                                            <div class="input-group-append">
                                                <button type="submit"
                                                        class="btn btn-link bg-primary border border-2 border-primary text-light rounded-pill rounded-start">
                                                    <i class="flaticon-search translate-middle-y"></i> {{ localize('Search Now') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>



                            <div class="d-block">

                                <ul class="nav nav-pills d-flex align-items-center justify-content-center tt-horizontal-tab tt-prompt-group-list mt-3 gap-2 nav-tabs-dropdown"

                                    id="pills-tab" role="tablist">



                                    <!-- <li class="nav-item d-flex align-items-center" role="presentation">

                                        <a class="nav-link active" id="tabId-all"data-bs-toggle="pill"

                                            data-bs-target="#all-tab" type="button" role="tab" aria-controls="all-tab"

                                            aria-selected="true" href="#">

                                            {{ localize('All Prompts') }}

                                        </a>

                                    </li> -->


                                </ul>

                            </div>

                        </div>

                        <div class="tab-content card-body mt-5" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="all-tab" role="tabpanel" aria-labelledby="tabId-all" tabindex="0">
                                <div class="row g-3" id="answer-box"></div>
                            </div>
                        </div>



                    <!-- Chat History Section -->
                    <div class="tab-content card-body" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="all-tab" role="tabpanel" aria-labelledby="tabId-all" tabindex="0">
                            <div class="row g-3">
                                @foreach ($searchuserchatdata as $chat)
                                    <div class="col-lg-12 col-sm-6">
                                        <div class="tt-single-template d-flex flex-column h-100 position-relative">
                                            <div class="card flex-column h-100 tt-template-card tt-corner-shape border-0 position-relative">
                                                
                                                {{-- Date & Time - Top Right --}}
                                                <div class="position-absolute top-0 end-0 m-2 text-muted small">
                                                    {{ \Carbon\Carbon::parse($chat->created_at)->format('d M Y, h:i A') }}
                                                </div>

                                                <div class="card-body d-flex flex-column h-100">
                                                    <div class="tt-card-info mb-4">
                                                        <h3 class="h6">{{ $chat->search }}</h3>
                                                        <p class="mb-0 response-text">{!! nl2br(e($chat->response)) !!}</p>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end template-actions px-3 pb-3">
                                                    <!-- Copy Button -->
                                                    <button type="button" class="btn btn-sm text-success me-2 copy-btn" data-bs-toggle="tooltip" data-bs-title="Copy Answer">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-copy">
                                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                        </svg>
                                                    </button>

                                                    <!-- Delete Button -->
                                                    <form action="{{ url('dashboard/users-chat-search-delete', $chat->id) }}" method="get" onsubmit="return confirm('Are you sure you want to delete this chat?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm text-danger" data-bs-toggle="tooltip" data-bs-title="Delete">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash">
                                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
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

    </section>

@endsection





@section('scripts')
   
<script>
    document.getElementById('ask-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const questionInput = document.getElementById('question');
        const question = questionInput.value.trim();

        if (!question) {
            alert("Please enter a question.");
            return;
        }

        const answerBox = document.getElementById('answer-box');

        // Temporary loader with dot animation
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'col-12 loading-placeholder';
        loadingDiv.innerHTML = `
            <div class="d-flex justify-content-center">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <div class="text-center text-info mt-2">Generating answer, please wait...</div>
        `;
        answerBox.insertAdjacentElement('afterbegin', loadingDiv);

        try {
            const res = await fetch('/dashboard/users-new-chat-ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ question })
            });

            const data = await res.json();
            const answer = data.answer || 'No answer returned.';
            const formattedAnswer = answer.replace(/\n/g, '<br>');

            // Get current date and time
            const now = new Date();
            const formattedDateTime = now.toLocaleString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true
            });

            const cardHTML = `
                <div class="col-lg-12 col-sm-6 mt-3" id="chat-${data.id}">
                    <div class="tt-single-template d-flex flex-column h-100 position-relative">
                        <div class="card flex-column h-100 tt-template-card tt-corner-shape border-0 position-relative">

                            <!-- Date & Time -->
                            <div class="position-absolute top-0 end-0 m-2 text-muted small">
                                ${formattedDateTime}
                            </div>

                            <div class="card-body d-flex flex-column h-100">
                                <div class="tt-card-info mb-4">
                                    <h3 class="h6">${data.question}</h3>
                                    <p class="mb-0 answer-text">${formattedAnswer}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-end template-actions px-3 pb-3">
                                <!-- Copy Button -->
                                <button class="btn btn-sm text-success me-2 copy-btn" data-bs-toggle="tooltip" data-bs-title="Copy Answer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-copy">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                </button>

                                <!-- Delete Button -->
                                <form action="/dashboard/users-chat-search-delete/${data.id}" method="get" onsubmit="return confirm('Are you sure you want to delete this chat?')">
                                    <button type="submit" class="btn btn-sm text-danger" data-bs-toggle="tooltip" data-bs-title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            loadingDiv.remove();
            answerBox.insertAdjacentHTML('afterbegin', cardHTML);

        } catch (error) {
            console.error(error);
            loadingDiv.remove();
            answerBox.insertAdjacentHTML('afterbegin', `
                <div class="col-12">
                    <div class="alert alert-danger">Something went wrong while fetching the answer.</div>
                </div>
            `);
        }
    });

    // Global copy listener
    document.addEventListener('click', function (e) {
        if (e.target.closest('.copy-btn')) {
            const card = e.target.closest('.tt-template-card');
            const text = card.querySelector('.answer-text').innerText;
            navigator.clipboard.writeText(text)
                .then(() => alert('Answer copied!'))
                .catch(err => alert('Copy failed: ' + err));
        }
    });
</script>



<!-- Copy Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const copyButtons = document.querySelectorAll('.copy-btn');

        copyButtons.forEach(button => {
            button.addEventListener('click', function () {
                const card = this.closest('.tt-template-card');
                const answer = card.querySelector('.response-text').innerText;

                navigator.clipboard.writeText(answer).then(() => {
                    alert('Answer copied to clipboard!');
                }).catch(() => {
                    alert('Failed to copy!');
                });
            });
        });
    });
</script>


@endsection

