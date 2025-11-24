@extends('backend.layouts.master')

@section('title')
    {{ localize('View Question') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
          
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('View Question') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('View Question') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="flash-message" class="alert alert-success d-none" role="alert"></div>

            <div class="row mb-4 g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-4" id="section-1">
                                <form class="app-search" action="{{ Request::fullUrl() }}" method="GET">
                                    <div class="card-header border-bottom-0">
                                        <div class="row justify-content-between g-3">
                                            <div class="col-auto flex-grow-1">
                                                <div class="tt-search-box">
                                                    <div class="input-group">
                                                        <span
                                                            class="position-absolute top-50 start-0 translate-middle-y ms-2">
                                                            <i data-feather="search"></i></span>
                                                        <input class="form-control rounded-start w-100" type="text"
                                                            id="search" name="search"
                                                            placeholder="{{ localize('Search') }}..."
                                                            @isset($searchKey)
                                                            value="{{ $searchKey }}"
                                                        @endisset>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" class="btn btn-primary">
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
                                            <th class="text-center" width="7%">{{ localize('S/L') }}</th>
                                            <th>{{ localize('Question') }}</th>
                                            <th data-breakpoints="xs sm md">{{ localize('Status') }}</th>
                                            <th data-breakpoints="xs sm" class="text-end">{{ localize('Action') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    @php $i=1; @endphp

                                    @foreach($subcategquestionedit as $chatcategoval)
                                        <tr>

                                            <td  width="7%">{{$i}}</td>
                                            <td>{{$chatcategoval->question}}</td>
                                            <td>
                                                <div class="form-check form-switch">
                                                <input 
                                                    type="checkbox" 
                                                    onchange="updateStatus(this, {{ $chatcategoval->id }})" 
                                                    class="form-check-input" 
                                                    {{ $chatcategoval->status == 1 ? 'checked' : '' }}
                                                />
                                            </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="dropdown tt-tb-dropdown">
                                                    <button type="button" class="btn p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i data-feather="more-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end shadow">
                                                        <a class="dropdown-item" href="{{url('dashboard/subcategorymenu-question-edit/'.$chatcategoval->id)}}"> <i data-feather="edit-3" class="me-2"></i>Edit </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @php $i++ @endphp

                                    @endforeach
                                    </tbody>
                                </table>

                                <!--pagination start-->

                                <div class="d-flex align-items-center justify-content-between px-4 pb-4">

                                    <span>{{ localize('Showing') }}

                                        {{ $subcategquestionedit->firstItem() }}-{{ $subcategquestionedit->lastItem() }} {{ localize('of') }}

                                        {{ $subcategquestionedit->total() }} {{ localize('results') }}</span>

                                    <nav>

                                        {{ $subcategquestionedit->appends(request()->input())->links() }}

                                    </nav>

                                </div>

                                <!--pagination end-->
                                
                            </div>
                        </div>
                    </div>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('View Question Information') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('All View Question') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


<script>
    function updateStatus(element, id) {
    const status = element.checked ? 1 : 0;
        $.ajax({
            url: '/dashboard/subcategorymenu-questionupdate-status',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    showFlashMessage(response.message);
                }
            },
            error: function() {
                showFlashMessage('Something went wrong', 'danger');
            }
        });
    }

    function showFlashMessage(message, type = 'success') {
        const flash = $('#flash-message');
        flash.removeClass('d-none alert-success alert-danger alert-warning')
             .addClass('alert-' + type)
             .text(message);

        setTimeout(() => {
            flash.addClass('d-none');
        }, 3000);
    }   
</script>

@endsection
