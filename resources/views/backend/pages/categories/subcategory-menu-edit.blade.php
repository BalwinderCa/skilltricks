@extends('backend.layouts.master')

@section('title')
    {{ localize('Update Question') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Question Edit') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ url('dashboard/subcategory-menu') }}">{{ localize('Question') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Update') }}</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ url('dashboard/subcategory-menu-update/'.$subcategorymenuedit->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $subcategorymenuedit->id }}">
                        <!--basic information start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>

                                <div class="mb-4">
                                    <label for="question" class="form-label">{{ localize('Select Chat Role Name ') }}</label>
                                    <select name="role" id="role_name" class="form-control" required>
                                        <option value="">--Select Chat Role Name--</option>
                                    @foreach($rolesdataedit as $rolesdataval)
                                        <option value="{{$rolesdataval->id}}" {{ $rolesdataval->id == $subcategorymenuedit->role ? 'selected' : '' }}>{{$rolesdataval->name}}</option>
                                    @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="question" class="form-label">{{ localize('Select Parent Category
                                        Name ') }}</label>
                                    <select name="category" id="category" class="form-control" required>
                                        <option value="">--Select Parent Category
                                        Name --</option>
                                    @foreach($chatcategoriesalllist as $chatcategoriesval)
                                        <option value="{{$chatcategoriesval->id}}" {{ $chatcategoriesval->id == $subcategorymenuedit->categories ? 'selected' : '' }}>{{$chatcategoriesval->name}}</option>
                                    @endforeach
                                    </select>
                                </div>

                                @php
                                   $chatsubcategoriesedit = DB::table('chat_subcategories')->where('id',$subcategorymenuedit->subcategories)->where('status',1)->get();
                                @endphp

                                <div class="mb-4">
                                    <label for="subcategories" class="form-label">{{ localize('Select Sub Category Name') }}</label>
                                    <select name="subcategories" id="subcategories" class="form-control">
                                        <option value="">--Select Sub Category Name--</option>
                                        @foreach($chatsubcategoriesedit as $chatcategoriesvalsub)
                                            <option value="{{$chatcategoriesvalsub->id}}" {{ $chatcategoriesvalsub->id == $subcategorymenuedit->subcategories ? 'selected' : '' }}>{{$chatcategoriesvalsub->sub_category}}</option>
                                        @endforeach
                                    </select>
                                </div>


                                <div class="mb-4">
                                    <label for="question" class="form-label">{{ localize('Question') }}</label>
                                    <div id="question-wrapper">
                                        <div class="input-group mb-2">
                                            <input class="form-control" type="text" name="question[]">
                                            <button type="button" class="btn btn-success add-btn">+</button>
                                        </div>
                                    </div>
                                </div>

                                {{--<!-- <div class="mb-4">
                                    <label for="status" class="form-label">Status <span class="text-danger ms-1">*</span></label>
                                    <select class="form-select select2" id="status" name="status" required>
                                        <option value="1" {{ $subcategorymenuedit->status == '1' ? 'selected' : '' }}>
                                            Active
                                        </option>
                                        <option value="0" {{ $subcategorymenuedit->status == '0' ? 'selected' : '' }}>
                                            Deactive
                                        </option>
                                    </select>
                                </div> -->--}}
                            </div>
                        </div>
                        <!--basic information end-->

                        <!-- submit button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i> {{ localize('Save Changes') }}
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
                            <h5 class="mb-4">{{ localize('Question Edit Information') }}</h5>
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
    </section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function () {
        // Add new question input
        $(document).on('click', '.add-btn', function () {
            let newInput = `
                <div class="input-group mb-2">
                    <input class="form-control" type="text" name="question[]">
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


@endsection
