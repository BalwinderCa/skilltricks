@extends('backend.layouts.master')

@section('title')
    {{ localize('Chat Subcategory') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Chat Subcategory') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Chat Subcategory') }}</li>
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
                                <form class="app-search" action="" method="GET">
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
                                            <th>{{ localize('Chat Role Name') }}</th>
                                            <th>{{ localize('Category Name') }}</th>
                                            <th>{{ localize('SubCategory Name') }}</th>
                                            <th data-breakpoints="xs sm ">{{ localize('Status') }}</th>
                                            <th data-breakpoints="xs sm" class="text-end">{{ localize('Action') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    @php $i=1; @endphp

                                    @foreach($chatcategoriessub as $chatcategoval)

                                      @php 
                                         $chatcategorieslist = DB::table('chat_categories')->where('id',$chatcategoval->parent_category)->first();
                                         $rolesdatalist = DB::table('chat_role_categories')->where('id',$chatcategoval->role_name)->first();
                                      @endphp

                                        <tr>
                                        <td  width="7%">{{$i}}</td>
                                        @if(!empty($rolesdatalist))
                                            <td>{{$rolesdatalist->name ?? ''}}</td>
                                        @else
                                           <td></td>
                                        @endif
                                            <td>{{$chatcategorieslist->name ?? ''}}</td>
                                            <td>{{$chatcategoval->sub_category}}</td>
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
                                                        <a class="dropdown-item" href="{{url('dashboard/chat-subcategories-edit/'.$chatcategoval->id)}}"> <i data-feather="edit-3" class="me-2"></i>Edit </a>

                                                       <!--  <a href="#" class="dropdown-item confirm-delete" data-href="#" title="Delete">
                                                            <i data-feather="trash-2" class="me-2"></i>
                                                            Delete
                                                        </a> -->
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

                                        {{ $chatcategoriessub->firstItem() }}-{{ $chatcategoriessub->lastItem() }} {{ localize('of') }}

                                        {{ $chatcategoriessub->total() }} {{ localize('results') }}</span>

                                    <nav>

                                        {{ $chatcategoriessub->appends(request()->input())->links() }}

                                    </nav>

                                </div>

                                <!--pagination end-->


                            </div>
                        </div>

                        <form action="{{url('dashboard/chat-subcategories-store')}}" class="pb-650" method="POST">
                            @csrf
                            <!-- faq info start-->
                            <div class="card mb-4" id="section-2">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('Add New SubCategory') }}</h5>

                                    <!-- Chat Role Dropdown -->
                                    <div class="mb-4">
                                        <label for="role_name" class="form-label">{{ localize('Select Chat Role Name') }}</label>
                                        <select name="role_name" id="role_name" class="form-control" required>
                                            <option value="">--Select Chat Role Name--</option>
                                            @foreach($rolesdata as $rolesdataval)
                                                <option value="{{ $rolesdataval->id }}">{{ $rolesdataval->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Parent Category Dropdown -->
                                    <div class="mb-4">
                                        <label for="parent_category" class="form-label">{{ localize('Select Parent Category Name') }}</label>
                                        <select name="parent_category" id="parent_category" class="form-control" required>
                                            <option value="">--Select Parent Category Name--</option>
                                        </select>
                                    </div>

                                    
                                    {{--<div class="mb-4">
                                        <label for="question" class="form-label">{{ localize('Select Parent Category
                                            Name ') }}</label>
                                        <select name="parent_category" id="" class="form-control" required>
                                            <option value="">--Select Parent Category
                                            Name --</option>
                                        @foreach($chatcategoriesall as $chatcategoriesval)
                                            <option value="{{$chatcategoriesval->id}}">{{$chatcategoriesval->name}}</option>
                                        @endforeach
                                        </select>
                                    </div>--}}

                                    <div class="mb-4">
                                        <label for="sub_category" class="form-label">{{ localize('Chat SubCategory Name') }}</label>
                                        <input class="form-control" type="text" id="sub_category" name="sub_category"
                                            placeholder="{{ localize('Chat SubCategory Name') }}" required>
                                    </div>

                                    <div class="mb-4">
                                        <label for="status" class="form-label">Status <span class="text-danger ms-1">*</span></label>

                                        <select class="form-select select2" id="status" name="status" required>
                                            <option value="1">
                                                Active
                                            </option>
                                            <option value="0">
                                                Deactive
                                            </option>
                                        </select>
                                    </div>

                                </div>
                            </div>
                            <!-- faq info end-->

                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-4">
                                        <button class="btn btn-primary" type="submit">
                                            <i data-feather="save" class="me-1"></i> {{ localize('Save SubCategory') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('SubCategory Information') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('All SubCategory') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-2">{{ localize('Add New Chat SubCategory') }}</a>
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
            url: '/dashboard/chatsubcategory-update-status',
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $('#role_name').on('change', function () {
        var roleId = $(this).val();

        if (roleId) {
            $.ajax({
                url: '{{ url("dashboard/get-parent-categories") }}',
                type: 'GET',
                data: { role_id: roleId },
                success: function (response) {
                    $('#parent_category').empty().append('<option value="">--Select Parent Category Name--</option>');
                    $.each(response.data, function (key, category) {
                        $('#parent_category').append('<option value="' + category.id + '">' + category.name + '</option>');
                    });
                }
            });
        } else {
            $('#parent_category').empty().append('<option value="">--Select Parent Category Name--</option>');
        }
    });
</script>


@endsection
