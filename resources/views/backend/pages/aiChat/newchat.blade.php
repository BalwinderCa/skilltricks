@extends('backend.layouts.master')



@section('title')

    {{ localize('New Chat') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}

@endsection



@section('contents')

    <section class="tt-section pt-4">

        <div class="container">

            <div class="row mb-4">

                <div class="col-12">

                    <div class="tt-page-header">

                        <div class="d-lg-flex align-items-center justify-content-lg-between">

                            <div class="tt-page-title mb-3 mb-lg-0">

                                <h1 class="h4 mb-lg-1">{{ localize('New Chat') }}</h1>

                                <ol class="breadcrumb breadcrumb-angle text-muted">

                                    <li class="breadcrumb-item"><a

                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>

                                    </li>

                                    <li class="breadcrumb-item">{{ localize('New Chat') }}</li>

                                </ol>

                            </div>

                            <div class="tt-action">

                            </div>

                        </div>

                    </div>

                </div>

            </div>





            <div class="row mb-4 g-4">



                <!--left sidebar-->

                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">

                    <form action="{{ url('dashboard/chat-search-question') }}" method="POST">

                        @csrf

                        <input type="hidden" name="id" value="{{ $user->id }}">

                        <!--basic information start-->

                        <div class="card mb-4" id="section-1">

                            <div class="card-body">

                                <h5 class="mb-4">{{ localize('Search Goals You Are Looking For By Answering Few Questions !!') }}</h5>



                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ localize('Role') }}<span class="text-danger">*</span></label>
                                    
                                    <select  class="form-control" name="chat_role_categories" required style="pointer-events: none;background-color: #e7e5e7;">
                                        <option value="">Select</option>
                                    @foreach($chatrolecategories as $vlaue)
                                        <option value="{{$vlaue->id}}" {{ $user->chat_role_categories == $vlaue->id ? 'selected' : '' }}>{{$vlaue->name}}</option>
                                    @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ localize('Category') }}<span class="text-danger">*</span></label>
                                    
                                    <select  class="form-control" name="categories" id="category" required>
                                        <option value="">Select</option>
                                    @foreach($chatcategories as $catvlaue)
                                        <option value="{{$catvlaue->id}}">{{$catvlaue->name}}</option>
                                    @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ localize('Sub Category') }}<span class="text-danger">*</span></label>
                                    
                                    <select name="subcategories" id="subcategories" class="form-control" required>
                                        <option value="">Select</option>
                                    </select>
                                </div>

                            </div>

                        </div>

                        <!--basic information end-->







                        <!-- submit button -->

                        <div class="row">

                            <div class="col-12">

                                <div class="mb-3">

                                    <button class="btn btn-primary" type="submit">

                                        <i data-feather="save" class="me-1"></i> {{ localize('Next') }}

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

                            <h5 class="mb-4">{{ localize('New Chat Information') }}</h5>

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

@endsection





@section('scripts')

 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $('#category').on('change', function () {
        let categoryId = $(this).val();

        if (categoryId) {
            $.ajax({
                url: "{{ url('dashboard/getsubcategories') }}", // this is the route you’ll create
                type: "GET",
                data: { category_id: categoryId },
                success: function (data) {
                    $('#subcategories').empty().append('<option value="">Select</option>');
                    $.each(data, function (key, subcategory) {
                        $('#subcategories').append('<option value="' + subcategory.id + '">' + subcategory.sub_category + '</option>');
                    });
                }
            });
        } else {
            $('#subcategories').empty().append('<option value="">Select</option>');
        }
    });
</script>

@endsection

