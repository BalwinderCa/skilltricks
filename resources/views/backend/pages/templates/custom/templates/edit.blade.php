@extends('backend.layouts.master')


@section('title')
    {{ localize('Edit Custom Template') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Custom Templates') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('custom.templates.index') }}">{{ localize('Custom') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Edit Template') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <select id="language"
                                    class="w-100 min-w-150 form-control text-capitalize country-flag-select"
                                    data-toggle="select2" onchange="localizeData(this.value)">
                                    @foreach (\App\Models\Language::isActive()->get() as $key => $language)
                                        <option value="{{ $language->code }}"
                                            {{ $lang_key == $language->code ? 'selected' : '' }}
                                            data-flag="{{ staticAsset('backend/assets/img/flags/' . $language->flag . '.png') }}">
                                            {{ $language->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row mb-4 g-4">

                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
                    <form action="{{ route('custom.templates.update') }}" method="POST" class="pb-650">
                        @csrf
                        <input type="hidden" name="id" value="{{ $template->id }}">
                        <input type="hidden" name="lang_key" value="{{ $lang_key }}">
                        <!--basic information start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>

                                <div class="mb-4">
                                    <label for="name" class="form-label">{{ localize('Template Name') }} <small
                                            class="ms-1 text-danger">*</small> </label>
                                    <input type="text" name="name" id="name"
                                        placeholder="{{ localize('Type template name') }}" class="form-control" required
                                        value="{{ $template->collectLocalization('name', $lang_key) }}">
                                </div>

                                @if (env('DEFAULT_LANGUAGE') == $lang_key)
                                    <div class="mb-4">
                                        <label for="icon" class="form-label">{{ localize('Icon') }}<a class="ms-1"
                                                href="https://icons8.com/line-awesome" target="_blank"
                                                rel="noopener noreferrer"><i data-feather="info"
                                                    class="icon-16"></i></a></label>
                                        <input class="form-control" type="text" id="icon" name="icon"
                                            placeholder='<i class="las la-info-circle"></i>' value="{{ $template->icon }}">
                                    </div>

                                    <div class="mb-4">
                                        <label for="category_id" class="form-label">{{ localize('Category') }}</label>
                                        <select class="form-control select2" name="category_id" data-toggle="select2">
                                            <option value="">{{ localize('Select a category') }}</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}"
                                                    @if ($template->custom_template_category_id == $category->id) selected @endif>
                                                    {{ $category->collectLocalization('name') }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="mb-4">
                                    <label class="form-label">{{ localize('Description') }}</label>
                                    <textarea class="form-control" name="description" id="description"
                                        placeholder="{{ localize('Type short description') }}">{{ $template->collectLocalization('description') }}</textarea>
                                </div>

                            </div>
                        </div>
                        <!--basic information end-->

                        @if (env('DEFAULT_LANGUAGE') == $lang_key)
                            <!--input information start-->
                            <div class="card mb-4" id="section-2">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('Input Information') }}</h5>
                                    @php
                                        $fields = json_decode($template->fields) ?? [];
                                    @endphp

                                    <div class="custom-input-informations">
                                        @foreach ($fields as $field)
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-3">
                                                    <label class="form-label">{{ localize('Input Type') }}
                                                        <small class="ms-1 text-danger">*</small></label>
                                                    <select class="form-control select2" name="input_types[]"
                                                        data-toggle="select2" required>
                                                        <option value="text"
                                                            @if ($field->field->type == 'text') selected @endif>
                                                            {{ localize('Input Field') }}</option>
                                                        <option value="textarea"
                                                            @if ($field->field->type == 'textarea') selected @endif>
                                                            {{ localize('Textarea Field') }}</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label">{{ localize('Input Name') }}
                                                        <small class="ms-1 text-danger">*</small> </label>
                                                    <input type="text" name="input_names[]"
                                                        onchange="generateInputNames(true)"
                                                        placeholder="{{ localize('Type input name') }}"
                                                        class="form-control" required value="{{ $field->field->name }}">

                                                </div>

                                                <div class="col-md-5">
                                                    <div class="d-flex align-items-center w-100">
                                                        <div class="w-100">
                                                            <label class="form-label">{{ localize('Input Label') }}
                                                                <small class="ms-1 text-danger">*</small> </label>
                                                            <input type="text" name="input_labels[]"
                                                                placeholder="{{ localize('Type input label') }}"
                                                                class="form-control" required
                                                                value="{{ $field->label }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="button" class="btn btn-link px-0 fw-medium" data-toggle="add-more"
                                        data-content='
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-3">
                                                <label class="form-label">{{ localize('Input Type') }}
                                                    <small class="ms-1 text-danger">*</small></label>
                                                <select class="form-control select2" name="input_types[]" data-toggle="select2"
                                                    required>
                                                    <option value="text">{{ localize('Input Field') }}</option>
                                                    <option value="textarea">{{ localize('Textarea Field') }}</option>
                                                </select>
                                            </div>
    
                                            <div class="col-md-4">
                                                <label class="form-label">{{ localize('Input Name') }}
                                                    <small class="ms-1 text-danger">*</small> </label>
                                                <input type="text" name="input_names[]"  onchange="generateInputNames(true)"
                                                    placeholder="{{ localize('Type input name') }}" class="form-control"
                                                    required> 
                                            </div>

                                            <div class="col-md-5"> 
                                                <div class="w-100">
                                                    <label class="form-label">{{ localize('Input Label') }}
                                                        <small class="ms-1 text-danger">*</small> </label>
                                                    <div class="d-flex align-items-center w-100">
                                                        <input type="text" name="input_labels[]"
                                                        placeholder="{{ localize('Type input label') }}"
                                                        class="form-control" required>
                                                        <button type="button" class="ms-1 btn btn-soft-danger" data-toggle="remove-parent"
                                                            data-parent=".row">
                                                            <i class="las la-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>'
                                        data-target=".custom-input-informations">
                                        <div class="d-flex align-items-center"><i data-feather="plus"></i>
                                            <span>{{ localize('Add More') }}</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                            <!--input information end-->

                            <!--prompt information start-->
                            <div class="card mb-4" id="section-3">
                                <div class="card-body">
                                    <h5 class="mb-4">{{ localize('Prompt Information') }}</h5>

                                    <div class="mb-4 hint d-none">
                                        <label class="form-label">{{ localize('Input Variables') }}</label>
                                        <div class="mb-1 input_names_prompts">
                                        </div>
                                        <small>*
                                            {{ localize('Click on variable to set the user input of it in your prompts') }}</small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label" for="prompt">{{ localize('Custom Prompt') }} <small
                                                class="text-danger">*</small></label>
                                        <textarea rows="4" class="form-control" name="prompt" id="prompt"
                                            placeholder="{{ localize('Type your prompt') }}" required>{{ $template->prompt }}</textarea>
                                        <small>* {{ localize('Old Prompt') }}: {{ $template->prompt }}</small>
                                    </div>

                                </div>
                            </div>
                            <!--prompt information end-->
                        @endif
                        <!-- submit button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i data-feather="save" class="me-1"></i> {{ localize('Save Template') }}
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
                            <h5 class="mb-4">{{ localize('Template Information') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('Basic') }}</a>
                                    </li>
                                    @if (env('DEFAULT_LANGUAGE') == $lang_key)
                                        <li>
                                            <a href="#section-2" class="">{{ localize('Inputs') }}</a>
                                        </li>
                                        <li>
                                            <a href="#section-3" class="">{{ localize('Prompts') }}</a>
                                        </li>
                                    @endif
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
    <script>
        "use strict";


        TT.inputNames = [];

        function slugify(str) {
            return str.toLowerCase().trim().replace(/[^\w\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');
        }

        function generateInputNames(nullPompt = false) {
            if (nullPompt) {
                $('textarea[name="prompt"]').val("");
            }
            TT.inputNames = [];

            $('input[name="input_names[]"]').each(function() {
                var $this = this;
                var value = $($this).val();
                value = value.toLowerCase().trim().replace(/[^\w\s-]/g, '').replace(/[\s_-]+/g, '-').replace(
                    /^-+|-+$/g, '')
                TT.inputNames.push(value);
            })

            let html = '';
            TT.inputNames.forEach(name => {
                if (name != "") {
                    var name2 = `"{_${name}_}"`;
                    html += "<span class='badge bg-soft-primary cursor-pointer me-2' onclick='addToPrompt(" +
                        "" +
                        name2 + "" +
                        ")'>" + name + "</span>"
                }
            });
            $('.input_names_prompts').empty();
            $('.input_names_prompts').html(html);

            if (html != '') {
                $('.hint').removeClass('d-none')
            } else {
                $('.hint').addClass('d-none')
            }
        }

        // add text to prompt
        function addToPrompt(value) {
            var prompt = $('textarea[name="prompt"]').val() || '';
            $('textarea[name="prompt"]').val(prompt + value);
        }

        // ready
        $(document).ready(function() {
            generateInputNames();
        });
    </script>
@endsection
