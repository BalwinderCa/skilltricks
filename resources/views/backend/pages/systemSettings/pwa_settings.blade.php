@extends('backend.layouts.master')

@section('title')
    {{ localize('PWA Settings') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection
@push('styles')
    <style>
        .color_field {

            height: 42px;

        }
    </style>
@endpush
@section('contents')
    <section class="tt-section pt-4">
        <div class="container">


            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('PWA Settings') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('PWA Settings') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row g-4">
                <!--left sidebar-->
                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1 pb-650">
                    <form action="{{ route('admin.settings.pwa.store') }}" method="POST" enctype="multipart/form-data"
                        class="mb-4">
                        @csrf
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-4">
                                            <label for="name" class="form-label">{{ localize('Name') }}
                                                <span class="text-danger ms-1">*</span></label>
                                            <input class="form-control" type="text" id="name"
                                                placeholder="{{ localize('Type Customer name') }}" name="name"
                                                value="{{ config('laravelpwa.manifest.name') }}">
                                            @if ($errors->has('name'))
                                                <span class="text-danger">{{ $errors->first('name') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-4">
                                            <label for="short_name" class="form-label">{{ localize('Short Name') }}
                                                <span class="text-danger ms-1">*</span></label>
                                            <input class="form-control" type="text" id="short_name"
                                                placeholder="{{ localize('PWA') }}" name="short_name"
                                                value="{{ config('laravelpwa.manifest.short_name') }}">
                                            @if ($errors->has('short_name'))
                                                <span class="text-danger">{{ $errors->first('short_name') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <input class="form-control" type="hidden" id="start_url"
                                    placeholder="{{ env('APP_URL') }}" name="start_url"
                                    value="{{ env('APP_URL') }}">
                                    <div class="col-lg-4">
                                        <div class="mb-4">
                                            <label for="background_color"
                                                class="form-label">{{ localize('Background Color') }}
                                                <span class="text-danger ms-1">*</span></label>
                                            <input class="form-control color_field" type="color" id="background_color"
                                                placeholder="{{ localize('PWA') }}" name="background_color"
                                                value="{{ config('laravelpwa.manifest.background_color') }}">
                                            @if ($errors->has('background_color'))
                                                <span class="text-danger">{{ $errors->first('background_color') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-4">
                                            <label for="theme_color" class="form-label">{{ localize('Theme Color') }}
                                                <span class="text-danger ms-1">*</span></label>
                                            <input class="form-control color_field" type="color" id="theme_color"
                                                placeholder="{{ localize('PWA') }}" name="theme_color"
                                                value="{{ config('laravelpwa.manifest.theme_color') }}">
                                            @if ($errors->has('theme_color'))
                                                <span class="text-danger">{{ $errors->first('theme_color') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-4">
                                            <label for="status_bar" class="form-label">{{ localize('Status Bar') }}
                                                <span class="text-danger ms-1">*</span></label>
                                            <input class="form-control color_field" type="color" id="status_bar"
                                                placeholder="{{ localize('PWA') }}" name="status_bar"
                                                value="{{ config('laravelpwa.manifest.base_color') }}">
                                            @if ($errors->has('status_bar'))
                                                <span class="text-danger">{{ $errors->first('status_bar') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <h5>Icons</h5>
                                    <div class="col-lg-3 mb-4">
                                        <div class="h-100">
                                            <label for="favicon" class="form-label">{{ localize('72x72') }}</label>
                                            <div class="file-drop-area file-upload text-center rounded-3 h-100 d-flex flex-column">
                                                <input type="file" class="file-drop-input" name="icon_72"
                                                    id="json" />
                                                <div class="file-drop-icon ci-cloud-upload">
                                                    {{-- <i data-feather="image"></i> --}}
                                                    <img src="{{ config('laravelpwa.manifest.icons.72x72.path') }}"
                                                        alt="">
                                                </div>
                                                <p class="text-dark fw-bold mb-2 mt-3">
                                                    {{ localize('Drop your files here or') }}
                                                    <a href="javascript::void(0);"
                                                        class="text-primary">{{ localize('Browse') }}</a>
                                                </p>
                                                <p class="mb-0 file-name text-muted">
                                                    <small>* {{ localize('Allowed file types: ') }} .png
                                                    </small>
                                                </p>
                                            </div>
                                            @if ($errors->has('icon_72'))
                                                <span class="text-danger">{{ $errors->first('icon_72') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-3 mb-4">
                                        <div class="h-100">
                                            <label for="favicon" class="form-label">{{ localize('96x96') }}</label>
                                            <div class="file-drop-area file-upload text-center rounded-3 h-100 d-flex flex-column">
                                                <input type="file" class="file-drop-input" name="icon_96"
                                                    id="json" />
                                                <div class="file-drop-icon ci-cloud-upload">
                                                    {{-- <i data-feather="image"></i> --}}
                                                    <img src="{{ config('laravelpwa.manifest.icons.96x96.path') }}"
                                                        alt="">
                                                </div>
                                                <p class="text-dark fw-bold mb-2 mt-3">
                                                    {{ localize('Drop your files here or') }}
                                                    <a href="javascript::void(0);"
                                                        class="text-primary">{{ localize('Browse') }}</a>
                                                </p>
                                                <p class="mb-0 file-name text-muted">
                                                    <small>* {{ localize('Allowed file types: ') }} .png
                                                    </small>
                                                </p>
                                            </div>
                                            @if ($errors->has('icon_96'))
                                                <span class="text-danger">{{ $errors->first('icon_96') }}</span>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-3 mb-4">
                                        <div class="h-100">
                                            <label for="favicon" class="form-label">{{ localize('128x128') }}</label>
                                            <div class="file-drop-area file-upload text-center rounded-3 h-100 d-flex flex-column">
                                                <input type="file" class="file-drop-input" name="icon_128"
                                                    id="json" />
                                                <div class="file-drop-icon ci-cloud-upload">
                                                    {{-- <i data-feather="image"></i> --}}
                                                    <img src="{{ config('laravelpwa.manifest.icons.128x128.path') }}"
                                                        alt="">
                                                </div>
                                                <p class="text-dark fw-bold mb-2 mt-3">
                                                    {{ localize('Drop your files here or') }}
                                                    <a href="javascript::void(0);"
                                                        class="text-primary">{{ localize('Browse') }}</a>
                                                </p>
                                                <p class="mb-0 file-name text-muted">
                                                    <small>* {{ localize('Allowed file types: ') }} .png
                                                    </small>
                                                </p>
                                            </div>
                                            @if ($errors->has('icon_128'))
                                                <span class="text-danger">{{ $errors->first('icon_128') }}</span>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-3 mb-4">
                                        <div class="h-100">
                                            <label for="favicon" class="form-label">{{ localize('144x144') }}</label>
                                            <div class="file-drop-area file-upload text-center rounded-3 h-100 d-flex flex-column">
                                                <input type="file" class="file-drop-input" name="icon_144"
                                                    id="json" />
                                                <div class="file-drop-icon ci-cloud-upload">
                                                    {{-- <i data-feather="image"></i> --}}
                                                    <img src="{{ config('laravelpwa.manifest.icons.144x144.path') }}"
                                                        alt="">
                                                </div>
                                                <p class="text-dark fw-bold mb-2 mt-3">
                                                    {{ localize('Drop your files here or') }}
                                                    <a href="javascript::void(0);"
                                                        class="text-primary">{{ localize('Browse') }}</a>
                                                </p>
                                                <p class="mb-0 file-name text-muted">
                                                    <small>* {{ localize('Allowed file types: ') }} .png
                                                    </small>
                                                </p>
                                            </div>
                                            @if ($errors->has('icon_144'))
                                                <span class="text-danger">{{ $errors->first('icon_144') }}</span>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-3 mb-4">
                                        <div class="h-100">
                                            <label for="favicon" class="form-label">{{ localize('152x152') }}</label>
                                            <div class="file-drop-area file-upload text-center rounded-3 h-100 d-flex flex-column">
                                                <input type="file" class="file-drop-input" name="icon_152"
                                                    id="json" />
                                                <div class="file-drop-icon ci-cloud-upload">
                                                    {{-- <i data-feather="image"></i> --}}
                                                    <img src="{{ config('laravelpwa.manifest.icons.152x152.path') }}"
                                                        alt="">
                                                </div>
                                                <p class="text-dark fw-bold mb-2 mt-3">
                                                    {{ localize('Drop your files here or') }}
                                                    <a href="javascript::void(0);"
                                                        class="text-primary">{{ localize('Browse') }}</a>
                                                </p>
                                                <p class="mb-0 file-name text-muted">
                                                    <small>* {{ localize('Allowed file types: ') }} .png
                                                    </small>
                                                </p>
                                            </div>
                                            @if ($errors->has('icon_152'))
                                                <span class="text-danger">{{ $errors->first('icon_152') }}</span>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-3 mb-4">
                                        <div class="h-100">
                                            <label for="favicon" class="form-label">{{ localize('192x192') }}</label>
                                            <div class="file-drop-area file-upload text-center rounded-3 h-100 d-flex flex-column">
                                                <input type="file" class="file-drop-input" name="icon_192"
                                                    id="json" />
                                                <div class="file-drop-icon ci-cloud-upload">
                                                    {{-- <i data-feather="image"></i> --}}
                                                    <img src="{{ config('laravelpwa.manifest.icons.192x192.path') }}"
                                                        alt="">
                                                </div>
                                                <p class="text-dark fw-bold mb-2 mt-3">
                                                    {{ localize('Drop your files here or') }}
                                                    <a href="javascript::void(0);"
                                                        class="text-primary">{{ localize('Browse') }}</a>
                                                </p>
                                                <p class="mb-0 file-name text-muted">
                                                    <small>* {{ localize('Allowed file types: ') }} .png
                                                    </small>
                                                </p>
                                            </div>
                                            @if ($errors->has('icon_192'))
                                                <span class="text-danger">{{ $errors->first('icon_192') }}</span>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-3 mb-4">
                                        <div class="h-100">
                                            <label for="favicon" class="form-label">{{ localize('384x384') }}</label>
                                            <div class="file-drop-area file-upload text-center rounded-3 h-100 d-flex flex-column">
                                                <input type="file" class="file-drop-input" name="icon_384"
                                                    id="json" />
                                                <div class="file-drop-icon ci-cloud-upload">
                                                    {{-- <i data-feather="image"></i> --}}
                                                    <img src="{{ config('laravelpwa.manifest.icons.384x384.path') }}"
                                                        alt="">
                                                </div>
                                                <p class="text-dark fw-bold mb-2 mt-3">
                                                    {{ localize('Drop your files here or') }}
                                                    <a href="javascript::void(0);"
                                                        class="text-primary">{{ localize('Browse') }}</a>
                                                </p>
                                                <p class="mb-0 file-name text-muted">
                                                    <small>* {{ localize('Allowed file types: ') }} .png
                                                    </small>
                                                </p>
                                            </div>
                                            @if ($errors->has('icon_384'))
                                                <span class="text-danger">{{ $errors->first('icon_384') }}</span>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-3 mb-4">
                                        <div class="h-100">
                                            <label for="favicon" class="form-label">{{ localize('512x512') }}</label>
                                            <div class="file-drop-area file-upload text-center rounded-3 h-100 d-flex flex-column">
                                                <input type="file" class="file-drop-input" name="icon_512"
                                                    id="json" />
                                                <div class="file-drop-icon ci-cloud-upload">
                                                    {{-- <i data-feather="image"></i> --}}
                                                    <img src="{{ config('laravelpwa.manifest.icons.512x512.path') }}"
                                                        alt="">
                                                </div>
                                                <p class="text-dark fw-bold mb-2 mt-3">
                                                    {{ localize('Drop your files here or') }}
                                                    <a href="javascript::void(0);"
                                                        class="text-primary">{{ localize('Browse') }}</a>
                                                </p>
                                                <p class="mb-0 file-name text-muted">
                                                    <small>* {{ localize('Allowed file types: ') }} .png
                                                    </small>
                                                </p>
                                            </div>
                                            @if ($errors->has('icon_512'))
                                                <span class="text-danger">{{ $errors->first('icon_512') }}</span>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                        <div class="mb-3">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="me-1"></i> {{ localize('Save Configuration') }}
                            </button>
                        </div>
                    </form>

                </div>

                <!--right sidebar-->
                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('PWA Settings') }}</h5>
                            <div class="tt-vertical-step">
                                <ul class="list-unstyled">
                                    <li>
                                        <a href="#section-1" class="active">{{ localize('Setup') }}</a>
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
