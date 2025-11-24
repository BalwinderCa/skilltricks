@extends('backend.layouts.master')

@section('title')
    {{ localize('Manage Question') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
          
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Manage Question') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Manage Question') }}</li>
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
                                            <th>{{ localize('Chat Role Name') }}</th>
                                            <th>{{ localize('Category Name') }}</th>
                                            <th>{{ localize('Sub Category Name') }}</th>
                                            <!-- <th data-breakpoints="xs sm md">{{ localize('Status') }}</th> -->
                                            <th data-breakpoints="xs sm" class="text-end">{{ localize('Action') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    @php $i=1; @endphp

                                    @foreach($subcategorymenudata as $chatcategoval)
                                      @php
                                         $rolesdatalist = DB::table('chat_role_categories')->where('id',$chatcategoval->role)->first();
                                         $chatcategories = DB::table('chat_categories')->where('id',$chatcategoval->categories)->first();
                                         $chatsubcategories = DB::table('chat_subcategories')->where('id',$chatcategoval->subcategories)->first();
                                      @endphp
                                        <tr>

                                            <td  width="7%">{{$i}}</td>
                                        @if(!empty($rolesdatalist))
                                            <td>{{$rolesdatalist->name ?? ''}}</td>
                                        @else
                                           <td></td>
                                        @endif

                                            <td>{{$chatcategories->name}}</td>

                                        @if(!empty($chatsubcategories))
                                            <td>{{$chatsubcategories->sub_category ?? ''}}</td>
                                        @else
                                           <td></td>
                                        @endif
                                            {{--<!-- <td>
                                                <div class="form-check form-switch">
                                                <input 
                                                    type="checkbox" 
                                                    onchange="updateStatus(this, {{ $chatcategoval->id }})" 
                                                    class="form-check-input" 
                                                    {{ $chatcategoval->status == 1 ? 'checked' : '' }}
                                                />
                                            </div>
                                            </td> -->--}}
                                            <td class="text-end">
                                                <div class="dropdown tt-tb-dropdown">
                                                    <button type="button" class="btn p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i data-feather="more-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end shadow">
                                                        <a class="dropdown-item" href="{{url('dashboard/subcategory-menu-edit/'.$chatcategoval->id)}}"> <i data-feather="edit-3" class="me-2"></i>Edit </a>

                                                        <a href="{{url('dashboard/subcategory-menu-question/'.$chatcategoval->id)}}" class="dropdown-item confirm-view" data-href="#" title="View">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.0003 3C17.3924 3 21.8784 6.87976 22.8189 12C21.8784 17.1202 17.3924 21 12.0003 21C6.60812 21 2.12215 17.1202 1.18164 12C2.12215 6.87976 6.60812 3 12.0003 3ZM12.0003 19C16.2359 19 19.8603 16.052 20.7777 12C19.8603 7.94803 16.2359 5 12.0003 5C7.7646 5 4.14022 7.94803 3.22278 12C4.14022 16.052 7.7646 19 12.0003 19ZM12.0003 16.5C9.51498 16.5 7.50026 14.4853 7.50026 12C7.50026 9.51472 9.51498 7.5 12.0003 7.5C14.4855 7.5 16.5003 9.51472 16.5003 12C16.5003 14.4853 14.4855 16.5 12.0003 16.5ZM12.0003 14.5C13.381 14.5 14.5003 13.3807 14.5003 12C14.5003 10.6193 13.381 9.5 12.0003 9.5C10.6196 9.5 9.50026 10.6193 9.50026 12C9.50026 13.3807 10.6196 14.5 12.0003 14.5Z"></path></svg>
                                                            View
                                                        </a>
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

                                        {{ $subcategorymenudata->firstItem() }}-{{ $subcategorymenudata->lastItem() }} {{ localize('of') }}

                                        {{ $subcategorymenudata->total() }} {{ localize('results') }}</span>

                                    <nav>

                                        {{ $subcategorymenudata->appends(request()->input())->links() }}

                                    </nav>

                                </div>

                                <!--pagination end-->
                                
                            </div>
                        </div>

                        <form action="{{url('dashboard/subcategory-menu-store')}}" class="pb-650" method="POST">
                            @csrf
                            <!-- faq info start-->
                            <div class="card mb-4" id="section-2">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('Add New Question') }}</h5>

                                    <div class="mb-4">
                                        <label for="question" class="form-label">{{ localize('Select Chat Role Name ') }}</label>
                                        <select name="role" id="role_name" class="form-control" required>
                                            <option value="">--Select Chat Role Name--</option>
                                        @foreach($rolesdata as $rolesdataval)
                                            <option value="{{$rolesdataval->id}}">{{$rolesdataval->name}}</option>
                                        @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="category" class="form-label">{{ localize('Select Parent Category Name') }}</label>
                                        <select name="category" id="category" class="form-control" required>
                                            <option value="">--Select Parent Category Name--</option>
                                        </select>
                                    </div>
                                    
                                    {{--<!-- <div class="mb-4">
                                        <label for="category" class="form-label">{{ localize('Select Parent Category Name') }}</label>
                                        <select name="category" id="category" class="form-control" required>
                                            <option value="">--Select Parent Category Name--</option>
                                            @foreach($chatcategoriesall as $chatcategoriesval)
                                                <option value="{{$chatcategoriesval->id}}">{{$chatcategoriesval->name}}</option>
                                            @endforeach
                                        </select>
                                    </div> -->--}}

                                    <div class="mb-4">
                                        <label for="subcategories" class="form-label">{{ localize('Select Sub Category Name') }}</label>
                                        <select name="subcategories" id="subcategories" class="form-control">
                                            <option value="">--Select Sub Category Name--</option>
                                        </select>
                                    </div>


                                   <div class="mb-4">
                                        <label for="question" class="form-label">{{ localize('Question') }}</label>
                                        <div id="question-wrapper">
                                            <div class="input-group mb-2">
                                                <input class="form-control" type="text" name="question[]" required>
                                                <button type="button" class="btn btn-success add-btn">+</button>
                                            </div>
                                        </div>
                                    </div>






                                    

                                    <!-- <div class="mb-4">
                                        <label for="status" class="form-label">Status <span class="text-danger ms-1">*</span></label>

                                        <select class="form-select select2" id="status" name="status" required>
                                            <option value="1">
                                                Active
                                            </option>
                                            <option value="0">
                                                Deactive
                                            </option>
                                        </select>
                                    </div> -->

                                </div>
                            </div>
                            <!-- faq info end-->

                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-4">
                                        <button class="btn btn-primary" type="submit">
                                            <i data-feather="save" class="me-1"></i> {{ localize('Save') }}
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
                            <h5 class="mb-4">{{ localize('Manage Question Information') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('All Manage Question') }}</a>
                                    </li>
                                    <li>
                                        <a href="#section-2">{{ localize('Add New Manage Question') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function () {
        // Add new question input
        $(document).on('click', '.add-btn', function () {
            let newInput = `
                <div class="input-group mb-2">
                    <input class="form-control" type="text" name="question[]" required>
                    <button type="button" class="btn btn-danger remove-btn">-</button>
                </div>
            `;
            $('#question-wrapper').append(newInput);
        });

        // Remove question input
        $(document).on('click', '.remove-btn', function () {
            $(this).closest('.input-group').remove();
        });
    });
</script>

<script>
    $('#role_name').on('change', function () {
        var roleId = $(this).val();

        if (roleId) {
            $.ajax({
                url: '{{ url("dashboard/get-parent-categories") }}',
                type: 'GET',
                data: { role_id: roleId },
                success: function (response) {
                    $('#category').empty().append('<option value="">--Select Parent Category Name--</option>');
                    $.each(response.data, function (key, category) {
                        $('#category').append('<option value="' + category.id + '">' + category.name + '</option>');
                    });
                }
            });
        } else {
            $('#category').empty().append('<option value="">--Select Parent Category Name--</option>');
        }
    });
</script>

<script>
    $('#category').on('change', function () {
        let categoryId = $(this).val();

        if (categoryId) {
            $.ajax({
                url: "{{ url('dashboard/getsubcategories') }}", // this is the route you’ll create
                type: "GET",
                data: { category_id: categoryId },
                success: function (data) {
                    $('#subcategories').empty().append('<option value="">--Select Sub Category Name--</option>');
                    $.each(data, function (key, subcategory) {
                        $('#subcategories').append('<option value="' + subcategory.id + '">' + subcategory.sub_category + '</option>');
                    });
                }
            });
        } else {
            $('#subcategories').empty().append('<option value="">--Select Sub Category Name--</option>');
        }
    });
</script>




<script>
    function updateStatus(element, id) {
    const status = element.checked ? 1 : 0;
        $.ajax({
            url: '/dashboard/chatcategory-update-status',
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
