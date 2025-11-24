@extends('backend.layouts.master')

@section('title')
    {{ localize('Generate Stable Diffusion Images') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Generate Stable Diffusion Images') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a></li>
                                    <li class="breadcrumb-item">{{ localize('Stable Diffusion Images') }}</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 mb-5">
                    <div class="card flex-column h-100">
                        <div class="card-header p-3 p-md-4 p-lg-5">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-md-9 col-12">

                                    <!-- button tab start -->
                                    <div class="row justify-content-center mb-4">
                                        <div class="col-auto">
                                            <div class="btn-group flex-wrap" role="tablist" id="pills-tab">
                                                <button type="button" onclick="handleTabClick('text-to-image')"
                                                    class="btn btn-outline-primary py-2 rounded-start-pill active"
                                                    id="text-to-image-tab" data-bs-toggle="pill"
                                                    data-bs-target="#text-to-image" type="button" role="tab"
                                                    aria-controls="text-to-image"
                                                    aria-selected="true">{{ localize('Text to Image') }}</button>

                                                <button type="button" onclick="handleTabClick('image-to-image')"
                                                    class="btn btn-outline-primary py-2" id="image-to-image-tab"
                                                    data-bs-toggle="pill" data-bs-target="#image-to-image" type="button"
                                                    role="tab" aria-controls="image-to-image"
                                                    aria-selected="false">{{ localize('Image to Image') }}</button>


                                                <button type="button" onclick="handleTabClick('upscale')"
                                                    class="btn btn-outline-primary py-2" id="upscale-tab"
                                                    data-bs-toggle="pill" data-bs-target="#upscale" type="button"
                                                    role="tab" aria-controls="upscale"
                                                    aria-selected="false">{{ localize('Upscale') }}</button>

                                                <button type="button" onclick="handleTabClick('multi-prompt')"
                                                    class="btn btn-outline-primary py-2 rounded-end-pill"
                                                    id="multi-prompt-tab" data-bs-toggle="pill"
                                                    data-bs-target="#multi-prompt" type="button" role="tab"
                                                    aria-controls="multi-prompt"
                                                    aria-selected="false">{{ localize('Multi Prompting') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- button tab end -->

                                    <!-- image generate form -->
                                    <form action="#" class="header-search-form generate-images-form" method="POST"
                                        data-engine="sd">
                                        @csrf
                                        <input type="hidden" class="input-type" name="type" value="text-to-image">
                                        @include('backend.pages.templates.inc.sd-advance-options')

                                        <!-- tab content start -->
                                        <div class="tab-content" id="pills-tabContent">
                                            <!-- text to image -->
                                            <div class="tab-pane fade show active" id="text-to-image" role="tabpanel"
                                                aria-labelledby="text-to-image-tab" tabindex="0">
                                                <!-- image generate form -->
                                                <div class="input-group">
                                                    <input
                                                        class="input-required form-control border border-2 border-primary rounded-pill rounded-end"
                                                        type="text" id="title" name="title"
                                                        placeholder="{{ localize('Type your image title or description that you are looking for') }}"
                                                        required>
                                                    <div class="input-group-append">
                                                        <button type="submit"
                                                            class="btn btn-link bg-primary border border-2 border-primary text-light rounded-pill rounded-start btn-create-content"><i
                                                                class="flaticon-search translate-middle-y"></i>{{ localize('Generate Image') }}</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- image to image -->
                                            <div class="tab-pane fade" id="image-to-image" role="tabpanel"
                                                aria-labelledby="image-to-image-tab" tabindex="0">
                                                <!-- image generate form -->
                                                <label for="titleImage" class="form-label mt-2">
                                                    {{ localize('Image Title or Description') }}</label>
                                                <input
                                                    class="input-required form-control rounded-pill mt-2 border border-primary mb-4"
                                                    type="text" id="titleImage" name="titleImage"
                                                    placeholder="{{ localize('Type your image title or description that you are looking for') }}">

                                                <div class="mb-4">
                                                    <label for="text-input"
                                                        class="form-label">{{ localize('Upload Image') }}</label>
                                                    <div class="file-drop-area file-upload text-center rounded-4 mb-4">
                                                        <input type="file" class="file-drop-input input-required"
                                                            name="imageFile">
                                                        <div class="file-drop-icon ci-cloud-upload">
                                                            <span data-feather="image"></span>
                                                        </div>
                                                        <p class="text-dark fw-bold mb-2 mt-3">
                                                            {{ localize('Drop your image here or browse') }}
                                                        </p>
                                                        <p class="mb-0 file-name text-muted">
                                                            {{ localize('(Only jpg, png, webp will be accepted)') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <button type="submit"
                                                    class="mt-4 btn btn-primary rounded-pill btn-create-content"><i
                                                        class="flaticon-search translate-middle-y"></i>{{ localize('Generate Image') }}</button>
                                            </div>

                                            <!-- upscale -->
                                            <div class="tab-pane fade" id="upscale" role="tabpanel"
                                                aria-labelledby="upscale-tab" tabindex="0">
                                                <!-- image generate form -->
                                                <label for="titleUpscale" class="form-label mt-2">
                                                    {{ localize('Image Title') }}</label>
                                                <input
                                                    class="input-required form-control rounded-pill mt-2 border border-primary mb-4"
                                                    type="text" id="titleImage" name="titleUpscale"
                                                    placeholder="{{ localize('Type your image title') }}">

                                                <div class="mb-4">
                                                    <label for="text-input"
                                                        class="form-label">{{ localize('Upload Image') }}</label>
                                                    <div class="file-drop-area file-upload text-center rounded-4 mb-4">
                                                        <input type="file" class="file-drop-input input-required"
                                                            name="imageUpscaleFile">
                                                        <div class="file-drop-icon ci-cloud-upload">
                                                            <span data-feather="image"></span>
                                                        </div>
                                                        <p class="text-dark fw-bold mb-2 mt-3">
                                                            {{ localize('Drop your image here or browse') }}
                                                        </p>
                                                        <p class="mb-0 file-name text-muted">
                                                            {{ localize('(Only jpg, png, webp will be accepted)') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <button type="submit"
                                                    class="mt-4 btn btn-primary rounded-pill btn-create-content"><i
                                                        class="flaticon-search translate-middle-y"></i>{{ localize('Generate Image') }}</button>
                                            </div>

                                            <!-- multi-prompting -->
                                            <div class="tab-pane fade" id="multi-prompt" role="tabpanel"
                                                aria-labelledby="multi-prompt-tab" tabindex="0">

                                                <label for="titles" class="form-label mt-2">
                                                    {{ localize('Image Title or Description') }}</label>
                                                <input
                                                    class="input-required form-control rounded-pill mt-2 border border-primary"
                                                    type="text" id="titles" name="titles[]"
                                                    placeholder="{{ localize('Type your image title or description that you are looking for') }}">

                                                <div class="multi-prompts">

                                                </div>

                                                <div>
                                                    <button type="button"
                                                        class="btn add-new-text rounded-pill btn-sm btn-outline-secondary mt-3"
                                                        data-toggle="add-more"
                                                        data-content='<div class="each-prompt d-flex align-items-center mt-3">
                                                            <input
                                                                class="input-required form-control rounded-pill border border-primary"
                                                                type="text" name="titles[]"
                                                                placeholder="{{ localize('Type another title or description') }}"
                                                                required> 
                                                            <div class="cursor-pointer text-danger ms-2"
                                                                data-toggle="remove-parent" data-parent=".each-prompt">
                                                                <i class="la la-trash fs-4"></i>
                                                            </div> 
                                                        </div>'
                                                        data-target=".multi-prompts">
                                                        <div class="d-flex align-items-center"><i data-feather="plus"
                                                                class="me-1"></i>
                                                            <span>{{ localize('Add More') }}</span>
                                                        </div>
                                                    </button>
                                                </div>

                                                <button type="submit"
                                                    class="mt-4 btn btn-primary rounded-pill btn-create-content"><i
                                                        class="flaticon-search translate-middle-y"></i>{{ localize('Generate Image') }}</button>
                                            </div>
                                        </div>
                                        <!-- tab content end -->
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="card-body d-flex flex-column h-100">
                            <h5 class="mb-4">{{ localize('Generated Image Result') }}</h5>
                            <div class="row g-3 tt-image-gallery ai-images-wrapper">
                                @include('backend.pages.templates.inc.images-list', [
                                    'images' => $images,
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@section('scripts')
    @include('backend.pages.templates.inc.template-scripts')

    <script>
        "use strict";

        function handleTabClick(type) {
            $('.input-required').prop("required", false);
            $('.input-required').val('');
            $(`#${type} .input-required`).prop("required", true);
            $('.input-type').val(type);

            if (type == "multi-prompt") {
                $('#resolution').prop('disabled', false);
                $('#negative_prompts').val('').prop('disabled', true).prop('title',
                    "Negative prompt is not allowed in multi-prompt");
            } else if (type == "image-to-image" || type == "upscale") {
                $('#resolution').prop('disabled', true);
            } else {
                $('#resolution').prop('disabled', false);
                $('#negative_prompts').val('').prop('disabled', false);
            }
        }
    </script>
@endsection
