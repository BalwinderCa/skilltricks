@extends('backend.layouts.master')

@section('title')
    {{ localize('Update Ads') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Google Ads') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>

                                    <li class="breadcrumb-item">
                                        <a href="{{ route('folders.index') }}">{{ localize('Google Ads') }}</a>
                                    </li>

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
                    <form action="{{ route('admin.settings.update.adsense') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $ads->id }}">
                        <input type="hidden" name="slug" value="{{ $ads->slug }}">
                        <input type="hidden" name="size" value="{{ $ads->size }}">
                        <!--basic information start-->
                        <div class="card mb-4" id="section-1">
                            <div class="card-body">
                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>
                                <div class="mb-4">
                                    <label for="name" class="form-label">{{ localize('Name') }}</label>
                                    <input class="form-control" type="text" id="name" disabled
                                        placeholder="{{ localize('Type folder name') }}" required
                                        value="{{ $ads->name .' '.$ads->size }}">

                                    <x-error :name="'name'" />
                                </div>

                                <div class="mb-4">
                                    <label for="name" class="form-label"> {{ localize('code') }} 
                                        <span  class="text-danger"> *</span> </label>

                                    <textarea name="code" class="form-control" id="code" cols="30" rows="10" required> {{$ads->code}}</textarea>

                                    <x-error :name="'code'" />
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">{{ localize('Status') }}<span
                                            class="text-danger ms-1"></span></label>
                                    <select class="select2 form-control" data-toggle="select2"
                                        name="status">
                                        <option value="0" {{$ads->is_active != 1 ? 'selected': ''}}>{{ localize('De-Active') }}</option>
                                        <option value="1" {{$ads->is_active == 1 ? 'selected': ''}}>{{ localize('Active') }}</option>
                                  
                                    </select>
                                    @if ($errors->has('status'))
                                        <span class="text-danger">{{ $errors->first('status') }}</span>
                                    @endif
                                </div>
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
                            <h5 class="mb-4">{{ localize('AdSense Information') }}</h5>
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
