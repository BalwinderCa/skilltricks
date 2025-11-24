@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Keys') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI Keys') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('admin.settings.openAi') }}">{{ localize('AI Settings') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AI Keys') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                    <a href="{{ route('admin.multiOpenAi.checkKeyHealth') }}" class="btn btn-info"><i
                                            data-feather="activity"></i> {{ localize('Check API Key Health') }}</a>

                                @can('add_multiOpenAi')
                                    <a href="{{ route('admin.multiOpenAi.create') }}" class="btn btn-primary"><i
                                            data-feather="plus"></i> {{ localize('Add New') }}</a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <div class="row g-4">
                <div class="col-12">
                    <div class="card mb-4" id="section-1">
                        <form class="app-search" action="{{ Request::fullUrl() }}" method="GET">
                            <div class="card-header border-bottom-0">
                                <div class="row justify-content-between g-3">
                                    <div class="col-auto flex-grow-1">
                                        <div class="tt-search-box">
                                            <div class="input-group">
                                                <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i
                                                        data-feather="search"></i></span>
                                                <input class="form-control rounded-start w-100" type="text"
                                                    id="search" name="search" placeholder="{{ localize('Search') }}..."
                                                    @isset($searchKey)
                                                value="{{ $searchKey }}"
                                            @endisset>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="input-group">
                                            <select class="form-select select2" name="status"
                                                data-minimum-results-for-search="Infinity">
                                                <option value="">{{ localize('Select status') }}</option>
                                                <option value="1"
                                                    @isset($status)
                                                     @if ($status == 1) selected @endif
                                                    @endisset>
                                                    {{ localize('Active') }}</option>
                                                <option value="0"
                                                    @isset($status)
                                                     @if ($status == 0) selected @endif
                                                    @endisset>
                                                    {{ localize('DeActive') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-secondary">
                                            <i data-feather="search" width="18"></i>
                                            {{ localize('Search') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <table class="table tt-footable border-top" data-use-parent-width="true">
                            <thead>
                                <tr>
                                    <th class="text-center">{{ localize('S/L') }}</th>
                                    <th>{{ localize('Engine') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Api Key') }}</th>

                                    <th data-breakpoints="xs sm md">{{ localize('Status') }}</th>

                                    <th data-breakpoints="xs sm" class="text-end">{{ localize('Action') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($openAiKeys as $key => $openAi)
                                    <tr>
                                        <td class="text-center">
                                            {{ $key + 1 + ($openAiKeys->currentPage() - 1) * $openAiKeys->perPage() }}</td>
                                        <td>

                                            @if ($openAi->engine == 1)
                                                Open Ai
                                            @elseif($openAi->engine == 2)
                                                Stable Diffusion
                                            @else
                                            @endif
                                        </td>

                                        <td>
                                            @if (env('DEMO_MODE') == 'On')
                                                ****************************
                                            @else
                                                {{ $openAi->api_key }}
                                            @endif
                                        </td>



                                        <td>
                                            @can('status_multiOpenAi')
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" onchange="updateStatus(this)"
                                                        class="form-check-input"
                                                        @if ($openAi->is_active) checked @endif
                                                        value="{{ $openAi->id }}">
                                                </div>
                                            @endcan
                                        </td>

                                        <td class="text-end">
                                            <div class="dropdown tt-tb-dropdown">
                                                <button type="button" class="btn p-0" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i data-feather="more-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end shadow">

                                                    @can('view_open_ai_models')
                                                        @if($openAi->engine == 1)
                                                            <a class="dropdown-item" target="_blank"
                                                                href="{{ route('admin.multiOpenAi.models', ['id' => $openAi->id]) }}">
                                                                <i data-feather="eye" class="me-2"></i>{{ localize('View') }}
                                                            </a>
                                                        @endif
                                                    @endcan
                                                    @can('edit_multiOpenAi')
                                                        <a class="dropdown-item"
                                                            href="{{ route('admin.multiOpenAi.edit', ['id' => $openAi->id, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}">
                                                            <i data-feather="edit-3" class="me-2"></i>{{ localize('Edit') }}
                                                        </a>
                                                    @endcan



                                                    @can('delete_multiOpenAi')
                                                        <a href="#" class="dropdown-item confirm-delete"
                                                            data-href="{{ route('admin.multiOpenAi.delete', $openAi->id) }}"
                                                            title="{{ localize('Delete') }}">
                                                            <i data-feather="trash-2" class="me-2"></i>
                                                            {{ localize('Delete') }}
                                                        </a>
                                                    @endcan
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @isset($msg)
                                <?= $msg; ?>
                        @endisset
                        <!--pagination start-->
                        <div class="d-flex align-items-center justify-content-between px-4 pb-4">
                            <span>{{ localize('Showing') }}
                                {{ $openAiKeys->firstItem() }}-{{ $openAiKeys->lastItem() }} {{ localize('of') }}
                                {{ $openAiKeys->total() }} {{ localize('results') }}</span>
                            <nav>
                                {{ $openAiKeys->appends(request()->input())->links() }}
                            </nav>
                        </div>
                        <!--pagination end-->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('scripts')
    <script>
        "use strict";

        function updateStatus(el) {
            if (el.checked) {
                var is_active = 1;
            } else {
                var is_active = 0;
            }
            $.post('{{ route('admin.multiOpenAi.updateStatus') }}', {
                    _token: '{{ csrf_token() }}',
                    id: el.value,
                    is_active: is_active
                },
                function(data) {
                    if (data == 1) {
                        notifyMe('success', '{{ localize('Status updated successfully') }}');
                    } else {
                        notifyMe('danger', '{{ localize('Something went wrong') }}');
                    }
                });
        }
    </script>
@endsection
