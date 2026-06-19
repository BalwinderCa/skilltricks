@extends('backend.layouts.master')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">



@section('title')

    {{ localize('Chat') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}

@endsection

<style>
  /* .custom-loder{  mix-blend-mode: luminosity;} */
    /* .template-actions {
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
    } */
     /* Parent - Container */
    .chat-container {
      display: flex;
      min-height: 100vh;
    }

    /* Parent - Sidebar */
    .sidebar {
      width: 260px;
      /* background-color: #f7f7f7; */
      background-color: --bs-card-bg: var(--bs-body-bg);
      border-right: 1px solid var(--bs-border-color-translucent);
      --bs-card-border-width: var(--bs-border-width);
      /* Child elements will inherit these properties */
    }
      /* Child - Sidebar Header */
      .sidebar .sidebar-header {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
      }
        /* Grandchild - Sidebar Header Buttons */
        .sidebar .sidebar-header .btn-icon {
          color: #666;
          background-color: transparent;
          border: none;
          border-radius: 4px;
          padding: 8px;
        }
          /* Great-grandchild - Icon hover state */
          .sidebar .sidebar-header .btn-icon:hover {
            background-color: #e0e0e0;
          }

      /* Child - Sidebar Content */
      .sidebar .sidebar-content {
        overflow-y: auto;
        height: calc(100vh - 60px);margin-right: 10px;
      }
        /* Grandchild - Sidebar Sections */
        .sidebar .sidebar-content .sidebar-section {
          padding: 5px 0px;
          /* border-bottom: 1px solid #e0e0e0; */
        }
          /* Great-grandchild - Section Title */
          .sidebar .sidebar-content .sidebar-section .section-title {
            font-size: 12px;
            color: #000;
            margin-bottom: 10px;
            font-weight: 500;
          }
          /* Great-grandchild - Sidebar Items */
          .sidebar .sidebar-content .sidebar-section .sidebar-item {
            padding: 8px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
          }
            /* Great-great-grandchild - Sidebar Item Hover */
            .sidebar .sidebar-content .sidebar-section .sidebar-item:hover {
              background-color: #e0e0e0;
            }
            .sidebar .sidebar-content .sidebar-section .section-title a{display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 12px;}
            /* Great-great-grandchild - Sidebar Item Icon */
            .sidebar .sidebar-content .sidebar-section .sidebar-item i {
              margin-right: 10px;
              color: #666;
            }


           .chat-messages .btn svg{width: 24px;
  height: 24px;
  font-size: 9px;}

    /* Parent - Main Content */
    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;background:#fff;
    }
      /* Child - Chat Header */
      .main-content .chat-header {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
        /* Grandchild - Header Title */
        .main-content .chat-header .header-title {
          font-weight: 600;
          display: flex;
          align-items: center;
        }
        /* Grandchild - Header Actions */
        .main-content .chat-header .header-actions {
          display: flex;
          align-items: center;
        }
          /* Great-grandchild - Action Button */
          .main-content .chat-header .header-actions .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            background-color: transparent;
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
          }
            /* Great-great-grandchild - Button Hover */
            .main-content .chat-header .header-actions .btn-action:hover {
              background-color: #f0f0f0;
            }
            /* Great-great-grandchild - Button Icon */
            .main-content .chat-header .header-actions .btn-action i {
              margin-right: 5px;
            }
  
            .chat-container .sidebar-content .sidebar-section .sidebar-item a{display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;color: #484848;
  font-size: 13px;width: 80%;}

      /* Child - Chat Messages */
      .main-content .chat-messages {
        flex: 1;
        padding: 0 20px;
        overflow-y: auto;
      }
        /* Grandchild - User Message */
        .main-content .chat-messages .user-message {
          background-color: #f0f0f0;
          padding: 12px 16px;
          border-radius: 8px;
          margin-bottom: 20px;
          max-width: 80%;
          margin-left: auto;margin-top:20px;
        }
        /* Grandchild - Bot Message */
        .main-content .chat-messages .bot-message {
          margin-bottom: 20px;
        }
          /* Great-grandchild - Message Paragraph */
          .main-content .chat-messages .bot-message p {
            margin-bottom: 15px;
            line-height: 1.5;
          }
          /* Great-grandchild - Message Heading */
          .main-content .chat-messages .bot-message h5 {
            font-weight: 600;
            margin-top: 20px;
            margin-bottom: 10px;
          }
          /* Uniform heading sizing across ALL messages.
             Markdown #/## render as h1/h2 (big + bold) which made follow-up
             messages look different from the first. Normalize every level so
             every message follows the same font/CSS. */
          .main-content .chat-messages .response-text h1,
          .main-content .chat-messages .response-text h2,
          .main-content .chat-messages .response-text h3,
          .main-content .chat-messages .response-text h4,
          .main-content .chat-messages .response-text h5,
          .main-content .chat-messages .response-text h6,
          .main-content .chat-messages .bot-message h1,
          .main-content .chat-messages .bot-message h2,
          .main-content .chat-messages .bot-message h3,
          .main-content .chat-messages .bot-message h4,
          .main-content .chat-messages .bot-message h6 {
            font-size: 1.05rem;
            font-weight: 600;
            line-height: 1.5;
            margin-top: 18px;
            margin-bottom: 8px;
          }
          /* Great-grandchild - Message List */
          .main-content .chat-messages .bot-message ul {
            padding-left: 20px;
            margin-bottom: 20px;
          }
            /* Great-great-grandchild - List Item */
            .main-content .chat-messages .bot-message ul li {
              margin-bottom: 8px;
            }
          /* Great-grandchild - Code Block */
          .main-content .chat-messages .bot-message .code-block {
            background-color: #f7f7f7;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
          }
            /* Great-great-grandchild - Code Header */
            .main-content .chat-messages .bot-message .code-block .code-header {
              display: flex;
              justify-content: space-between;
              color: #666;
              font-size: 12px;
              margin-bottom: 10px;
            }
            /* Great-great-grandchild - Code Content */
            .main-content .chat-messages .bot-message .code-block pre {
              margin-bottom: 0;
              color: #0066cc;
            }

      /* Child - Chat Input */
      .main-content .chat-input {
        padding: 15px;
        border-top: 1px solid #e0e0e0;
      }
        /* Grandchild - Input Container */
        .main-content .chat-input .input-container {
          display: flex;
          border: 1px solid #e0e0e0;
          border-radius: 8px;
          padding: 8px 12px;
          background-color: #f7f7f7;
        }
          /* Great-grandchild - Text Input */
          .main-content .chat-input .input-container input {
            flex: 1;
            border: none;
            background-color: transparent;
            outline: none;
            padding: 8px;
          }
          /* Great-grandchild - Input Buttons */
          .main-content .chat-input .input-container .input-btn {
            background-color: transparent;
            border: none;
            color: #666;
            padding: 8px;
            border-radius: 4px;
          }
            /* Great-great-grandchild - Button Hover */
            .main-content .chat-input .input-container .input-btn:hover {
              background-color: #e0e0e0;
            }
        /* Grandchild - Input Footer */
        .main-content .chat-input .input-footer {
          text-align: center;
          font-size: 12px;
          color: #666;
          margin-top: 8px;
        }

    /* Avatar element */
    .avatar {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background-color: #0066cc;
      display: inline-block;
      margin-right: 10px;
    }

    [data-bs-theme="dark"] .main-content {background-color:#161C24;}
    [data-bs-theme="dark"] .main-content .chat-input .input-container{background-color: #161C24;border: 1px solid #454545;}
    [data-bs-theme="dark"] .main-content .chat-input{border-top: 1px solid #454545}
    [data-bs-theme="dark"] .sidebar .sidebar-content .sidebar-section .section-title,
    [data-bs-theme="dark"] .chat-container .sidebar-content .sidebar-section .sidebar-item a,
    [data-bs-theme="dark"] .sidebar .sidebar-content .sidebar-section .sidebar-item{color: #fff;}
    [data-bs-theme="dark"] .sidebar .sidebar-content .sidebar-section .sidebar-item:hover,
    [data-bs-theme="dark"] .main-content .chat-messages .user-message{background-color: #242424;}

    /* Strategy Map Radio Buttons */
    .strategy-map-section {
        margin: 20px 0;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #0066cc;
    }

    .strategy-options {
        margin-top: 15px;
    }

    .strategy-option {
        padding: 12px;
        margin-bottom: 10px;
        background-color: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .strategy-option:hover {
        border-color: #0066cc;
        background-color: #f0f7ff;
    }

    .strategy-option input[type="radio"]:checked + .strategy-label,
    .strategy-option:has(input[type="radio"]:checked) {
        border-color: #0066cc;
        background-color: #e6f2ff;
        font-weight: 600;
    }

    .strategy-label {
        cursor: pointer;
        display: flex;
        align-items: flex-start;
        flex: 1;
        line-height: 1.5;
    }

    .strategy-radio {
        cursor: pointer;
        width: 18px;
        height: 18px;
        margin-top: 3px;
        flex-shrink: 0;
    }

    .response-section {
        margin-bottom: 20px;
    }

    [data-bs-theme="dark"] .strategy-map-section {
        background-color: #1a1a1a;
        border-left-color: #4a9eff;
    }

    [data-bs-theme="dark"] .strategy-option {
        background-color: #242424;
        border-color: #454545;
    }

    [data-bs-theme="dark"] .strategy-option:hover {
        border-color: #4a9eff;
        background-color: #1a2332;
    }

    [data-bs-theme="dark"] .strategy-option:has(input[type="radio"]:checked) {
        border-color: #4a9eff;
        background-color: #1a2332;
    }

    /* Strategy loaded indicator */
    .strategy-loaded {
        opacity: 1;
    }

    .strategy-option:not(.strategy-loaded) {
        opacity: 0.8;
    }

    /* Selected strategy in the final answer — match the selected scenario look */
    .strategy-option.strategy-selected {
        border-color: #6f42c1;
        background-color: #f1e8ff;
        box-shadow: 0 2px 6px rgba(111, 66, 193, 0.2);
    }

    [data-bs-theme="dark"] .strategy-option.strategy-selected {
        border-color: #a07cf0;
        background-color: #1e1b2a;
    }

    /* Spinning animation for loading */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .spin {
        animation: spin 1s linear infinite;
        display: inline-block;
    }

    /* Scenario simulations */
    .scenario-section {
        margin: 20px 0;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #6f42c1;
    }

    .scenario-options {
        margin-top: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .scenario-option {
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 6px;
        background-color: #fff;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .scenario-option:hover {
        border-color: #6f42c1;
        background-color: #f6f0ff;
    }

    .scenario-option.scenario-selected {
        border-color: #6f42c1;
        background-color: #f1e8ff;
        box-shadow: 0 2px 6px rgba(111, 66, 193, 0.2);
    }

    .scenario-radio {
        cursor: pointer;
        width: 18px;
        height: 18px;
        margin-top: 3px;
        flex-shrink: 0;
    }

    .scenario-label {
        cursor: pointer;
        flex: 1;
        line-height: 1.5;
    }

    [data-bs-theme="dark"] .scenario-section {
        background-color: #1a1a1a;
        border-left-color: #a07cf0;
    }

    [data-bs-theme="dark"] .scenario-option {
        background-color: #242424;
        border-color: #454545;
    }

    [data-bs-theme="dark"] .scenario-option:hover,
    [data-bs-theme="dark"] .scenario-option.scenario-selected {
        border-color: #a07cf0;
        background-color: #1e1b2a;
    }

    /* Add Context Form Styles */
    .add-context-form {
        margin-top: 20px;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }

    .add-context-form .form-group {
        margin-bottom: 15px;
    }

    .add-context-form label {
        font-weight: 500;
        margin-bottom: 5px;
        display: block;
        color: #333;
    }

    .add-context-form input,
    .add-context-form textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .add-context-form textarea {
        min-height: 100px;
        resize: vertical;
    }

    .add-context-form .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    [data-bs-theme="dark"] .add-context-form {
        background-color: #1a1a1a;
        border-color: #454545;
    }

    [data-bs-theme="dark"] .add-context-form label {
        color: #fff;
    }

    [data-bs-theme="dark"] .add-context-form input,
    [data-bs-theme="dark"] .add-context-form textarea {
        background-color: #242424;
        border-color: #454545;
        color: #fff;
    }

    /* GoalSync structured (JSON) renderer */
    .gs-section { margin-bottom: 18px; }
    .gs-section > h5 {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .gs-section ul { margin-bottom: 0; padding-left: 1.2rem; }
    .gs-role-block {
        padding: 8px 10px;
        border-left: 3px solid #6f42c1;
        background: #faf8ff;
        border-radius: 4px;
    }
    .gs-role-block .gs-role-title { margin-bottom: 2px; }
    [data-bs-theme="dark"] .gs-role-block {
        background: #1f1b2a;
        border-left-color: #a07cf0;
    }

    /* Recommended Action Table suggestion */
    .action-table-suggestion-box {
        border: 1px dashed #b9c6e0;
        background: #f5f8ff;
        border-radius: 8px;
        padding: 12px 14px;
    }
    .action-table-suggestion-box .ats-title {
        font-weight: 600;
        font-size: 0.95rem;
        color: #2c3e66;
    }
    .recommended-action-table th,
    .recommended-action-table td {
        vertical-align: top;
        font-size: 0.9rem;
    }
    .recommended-action-table th:nth-child(3),
    .recommended-action-table td:nth-child(3) {
        white-space: nowrap;
        width: 1%;
    }
    .recommended-action-table .form-check {
        margin-bottom: 2px;
    }
    [data-bs-theme="dark"] .action-table-suggestion-box {
        background: #1f2633;
        border-color: #3a455c;
    }
    [data-bs-theme="dark"] .action-table-suggestion-box .ats-title {
        color: #cdd8f0;
    }
</style>


@section('contents')

    <section class="tt-section pt-4">

        <div class="container">

            <!-- <div class="row mb-4">

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

            </div> -->


            <div class="chat-container">
                <!-- Sidebar -->
                <div class="sidebar">
                <!-- Sidebar Header -->
                <div class="sidebar-header d-none">
                    <button class="btn-icon"><i class="bi bi-list"></i></button>
                    <button class="btn-icon"><i class="bi bi-search"></i></button>
                    <button class="btn-icon"><i class="bi bi-chat-square-text"></i></button>
                </div>
                
                <!-- Sidebar Content -->
                <div class="sidebar-content">
                    <div class="sidebar-section">
                {{--<!-- <a href="{{url('dashboard/newusers-new-chat/'.$id)}}" style="color:black;"> -->--}}
                <a href="{{url('dashboard/newchat')}}" style="color:black;">
                    <div class="sidebar-item">
                        <i class="bi bi-chat-square-text"></i>
                        <span>New Chat</span>
                    </div>
                </a>
                        <!-- <div class="sidebar-item">
                            <i class="bi bi-chat-square-text"></i>
                            <span>Explore GPTs</span>
                        </div>
                        <div class="sidebar-item">
                            <i class="bi bi-chat-square-text"></i>
                            <span>Library</span>
                            <span style="margin-left: auto; font-size: 12px; color: #666;">1</span>
                        </div> -->
                    </div>

                    @php
                        $sortedGroups = $searchuserchatdatanew->sortByDesc(function ($chats) {
                            return $chats->max('created_at');
                        });
                    @endphp

                    @foreach ($sortedGroups as $group => $chats)
                        <div class="sidebar-section">
                            <div class="section-title">{{ $group }}</div>

                            @foreach ($chats->sortByDesc('created_at')->unique('search_user_chat_id') as $chat)
                                <div class="sidebar-item d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                    <a href="{{ url('dashboard/users-new-chat/' . $chat->search_user_chat_id) }}">
                                        {{ \Illuminate\Support\Str::words(strip_tags($chat->search), 8, '...') }}
                                    </a>

                                   <form class="mb-0" action="{{ url('dashboard/users-chat-search-delete', $chat->search_user_chat_id) }}" method="get" onsubmit="return confirm('Are you sure you want to delete this chat?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm p-0" data-bs-toggle="tooltip" data-bs-title="Delete">
                                      <i class="feather feather-trash bi bi-trash text-danger m-0"></i>
                                        <!-- <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg> -->
                                    </button>
                                </form>
                                </div>
                            @endforeach
                        </div>
                    @endforeach


                </div>
                </div>
                
                <!-- Main Content -->
                <div class="main-content">
                <!-- Chat Header -->
                <div class="chat-header">
                    <div class="header-title">
                    <span>StrategiStudio</span>
                    </div>
                    <div class="header-actions">
                    @if(isset($documentCount) && $documentCount > 0)
                        <span class="badge bg-info text-white me-2" data-bs-toggle="tooltip" data-bs-placement="top"
                            title="{{ localize('Company documents are being used as context for AI responses') }}">
                            <i data-feather="file-text" class="icon-14 me-1"></i>
                            {{ $documentCount }} {{ localize('Document') }}{{ $documentCount > 1 ? 's' : '' }}
                        </span>
                    @endif
                    {{-- Lifetime token total for THIS chat across all sections. --}}
                    @php $chatTotalTokens = $chatTotalTokens ?? 0; @endphp
                    <span id="token-usage-badge" class="badge"
                        style="{{ $chatTotalTokens > 0 ? '' : 'display:none;' }}background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;font-weight:600;"
                        data-chat-total="{{ $chatTotalTokens }}"
                        data-bs-toggle="tooltip" data-bs-placement="top"
                        title="{{ localize('Total tokens used in this chat across all sections.') }}">
                        <i data-feather="activity" class="icon-14 me-1"></i>
                        <span id="token-usage-text">{{ number_format($chatTotalTokens) }} {{ localize('tokens') }}</span>
                    </span>
                    </div>
                </div>
                
                <!-- Chat Messages -->
            <!-- Chat Messages -->
                <form id="ask-form">
                    <input type="hidden" name="user_id" id="user_id" value="{{ $user->id }}">
                    <input type="hidden" name="id" id="chat_id" value="{{ $id }}">
                    
                    <div class="chat-messages" id="chat-messages">
                        @foreach ($searchuserchatdata as $index => $chat)
                           <div class="tt-template-carddads">
                            <div class="user-message">{{ $chat->search ?? '' }}</div>
                            <div class="bot-message  response-text" data-md="{{ base64_encode($chat->response ?? '') }}"></div>
                            <!-- Copy Button -->
                                <button type="button" class="btn btn-sm text-success me-2 copy-btn" data-bs-toggle="tooltip" data-bs-title="Copy Answer">
                                    <!-- <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="feather feather-copy">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg> -->
                                    <i class="bi bi-copy"></i>
                                </button>
                           </div>
                        @endforeach
                        
                        {{-- Always show Leadership Alignment Brief at the end if it exists in database --}}
                        @if(isset($leadershipBriefFromDB) && !empty($leadershipBriefFromDB))
                            <div class="tt-template-carddads">
                                <div class="leadership-alignment-brief mt-3">
                                    <div class="response-text" data-md="{{ base64_encode($leadershipBriefFromDB ?? '') }}"></div>
                                </div>
                            </div>
                            <script>
                                console.log('📋 BRIEF FROM DATABASE (Blade Template):');
                                console.log('📍 Brief exists:', true);
                                console.log('📍 Brief length:', {{ strlen($leadershipBriefFromDB) }});
                                console.log('📍 Brief preview (first 200 chars):', {!! json_encode(substr($leadershipBriefFromDB, 0, 200)) !!});
                                console.log('📍 Full brief (truncated for console):', {!! json_encode(strlen($leadershipBriefFromDB) > 500 ? substr($leadershipBriefFromDB, 0, 500) . '...' : $leadershipBriefFromDB) !!});
                            </script>
                        @else
                            <script>
                                console.log('📋 BRIEF FROM DATABASE (Blade Template):');
                                console.log('📍 Brief exists:', false);
                                console.log('📍 leadershipBriefFromDB isset:', {{ isset($leadershipBriefFromDB) ? 'true' : 'false' }});
                                @if(isset($leadershipBriefFromDB))
                                    console.log('📍 leadershipBriefFromDB is empty:', {{ empty($leadershipBriefFromDB) ? 'true' : 'false' }});
                                    console.log('📍 leadershipBriefFromDB value:', {!! json_encode($leadershipBriefFromDB) !!});
                                @endif
                            </script>
                        @endif
                    </div>

                    <!-- Chat Input -->
                    <div class="chat-input">
                        <h3 class="text-center textcheck" style="margin-top: 110px;"><b>Tell Us Your Final Goal To Conclude</b></h3>
                        <div class="input-container mt-4">
                            <input type="text" id="question" placeholder="Ask anything">
                            <button type="submit" class="input-btn">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </div>
                </form>
                </div>
            </div>


            <div class="row mb-3 g-3">

                <div class="col-xl-12">

                    
                </div>

            </div>



        </div>

    </section>

    <!-- Add Context Modal -->
    <div class="modal fade" id="addContextModal" tabindex="-1" aria-labelledby="addContextModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addContextModalLabel">
                        <i class="bi bi-info-circle me-2"></i>Add Additional Context
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="contextForm">
                        <div class="mb-3">
                            <label for="context-field" class="form-label" id="context-field-label">Describe the situation behind this goal (1–2 sentences)</label>
                            <textarea class="form-control" id="context-field" rows="5" placeholder="Enter 1-2 sentences describing the situation behind your goal..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitContextBtn">
                        <i class="bi bi-check-circle me-1"></i> Submit & Send to StrategiStudio
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection





@section('scripts')

<script>
    // Pre-render loader image URL to avoid pending requests
    const loaderImageUrl = "{{ asset('backend/assets/img/loader-img.gif') }}";
    
    // Preload the loader image to ensure it's ready
    const preloadImage = new Image();
    preloadImage.src = loaderImageUrl;
    
    const inputField = document.getElementById('question');
    const heading = document.querySelector('.textcheck');

    inputField.addEventListener('input', function () {
        if (this.value.trim() !== "") {
            heading.style.display = 'none';
        } else {
            heading.style.display = 'block';
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    // Render all server-stored messages with marked.js so they match
    // live (JS-appended) responses exactly. Avoids font/size mismatch
    // caused by PHP CommonMark vs marked.js producing different HTML.
    function renderStoredMarkdown() {
        document.querySelectorAll('[data-md]').forEach(function (el) {
            try {
                var raw = decodeURIComponent(escape(atob(el.dataset.md)));
                // New JSON-contract answers render read-only via renderAnswer;
                // legacy markdown answers fall back to marked.parse.
                var parsed = (typeof window.parseAnswerJson === 'function')
                    ? window.parseAnswerJson(raw) : null;
                if (parsed && typeof window.renderAnswer === 'function') {
                    el.innerHTML = window.renderAnswer(parsed, { interactive: false });
                } else {
                    // Legacy markdown. The live final showed the COLLAPSED 3-line
                    // scenarios from the strategy bundle, but the stored response
                    // keeps the verbose 🔮 section. Pull the collapsed scenarios
                    // from the bundle (first strategy) so reload matches live.
                    var collapsedScenario = null;
                    var bundleMatch = String(raw).match(/%%%BUNDLES_JSON%%%([\s\S]*?)%%%END_BUNDLES_JSON%%%/);
                    if (bundleMatch) {
                        try {
                            var bj = JSON.parse(bundleMatch[1].trim());
                            var firstKey = Object.keys(bj)[0];
                            var sm = firstKey && String(bj[firstKey]).match(/🔮[\s\S]*?(?=👥|📌|✅|$)/);
                            if (sm) collapsedScenario = sm[0].trim();
                        } catch (e) { /* keep verbose section on parse failure */ }
                    }

                    // Strip the machine-only data block (and stray markers).
                    var md = String(raw)
                        .replace(/%%%BUNDLES_JSON%%%[\s\S]*?%%%END_BUNDLES_JSON%%%/g, '')
                        .replace(/%%%(?:END_)?BUNDLES_JSON%%%/g, '')
                        .trim();

                    // Swap the verbose 🔮 section for the collapsed one.
                    if (collapsedScenario) {
                        md = md.replace(/🔮[\s\S]*?(?=👥|📌|✅|$)/, collapsedScenario + '\n\n');
                    }
                    // Split into per-section .response-text blocks (like the live
                    // final answer) so the Export-role-goals button can sit right
                    // after the 👥 roles section instead of after the whole blob.
                    var parts = md.split(/(?=🧩|📁|📊|📈|🗺️|🔮|👥|📌|✅|📋)/)
                        .filter(function (p) { return p.trim() !== ''; });
                    if (parts.length > 1) {
                        el.classList.remove('response-text');
                        el.innerHTML = parts.map(function (p) {
                            return '<div class="response-text">' + marked.parse(p) + '</div>';
                        }).join('');
                    } else {
                        el.innerHTML = marked.parse(md);
                    }
                }
            } catch (e) {
                console.error('Markdown render failed:', e);
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderStoredMarkdown);
    } else {
        renderStoredMarkdown();
    }
</script>

<script>
    // ============================================================
    // GoalSync structured renderer (JSON contract → HTML).
    // Single source of truth for rendering an answer. Emojis live in
    // the templates here, NOT as delimiters in the model output.
    //
    // Contract (see AiChatController GoalSync JSON prompt):
    //   { acknowledgement, documentInsights[], goalAssessment,
    //     scoring[{label,value}], strategyMap[{id,name,rationale,teams,
    //     tradeoffs,risk}], scenarios[{id,label,text}],
    //     rolesGoals[{role,goal,action}], complementaryGoals[],
    //     finalOutcome, selectedStrategyId, selectedScenarioId }
    //
    // NOTE: defined now (Phase 1); the live/reload pipelines are switched
    // onto it in later phases. Not called yet — no behavior change.
    // ============================================================
    (function () {
        function esc(v) {
            return String(v == null ? '' : v)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function nonEmpty(v) { return v != null && String(v).trim() !== ''; }

        // Remove GoalSync section-header emojis the model sometimes leaks into
        // text/list items (e.g. a stray "📊" bullet inside Document Insights).
        function stripMarkers(s) {
            return String(s == null ? '' : s)
                .replace(/[🧩📁📊📈🗺️🔮👥📌✅📋]/g, '')
                .replace(/️/g, '')
                .replace(/\s+/g, ' ')
                .trim();
        }

        // Clean a list of strings: strip markers and drop empties.
        function cleanList(arr) {
            if (!Array.isArray(arr)) return [];
            return arr.map(stripMarkers).filter(x => x !== '');
        }

        function sectionAcknowledgement(d) {
            if (!nonEmpty(d.acknowledgement)) return '';
            return `<div class="gs-section gs-ack">
                <h5>🧩 Chat Acknowledgement</h5>
                <p>${esc(d.acknowledgement)}</p>
            </div>`;
        }

        function sectionDocInsights(d) {
            const list = cleanList(d.documentInsights);
            if (!list.length) return '';
            const items = list.map(i => `<li>${esc(i)}</li>`).join('');
            return `<div class="gs-section gs-doc-insights">
                <h5>📁 Document Insights</h5>
                <ul>${items}</ul>
            </div>`;
        }

        function sectionGoalAssessment(d) {
            if (!nonEmpty(d.goalAssessment)) return '';
            return `<div class="gs-section gs-goal-assessment">
                <h5>📊 Goal Assessment Summary</h5>
                <p>${esc(d.goalAssessment)}</p>
            </div>`;
        }

        function sectionScoring(d) {
            if (!Array.isArray(d.scoring) || !d.scoring.length) return '';
            const items = d.scoring.map(s =>
                `<li><strong>${esc(s.label)}:</strong> ${esc(s.value)}</li>`).join('');
            return `<div class="gs-section gs-scoring">
                <h5>📈 Scoring</h5>
                <ul>${items}</ul>
            </div>`;
        }

        function sectionStrategyMap(d, opts) {
            if (!Array.isArray(d.strategyMap) || !d.strategyMap.length) return '';
            const interactive = opts && opts.interactive;
            const selId = d.selectedStrategyId;
            const rows = d.strategyMap.map(s => {
                const sel = s.id === selId;
                const input = interactive
                    ? `<input type="radio" name="gs-strategy" value="${esc(s.id)}" class="strategy-radio me-2" ${sel ? 'checked' : ''}>`
                    : '';
                const badge = sel ? '<span class="badge bg-primary ms-auto">Selected</span>' : '';
                return `<div class="strategy-option mb-2 ${sel ? 'strategy-loaded strategy-selected' : ''}" data-strategy-id="${esc(s.id)}">
                    <div class="strategy-label">
                        ${input}<strong>${esc(s.name)}:</strong> ${esc(s.rationale)}
                        ${s.teams ? ` | Teams: ${esc(s.teams)}` : ''}${s.tradeoffs ? ` | Trade-offs: ${esc(s.tradeoffs)}` : ''}${s.risk ? ` | Risk: ${esc(s.risk)}` : ''}
                        ${badge}
                    </div>
                </div>`;
            }).join('');
            return `<div class="gs-section gs-strategy-map">
                <h5>🗺️ Strategy Map (Decision Paths)</h5>
                <div class="strategy-options mt-3">${rows}</div>
            </div>`;
        }

        function sectionScenarios(d, opts) {
            if (!Array.isArray(d.scenarios) || !d.scenarios.length) return '';
            const interactive = opts && opts.interactive;
            const selId = d.selectedScenarioId;
            const rows = d.scenarios.map((s, idx) => {
                const sel = s.id ? s.id === selId : idx === 0;
                const input = interactive
                    ? `<input type="radio" name="gs-scenario" value="${esc(s.id)}" class="scenario-radio me-2" ${sel ? 'checked' : ''}>`
                    : '';
                const badge = sel ? '<span class="badge bg-primary ms-auto">Selected</span>' : '';
                return `<div class="scenario-option ${sel ? 'scenario-selected' : ''}" data-scenario-id="${esc(s.id)}">
                    <span class="scenario-label">${input}<strong>${esc(s.label)}:</strong> ${esc(s.text)}</span>
                    ${badge}
                </div>`;
            }).join('');
            return `<div class="gs-section gs-scenarios">
                <h5>🔮 Scenario Simulations</h5>
                <div class="scenario-options mt-3">${rows}</div>
            </div>`;
        }

        function sectionRolesGoals(d) {
            if (!Array.isArray(d.rolesGoals) || !d.rolesGoals.length) return '';
            // Dedupe roles the model sometimes repeats (keep first occurrence,
            // match case-insensitively on a normalized role title).
            const seen = new Set();
            const roles = d.rolesGoals.filter(r => {
                const key = String(r && r.role || '').trim().toLowerCase().replace(/\s+/g, ' ');
                if (!key || seen.has(key)) return false;
                seen.add(key);
                return true;
            });
            if (!roles.length) return '';
            const blocks = roles.map(r => `<div class="gs-role-block mb-2">
                <div class="gs-role-title"><strong>${esc(r.role)}</strong></div>
                <div class="gs-role-goal"><strong>Goal:</strong> ${esc(r.goal)}</div>
                <div class="gs-role-action"><strong>Actions:</strong> ${esc(r.action)}</div>
            </div>`).join('');
            return `<div class="gs-section gs-roles-goals">
                <h5>👥 Rephrased Goals by Role</h5>
                ${blocks}
            </div>`;
        }

        function sectionComplementary(d) {
            const list = cleanList(d.complementaryGoals);
            if (!list.length) return '';
            const items = list.map(g => `<li>${esc(g)}</li>`).join('');
            return `<div class="gs-section gs-complementary">
                <h5>📌 Complementary Goals</h5>
                <ul>${items}</ul>
            </div>`;
        }

        function sectionFinalOutcome(d) {
            if (!nonEmpty(d.finalOutcome)) return '';
            return `<div class="gs-section gs-final-outcome">
                <h5>✅ Final Outcome Summary</h5>
                <p>${esc(d.finalOutcome)}</p>
            </div>`;
        }

        // Flatten the nested contract (top-level sections + per-strategy /
        // per-scenario variants) into the flat shape the section renderers
        // expect, resolving the given (or default) strategy + scenario.
        window.flattenContract = function (data, selStrategy, selScenario) {
            if (!data || typeof data !== 'object') return data;
            const sid = selStrategy || data.selectedStrategyId
                || ((data.strategyMap || [])[0] || {}).id;
            // Fall back to the default/first strategy's variant when the
            // selected strategy has no own variant. Without this every
            // downstream section (scenarios, roles, outcome) vanishes for
            // strategies the model didn't generate (e.g. only s1 populated),
            // collapsing the wizard and showing "Finish" on the strategy step.
            const defSid = data.selectedStrategyId || ((data.strategyMap || [])[0] || {}).id;
            const v = (data.strategyVariants && (data.strategyVariants[sid]
                || data.strategyVariants[defSid])) || {};
            const scid = selScenario || v.selectedScenarioId
                || ((v.scenarios || [])[0] || {}).id;
            const sv = (v.scenarioVariants && v.scenarioVariants[scid]) || {};
            // Fall back to the default/first scenario's variant when the
            // selected scenario has no own downstream content. Without this the
            // roles/complementary/finalOutcome steps vanish for scenarios the
            // model didn't fully generate (e.g. only sc1 populated), which
            // collapses the wizard and shows "Finish" on the scenario step.
            const defScid = v.selectedScenarioId || ((v.scenarios || [])[0] || {}).id;
            const dsv = (v.scenarioVariants && v.scenarioVariants[defScid]) || {};
            // Prefer scenario-level content, but fall back to the default
            // scenario, then strategy-variant or top-level if the model placed
            // these fields higher up.
            return {
                acknowledgement: data.acknowledgement,
                documentInsights: data.documentInsights,
                goalAssessment: data.goalAssessment,
                scoring: data.scoring,
                strategyMap: data.strategyMap,
                selectedStrategyId: sid,
                scenarios: v.scenarios || [],
                selectedScenarioId: scid,
                rolesGoals: sv.rolesGoals || dsv.rolesGoals || v.rolesGoals || data.rolesGoals || [],
                complementaryGoals: sv.complementaryGoals || dsv.complementaryGoals || v.complementaryGoals || data.complementaryGoals || [],
                finalOutcome: sv.finalOutcome || dsv.finalOutcome || v.finalOutcome || data.finalOutcome
            };
        };

        // Render a full answer object to HTML. opts.interactive => radios for
        // strategy/scenario selection (live flow); otherwise read-only (reload).
        // Accepts either the flat view shape or the nested contract.
        window.renderAnswer = function (data, opts) {
            if (!data || typeof data !== 'object') return '';
            opts = opts || {};
            // Auto-flatten a nested contract (e.g. stored response on reload).
            if (data.strategyVariants) data = window.flattenContract(data);
            return [
                sectionAcknowledgement(data),
                sectionDocInsights(data),
                sectionGoalAssessment(data),
                sectionScoring(data),
                sectionStrategyMap(data, opts),
                sectionScenarios(data, opts),
                sectionRolesGoals(data),
                sectionComplementary(data),
                sectionFinalOutcome(data),
            ].filter(Boolean).join('\n');
        };

        // Detect whether a stored response is the new JSON contract or legacy
        // markdown, so the reload path can pick the right renderer (Phase 4).
        window.parseAnswerJson = function (raw) {
            if (!raw) return null;
            const t = String(raw).trim();
            if (t[0] !== '{' && t[0] !== '[') return null; // legacy markdown
            try {
                const d = JSON.parse(t);
                return (d && typeof d === 'object') ? d : null;
            } catch (e) {
                return null;
            }
        };

        // ----------------------------------------------------------------
        // Step-by-step wizard driven entirely by the JSON contract.
        // Strategy + scenario selection swap pre-generated variants
        // client-side (no extra AI calls). On Finish it renders the full
        // answer and triggers the action-table + alignment-brief extras.
        // ----------------------------------------------------------------
        window.renderJsonWizard = function (loadingDiv, data) {
            let selStrategy = data.selectedStrategyId
                || ((data.strategyMap || [])[0] || {}).id;

            const variant = () => (data.strategyVariants && data.strategyVariants[selStrategy]) || {};
            const scenarioList = () => variant().scenarios || [];
            let selScenario = variant().selectedScenarioId || (scenarioList()[0] || {}).id;

            // Flatten the active strategy/scenario into the shape the section
            // renderers (and window.renderAnswer) expect.
            function viewData() {
                return window.flattenContract(data, selStrategy, selScenario);
            }

            const allStepFns = [
                d => sectionAcknowledgement(d),
                d => sectionDocInsights(d),
                d => sectionGoalAssessment(d),
                d => sectionScoring(d),
                d => sectionStrategyMap(d, { interactive: true }),
                d => sectionScenarios(d, { interactive: true }),
                d => sectionRolesGoals(d),
                d => sectionComplementary(d),
                d => sectionFinalOutcome(d),
            ];

            // Recompute visible (non-empty) steps each render, so switching
            // strategy/scenario re-evaluates which sections have content
            // (e.g. a scenario that does have a Final Outcome).
            function visibleSteps() {
                const d = viewData();
                return allStepFns.filter(fn => fn(d).trim() !== '');
            }

            let step = 0;
            let inFinal = false;

            function strategyName() {
                const s = (data.strategyMap || []).find(x => x.id === selStrategy);
                return s ? s.name : '';
            }
            function scenarioLabel() {
                const sc = scenarioList().find(x => x.id === selScenario);
                return sc ? sc.label : '';
            }

            function wireSelection() {
                loadingDiv.querySelectorAll('input[name="gs-strategy"]').forEach(r => {
                    r.addEventListener('change', function () {
                        selStrategy = this.value;
                        selScenario = variant().selectedScenarioId || (scenarioList()[0] || {}).id;
                        inFinal ? renderFinal() : renderStep();
                    });
                });
                loadingDiv.querySelectorAll('input[name="gs-scenario"]').forEach(r => {
                    r.addEventListener('change', function () {
                        selScenario = this.value;
                        inFinal ? renderFinal() : renderStep();
                    });
                });
            }

            function renderStep() {
                inFinal = false;
                const d = viewData();
                const s = visibleSteps();
                if (step > s.length - 1) step = s.length - 1;
                if (step < 0) step = 0;
                const isLast = step === s.length - 1;
                loadingDiv.innerHTML = `
                    <div class="response-text">${s[step](d)}</div>
                    <div class="mt-2 gs-wizard-nav">
                        ${step > 0 ? '<button type="button" class="btn btn-secondary btn-sm gs-prev">Previous</button>' : ''}
                        <button type="button" class="btn btn-primary btn-sm gs-next">${isLast ? 'Finish' : 'Next'}</button>
                    </div>`;
                wireSelection();
            }

            function renderFinal() {
                inFinal = true;
                const d = viewData();
                window.selectedStrategy = strategyName();
                window.selectedScenario = scenarioLabel();
                loadingDiv.innerHTML = `
                    <div class="response-text">${window.renderAnswer(d, { interactive: true })}</div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm text-success copy-btn" data-bs-toggle="tooltip" title="Copy Answer"><i class="bi bi-copy"></i></button>
                    </div>`;
                wireSelection();
                setTimeout(() => {
                    // The brief may have been generated already during the final
                    // step; Finish rebuilds the DOM, so re-insert the cached copy
                    // instead of making a second API call. Otherwise generate it.
                    const card = loadingDiv.closest('.tt-template-carddads') || loadingDiv;
                    if (window.briefGenerationCompleted && window.generatedBriefHtml
                        && !card.querySelector('.leadership-alignment-brief')) {
                        const finalSec = Array.from(card.querySelectorAll('.response-text')).find(el =>
                            el.textContent.includes('✅') && el.textContent.includes('Final Outcome'));
                        const briefDiv = document.createElement('div');
                        briefDiv.className = 'leadership-alignment-brief mt-3';
                        const rt = document.createElement('div');
                        rt.className = 'response-text';
                        rt.innerHTML = window.generatedBriefHtml;
                        briefDiv.appendChild(rt);
                        (finalSec || loadingDiv).insertAdjacentElement('afterend', briefDiv);
                    } else if (typeof autoGenerateAlignmentBrief === 'function') {
                        autoGenerateAlignmentBrief();
                    }
                    if (typeof addActionTableSuggestion === 'function') addActionTableSuggestion();
                }, 300);
            }

            loadingDiv.addEventListener('click', function (e) {
                const next = e.target.closest('.gs-next');
                const prev = e.target.closest('.gs-prev');
                if (next) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Block Finish while the alignment brief is still generating
                    // so renderFinal doesn't rebuild the DOM and lose the
                    // in-progress brief (the button is also visually disabled).
                    if (window.briefGenerationInProgress) return;
                    if (step >= visibleSteps().length - 1) renderFinal();
                    else { step++; renderStep(); }
                } else if (prev) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (step > 0) { step--; renderStep(); }
                }
            });

            renderStep();
        };
    })();

    // Re-run the stored-answer renderer now that renderAnswer/parseAnswerJson
    // exist (this script runs after the one that first calls it), so stored
    // JSON answers render structured instead of falling back to raw markdown.
    if (typeof renderStoredMarkdown === 'function') renderStoredMarkdown();
</script>
<script>
    // document.getElementById('ask-form').addEventListener('submit', async function (e) {
    //     e.preventDefault();

    //     const questionInput = document.getElementById('question');
    //     const userIdInput = document.getElementById('user_id');
    //     const chatIdInput = document.getElementById('chat_id');
    //     const chatContainer = document.getElementById('chat-messages');

    //     const question = questionInput.value.trim();
    //     const user_id = userIdInput.value;
    //     const chat_id = chatIdInput.value;

    //     if (!question) {
    //         alert("Please enter a question.");
    //         return;
    //     }

    //     // Append user question
    //     const userCard = document.createElement('div');
    //     userCard.className = 'tt-template-carddads';
    //     userCard.innerHTML = `<div class="user-message">${question}</div>`;
    //     chatContainer.appendChild(userCard);

    //     // Append loader placeholder inside same card
    //     const loadingDiv = document.createElement('div');
    //     loadingDiv.className = 'bot-message';
    //     loadingDiv.innerHTML = `
    //         <div class="text-center text-info">
    //             <div class="spinner-border-img text-info" role="status">
    //                 <span class="visually-hidden">Loading...</span>
    //             </div>
    //             <div>
    //               <img class="custom-loder" width="150" height="150" src="/public/backend/assets/img/loader-img.gif" alt="">
    //             </div>
    //             <div class="mt-2">please wait...</div>
    //         </div>`;
    //     userCard.appendChild(loadingDiv);

    //     try {
    //         const res = await fetch('/dashboard/users-new-chat-ask', {
    //             method: 'POST',
    //             headers: {
    //                 'Content-Type': 'application/json',
    //                 'X-CSRF-TOKEN': '{{ csrf_token() }}'
    //             },
    //             body: JSON.stringify({ question, user_id, chat_id })
    //         });

    //         const data = await res.json();
    //         const answer = data.answer || 'No answer returned.';
    //         const formattedAnswer = marked.parse(answer);


    //         // Replace loading with actual bot response and add copy button
    //         loadingDiv.innerHTML = `
    //             <div class="response-text">${formattedAnswer}</div>
    //             <button type="button" class="btn btn-sm text-success mt-1 copy-btn" data-bs-toggle="tooltip" title="Copy Answer">
    //                 <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
    //                     stroke-linecap="round" stroke-linejoin="round" class="feather feather-copy">
    //                     <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
    //                     <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
    //                 </svg>
    //             </button>`;
    //     } catch (error) {
    //         console.error(error);
    //         loadingDiv.innerHTML = `<div class="alert alert-danger">Something went wrong while fetching the answer.</div>`;
    //     }

    //     questionInput.value = ''; // Clear input
    // });

    // // ✅ Copy answer (works for static + dynamic)
    // document.addEventListener('click', function (e) {
    //     if (e.target.closest('.copy-btn')) {
    //         const card = e.target.closest('.tt-template-carddads');
    //         const text = card.querySelector('.response-text')?.innerText || '';
    //         navigator.clipboard.writeText(text)
    //             .then(() => alert('Answer copied!'))
    //             .catch(err => alert('Copy failed: ' + err));
    //     }
    // });
</script>



 <script>
// Global variables to store current context
let currentUserCard = null;
let currentSendCallback = null;
let currentChatId = null;
let currentUserId = null;
let currentQuestion = null;
let currentUser_id = null;
let currentChat_id = null;
let currentContextOptionsDiv = null;

// Function to show add context modal
function showAddContextModal(userCard, chatId, userId, sendToChatGPTCallback) {
    currentUserCard = userCard;
    currentChatId = chatId;
    currentUserId = userId;
    currentSendCallback = sendToChatGPTCallback;
    
    // Clear form
    document.getElementById('context-field').value = '';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('addContextModal'));
    modal.show();
}

// Function to submit context from modal
async function submitContextFromModal() {
    const contextField = document.getElementById('context-field').value.trim();

    // Check if field has content
    if (!contextField) {
        alert('Please enter some context before submitting.');
        return;
    }

    // Show loading state
    const submitBtn = document.getElementById('submitContextBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Processing...';

    // Prepare context data
    const contextData = {
        additional_details: contextField
    };

    // Save context to database (optional, for future use)
    try {
        const response = await fetch('/dashboard/users-new-chat-add-context', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                chat_id: currentChatId,
                user_id: currentUserId,
                field1: '',
                field2: '',
                field3: '',
                additional_details: contextField
            })
        });
        // Don't wait for response, just log it
        response.json().then(data => {
            if (data.success) {
                console.log('Context saved to database');
            }
        }).catch(err => console.error('Error saving context:', err));
    } catch (error) {
        console.error('Error saving context to database:', error);
        // Continue anyway, context will still be sent to StrategiStudio
    }

    // Hide modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('addContextModal'));
    modal.hide();

    // Reset button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;

    // Call the sendToChatGPT callback with context
    if (currentSendCallback) {
        currentSendCallback(contextData);
    }
}

// Event listener for modal submit button
document.addEventListener('DOMContentLoaded', function() {
    const submitContextBtn = document.getElementById('submitContextBtn');
    if (submitContextBtn) {
        submitContextBtn.addEventListener('click', function() {
            submitContextFromModal();
        });
    }
});

// Chat lifetime token total (authoritative value from the server, persisted in
// the DB across all sections and page reloads). Seeded from the badge on load.
(function () {
    const badge = document.getElementById('token-usage-badge');
    window.chatTokenTotal = badge ? (parseInt(badge.getAttribute('data-chat-total'), 10) || 0) : 0;
})();

// Update the header badge with the chat's lifetime token total. Pass the server's
// chat_total_tokens; optionally lastUsage { prompt_tokens, completion_tokens,
// total_tokens } to show the last request's breakdown in the tooltip.
window.setChatTokenTotal = function (total, lastUsage) {
    const badge = document.getElementById('token-usage-badge');
    const text = document.getElementById('token-usage-text');
    if (!badge || !text) return;

    const parsed = parseInt(total, 10);
    if (!isNaN(parsed)) window.chatTokenTotal = parsed;

    const fmt = (n) => (parseInt(n, 10) || 0).toLocaleString();
    text.textContent = `${fmt(window.chatTokenTotal)} tokens`;
    badge.style.display = '';

    let title = `Total tokens used in this chat (all sections): ${fmt(window.chatTokenTotal)}.`;
    if (lastUsage) {
        title += ` Last request: ${fmt(lastUsage.prompt_tokens)} prompt + `
            + `${fmt(lastUsage.completion_tokens)} response = ${fmt(lastUsage.total_tokens)} tokens.`;
    }
    badge.setAttribute('title', title);
    if (window.bootstrap && window.bootstrap.Tooltip) {
        const inst = window.bootstrap.Tooltip.getInstance(badge);
        if (inst) inst.dispose();
        new window.bootstrap.Tooltip(badge);
    }
};

document.getElementById('ask-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const questionInput = document.getElementById('question');
    const userIdInput = document.getElementById('user_id');
    const chatIdInput = document.getElementById('chat_id');
    const chatContainer = document.getElementById('chat-messages');

    const question = questionInput.value.trim();
    const user_id = userIdInput.value;
    const chat_id = chatIdInput.value;

    // Validate question before proceeding
    if (!question) {
        alert("Please enter a question or goal.");
        questionInput.focus();
        return;
    }
    
    // Validate chat_id
    if (!chat_id) {
        alert("Chat ID is missing. Please refresh the page and try again.");
        return;
    }
    
    // Validate user_id
    if (!user_id) {
        alert("User ID is missing. Please refresh the page and try again.");
        return;
    }

    const userCard = document.createElement('div');
    userCard.className = 'tt-template-carddads';
    userCard.innerHTML = `<div class="user-message">${question}</div>`;
    chatContainer.appendChild(userCard);

    // Show context options buttons instead of immediately sending
    const contextOptionsDiv = document.createElement('div');
    contextOptionsDiv.className = 'context-options-container mt-3';
    contextOptionsDiv.innerHTML = `
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm add-context-btn">
                <i class="bi bi-plus-circle me-1"></i> Add More Context
            </button>
            <button type="button" class="btn btn-primary btn-sm skip-context-btn">
                <i class="bi bi-arrow-right me-1"></i> Continue to StrategiStudio
            </button>
        </div>
    `;
    userCard.appendChild(contextOptionsDiv);

    // Store question and IDs for later use
    userCard.dataset.question = question;
    userCard.dataset.userId = user_id;
    userCard.dataset.chatId = chat_id;

    // Store values globally for sendToChatGPT function
    currentQuestion = question;
    currentUser_id = user_id;
    currentChat_id = chat_id;
    currentUserCard = userCard;
    currentContextOptionsDiv = contextOptionsDiv;

    // Function to actually send the request to StrategiStudio
    window.sendToChatGPT = async function(contextData = null) {
        // Validate required fields
        if (!currentQuestion || !currentQuestion.trim()) {
            alert('Please enter a question or goal.');
            return;
        }
        
        if (!currentChat_id) {
            alert('Chat ID is missing. Please refresh the page and try again.');
            return;
        }
        
        if (!currentUser_id) {
            alert('User ID is missing. Please refresh the page and try again.');
            return;
        }
        
        // Hide context options
        if (currentContextOptionsDiv) {
            currentContextOptionsDiv.style.display = 'none';
        }
        
        // Show loading
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'bot-message';
        loadingDiv.innerHTML = `
            <div class="text-center text-info" style="padding: 20px;">
                <img class="custom-loder" width="150" height="150" src="${loaderImageUrl}" alt="Loading..." style="display: block; margin: 0 auto 10px auto; max-width: 150px; height: auto;">
                <div class="mt-2">please wait...</div>
            </div>`;
        if (currentUserCard) {
            currentUserCard.appendChild(loadingDiv);
        }

        // Prepare request body
        const requestBody = {
            question: currentQuestion.trim(),
            user_id: currentUser_id,
            chat_id: currentChat_id
        };

        // Add context if provided
        if (contextData) {
            requestBody.additional_context = contextData;
        }

        // try {
            const res = await fetch('/dashboard/users-new-chat-ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(requestBody)
            });

            if (!res.ok) {
                const errorText = await res.text();
                loadingDiv.innerHTML = `<div class="alert alert-danger">Error: ${res.status} ${res.statusText}. ${errorText.substring(0, 200)}</div>`;
                return;
            }

            const data = await res.json();

            // Surface the chat's lifetime token total (all sections) in the header.
            if (data.chat_total_tokens !== undefined) window.setChatTokenTotal(data.chat_total_tokens, data.usage);

            // JSON contract path (Phase 2): render via the structured wizard
            // instead of the markdown split/collapse pipeline. Falls through to
            // markdown if the payload isn't valid JSON.
            if (data.format === 'json') {
                const parsedAnswer = window.parseAnswerJson(data.answer);
                if (parsedAnswer) {
                    if (data.chat_id) {
                        window.chatChatId = data.chat_id;
                        const ci = document.getElementById('chat_id');
                        if (ci) ci.value = data.chat_id;
                    }
                    window.chatUserId = window.chatUserId || currentUser_id;
                    window.chatQuestion = window.chatQuestion || currentQuestion;
                    window.renderJsonWizard(loadingDiv, parsedAnswer);
                    return;
                }
                // not parseable -> continue with markdown rendering below
            }

            let fullAnswer = data.answer || 'No answer returned.';
            const previousContext = data.previousContext || {};
            const chectdata = data.chectdata || {};

            // Force each role's "Actions:" onto a single line — but ONLY inside the
            // 👥 Rephrased Goals by Role section, never anywhere else. Model often
            // returns multi-line actions even when told not to.
            window.collapseActions = function (md) {
                if (!md) return md;
                const startIdx = md.indexOf('👥');
                if (startIdx === -1) return md;
                // roles section ends at the next major section marker
                let endIdx = md.length;
                ['📌', '✅', '🔮'].forEach(em => {
                    const p = md.indexOf(em, startIdx + 1);
                    if (p !== -1 && p < endIdx) endIdx = p;
                });
                const before = md.slice(0, startIdx);
                const section = md.slice(startIdx, endIdx);
                const after = md.slice(endIdx);

                const lines = section.split('\n');
                const out = [];
                for (let i = 0; i < lines.length; i++) {
                    // "Actions:" line — collapse any multi-line actions into one,
                    // bold the label so it always renders as its own visible line.
                    const m = lines[i].match(/^(\s*)Actions:\s*(.*)$/i);
                    if (m) {
                        const parts = [];
                        if (m[2].trim()) parts.push(m[2].trim().replace(/^[-•*]\s*/, ''));
                        let j = i + 1;
                        for (; j < lines.length; j++) {
                            const t = lines[j].trim();
                            if (t === '') break;
                            if (/^\d+[.)]\s/.test(t)) break;             // next numbered role
                            if (/^[🔮👥📌✅📁📊📈🗺️🧩]/.test(t)) break;     // next section
                            if (/^Goal:/i.test(t)) break;
                            // lookahead: a role title line is followed by a "Goal:" line
                            let k = j + 1;
                            while (k < lines.length && lines[k].trim() === '') k++;
                            if (k < lines.length && /^Goal:/i.test(lines[k].trim())) break;
                            parts.push(t.replace(/^[-•*]\s*/, ''));
                        }
                        out.push(`${m[1]}**Actions:** ${parts.join(' ').replace(/\s+/g, ' ').trim()}  `);
                        i = j - 1;
                        continue;
                    }

                    // "Goal:" line — bold the label and force a hard line break
                    // (two trailing spaces) so the following "Actions:" line renders
                    // on its own line instead of being soft-joined by markdown.
                    // Also split an inline "... Actions: ..." that the model put on
                    // the SAME line as the goal into its own Actions line.
                    const g = lines[i].match(/^(\s*)Goal:\s*(.*)$/i);
                    if (g) {
                        const indent = g[1];
                        let goalText = g[2].trim();
                        const aIdx = goalText.search(/\bActions:/i);
                        if (aIdx !== -1) {
                            const actionText = goalText.slice(aIdx).replace(/^Actions:\s*/i, '').trim();
                            goalText = goalText.slice(0, aIdx).trim();
                            out.push(`${indent}**Goal:** ${goalText}  `);
                            out.push(`${indent}**Actions:** ${actionText}  `);
                        } else {
                            out.push(`${indent}**Goal:** ${goalText}  `);
                        }
                        continue;
                    }

                    // Role title line (non-empty, not a bullet/section header) that is
                    // followed by a "Goal:" line — bold it and add a hard break.
                    const raw = lines[i];
                    const trimmed = raw.trim();
                    if (trimmed && !/^[-•*]/.test(trimmed) && !/^[🔮👥📌✅📁📊📈🗺️🧩]/.test(trimmed)) {
                        let k = i + 1;
                        while (k < lines.length && lines[k].trim() === '') k++;
                        if (k < lines.length && /^Goal:/i.test(lines[k].trim())) {
                            const title = trimmed.replace(/\*\*/g, '').replace(/^(\d+[.)]\s*)/, '');
                            // Blank line before the title forces a paragraph break so
                            // the first role never soft-joins with the 👥 header line.
                            if (out.length && out[out.length - 1].trim() !== '') {
                                out.push('');
                            }
                            out.push(`**${title}**  `);
                            continue;
                        }
                    }

                    out.push(raw);
                }
                return before + out.join('\n') + after;
            };

            // Phase 1: extract per-strategy JSON bundles emitted by the first call.
            // These let strategy/scenario selection render client-side with no extra
            // AI calls. If parsing fails we strip nothing and fall back to fetches.
            window.strategyBundles = null;
            try {
                const bundleMatch = fullAnswer.match(/%%%BUNDLES_JSON%%%([\s\S]*?)%%%END_BUNDLES_JSON%%%/);
                if (bundleMatch) {
                    // Remove the data block from what we display to the user
                    fullAnswer = fullAnswer.replace(bundleMatch[0], '').trim();
                    let raw = bundleMatch[1].trim().replace(/^```(?:json)?/i, '').replace(/```$/, '').trim();
                    window.strategyBundles = JSON.parse(raw);
                    // Collapse Actions inside every bundle value too
                    Object.keys(window.strategyBundles).forEach(k => {
                        window.strategyBundles[k] = window.collapseActions(window.strategyBundles[k]);
                    });
                    console.log('✅ Parsed strategy bundles:', Object.keys(window.strategyBundles));
                }
            } catch (e) {
                window.strategyBundles = null;
                console.warn('⚠️ Could not parse strategy bundles, falling back to per-selection AI calls:', e);
            }

            // Collapse Actions in the visible first-response text
            fullAnswer = window.collapseActions(fullAnswer);
            
            // Update chat_id if a new one was created
            if (data.chat_id && data.chat_id !== currentChat_id) {
                currentChat_id = data.chat_id;
                const chatIdInput = document.getElementById('chat_id');
                if (chatIdInput) {
                    chatIdInput.value = data.chat_id;
                }
            }

            // Check if this is the first message (status1 is 0) or status2 is 0
            const isFirstMessage = (previousContext && (previousContext.status1 === 0 || previousContext.status1 === '0')) || 
                                   (chectdata && (chectdata.status1 === 0 || chectdata.status1 === '0'));
            const isSecondMessage = (previousContext && (previousContext.status2 === 0 || previousContext.status2 === '0')) || 
                                    (chectdata && (chectdata.status2 === 0 || chectdata.status2 === '0'));

            if (isFirstMessage) {
            // Parse the full response into sections
            const sections = fullAnswer.split(/(?=🧩|📁|📊|📈|🗺️|🔮|👥|📌|✅|📋)/);
            if (!sections.length) {
                loadingDiv.innerHTML = `<div class="alert alert-warning">No sections found in the response.</div>`;
                return;
            }

            // Find Strategy Map section and extract strategy points
            const strategyMapIndex = sections.findIndex(s => s.includes('🗺️'));
            let strategyPoints = [];
            let strategyMapSection = '';
            
            // Store sections globally for eager loading
            window.chatSections = sections;
            window.chatQuestion = question;
            window.chatUserId = user_id;
            window.chatChatId = chat_id;
            
            // Check if there's a selected strategy from database (page reload)
            @if(isset($selectedStrategyFromDB) && $selectedStrategyFromDB)
                window.selectedStrategy = @json($selectedStrategyFromDB);
                console.log('Loaded selected strategy from DB:', window.selectedStrategy);
            @endif
            
            // Initialize selectedStrategy globally if not set
            if (!window.selectedStrategy) {
                window.selectedStrategy = null;
            }
            let selectedStrategy = window.selectedStrategy;

            if (strategyMapIndex !== -1) {
                strategyMapSection = sections[strategyMapIndex];

                // Extract strategy points
                const strategyLines = strategyMapSection.split('\n');
                let foundHeader = false;
                const strategyItems = [];
                
                for (let i = 0; i < strategyLines.length; i++) {
                    const line = strategyLines[i].trim();
                    
                    if (!line) continue;
                    
                    if (line.includes('🗺️') || line.includes('Strategy Map')) {
                        foundHeader = true;
                        continue;
                    }
                    
                    if (foundHeader && line && 
                        (line.startsWith('-') || 
                         line.startsWith('•') || 
                         line.startsWith('*') ||
                         /^\d+\./.test(line) ||
                         /^[A-Z][a-z]+:/.test(line))) {
                        strategyItems.push(line);
                    }
                }

                strategyPoints = strategyItems.map(line => {
                    let cleaned = line.replace(/^[-•*]\s*/, '').replace(/^\d+\.\s*/, '');
                    cleaned = cleaned.trim();
                    
                    // Remove markdown bold formatting (**text**) from the strategy text
                    // This ensures cache keys don't have ** in them
                    cleaned = cleaned.replace(/\*\*([^*]+?):\*\*/g, '$1:');
                    cleaned = cleaned.replace(/\*\*([^*]+?)\*\*/g, '$1');
                    cleaned = cleaned.replace(/\*\*/g, '');
                    
                    // Remove "Path A:", "Path B:", "Path C:", "Path D:" patterns (case insensitive)
                    cleaned = cleaned.replace(/^Path\s+[A-Z][\.:]\s*/i, '');
                    
                    // Remove "Path 1:", "Path 2:", "Path 3:", "Path 4:" patterns (case insensitive)
                    cleaned = cleaned.replace(/^Path\s+\d+[\.:]\s*/i, '');
                    
                    // Remove any leading numbers or "Strategy 1:", "Strategy 2:" patterns
                    cleaned = cleaned.replace(/^(Strategy\s+)?\d+[\.:]\s*/i, '');
                    cleaned = cleaned.replace(/^\d+[\.:]\s*/, '');
                    
                    return cleaned;
                }).filter(point => point.length > 0);
                
                // Enforce maximum of 4 strategies (take first 4 if more are found)
                if (strategyPoints.length > 4) {
                    console.log(`Found ${strategyPoints.length} strategies, limiting to 4`);
                    strategyPoints = strategyPoints.slice(0, 4);
                }
                
                // Ensure minimum of 3 strategies (if less than 3, keep what we have)
                if (strategyPoints.length < 3) {
                    console.log(`Warning: Only found ${strategyPoints.length} strategies (minimum is 3)`);
                }
            }

            // Store strategy data globally for eager loading
            window.strategyPoints = strategyPoints;
            window.strategyMapSection = strategyMapSection;
            window.strategyMapIndex = strategyMapIndex;

            // Scenario simulations parsing
            const scenarioIndex = sections.findIndex(s => s.includes('🔮'));
            let scenarioOptions = [];
            let scenarioSection = '';

            if (scenarioIndex !== -1) {
                scenarioSection = sections[scenarioIndex];

                // Defensive: the model sometimes leaks role "Goal:"/"Actions:" lines
                // into the 🔮 Scenario Simulations section (before the 👥 header).
                // Cut the section at the first such line so raw-markdown render paths
                // never show role goals/actions under Scenario Simulations.
                const rawScenarioLines = scenarioSection.split('\n');
                let scenarioCutAt = rawScenarioLines.length;
                for (let i = 1; i < rawScenarioLines.length; i++) {
                    const t = rawScenarioLines[i].trim().replace(/^[-•*]\s*/, '').replace(/\*/g, '');
                    if (/^Goal:/i.test(t) || /^Actions:/i.test(t) || rawScenarioLines[i].includes('👥')) {
                        scenarioCutAt = i;
                        break;
                    }
                }
                scenarioSection = rawScenarioLines.slice(0, scenarioCutAt).join('\n').trim();
                // Keep the sections array in sync so the raw-markdown fallback
                // render path (marked.parse(step)) also shows the cleaned section.
                sections[scenarioIndex] = scenarioSection;

                const scenarioLines = scenarioSection.split('\n');
                let foundScenarioHeader = false;
                const scenarioItems = [];

                scenarioLines.forEach(line => {
                    const trimmed = line.trim();
                    if (!trimmed) {
                        return;
                    }

                    if (trimmed.includes('🔮') || trimmed.toLowerCase().includes('scenario')) {
                        foundScenarioHeader = true;
                        return;
                    }

                    if (foundScenarioHeader && (trimmed.startsWith('-') || trimmed.startsWith('•') || trimmed.startsWith('*'))) {
                        scenarioItems.push(trimmed);
                    }
                });

                scenarioOptions = scenarioItems.map(line => {
                    let cleaned = line.replace(/^[-•*]\s*/, '').trim();
                    cleaned = cleaned.replace(/\*\*([^*]+)\*\*/g, '**$1**'); // normalize bold markers
                    return cleaned;
                }).filter(item => item.length > 0);

                if (scenarioOptions.length > 4) {
                    console.log(`Found ${scenarioOptions.length} scenarios, limiting to 4`);
                    scenarioOptions = scenarioOptions.slice(0, 4);
                }

                if (scenarioOptions.length < 3 && scenarioOptions.length > 0) {
                    console.log(`Warning: Only ${scenarioOptions.length} scenarios detected (minimum expected is 3).`);
                }
            }

            window.scenarioIndex = scenarioIndex;
            window.scenarioSection = scenarioSection;
            window.scenarioOptions = scenarioOptions;
            if (!window.selectedScenario && scenarioOptions.length > 0) {
                window.selectedScenario = scenarioOptions[0];
            }

            // Store cache globally so eager loading can access it
            if (!window.strategyResponsesCache) {
                window.strategyResponsesCache = {};
            }
            if (!window.scenarioResponsesCache) {
                window.scenarioResponsesCache = {};
            }
            const strategyResponsesCache = window.strategyResponsesCache;

            // Phase 1: pre-fill strategy + scenario caches from the bundles returned
            // by the first call. With caches warm, selecting a strategy/scenario
            // renders client-side and never fires an extra AI call.
            if (window.strategyBundles && typeof window.strategyBundles === 'object') {
                const bundleKeys = Object.keys(window.strategyBundles);
                const matchBundle = (point) => {
                    if (window.strategyBundles[point]) return window.strategyBundles[point];
                    const p = point.substring(0, 30).toLowerCase();
                    const k = bundleKeys.find(bk =>
                        bk.toLowerCase().includes(p) ||
                        point.toLowerCase().includes(bk.substring(0, 30).toLowerCase()));
                    return k ? window.strategyBundles[k] : null;
                };
                (window.strategyPoints || []).forEach(point => {
                    const block = matchBundle(point);
                    if (!block) return;
                    window.strategyResponsesCache[point] = block;

                    // 👥📌✅ portion is what the scenario cache serves
                    const rolesIdx = block.indexOf('👥');
                    const rolesPart = rolesIdx !== -1 ? block.substring(rolesIdx) : block;

                    // this strategy's own scenarios (from its 🔮 section)
                    const scenMatch = block.match(/🔮[\s\S]*?(?=👥|📌|✅|$)/);
                    const strategyKey = point.substring(0, 50);
                    if (scenMatch) {
                        scenMatch[0].split('\n').forEach(line => {
                            const t = line.trim();
                            if (t && (t.startsWith('-') || t.startsWith('•') || t.startsWith('*'))) {
                                const sc = t.replace(/^[-•*]\s*/, '')
                                            .replace(/\*\*([^*]+)\*\*/g, '$1')
                                            .replace(/\*\*/g, '').trim();
                                if (sc) window.scenarioResponsesCache[`${strategyKey}||${sc}`] = rolesPart;
                            }
                        });
                    }
                });
                console.log('✅ Pre-filled caches from bundles. Strategies cached:', Object.keys(window.strategyResponsesCache));
            }

            let currentStep = 0;

            // Track API call states for Next button control
            window.apiCallInProgress = false;
            window.pendingApiCalls = 0;
            
            // Function to check if next step's data is ready
            function isNextStepDataReady(nextStepIndex) {
                // If next step IS the strategy map, strategies are already in the response - always ready
                if (strategyMapIndex !== -1 && nextStepIndex === strategyMapIndex) {
                    // Strategies are in the first response, no need to check loading
                    return true;
                }
                
                // If next step is after strategy map, check if selected strategy data is ready
                if (strategyMapIndex !== -1 && nextStepIndex > strategyMapIndex) {
                    const selectedStrategy = window.selectedStrategy;
                    if (selectedStrategy) {
                        // Check if strategy-specific content is cached
                        if (!window.strategyResponsesCache || !window.strategyResponsesCache[selectedStrategy]) {
                            return false;
                        }
                    } else {
                        // No strategy selected yet, but we can still show generic content from first response
                        // So allow navigation
                        return true;
                    }
                }
                
                // If next step IS the scenario selection, scenarios are already in the response
                if (scenarioIndex !== -1 && nextStepIndex === scenarioIndex) {
                    // Scenarios are in the first response, no need to check loading
                    return true;
                }
                
                // If next step is after scenario selection, check if selected scenario data is ready
                if (scenarioIndex !== -1 && nextStepIndex > scenarioIndex) {
                    const selectedScenario = window.selectedScenario;
                    if (selectedScenario) {
                        // Check if scenario response is cached (using strategy-specific cache key)
                        const strategyKey = window.selectedStrategy ? window.selectedStrategy.substring(0, 50) : 'no-strategy';
                        const scenarioCacheKey = `${strategyKey}||${selectedScenario}`;
                        if (!window.scenarioResponsesCache || !window.scenarioResponsesCache[scenarioCacheKey]) {
                            console.log('⚠️ Scenario data not ready - cache key:', scenarioCacheKey);
                            return false;
                        }
                        console.log('✅ Scenario data ready - cache key:', scenarioCacheKey);
                    }
                }
                
                return true;
            }
            
            // Function to update Next button state
            function updateNextButtonState() {
                const nextBtn = loadingDiv.querySelector('.next-step-btn');
                if (!nextBtn) return;
                
                const nextStepIndex = currentStep + 1;
                
                // Check if there's a next step
                if (nextStepIndex >= sections.length) {
                    // This is the last step, always enable
                    nextBtn.disabled = false;
                    nextBtn.classList.remove('disabled');
                    return;
                }
                
                // Check if NEXT step's data is ready (this applies to ALL steps including step 0)
                const dataReady = isNextStepDataReady(nextStepIndex);
                
                if (!dataReady) {
                    // Next step's data isn't ready yet - disable button
                    nextBtn.disabled = true;
                    nextBtn.classList.add('disabled');
                    if (!nextBtn.dataset.originalText) {
                        nextBtn.dataset.originalText = nextBtn.textContent.trim();
                    }
                    nextBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Loading...';
                } else {
                    // Next step's data is ready, enable button
                    nextBtn.disabled = false;
                    nextBtn.classList.remove('disabled');
                    const originalText = nextBtn.dataset.originalText || (currentStep === sections.length - 1 ? 'Finish' : 'Next');
                    nextBtn.innerHTML = originalText;
                }
            }

            // Strategies are already in the first response - no need to pre-load them
            // We only load strategy-specific content when user selects a strategy
            
            // Scenarios are already in the first response - no need to pre-load them
            // We only load scenario-specific content when user actually selects a scenario
            
            // Don't pre-load strategies - strategies are already in the first response
            // Only load strategy-specific content when user actually selects a strategy
            function startLoadingStrategiesIfNeeded() {
                // Strategies are already in the response, no need to pre-load
                // We'll load strategy-specific content only when user selects one
                return;
            }
            
            // Scenarios are already in the first response - no need to pre-load them
            // We only load scenario-specific content when user actually selects a scenario
            function startLoadingScenariosIfNeeded() {
                // Scenarios are already in the response, no need to pre-load
                // We'll load scenario-specific content only when user selects one
                return;
            }


            function getUpdatedSectionParts(responseText, emojiOrder = ['🔮', '👥', '📌', '✅']) {
                if (!responseText) {
                    return [];
                }

                const parts = [];
                emojiOrder.forEach((emoji, emojiIdx) => {
                    const emojiIndex = responseText.indexOf(emoji);
                    if (emojiIndex !== -1) {
                        let nextEmojiIndex = responseText.length;

                        for (let i = emojiIdx + 1; i < emojiOrder.length; i++) {
                            const nextEmoji = emojiOrder[i];
                            const nextIndex = responseText.indexOf(nextEmoji, emojiIndex + 1);
                            if (nextIndex !== -1) {
                                nextEmojiIndex = Math.min(nextEmojiIndex, nextIndex);
                                break;
                            }
                        }

                        const sectionContent = responseText.substring(emojiIndex, nextEmojiIndex).trim();
                        if (sectionContent && sectionContent.length > 10) {
                            parts.push(sectionContent);
                        }
                    }
                });

                return parts;
            }

            async function fetchScenarioResponse(scenarioText, isUserSelection = false) {
                if (!scenarioText) return;

                if (!window.scenarioResponsesCache) {
                    window.scenarioResponsesCache = {};
                }
                
                // CRITICAL: Include strategy in cache key since scenarios are strategy-specific
                // This ensures "Best Case:" from Strategy 1 is different from "Best Case:" from Strategy 2
                const strategyKey = window.selectedStrategy ? window.selectedStrategy.substring(0, 50) : 'no-strategy';
                const cacheKey = `${strategyKey}||${scenarioText}`;
                
                console.log('🔑 Scenario cache key:', cacheKey);
                console.log('🔑 Strategy:', strategyKey);
                console.log('🔑 Scenario:', scenarioText.substring(0, 50));

                if (window.scenarioResponsesCache[cacheKey]) {
                    console.log('✅ Using cached scenario response');
                    if (currentStep > scenarioIndex && typeof renderStep === 'function') {
                        renderStep();
                    }
                    return;
                }
                
                console.log('🔄 Fetching new scenario response (not in cache)');

                // Track API call
                window.pendingApiCalls++;
                window.apiCallInProgress = true;
                updateNextButtonState();

                const sectionsBeforeScenario = window.chatSections && window.scenarioIndex > -1
                    ? window.chatSections.slice(0, window.scenarioIndex).join('')
                    : '';

                try {
                    const scenarioRes = await fetch('/dashboard/users-new-chat-update-scenario', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            chat_id: window.chatChatId,
                            user_id: window.chatUserId,
                            selected_scenario: scenarioText,
                            selected_strategy: window.selectedStrategy || null,
                            original_question: window.chatQuestion,
                            sections_before: sectionsBeforeScenario,
                            scenario_section: window.scenarioSection,
                            is_user_selection: isUserSelection
                        })
                    });

                    if (scenarioRes.ok) {
                        const updateData = await scenarioRes.json();
                        if (updateData.chat_total_tokens !== undefined) window.setChatTokenTotal(updateData.chat_total_tokens);
                        const response = window.collapseActions(updateData.updated_sections || '');
                        if (!window.scenarioResponsesCache) {
                            window.scenarioResponsesCache = {};
                        }
                        // Use strategy-specific cache key
                        const strategyKey = window.selectedStrategy ? window.selectedStrategy.substring(0, 50) : 'no-strategy';
                        const cacheKey = `${strategyKey}||${scenarioText}`;
                        window.scenarioResponsesCache[cacheKey] = response;
                        console.log('💾 Cached scenario response with key:', cacheKey);
                        if (currentStep > scenarioIndex && typeof renderStep === 'function') {
                            renderStep();
                        }
                        // Update Next button state when scenario data is loaded
                        updateNextButtonState();
                    }
                } catch (error) {
                    console.error(`Error loading scenario "${scenarioText}":`, error);
                } finally {
                    // Update API call state
                    window.pendingApiCalls = Math.max(0, window.pendingApiCalls - 1);
                    window.apiCallInProgress = window.pendingApiCalls > 0;
                    updateNextButtonState();
                }
            }

            function renderStep() {
                const step = sections[currentStep];
                let stepHtml = '';
                
                console.log('Rendering step:', currentStep, 'of', sections.length);
                console.log('Strategy Map Index:', strategyMapIndex);
                console.log('Selected Strategy:', window.selectedStrategy);
                console.log('Cache keys:', window.strategyResponsesCache ? Object.keys(window.strategyResponsesCache) : 'No cache');
                
                // Check if this is the Strategy Map section
                if (currentStep === strategyMapIndex && strategyPoints.length > 0) {
                    // Render Strategy Map with radio buttons
                    const strategyLines = strategyMapSection.split('\n');
                    const headerLine = strategyLines.find(line => line.includes('🗺️') || line.includes('Strategy Map'));
                    
                    stepHtml = `<div class="response-text">`;
                    if (headerLine) {
                        // Parse header but clean it up
                        let cleanHeader = headerLine.replace(/\*\*/g, '').trim();
                        stepHtml += marked.parse(cleanHeader);
                    }
                    
                    stepHtml += `<div class="strategy-options mt-3">`;
                    
                    strategyPoints.forEach((point, index) => {
                        const strategyId = `strategy-${Date.now()}-${index}`;
                        
                        // Point is already cleaned (no **) from extraction, but we need to add bold formatting for display
                        // Strategy format is usually: "Strategy Name: Description"
                        // We want to make "Strategy Name:" bold
                        let displayPoint = point;
                        
                        // Find the colon and make the part before it bold
                        const colonIndex = displayPoint.indexOf(':');
                        if (colonIndex > 0) {
                            const strategyName = displayPoint.substring(0, colonIndex + 1);
                            const description = displayPoint.substring(colonIndex + 1);
                            displayPoint = `<strong>${strategyName}</strong>${description}`;
                        } else {
                            // If no colon, just make the first few words bold (first 30 chars or until space)
                            const words = displayPoint.split(' ');
                            if (words.length > 0) {
                                const firstPart = words.slice(0, Math.min(3, words.length)).join(' ');
                                const rest = words.slice(Math.min(3, words.length)).join(' ');
                                displayPoint = `<strong>${firstPart}</strong> ${rest}`;
                            }
                        }
                        // For the value attribute, use plain text (point is already clean)
                        const escapedPoint = point.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        
                        // Check cache using clean point (no **)
                        const isLoaded = window.strategyResponsesCache && window.strategyResponsesCache.hasOwnProperty(point);
                        const isChecked = selectedStrategy === point || (selectedStrategy && selectedStrategy.includes(point.substring(0, 30)));
                        
                        stepHtml += `
                            <div class="strategy-option mb-2 ${isLoaded ? 'strategy-loaded' : ''}">
                                <input type="radio" 
                                       id="${strategyId}" 
                                       name="selected-strategy-${Date.now()}" 
                                       value="${escapedPoint}" 
                                       data-original-strategy="${escapedPoint}"
                                       class="strategy-radio me-2"
                                       ${isChecked ? 'checked' : ''}>
                                <label for="${strategyId}" class="strategy-label">
                                    ${displayPoint}
                                </label>
                            </div>
                        `;
                    });
                    
                    stepHtml += `</div></div>`;

                    // Strategies should already be loaded (started 1 step behind)
                    // Just show current status
                } else if (currentStep === scenarioIndex && window.scenarioOptions && window.scenarioOptions.length > 0) {
                    // Use window.scenarioOptions which gets updated when strategy is selected
                    // CRITICAL: Always use the latest window.scenarioOptions (updated when strategy changes)
                    const currentScenarioOptions = window.scenarioOptions || [];
                    console.log('🔍 ===== RENDERING SCENARIO PAGE =====');
                    console.log('🔍 Current step:', currentStep);
                    console.log('🔍 Scenario index:', scenarioIndex);
                    console.log('🔍 window.scenarioOptions:', currentScenarioOptions);
                    console.log('🔍 window.scenarioOptions length:', currentScenarioOptions.length);
                    console.log('🔍 Current selectedScenario:', window.selectedScenario);
                    console.log('🔍 Selected strategy:', window.selectedStrategy);
                    
                    if (currentScenarioOptions.length === 0) {
                        console.error('❌ ERROR: window.scenarioOptions is empty!');
                    }
                    
                    const scenarioLines = (window.scenarioSection || '').split('\n');
                    const scenarioHeaderLine = scenarioLines.find(line => line.includes('🔮') || line.toLowerCase().includes('scenario'));
                    const scenarioGroupName = `scenario-${Date.now()}`;
                    
                    // Always use first scenario if selectedScenario doesn't match (scenarios changed with strategy)
                    let activeScenario = currentScenarioOptions[0] || null;
                    
                    // Only try to match if we have a selectedScenario and it exists in current options
                    if (window.selectedScenario && currentScenarioOptions.length > 0) {
                        // Try exact match first
                        if (currentScenarioOptions.includes(window.selectedScenario)) {
                            activeScenario = window.selectedScenario;
                            console.log('🔍 Exact match found for selectedScenario');
                        } else {
                            // Try fuzzy match - check if any scenario starts with selected scenario or vice versa
                            const match = currentScenarioOptions.find(opt => 
                                opt === window.selectedScenario ||
                                opt.startsWith(window.selectedScenario.substring(0, 30)) ||
                                window.selectedScenario.startsWith(opt.substring(0, 30))
                            );
                            if (match) {
                                activeScenario = match;
                                console.log('🔍 Fuzzy match found for selectedScenario');
                            } else {
                                console.log('🔍 No match found, using first scenario:', activeScenario);
                            }
                        }
                    }
                    
                    // Update window.selectedScenario to match activeScenario
                    if (activeScenario) {
                        window.selectedScenario = activeScenario;
                    }
                    
                    console.log('🔍 Active scenario selected:', activeScenario);
                    console.log('🔍 ===== END SCENARIO PAGE RENDER =====');

                    if (!window.selectedScenario && activeScenario) {
                        window.selectedScenario = activeScenario;
                    }

                    stepHtml = `<div class="response-text">`;
                    if (scenarioHeaderLine) {
                        let cleanHeader = scenarioHeaderLine.replace(/\*\*/g, '').trim();
                        stepHtml += marked.parse(cleanHeader);
                    }

                    stepHtml += `<div class="scenario-options mt-3">`;

                    currentScenarioOptions.forEach((scenarioText, index) => {
                        const scenarioId = `${scenarioGroupName}-${index}`;
                        const isSelected = window.selectedScenario
                            ? window.selectedScenario === scenarioText
                            : index === 0;

                        let displayScenario = scenarioText;
                        const colonIdx = displayScenario.indexOf(':');
                        if (colonIdx > 0) {
                            const scenarioName = displayScenario.substring(0, colonIdx + 1);
                            const scenarioDescription = displayScenario.substring(colonIdx + 1);
                            displayScenario = `<strong>${scenarioName}</strong>${scenarioDescription}`;
                        } else {
                            const words = displayScenario.split(' ');
                            if (words.length > 0) {
                                const firstChunk = words.slice(0, Math.min(3, words.length)).join(' ');
                                const restChunk = words.slice(Math.min(3, words.length)).join(' ');
                                displayScenario = `<strong>${firstChunk}</strong> ${restChunk}`;
                            }
                        }

                        const escapedScenario = scenarioText.replace(/"/g, '&quot;').replace(/'/g, '&#39;');

                        stepHtml += `
                            <label class="scenario-option ${isSelected ? 'scenario-selected' : ''}" for="${scenarioId}">
                                <input type="radio"
                                       id="${scenarioId}"
                                       name="scenario-choice-${scenarioGroupName}"
                                       value="${escapedScenario}"
                                       data-scenario="${escapedScenario}"
                                       class="scenario-radio me-2"
                                       ${isSelected ? 'checked' : ''}>
                                <span class="scenario-label">${displayScenario}</span>
                            </label>
                        `;
                    });

                    stepHtml += `</div>`;

                    stepHtml += `</div>`;

                    // Scenarios should already be loaded (started 1 step behind)
                    // Just show current status
                    
                    // Also load the active scenario if not cached (using strategy-specific key)
                    // Only load if not already loading to prevent duplicate calls
                    if (activeScenario && !window.scenarioSelectionInProgress) {
                        const strategyKey = window.selectedStrategy ? window.selectedStrategy.substring(0, 50) : 'no-strategy';
                        const activeScenarioCacheKey = `${strategyKey}||${activeScenario}`;
                        if (!window.scenarioResponsesCache || !window.scenarioResponsesCache[activeScenarioCacheKey]) {
                            console.log('🔄 Auto-loading active scenario:', activeScenario.substring(0, 50));
                            fetchScenarioResponse(activeScenario, false);
                        } else {
                            console.log('✅ Active scenario already cached');
                        }
                    }
                } else {
                    // For other sections, check if we need to use updated content
                    const currentSelectedScenario = window.selectedScenario;
                    // Use strategy-specific cache key
                    const strategyKey = window.selectedStrategy ? window.selectedStrategy.substring(0, 50) : 'no-strategy';
                    const scenarioCacheKey = currentSelectedScenario ? `${strategyKey}||${currentSelectedScenario}` : null;
                    const scenarioCache = (scenarioIndex !== -1 && window.scenarioResponsesCache && scenarioCacheKey)
                        ? window.scenarioResponsesCache[scenarioCacheKey]
                        : null;
                    const currentSelectedStrategy = window.selectedStrategy || selectedStrategy;
                    const strategyCache = (window.strategyResponsesCache && currentSelectedStrategy)
                        ? window.strategyResponsesCache[currentSelectedStrategy]
                        : null;

                    if (scenarioIndex !== -1 && currentStep > scenarioIndex && scenarioCache) {
                        const updatedIndex = currentStep - scenarioIndex - 1;
                        console.log('Using cached scenario response. Updated index:', updatedIndex);
                        const updatedParts = getUpdatedSectionParts(scenarioCache, ['👥', '📌', '✅']);

                        if (updatedIndex >= 0 && updatedIndex < updatedParts.length) {
                            const partToShow = updatedParts[updatedIndex];
                            if (partToShow && partToShow.trim() && partToShow.length > 10) {
                                stepHtml = `<div class="response-text">${marked.parse(partToShow)}</div>`;
                            } else {
                                stepHtml = `<div class="response-text">${marked.parse(step)}</div>`;
                            }
                        } else {
                            stepHtml = `<div class="response-text">${marked.parse(step)}</div>`;
                        }

                    } else if (currentStep > strategyMapIndex && strategyCache) {
                        const updatedIndex = currentStep - strategyMapIndex - 1;
                        console.log('Using cached strategy response. Updated index:', updatedIndex);
                        const updatedParts = getUpdatedSectionParts(strategyCache, ['🔮', '👥', '📌', '✅']);

                        if (updatedIndex >= 0 && updatedIndex < updatedParts.length) {
                            const partToShow = updatedParts[updatedIndex];
                            if (partToShow && partToShow.trim() && partToShow.length > 10) {
                                stepHtml = `<div class="response-text">${marked.parse(partToShow)}</div>`;
                            } else {
                                stepHtml = `<div class="response-text">${marked.parse(step)}</div>`;
                            }
                        } else {
                            stepHtml = `<div class="response-text">${marked.parse(step)}</div>`;
                        }
                    } else {
                        if (!currentSelectedStrategy) {
                            console.log('No strategy selected');
                        } else if (!window.strategyResponsesCache) {
                            console.log('No cache object');
                        } else if (!window.strategyResponsesCache[currentSelectedStrategy]) {
                            console.log('Strategy not in cache:', currentSelectedStrategy);
                            console.log('Available strategies:', Object.keys(window.strategyResponsesCache));
                        }
                        // Show original content if no strategy selected or not loaded yet
                        stepHtml = `<div class="response-text">${marked.parse(step)}</div>`;
                    }
                }

                loadingDiv.innerHTML = stepHtml + `
                    <div class="mt-2">
                        ${currentStep > 0 ? '<button type="button" class="btn btn-secondary btn-sm prev-step-btn">Previous</button>' : ''}
                        <button type="button" class="btn btn-primary btn-sm next-step-btn">
                            ${currentStep === sections.length - 1 ? 'Finish' : 'Next'}
                        </button>
                    </div>
                `;
                
                // Start loading data 1 step ahead if needed
                startLoadingStrategiesIfNeeded();
                startLoadingScenariosIfNeeded();
                
                // Update button state after rendering (checks if next step's data is ready)
                updateNextButtonState();

                // Add event listener for strategy selection if this is the Strategy Map step
                if (currentStep === strategyMapIndex && strategyPoints.length > 0) {
                    const radioButtons = loadingDiv.querySelectorAll('.strategy-radio');
                    radioButtons.forEach(radio => {
                        radio.addEventListener('change', function() {
                            if (this.checked) {
                                // Get the original strategy text from data attribute (this is the cache key)
                                let exactStrategy = this.getAttribute('data-original-strategy') || this.value;
                                
                                // Clean "Path A:", "Path B:", "Path 1:", etc. from strategy text for cache key
                                exactStrategy = exactStrategy.replace(/^Path\s+[A-Z][\.:]\s*/i, '');
                                exactStrategy = exactStrategy.replace(/^Path\s+\d+[\.:]\s*/i, '');
                                
                                // Store globally so it persists across navigation
                                window.selectedStrategy = exactStrategy;
                                selectedStrategy = exactStrategy;
                                
                                // Scenarios are strategy-specific. Reset the scenario cache,
                                // then (Phase 1) immediately repopulate it for THIS strategy
                                // from its bundle so scenario selection stays client-side.
                                window.scenarioResponsesCache = {};
                                if (window.strategyBundles) {
                                    const block = window.strategyResponsesCache[exactStrategy];
                                    if (block) {
                                        const rolesIdx = block.indexOf('👥');
                                        const rolesPart = rolesIdx !== -1 ? block.substring(rolesIdx) : block;
                                        const scenMatch = block.match(/🔮[\s\S]*?(?=👥|📌|✅|$)/);
                                        const sKey = exactStrategy.substring(0, 50);
                                        if (scenMatch) {
                                            scenMatch[0].split('\n').forEach(line => {
                                                const t = line.trim();
                                                if (t && (t.startsWith('-') || t.startsWith('•') || t.startsWith('*'))) {
                                                    const sc = t.replace(/^[-•*]\s*/, '')
                                                                .replace(/\*\*([^*]+)\*\*/g, '$1')
                                                                .replace(/\*\*/g, '').trim();
                                                    if (sc) window.scenarioResponsesCache[`${sKey}||${sc}`] = rolesPart;
                                                }
                                            });
                                        }
                                    }
                                }
                                
                                console.log('Strategy selected:', exactStrategy);
                                console.log('Available cache keys:', Object.keys(window.strategyResponsesCache || {}));
                                console.log('Cache available for exact strategy:', window.strategyResponsesCache && window.strategyResponsesCache[exactStrategy] ? 'Yes' : 'No');
                                
                                if (window.strategyResponsesCache && window.strategyResponsesCache[exactStrategy]) {
                                    console.log('Cached response preview for selected strategy:', window.strategyResponsesCache[exactStrategy].substring(0, 200));
                                }
                                
                                // Immediately update subsequent sections using cached response
                                if (window.strategyResponsesCache && window.strategyResponsesCache[exactStrategy]) {
                                    console.log('Using cached response for exact strategy - no API call needed');
                                    
                                    // CRITICAL: Extract scenarios from cached response too!
                                    const cachedStrategyContent = window.strategyResponsesCache[exactStrategy];
                                    console.log('Extracting scenarios from cached strategy content...');
                                    
                                    if (cachedStrategyContent && cachedStrategyContent.includes('🔮')) {
                                        // Find scenario section in cached strategy content
                                        const scenarioMatch = cachedStrategyContent.match(/🔮[\s\S]*?(?=\n\s*(?:👥|📌|✅)|$)/);
                                        if (scenarioMatch) {
                                            const newScenarioSection = scenarioMatch[0];
                                            const scenarioLines = newScenarioSection.split('\n');
                                            let foundScenarioHeader = false;
                                            const newScenarioItems = [];
                                            
                                            for (let i = 0; i < scenarioLines.length; i++) {
                                                const line = scenarioLines[i].trim();
                                                if (!line) continue;
                                                
                                                if (line.includes('🔮') || line.toLowerCase().includes('scenario')) {
                                                    foundScenarioHeader = true;
                                                    continue;
                                                }
                                                
                                                if (foundScenarioHeader && line && 
                                                    (line.startsWith('-') || 
                                                     line.startsWith('•') || 
                                                     line.startsWith('*'))) {
                                                    newScenarioItems.push(line);
                                                }
                                            }
                                            
                                            const newScenarioOptions = newScenarioItems.map(line => {
                                                let cleaned = line.replace(/^[-•*]\s*/, '').trim();
                                                cleaned = cleaned.replace(/\*\*([^*]+)\*\*/g, '$1');
                                                cleaned = cleaned.replace(/\*\*/g, '');
                                                return cleaned.trim();
                                            }).filter(item => item.length > 0);
                                            
                                            if (newScenarioOptions.length > 0) {
                                                // Clear scenario cache when scenarios change
                                                if (window.scenarioResponsesCache) {
                                                    console.log('🗑️ Clearing scenario responses cache - new scenarios from cached strategy');
                                                    window.scenarioResponsesCache = {};
                                                }
                                                
                                                // Update scenarios with strategy-specific ones
                                                window.scenarioSection = newScenarioSection;
                                                window.scenarioOptions = newScenarioOptions;
                                                
                                                // Reset selected scenario to first one
                                                window.selectedScenario = newScenarioOptions[0];
                                                
                                                console.log('✅ Updated window.scenarioOptions from cache:', window.scenarioOptions);
                                                console.log('New scenarios count:', newScenarioOptions.length);
                                            }
                                        }
                                    }
                                    
                                    // If we're viewing a section after strategy map, update it immediately
                                    if (currentStep >= strategyMapIndex) {
                                        console.log('Re-rendering to show updated scenarios from cache');
                                        renderStep();
                                    }
                                    
                                    // Update Next button state after strategy selection
                                    updateNextButtonState();
                                } else {
                                    // Strategy-specific content not cached - load it now when user selects
                                    console.log('Loading strategy-specific content for selected strategy:', exactStrategy);
                                    
                                    window.pendingApiCalls++;
                                    window.apiCallInProgress = true;
                                    updateNextButtonState();
                                    
                                    fetch('/dashboard/users-new-chat-update-strategy', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({
                                            chat_id: window.chatChatId,
                                            user_id: window.chatUserId,
                                            selected_strategy: exactStrategy,
                                            original_question: window.chatQuestion,
                                            sections_before: window.chatSections.slice(0, window.strategyMapIndex).join(''),
                                            strategy_map: window.strategyMapSection,
                                            is_user_selection: true // This is actual user selection - save to DB
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        console.log('=== STRATEGY SELECTED DEBUG ===');
                                        console.log('Strategy:', exactStrategy);
                                        console.log('Response data:', data);

                                        if (data.chat_total_tokens !== undefined) window.setChatTokenTotal(data.chat_total_tokens);

                                        // Cache the strategy-specific content
                                        if (!window.strategyResponsesCache) {
                                            window.strategyResponsesCache = {};
                                        }
                                        window.strategyResponsesCache[exactStrategy] = data.updated_sections || '';
                                        
                                        // Extract scenarios from strategy-specific content
                                        const strategyContent = data.updated_sections || '';
                                        console.log('Strategy content length:', strategyContent.length);
                                        console.log('Strategy content preview:', strategyContent.substring(0, 500));
                                        
                                        if (strategyContent && strategyContent.includes('🔮')) {
                                            console.log('Found 🔮 emoji in strategy content');
                                            
                                            // Find scenario section in strategy-specific content - use a better regex
                                            // Match from 🔮 until we hit 👥, 📌, or ✅ (non-greedy)
                                            const scenarioMatch = strategyContent.match(/🔮[\s\S]*?(?=\n\s*(?:👥|📌|✅)|$)/);
                                            if (scenarioMatch) {
                                                const newScenarioSection = scenarioMatch[0];
                                                console.log('Extracted scenario section:', newScenarioSection.substring(0, 300));
                                                
                                                const scenarioLines = newScenarioSection.split('\n');
                                                let foundScenarioHeader = false;
                                                const newScenarioItems = [];
                                                
                                                for (let i = 0; i < scenarioLines.length; i++) {
                                                    const line = scenarioLines[i].trim();
                                                    if (!line) continue;
                                                    
                                                    if (line.includes('🔮') || line.toLowerCase().includes('scenario')) {
                                                        foundScenarioHeader = true;
                                                        console.log('Found scenario header:', line);
                                                        continue;
                                                    }
                                                    
                                                    if (foundScenarioHeader && line && 
                                                        (line.startsWith('-') || 
                                                         line.startsWith('•') || 
                                                         line.startsWith('*'))) {
                                                        newScenarioItems.push(line);
                                                        console.log('Found scenario item:', line);
                                                    }
                                                }
                                                
                                                console.log('Total scenario items found:', newScenarioItems.length);
                                                
                                                const newScenarioOptions = newScenarioItems.map(line => {
                                                    let cleaned = line.replace(/^[-•*]\s*/, '').trim();
                                                    // Remove markdown bold but keep the text
                                                    cleaned = cleaned.replace(/\*\*([^*]+)\*\*/g, '$1');
                                                    // Remove any remaining **
                                                    cleaned = cleaned.replace(/\*\*/g, '');
                                                    return cleaned.trim();
                                                }).filter(item => item.length > 0);
                                                
                                                console.log('Cleaned scenario options:', newScenarioOptions);
                                                
                                                if (newScenarioOptions.length > 0) {
                                                    // CRITICAL: Clear scenario cache when scenarios change
                                                    // Old cached scenario responses are invalid for new strategy
                                                    if (window.scenarioResponsesCache) {
                                                        console.log('🗑️ Clearing scenario responses cache - new scenarios for new strategy');
                                                        window.scenarioResponsesCache = {};
                                                    }
                                                    
                                                    // Update scenarios with strategy-specific ones
                                                    window.scenarioSection = newScenarioSection;
                                                    
                                                    // Store old scenarios for comparison
                                                    const oldScenarios = window.scenarioOptions ? [...window.scenarioOptions] : [];
                                                    
                                                    // Update scenarios
                                                    window.scenarioOptions = newScenarioOptions;
                                                    
                                                    console.log('✅ Updated window.scenarioOptions:', window.scenarioOptions);
                                                    console.log('Old scenarios:', oldScenarios);
                                                    console.log('Scenarios changed?', JSON.stringify(oldScenarios) !== JSON.stringify(newScenarioOptions));
                                                    
                                                    // Always reset selected scenario to first one when strategy changes
                                                    // This ensures we're using the new scenarios
                                                    window.selectedScenario = newScenarioOptions[0];
                                                    
                                                    console.log('Reset selectedScenario to first option:', window.selectedScenario);
                                                    console.log('Updated scenarios for strategy:', exactStrategy);
                                                    console.log('New scenarios count:', newScenarioOptions.length);
                                                } else {
                                                    console.warn('⚠️ No scenarios extracted from strategy content');
                                                }
                                            } else {
                                                console.warn('⚠️ Could not match scenario section with regex');
                                            }
                                        } else {
                                            console.warn('⚠️ No 🔮 emoji found in strategy content');
                                        }
                                        
                                        console.log('=== END DEBUG ===');
                                        
                                        // Force re-render if viewing any section from strategy map onwards (including scenario page)
                                        if (currentStep >= strategyMapIndex) {
                                            console.log('Re-rendering to show updated scenarios for strategy:', exactStrategy);
                                            renderStep();
                                        }
                                        
                                        updateNextButtonState();
                                    })
                                    .catch(err => {
                                        console.error('Error loading strategy-specific content:', err);
                                    })
                                    .finally(() => {
                                        window.pendingApiCalls = Math.max(0, window.pendingApiCalls - 1);
                                        window.apiCallInProgress = window.pendingApiCalls > 0;
                                        updateNextButtonState();
                                    });
                                }
                                
                                // Re-render current step to show selected state
                                renderStep();
                            }
                        });
                    });
                } else if (currentStep === scenarioIndex && window.scenarioOptions && window.scenarioOptions.length > 0) {
                    const scenarioRadios = loadingDiv.querySelectorAll('.scenario-radio');
                    scenarioRadios.forEach(radio => {
                        // Remove any existing event listeners to prevent duplicates
                        const newRadio = radio.cloneNode(true);
                        radio.parentNode.replaceChild(newRadio, radio);
                        
                        newRadio.addEventListener('change', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            if (this.checked) {
                                const scenarioValue = this.getAttribute('data-scenario') || this.value;
                                
                                // Prevent duplicate calls - check if already processing
                                if (window.scenarioSelectionInProgress) {
                                    console.log('⚠️ Scenario selection already in progress, ignoring duplicate click');
                                    return;
                                }
                                
                                window.scenarioSelectionInProgress = true;
                                console.log('📌 Scenario selected:', scenarioValue.substring(0, 50));
                                
                                window.selectedScenario = scenarioValue;
                                
                                // Check if scenario is already cached to avoid unnecessary loading indicator
                                const strategyKey = window.selectedStrategy ? window.selectedStrategy.substring(0, 50) : 'no-strategy';
                                const cacheKey = `${strategyKey}||${scenarioValue}`;
                                const isCached = window.scenarioResponsesCache && window.scenarioResponsesCache[cacheKey];
                                
                                if (!isCached) {
                                    // Remove any existing loading indicators
                                    const existingLoaders = loadingDiv.querySelectorAll('.scenario-loading-indicator');
                                    existingLoaders.forEach(loader => loader.remove());
                                    
                                    // Add loading indicator only if not cached
                                    const loadingIndicator = document.createElement('div');
                                    loadingIndicator.className = 'scenario-loading-indicator alert alert-info mt-2';
                                    loadingIndicator.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Loading scenario content...';
                                    const scenarioOptionsDiv = loadingDiv.querySelector('.scenario-options');
                                    if (scenarioOptionsDiv) {
                                        scenarioOptionsDiv.insertAdjacentElement('afterend', loadingIndicator);
                                    }
                                }
                                
                                fetchScenarioResponse(scenarioValue, true).then(() => {
                                    // Remove loading indicator after API call completes
                                    const loaders = loadingDiv.querySelectorAll('.scenario-loading-indicator');
                                    loaders.forEach(loader => loader.remove());
                                }).catch(() => {
                                    // Remove loading indicator even on error
                                    const loaders = loadingDiv.querySelectorAll('.scenario-loading-indicator');
                                    loaders.forEach(loader => loader.remove());
                                }).finally(() => {
                                    window.scenarioSelectionInProgress = false;
                                    // Re-render to show updated content
                                    renderStep();
                                    // Update Next button state after scenario selection
                                    updateNextButtonState();
                                });
                            }
                        });
                    });
                }
            }

            function buildFinalSectionHtml(sectionIndex) {
                const step = sections[sectionIndex] || '';

                if (sectionIndex === strategyMapIndex && strategyPoints.length > 0) {
                    const strategyLines = (strategyMapSection || '').split('\n');
                    const headerLine = strategyLines.find(line => line.includes('🗺️') || line.includes('Strategy Map'));
                    let html = `<div class="response-text">`;
                    if (headerLine) {
                        const cleanHeader = headerLine.replace(/\*\*/g, '').trim();
                        html += marked.parse(cleanHeader);
                    }
                    html += `<div class="strategy-options mt-3">`;
                    strategyPoints.forEach(point => {
                        const colonIndex = point.indexOf(':');
                        let displayPoint = point;
                        if (colonIndex > 0) {
                            const title = point.substring(0, colonIndex + 1);
                            const description = point.substring(colonIndex + 1);
                            displayPoint = `<strong>${title}</strong>${description}`;
                        } else {
                            const words = point.split(' ');
                            const head = words.slice(0, Math.min(3, words.length)).join(' ');
                            const rest = words.slice(Math.min(3, words.length)).join(' ');
                            displayPoint = `<strong>${head}</strong> ${rest}`;
                        }
                        const isSelected = window.selectedStrategy === point || (window.selectedStrategy && window.selectedStrategy.includes(point.substring(0, 30)));
                        html += `
                            <div class="strategy-option mb-2 ${isSelected ? 'strategy-loaded strategy-selected' : ''}">
                                <div class="strategy-label">
                                    ${displayPoint}
                                    ${isSelected ? '<span class="badge bg-primary ms-auto">Selected</span>' : ''}
                                </div>
                            </div>
                        `;
                    });
                    html += `</div></div>`;
                    return html;
                }

                if (sectionIndex === scenarioIndex && window.scenarioOptions && window.scenarioOptions.length > 0) {
                    // Use window.scenarioOptions which gets updated when strategy is selected
                    const currentScenarioOptions = window.scenarioOptions;
                    const scenarioLines = (window.scenarioSection || '').split('\n');
                    const headerLine = scenarioLines.find(line => line.includes('🔮') || line.toLowerCase().includes('scenario'));
                    let html = `<div class="response-text">`;
                    if (headerLine) {
                        const cleanHeader = headerLine.replace(/\*\*/g, '').trim();
                        html += marked.parse(cleanHeader);
                    }
                    html += `<div class="scenario-options mt-3">`;
                    currentScenarioOptions.forEach((text, idx) => {
                        const colonIndex = text.indexOf(':');
                        let displayPoint = text;
                        if (colonIndex > 0) {
                            const title = text.substring(0, colonIndex + 1);
                            const description = text.substring(colonIndex + 1);
                            displayPoint = `<strong>${title}</strong>${description}`;
                        } else {
                            const words = text.split(' ');
                            const head = words.slice(0, Math.min(3, words.length)).join(' ');
                            const rest = words.slice(Math.min(3, words.length)).join(' ');
                            displayPoint = `<strong>${head}</strong> ${rest}`;
                        }
                        const isSelected = window.selectedScenario ? window.selectedScenario === text : idx === 0;
                        html += `
                            <div class="scenario-option ${isSelected ? 'scenario-selected' : ''}">
                                <span class="scenario-label">${displayPoint}</span>
                                ${isSelected ? '<span class="badge bg-primary ms-auto">Selected</span>' : ''}
                            </div>
                        `;
                    });
                    html += `</div></div>`;
                    return html;
                }

                const currentSelectedScenario = window.selectedScenario;
                // Use strategy-specific cache key
                const strategyKey = window.selectedStrategy ? window.selectedStrategy.substring(0, 50) : 'no-strategy';
                const scenarioCacheKey = currentSelectedScenario ? `${strategyKey}||${currentSelectedScenario}` : null;
                const scenarioCache = (scenarioIndex !== -1 && window.scenarioResponsesCache && scenarioCacheKey)
                    ? window.scenarioResponsesCache[scenarioCacheKey]
                    : null;
                const currentSelectedStrategy = window.selectedStrategy || selectedStrategy;
                const strategyCache = (window.strategyResponsesCache && currentSelectedStrategy)
                    ? window.strategyResponsesCache[currentSelectedStrategy]
                    : null;

                if (scenarioIndex !== -1 && sectionIndex > scenarioIndex && scenarioCache) {
                    const updatedIndex = sectionIndex - scenarioIndex - 1;
                    const updatedParts = getUpdatedSectionParts(scenarioCache, ['👥', '📌', '✅']);
                    if (updatedIndex >= 0 && updatedIndex < updatedParts.length) {
                        const partToShow = updatedParts[updatedIndex];
                        if (partToShow && partToShow.trim() && partToShow.length > 10) {
                            return `<div class="response-text">${marked.parse(partToShow)}</div>`;
                        }
                    }
                }

                if (sectionIndex > strategyMapIndex && strategyCache) {
                    const updatedIndex = sectionIndex - strategyMapIndex - 1;
                    const updatedParts = getUpdatedSectionParts(strategyCache, ['🔮', '👥', '📌', '✅']);
                    if (updatedIndex >= 0 && updatedIndex < updatedParts.length) {
                        const partToShow = updatedParts[updatedIndex];
                        if (partToShow && partToShow.trim() && partToShow.length > 10) {
                            return `<div class="response-text">${marked.parse(partToShow)}</div>`;
                        }
                    }
                }

                return `<div class="response-text">${marked.parse(step)}</div>`;
            }

            function renderFinalAnswer() {
                let combined = '';
                sections.forEach((_, idx) => {
                    combined += `<div class="response-section">${buildFinalSectionHtml(idx)}</div>`;
                });
                loadingDiv.innerHTML = `
                    ${combined}
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm text-success copy-btn" data-bs-toggle="tooltip" title="Copy Answer">
                            <i class="bi bi-copy"></i>
                        </button>
                    </div>
                `;
                
                // Add export button and auto-generate brief after final answer is rendered
                // Use longer timeout to ensure DOM is fully updated
                setTimeout(() => {
                    console.log('🎯 renderFinalAnswer: Adding export button and generating brief...');
                    addExportButtonToRoleGoals();
                    
                    // renderFinalAnswer rebuilds the DOM, wiping any brief that was
                    // already inserted during step navigation. If the brief was
                    // already generated, re-insert the cached copy instead of making
                    // a second API call (which caused the duplicate "Generating
                    // Leadership Alignment Brief..." on Finish).
                    const allResponseTexts = Array.from(loadingDiv.querySelectorAll('.response-text'));
                    const finalOutcomeSection = allResponseTexts.find(el =>
                        el.textContent.includes('✅') && el.textContent.includes('Final Outcome')
                    );

                    if (window.briefGenerationCompleted && window.generatedBriefHtml) {
                        console.log('♻️ Re-inserting cached Leadership Alignment Brief (no regeneration)');
                        const cardContainer = (finalOutcomeSection || loadingDiv).closest('.tt-template-carddads') || loadingDiv;
                        if (!cardContainer.querySelector('.leadership-alignment-brief')) {
                            const briefDiv = document.createElement('div');
                            briefDiv.className = 'leadership-alignment-brief mt-3';
                            const responseTextDiv = document.createElement('div');
                            responseTextDiv.className = 'response-text';
                            responseTextDiv.innerHTML = window.generatedBriefHtml;
                            briefDiv.appendChild(responseTextDiv);
                            if (finalOutcomeSection) {
                                finalOutcomeSection.insertAdjacentElement('afterend', briefDiv);
                            } else {
                                loadingDiv.appendChild(briefDiv);
                            }
                        }
                    } else {
                        // Not generated yet — generate once
                        autoGenerateAlignmentBrief();
                    }
                }, 500); // Increased to 500ms to ensure DOM is ready
            }

            renderStep();

            loadingDiv.addEventListener('click', function (e) {
                if (e.target.classList.contains('next-step-btn') || e.target.closest('.next-step-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const btn = e.target.classList.contains('next-step-btn') ? e.target : e.target.closest('.next-step-btn');
                    if (btn && btn.disabled) {
                        return; // Don't proceed if button is disabled
                    }
                    if (currentStep === sections.length - 1) {
                        renderFinalAnswer();
                    } else {
                        currentStep++;
                        renderStep();
                    }
                } else if (e.target.classList.contains('prev-step-btn') || e.target.closest('.prev-step-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (currentStep > 0) {
                        currentStep--;
                        renderStep();
                    }
                }
            });

        } else if (isSecondMessage) {
            // For second message, show step-by-step navigation
            const steps = fullAnswer.split(/(?=🧩|📁|📊|📈|🗺️|🔮|👥|📌|✅)/);
            if (!steps.length) {
                loadingDiv.innerHTML = `<div class="alert alert-warning">No steps found in the response.</div>`;
                return;
            }

            let currentStep = 0;

            function renderStep() {
                const step = steps[currentStep];
                loadingDiv.innerHTML = `
                    <div class="response-text">${marked.parse(step)}</div>
                    <div class="mt-2">
                        ${currentStep > 0 ? '<button type="button" class="btn btn-secondary btn-sm prev-step-btn">Previous</button>' : ''}
                        <button type="button" class="btn btn-primary btn-sm next-step-btn">
                            ${currentStep === steps.length - 1 ? 'Finish' : 'Next'}
                        </button>
                    </div>
                `;
            }

            function renderFinalSteps() {
                const combined = steps.map(step => `<div class="response-text">${marked.parse(step)}</div>`).join('');
                loadingDiv.innerHTML = `
                    ${combined}
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm text-success copy-btn" data-bs-toggle="tooltip" title="Copy Answer">
                            <i class="bi bi-copy"></i>
                        </button>
                    </div>
                `;
                
                // Add export button and auto-generate brief after final steps are rendered
                setTimeout(() => {
                    addExportButtonToRoleGoals();
                    autoGenerateAlignmentBrief();
                }, 100);
            }

            renderStep();

            loadingDiv.addEventListener('click', function (e) {
                if (e.target.classList.contains('next-step-btn') || e.target.closest('.next-step-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const btn = e.target.classList.contains('next-step-btn') ? e.target : e.target.closest('.next-step-btn');
                    if (btn && btn.disabled) {
                        return; // Don't proceed if button is disabled
                    }
                    if (currentStep === steps.length - 1) {
                        renderFinalSteps();
                    } else {
                        currentStep++;
                        renderStep();
                    }
                } else if (e.target.classList.contains('prev-step-btn') || e.target.closest('.prev-step-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (currentStep > 0) {
                        currentStep--;
                        renderStep();
                    }
                }
            });

        } else {
            const formattedAnswer = marked.parse(fullAnswer);
            loadingDiv.innerHTML = `
                <div class="response-text">${formattedAnswer}</div>
                <button type="button" class="btn btn-sm text-success mt-1 copy-btn" data-bs-toggle="tooltip" title="Copy Answer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" class="feather feather-copy">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                </button>`;
            
            // Add export button and auto-generate brief after answer is rendered
            setTimeout(() => {
                addExportButtonToRoleGoals();
                autoGenerateAlignmentBrief();
            }, 100);
        }

            // } catch (error) {
            //     console.error(error);
            //     loadingDiv.innerHTML = `<div class="alert alert-danger">Something went wrong while fetching the answer.</div>`;
            // }
        }

        // Add event listeners for context buttons (outside the function so they're available immediately)
        const addContextBtn = contextOptionsDiv.querySelector('.add-context-btn');
        const skipContextBtn = contextOptionsDiv.querySelector('.skip-context-btn');

        addContextBtn.addEventListener('click', function() {
            showAddContextModal(userCard, chat_id, user_id, window.sendToChatGPT);
        });

        skipContextBtn.addEventListener('click', function() {
            if (window.sendToChatGPT) {
                window.sendToChatGPT(null); // Send without context
            }
        });

    questionInput.value = '';
});

// ✅ Copy Button
document.addEventListener('click', function (e) {
    if (e.target.closest('.copy-btn')) {
        const card = e.target.closest('.tt-template-carddads');
        const sections = card ? card.querySelectorAll('.response-text') : [];
        const text = sections.length
            ? Array.from(sections).map(section => section.innerText.trim()).filter(Boolean).join('\n\n')
            : '';
        navigator.clipboard.writeText(text)
            .then(() => alert('Answer copied!'))
            .catch(err => alert('Copy failed: ' + err));
    }
});
</script>






<!-- Copy Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.copy-btn').forEach(button => {
            button.addEventListener('click', function () {
                const card = this.closest('.tt-template-carddads');
                const sections = card ? card.querySelectorAll('.response-text') : [];
                const answer = sections.length
                    ? Array.from(sections).map(section => section.innerText.trim()).filter(Boolean).join('\n\n')
                    : '';

                if (!answer.trim()) {
                    alert('Nothing to copy!');
                    return;
                }

                navigator.clipboard.writeText(answer).then(() => {
                    alert('Answer copied to clipboard!');
                }).catch(() => {
                    alert('Failed to copy!');
                });
            });
        });
    });

    // Export Role Goals to Spreadsheet
    window.exportRoleGoals = function(roleGoalsText, goal, scenario, strategy) {
        console.log('Starting export...', { roleGoalsTextLength: roleGoalsText.length, goal, scenario, strategy });
        
        // Show loading indicator
        const loadingMsg = document.createElement('div');
        loadingMsg.className = 'alert alert-info mt-2';
        loadingMsg.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Preparing export...';
        const exportBtn = document.querySelector('.export-role-goals-btn');
        if (exportBtn) {
            exportBtn.disabled = true;
            exportBtn.insertAdjacentElement('afterend', loadingMsg);
        }
        
        const formData = new FormData();
        formData.append('role_goals_text', roleGoalsText);
        formData.append('goal', goal || '');
        formData.append('scenario', scenario || '');
        formData.append('strategy', strategy || '');
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route("users-new-chat-export-role-goals.index") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Export response status:', response.status, response.statusText);
            
            if (response.ok) {
                // Check if response is actually a file
                const contentType = response.headers.get('content-type');
                console.log('Response content-type:', contentType);
                
                if (contentType && (contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') || contentType.includes('application/octet-stream'))) {
                    return response.blob();
                } else {
                    // If not a blob, try to get as text to see error
                    return response.text().then(text => {
                        console.error('Unexpected response:', text);
                        throw new Error('Server returned non-file response: ' + text.substring(0, 200));
                    });
                }
            } else {
                return response.text().then(text => {
                    console.error('Export failed:', response.status, text);
                    throw new Error('Export failed: ' + response.status + ' - ' + text.substring(0, 200));
                });
            }
        })
        .then(blob => {
            if (blob instanceof Blob) {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `role_goals_${new Date().getTime()}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                // Remove loading message
                if (loadingMsg) loadingMsg.remove();
                if (exportBtn) exportBtn.disabled = false;
                
                console.log('Export completed successfully');
            } else {
                throw new Error('Invalid response format');
            }
        })
        .catch(error => {
            console.error('Export error:', error);
            if (loadingMsg) loadingMsg.remove();
            if (exportBtn) exportBtn.disabled = false;
            alert('Failed to export role goals: ' + error.message + '\n\nPlease check the browser console for details.');
        });
    };

    // Generate Leadership Alignment Brief (automatically called)
    window.generateLeadershipAlignmentBrief = function(chatId, selectedStrategy, selectedScenario, originalQuestion, fullResponse) {
        // Find the loading indicator we created
        const loadingIndicator = document.querySelector('.leadership-alignment-brief-loading');
        
        // Re-find final outcome section (DOM might have changed after renderFinalAnswer)
        const allResponseTexts = Array.from(document.querySelectorAll('.response-text'));
        console.log('🔍 generateLeadershipAlignmentBrief: Searching for Final Outcome section...');
        console.log('📍 Total response-text elements:', allResponseTexts.length);
        
        const finalOutcomeSection = allResponseTexts.find(el => {
            const text = el.textContent || el.innerText;
            const hasCheckmark = text.includes('✅');
            const hasFinalOutcome = text.includes('Final Outcome');
            return hasCheckmark && hasFinalOutcome;
        });
        
        if (!finalOutcomeSection) {
            console.error('❌ Final Outcome section not found in generateLeadershipAlignmentBrief');
            console.log('📍 Available response-text elements:', allResponseTexts.map(el => {
                const text = el.textContent || el.innerText;
                return text.substring(0, 80).replace(/\n/g, ' ');
            }));
            if (loadingIndicator) loadingIndicator.remove();
            return;
        }
        
        console.log('✅ Final Outcome section found:', finalOutcomeSection);
        console.log('📍 Final Outcome section text preview:', (finalOutcomeSection.textContent || finalOutcomeSection.innerText).substring(0, 100));
        
        const cardContainer = finalOutcomeSection.closest('.tt-template-carddads');
        if (!cardContainer) {
            console.error('❌ Card container not found for Final Outcome section');
            if (loadingIndicator) loadingIndicator.remove();
            return;
        }
        
        console.log('✅ Card container found:', cardContainer);

        console.log('🔄 Calling API to generate Leadership Alignment Brief...', {
            chatId,
            hasStrategy: !!selectedStrategy,
            hasScenario: !!selectedScenario,
            hasQuestion: !!originalQuestion,
            responseLength: fullResponse.length
        });
        
        fetch('{{ route("users-new-chat-generate-alignment-brief.index") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                chat_id: chatId,
                selected_strategy: selectedStrategy || '',
                selected_scenario: selectedScenario || '',
                original_question: originalQuestion || '',
                full_response: fullResponse || ''
            })
        })
        .then(response => {
            console.log('📡 API Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📡 API Response data:', { success: data.success, hasBrief: !!data.brief, error: data.error });

            if (data.chat_total_tokens !== undefined) window.setChatTokenTotal(data.chat_total_tokens);

            // Mark as no longer in progress
            window.briefGenerationInProgress = false;
            setBriefLoadingState(false); // re-enable Finish

            // Remove loading indicator
            if (loadingIndicator) loadingIndicator.remove();
            
            if (data.success && data.brief) {
                console.log('✅ Brief generated successfully, length:', data.brief.length);
                console.log('💾 Brief should be saved to database with chat_id:', chatId);
                console.log('💾 Brief preview (first 200 chars):', data.brief.substring(0, 200));
                
                // Check for warning about save failure
                if (data.warning) {
                    console.error('⚠️ WARNING:', data.warning);
                    console.error('⚠️ Brief was generated but may not be saved to database!');
                }
                
                // Check if brief already exists
                const existingBrief = cardContainer.querySelector('.leadership-alignment-brief');
                if (existingBrief) {
                    console.log('⚠️ Brief already exists in card container, skipping insertion');
                    window.briefGenerationCompleted = true;
                    return; // Already displayed
                }
                
                // Mark as completed
                window.briefGenerationCompleted = true;
                
                // Check if marked is available
                if (typeof marked === 'undefined') {
                    console.error('❌ marked library is not loaded!');
                    // Fallback: use plain text with basic formatting
                    const briefText = data.brief.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                                                .replace(/\n/g, '<br>');
                    // Cache so Finish can re-insert without a second API call
                    window.generatedBriefHtml = briefText;
                    const briefDiv = document.createElement('div');
                    briefDiv.className = 'leadership-alignment-brief mt-3';
                    briefDiv.innerHTML = `<div class="response-text">${briefText}</div>`;
                    finalOutcomeSection.insertAdjacentElement('afterend', briefDiv);
                } else {
                    // Create brief section with markdown parsing
                    console.log('📝 Parsing brief with marked library...');
                    let parsedBrief;
                    try {
                        parsedBrief = marked.parse(data.brief);
                        console.log('✅ Brief parsed successfully, HTML length:', parsedBrief.length);
                    } catch (parseError) {
                        console.error('❌ Error parsing brief with marked:', parseError);
                        // Fallback to plain text
                        parsedBrief = data.brief.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                                                .replace(/\n/g, '<br>');
                    }
                    
                    // Cache so Finish can re-insert without a second API call
                    window.generatedBriefHtml = parsedBrief;

                    const briefDiv = document.createElement('div');
                    briefDiv.className = 'leadership-alignment-brief mt-3';

                    // Create response-text div
                    const responseTextDiv = document.createElement('div');
                    responseTextDiv.className = 'response-text';
                    responseTextDiv.innerHTML = parsedBrief;
                    
                    briefDiv.appendChild(responseTextDiv);
                    
                    // Insert after final outcome section, inside the same card container
                    console.log('📍 Inserting brief after final outcome section...');
                    console.log('📍 Final outcome section:', finalOutcomeSection);
                    console.log('📍 Card container:', cardContainer);
                    console.log('📍 Brief div HTML length:', briefDiv.innerHTML.length);
                    
                    finalOutcomeSection.insertAdjacentElement('afterend', briefDiv);
                    
                    // Verify insertion and scroll into view
                    setTimeout(() => {
                        const insertedBrief = cardContainer.querySelector('.leadership-alignment-brief');
                        if (insertedBrief) {
                            const responseText = insertedBrief.querySelector('.response-text');
                            console.log('✅ Brief successfully inserted and visible in DOM');
                            console.log('📍 Brief element:', insertedBrief);
                            console.log('📍 Response text element:', responseText);
                            console.log('📍 Brief innerHTML length:', insertedBrief.innerHTML.length);
                            console.log('📍 Brief textContent length:', insertedBrief.textContent.length);
                            console.log('📍 Response text innerHTML length:', responseText ? responseText.innerHTML.length : 0);
                            console.log('📍 Response text textContent length:', responseText ? responseText.textContent.length : 0);
                            
                            if (!responseText || responseText.innerHTML.trim().length === 0) {
                                console.error('❌ Response text is empty! Re-inserting content...');
                                responseText.innerHTML = parsedBrief;
                            }
                            
                            console.log('💾 IMPORTANT: Brief is now in DOM. After page reload, it should load from database.');
                            console.log('💾 To verify: Reload the page and check console for "📋 BRIEF FROM DATABASE" logs');
                            
                            // Scroll the brief into view
                            insertedBrief.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            
                            // Add a visual highlight to show the brief was added
                            insertedBrief.style.transition = 'background-color 0.3s';
                            insertedBrief.style.backgroundColor = 'rgba(0, 123, 255, 0.1)';
                            setTimeout(() => {
                                insertedBrief.style.backgroundColor = '';
                            }, 2000);
                        } else {
                            console.error('❌ Brief insertion failed - not found in DOM after insertion');
                        }
                    }, 100);
                }
            } else {
                // Mark as completed even on error to prevent retries
                window.briefGenerationCompleted = true;
                
                // Show error message if generation fails
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-warning mt-3';
                errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Could not generate alignment brief: ' + (data.error || 'Unknown error');
                if (loadingIndicator) {
                    loadingIndicator.replaceWith(errorDiv);
                } else {
                    finalOutcomeSection.insertAdjacentElement('afterend', errorDiv);
                }
            }
        })
        .catch(error => {
            // Mark as no longer in progress and completed (to prevent retries)
            window.briefGenerationInProgress = false;
            window.briefGenerationCompleted = true;
            setBriefLoadingState(false); // re-enable Finish

            // Remove loading indicator
            if (loadingIndicator) loadingIndicator.remove();
            
            console.error('Error generating brief:', error);
            // Don't show alert for automatic generation - just log it
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-warning mt-3';
            errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Could not generate alignment brief. Please refresh and try again.';
            if (finalOutcomeSection) {
                finalOutcomeSection.insertAdjacentElement('afterend', errorDiv);
            }
        });
    };

    // Add export button to role goals section
    function addExportButtonToRoleGoals() {
        // Find ALL response-text elements and check which one contains role goals
        const allResponseTexts = Array.from(document.querySelectorAll('.response-text'));
        const roleGoalsSection = allResponseTexts.find(el => 
            el.textContent.includes('👥') && 
            (el.textContent.includes('Rephrased Goals') || el.textContent.includes('Goals by Role'))
        );
        
        if (!roleGoalsSection) {
            console.log('🔍 No Role Goals section found - export button not needed');
            return; // No role goals section, no export button needed
        }
        
        // Find the parent card container
        const cardContainer = roleGoalsSection.closest('.tt-template-carddads');
        if (!cardContainer) {
            console.log('⚠️ Role Goals section found but no card container');
            return;
        }
        
        // Don't add export button if this card contains Leadership Alignment Brief
        const hasLeadershipBrief = cardContainer.querySelector('.leadership-alignment-brief');
        if (hasLeadershipBrief) {
            console.log('⚠️ Skipping export button - Leadership Alignment Brief found in this card');
            return; // Don't add export button to final message
        }
        
        // Don't add export button if this card only has Final Outcome (no Role Goals in this specific card)
        // Check if Role Goals section is actually in THIS card container
        const roleGoalsInThisCard = cardContainer.querySelector('.response-text') && 
                                    cardContainer.textContent.includes('👥') &&
                                    (cardContainer.textContent.includes('Rephrased Goals') || 
                                     cardContainer.textContent.includes('Goals by Role'));
        
        if (!roleGoalsInThisCard) {
            console.log('⚠️ Skipping export button - Role Goals not in this card container');
            return;
        }
        
        const existingExportBtn = cardContainer.querySelector('.export-role-goals-btn');
        if (!existingExportBtn) {
            console.log('✅ Adding export button after Role Goals section');
            
            // Find where the Role Goals section ends
            // Look for the next section marker (📌, ✅, or 📋) or end of the response-text
            const roleGoalsText = roleGoalsSection.textContent || roleGoalsSection.innerText;
            const roleGoalsEndIndex = roleGoalsText.indexOf('📌');
            const finalOutcomeIndex = roleGoalsText.indexOf('✅');
            const briefIndex = roleGoalsText.indexOf('📋');
            
            // Find the earliest next section marker after Role Goals
            let nextSectionIndex = -1;
            if (roleGoalsEndIndex !== -1) nextSectionIndex = roleGoalsEndIndex;
            if (finalOutcomeIndex !== -1 && (nextSectionIndex === -1 || finalOutcomeIndex < nextSectionIndex)) {
                nextSectionIndex = finalOutcomeIndex;
            }
            if (briefIndex !== -1 && (nextSectionIndex === -1 || briefIndex < nextSectionIndex)) {
                nextSectionIndex = briefIndex;
            }
            
            // Extract just the Role Goals section (up to the next section marker)
            let roleGoalsOnly = roleGoalsText;
            if (nextSectionIndex !== -1) {
                roleGoalsOnly = roleGoalsText.substring(0, nextSectionIndex).trim();
            }
            
            const exportBtn = document.createElement('button');
            exportBtn.type = 'button'; // Prevent form submission
            exportBtn.className = 'btn btn-primary btn-sm export-role-goals-btn mt-3 mb-3';
            exportBtn.style.display = 'block';
            exportBtn.innerHTML = '<i class="bi bi-download me-1"></i>Export Role Goals to Spreadsheet';
            exportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Extract role goals text (just the Role Goals section, not the entire response)
                const goal = window.chatQuestion || '';
                const scenario = window.selectedScenario || '';
                const strategy = window.selectedStrategy || '';
                
                console.log('Exporting role goals:', { 
                    roleGoalsText: roleGoalsOnly.substring(0, 100), 
                    goal, 
                    scenario, 
                    strategy 
                });
                
                window.exportRoleGoals(roleGoalsOnly, goal, scenario, strategy);
            });
            
            // Insert button after the response-text div containing Role Goals, inside the card container
            // This places it right after the Role Goals section ends
            roleGoalsSection.insertAdjacentElement('afterend', exportBtn);
        }
    }

    // Small HTML escaper for table cell content
    function escapeActionHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Build the Recommended Action Table HTML with a 3-option decision column.
    // No option is pre-selected ("do nothing for now" is the default state).
    window.renderRecommendedActionTable = function(rows, container) {
        const choices = ['Act on it', 'Review in detail', 'Not viable for us'];
        let html = '<div class="response-text"><div class="table-responsive">'
            + '<table class="table table-bordered recommended-action-table">'
            + '<thead><tr>'
            + '<th>Role</th><th>Recommended Action</th><th>Choice / Decision</th>'
            + '</tr></thead><tbody>';

        rows.forEach((r, i) => {
            const role = escapeActionHtml(r.role);
            const action = escapeActionHtml(r.action);
            let opts = '';
            choices.forEach((c, j) => {
                const id = `action_choice_${i}_${j}`;
                opts += `<div class="form-check">
                    <input class="form-check-input action-choice-input" type="radio" name="action_choice_${i}" id="${id}" value="${escapeActionHtml(c)}" data-role="${role}">
                    <label class="form-check-label" for="${id}">${c}</label>
                </div>`;
            });
            html += `<tr>
                <td><strong>${role}</strong></td>
                <td>${action}</td>
                <td>${opts}</td>
            </tr>`;
        });

        html += '</tbody></table></div></div>';
        container.innerHTML = html;
    };

    // Call backend to generate the table from existing chat/scenario/role data
    function generateRecommendedActionTable(roleGoalsSection, genBtn, resultDiv) {
        genBtn.disabled = true;
        resultDiv.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split me-2"></i>Generating recommended action table...</div>';

        // Role goals text from this card (already collapsed to one-line actions)
        const roleGoalsText = (roleGoalsSection.textContent || roleGoalsSection.innerText || '').trim();

        // Whole card text as broader context
        const cardContainer = roleGoalsSection.closest('.tt-template-carddads');
        const fullResponse = cardContainer
            ? Array.from(cardContainer.querySelectorAll('.response-text')).map(el => el.textContent.trim()).filter(Boolean).join('\n\n')
            : roleGoalsText;

        fetch('{{ route("users-new-chat-generate-action-table.index") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                chat_id: window.chatChatId || (document.getElementById('chat_id') ? document.getElementById('chat_id').value : ''),
                original_question: window.chatQuestion || '',
                selected_strategy: window.selectedStrategy || '',
                selected_scenario: window.selectedScenario || '',
                role_goals_text: roleGoalsText,
                full_response: fullResponse
            })
        })
        .then(r => r.json())
        .then(data => {
            genBtn.disabled = false;
            if (data.chat_total_tokens !== undefined) window.setChatTokenTotal(data.chat_total_tokens);
            if (data.success && Array.isArray(data.rows) && data.rows.length) {
                window.renderRecommendedActionTable(data.rows, resultDiv);
            } else {
                resultDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Could not generate table: ' + (data.error || 'no rows returned') + '</div>';
            }
        })
        .catch(err => {
            genBtn.disabled = false;
            resultDiv.innerHTML = '<div class="alert alert-danger">Error generating table: ' + err.message + '</div>';
        });
    }

    // Inject the "Refining the Workflow Output" suggestion + button into every
    // card that has a Role Goals section (but only once per card).
    function addActionTableSuggestion() {
        const roleGoalsSections = Array.from(document.querySelectorAll('.response-text')).filter(el =>
            el.textContent.includes('👥') &&
            (el.textContent.includes('Rephrased Goals') || el.textContent.includes('Goals by Role'))
        );

        roleGoalsSections.forEach(roleGoalsSection => {
            const cardContainer = roleGoalsSection.closest('.tt-template-carddads');
            if (!cardContainer) return;
            // Only show on the FINAL answer. While stepping through the wizard a
            // "Next/Finish" button is still present in the card — skip until the
            // user clicks Finish (final render removes the step buttons).
            // (.next-step-btn = markdown wizard, .gs-next = JSON wizard)
            if (cardContainer.querySelector('.next-step-btn') || cardContainer.querySelector('.gs-next')) return;

            // Place at the very end of everything: after the Leadership Alignment
            // Brief if it exists (it may be its own card on reload), otherwise at
            // the end of the answer card.
            const briefEl = document.querySelector('.leadership-alignment-brief');
            const targetCard = (briefEl && briefEl.closest('.tt-template-carddads')) || cardContainer;
            if (targetCard.querySelector('.action-table-suggestion')) return; // already added

            const wrap = document.createElement('div');
            wrap.className = 'action-table-suggestion mt-3';
            wrap.innerHTML = `
                <div class="action-table-suggestion-box">
                    <div class="ats-title"><i class="bi bi-lightbulb me-1"></i>Refining the Workflow Output</div>
                    <button type="button" class="btn btn-primary btn-sm generate-action-table-btn mt-2">
                        <i class="bi bi-table me-1"></i>Generate Recommended Action Table
                    </button>
                </div>
                <div class="action-table-result mt-3"></div>
            `;

            targetCard.appendChild(wrap);

            const genBtn = wrap.querySelector('.generate-action-table-btn');
            const resultDiv = wrap.querySelector('.action-table-result');
            genBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                generateRecommendedActionTable(roleGoalsSection, genBtn, resultDiv);
            });
        });
    }

    // Track if brief generation is in progress or completed
    window.briefGenerationInProgress = false;
    window.briefGenerationCompleted = false;
    
    // Initialize window variables from existing chat data (for page reload)
    @if(count($searchuserchatdata) > 0)
        // Get the first question from the chat data
        const firstChat = @json($searchuserchatdata[0]);
        if (firstChat && firstChat.search) {
            window.chatQuestion = firstChat.search;
            console.log('📝 Loaded chatQuestion from database:', window.chatQuestion);
        }
        
        // Get chat ID and user ID from hidden inputs
        const chatIdInput = document.getElementById('chat_id');
        const userIdInput = document.getElementById('user_id');
        if (chatIdInput) {
            window.chatChatId = chatIdInput.value;
        }
        if (userIdInput) {
            window.chatUserId = userIdInput.value;
        }
    @endif
    
    // Check if brief already exists from database (page load)
    console.log('🔍 CHECKING BRIEF FROM DATABASE...');
    console.log('📍 PHP Variable Check:');
    console.log('  - isset($leadershipBriefFromDB):', {{ isset($leadershipBriefFromDB) ? 'true' : 'false' }});
    @if(isset($leadershipBriefFromDB))
        console.log('  - leadershipBriefFromDB is set');
        console.log('  - empty($leadershipBriefFromDB):', {{ empty($leadershipBriefFromDB) ? 'true' : 'false' }});
        console.log('  - strlen($leadershipBriefFromDB):', {{ strlen($leadershipBriefFromDB) }});
        console.log('  - Brief preview (first 300 chars):', {!! json_encode(substr($leadershipBriefFromDB, 0, 300)) !!});
    @else
        console.log('  - leadershipBriefFromDB is NOT set');
    @endif
    
    @if(isset($leadershipBriefFromDB) && !empty($leadershipBriefFromDB))
        console.log('✅ BRIEF EXISTS IN DATABASE - Length:', {{ strlen($leadershipBriefFromDB) }});
        window.briefGenerationCompleted = true;
        // Wait for DOM to be ready, then verify brief is visible
        setTimeout(() => {
            console.log('🔍 Checking DOM for brief element...');
            const briefInDOM = document.querySelector('.leadership-alignment-brief');
            if (briefInDOM) {
                console.log('✅ Leadership Alignment Brief found in DOM from database');
                const briefContent = briefInDOM.querySelector('.response-text');
                if (briefContent && briefContent.textContent.trim().length > 0) {
                    console.log('✅ Brief content found in DOM');
                    console.log('  - Content length:', briefContent.textContent.length);
                    console.log('  - Content preview (first 200 chars):', briefContent.textContent.substring(0, 200) + '...');
                    console.log('  - Brief element:', briefInDOM);
                    console.log('  - Brief parent:', briefInDOM.parentElement);
                    console.log('  - Brief is visible:', briefInDOM.offsetHeight > 0 && briefInDOM.offsetWidth > 0);
                    console.log('  - Brief computed style display:', window.getComputedStyle(briefInDOM).display);
                    console.log('  - Brief computed style visibility:', window.getComputedStyle(briefInDOM).visibility);
                } else {
                    console.warn('⚠️ Brief element found but no response-text content or content is empty');
                    console.log('  - Brief element HTML:', briefInDOM.innerHTML.substring(0, 200));
                    console.log('  - Brief element children:', briefInDOM.children.length);
                }
            } else {
                console.error('❌ Leadership Alignment Brief NOT found in DOM even though it should be loaded from DB');
                console.log('  - Searching for all .leadership-alignment-brief elements:', document.querySelectorAll('.leadership-alignment-brief').length);
                console.log('  - All .response-text elements:', document.querySelectorAll('.response-text').length);
                console.log('  - All .tt-template-carddads elements:', document.querySelectorAll('.tt-template-carddads').length);
                
                // Try to find any element containing "LEADERSHIP ALIGNMENT BRIEF"
                const allText = Array.from(document.querySelectorAll('*')).map(el => el.textContent).join(' ');
                if (allText.includes('LEADERSHIP ALIGNMENT BRIEF') || allText.includes('Leadership Alignment Brief')) {
                    console.log('  - ⚠️ Found text "LEADERSHIP ALIGNMENT BRIEF" in page, but element not found with class');
                }
            }
        }, 1000);
    @else
        console.log('❌ No Leadership Alignment Brief in database for this chat');
        console.log('  - This means either:');
        console.log('    1. Brief was never generated');
        console.log('    2. Brief was generated but not saved to database');
        console.log('    3. Brief exists but variable is not set correctly');
    @endif

    // Automatically generate and display Leadership Alignment Brief after final outcome
    // Disable/enable the visible Finish button while the brief is generating
    function setBriefLoadingState(isLoading) {
        // Cover both renderers' Finish buttons: legacy .next-step-btn and the
        // JSON wizard's .gs-next. Without .gs-next the wizard Finish stayed
        // clickable mid-generation, so clicking it rebuilt the DOM and dropped
        // the in-progress brief.
        document.querySelectorAll('.next-step-btn, .gs-next').forEach(function (btn) {
            if (isLoading) {
                const label = (btn.textContent || '').trim();
                if (label === 'Finish' || btn.dataset.briefLock) {
                    btn.dataset.briefLock = '1';
                    if (!btn.dataset.briefOrigText) btn.dataset.briefOrigText = btn.innerHTML;
                    btn.disabled = true;
                    btn.classList.add('disabled');
                    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Generating...';
                }
            } else if (btn.dataset.briefLock) {
                btn.disabled = false;
                btn.classList.remove('disabled');
                if (btn.dataset.briefOrigText) btn.innerHTML = btn.dataset.briefOrigText;
                delete btn.dataset.briefLock;
                delete btn.dataset.briefOrigText;
            }
        });
    }

    function autoGenerateAlignmentBrief() {
        // Prevent multiple simultaneous calls
        if (window.briefGenerationInProgress || window.briefGenerationCompleted) {
            console.log('⏭️ Skipping brief generation - already in progress or completed');
            return;
        }
        
        console.log('🔍 autoGenerateAlignmentBrief: Starting...');
        
        // Check if brief already exists in DOM (from database)
        const existingBriefInDOM = document.querySelector('.leadership-alignment-brief');
        if (existingBriefInDOM) {
            const briefContent = existingBriefInDOM.querySelector('.response-text');
            if (briefContent && briefContent.textContent.trim().length > 0) {
                console.log('✅ Leadership Alignment Brief found in DOM from database');
                console.log('📋 Brief content preview:', briefContent.textContent.substring(0, 150) + '...');
                window.briefGenerationCompleted = true;
                return;
            } else {
                console.warn('⚠️ Brief element found but content is empty');
            }
        } else {
            console.log('🔍 No brief found in DOM, will check if it needs to be generated');
        }
        
        // Search for Final Outcome section - check both in all documents and within specific card containers
        const allResponseTexts = Array.from(document.querySelectorAll('.response-text'));
        console.log('🔍 Searching for Final Outcome section...');
        console.log('📍 Total response-text elements found:', allResponseTexts.length);
        
        const finalOutcomeSection = allResponseTexts.find(el => {
            const text = el.textContent || el.innerText;
            const hasCheckmark = text.includes('✅');
            const hasFinalOutcome = text.includes('Final Outcome');
            if (hasCheckmark && hasFinalOutcome) {
                console.log('✅ Found Final Outcome section:', text.substring(0, 100));
            }
            return hasCheckmark && hasFinalOutcome;
        });
        
        if (!finalOutcomeSection) {
            console.warn('⚠️ Final Outcome section not found. Available sections:', 
                allResponseTexts.map(el => {
                    const text = el.textContent || el.innerText;
                    return text.substring(0, 50).replace(/\n/g, ' ');
                })
            );
            return;
        }
        
        console.log('✅ Final Outcome section found:', finalOutcomeSection);
        
        // Find the parent card container
        const cardContainer = finalOutcomeSection.closest('.tt-template-carddads');
        if (!cardContainer) return;

        // Note: the brief intentionally self-gates by requiring a visible
        // "✅ Final Outcome" section, so it generates as soon as that section
        // appears (the final wizard step) and persists into the final message.

        // Check if brief already exists in this card container
        const existingBriefInCard = cardContainer.querySelector('.leadership-alignment-brief');
        if (existingBriefInCard) {
            window.briefGenerationCompleted = true;
            return; // Already generated
        }
        
        // Check if loading indicator already exists
        const existingLoading = cardContainer.querySelector('.leadership-alignment-brief-loading');
        if (existingLoading) {
            return; // Already generating
        }
        
        // Mark as in progress
        window.briefGenerationInProgress = true;
        setBriefLoadingState(true); // disable Finish while generating

        // Show loading indicator
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'alert alert-info mt-3 leadership-alignment-brief-loading';
        loadingDiv.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Generating Leadership Alignment Brief...';
        finalOutcomeSection.insertAdjacentElement('afterend', loadingDiv);
        
        // Get required data
        const chatId = document.getElementById('chat_id')?.value || window.chatChatId;
        const selectedStrategy = window.selectedStrategy || '';
        const selectedScenario = window.selectedScenario || '';
        const originalQuestion = window.chatQuestion || '';
        const fullResponse = Array.from(document.querySelectorAll('.response-text'))
            .map(el => el.textContent || el.innerText)
            .join('\n\n');
        
        // Validate required data before calling API
        if (!chatId) {
            console.error('❌ Cannot generate brief: chat_id is missing');
            console.log('📍 chat_id element value:', document.getElementById('chat_id')?.value);
            console.log('📍 window.chatChatId:', window.chatChatId);
            return;
        }
        
        if (!originalQuestion) {
            console.error('❌ Cannot generate brief: original question is missing');
            console.log('📍 window.chatQuestion:', window.chatQuestion);
            return;
        }
        
        console.log('📋 Data validation passed:', {
            chatId,
            hasStrategy: !!selectedStrategy,
            hasScenario: !!selectedScenario,
            hasQuestion: !!originalQuestion,
            responseLength: fullResponse.length
        });
        
        // Generate brief automatically
        window.generateLeadershipAlignmentBrief(chatId, selectedStrategy, selectedScenario, originalQuestion, fullResponse);
    }

    // Monitor for new sections and add buttons (with debouncing to prevent infinite loops)
    let observerTimeout;
    const observer = new MutationObserver(function(mutations) {
        // Debounce to prevent rapid repeated calls
        clearTimeout(observerTimeout);
        observerTimeout = setTimeout(() => {
            addExportButtonToRoleGoals();
            addActionTableSuggestion();
            // Only call autoGenerateAlignmentBrief if not already in progress or completed
            if (!window.briefGenerationInProgress && !window.briefGenerationCompleted) {
                autoGenerateAlignmentBrief();
            }
        }, 500); // 500ms debounce
    });

    observer.observe(document.getElementById('chat-messages'), {
        childList: true,
        subtree: true
    });

    // Initial check (with delay to ensure DOM is ready)
    setTimeout(() => {
        addExportButtonToRoleGoals();
        addActionTableSuggestion();
        if (!window.briefGenerationInProgress && !window.briefGenerationCompleted) {
            autoGenerateAlignmentBrief();
        }
    }, 2000); // Increased delay to 2 seconds
</script>



@endsection

