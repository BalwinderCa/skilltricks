@extends('support::layouts.master')
@section('title')
    {{ localize('Create Ticket') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection
@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Create Ticket') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Create Ticket') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-12">
                  <div class="card">
                    <div class="card-body">
                        <form action="{{ route('support.ticket.store') }}" method="POST" enctype="multipart/form-data"
                        class="">
                        @csrf
                        <div class="mb-3">
                            <label for="title"
                                class="form-label">{{ localize('Title') }}<span
                                    class="text-danger ms-1">*</span></label>
                            <input type="text" id="title" name="title" value="{{old('title')}}"
                                class="form-control">
                                @if ($errors->has('title'))
                                    <span class="text-danger">{{ $errors->first('title') }}</span>
                                @endif
                        </div>
                       
                        <div class="mb-3">
                            <label for="category"
                                class="form-label">{{ localize('Category') }}
                                <span class="text-danger ms-1">*</span></label>
                            <select class="form-select select2" id="category" name="category" 
                                required>
                                <option value="">
                                    {{ localize('Select Category') }}
                                </option>
                                @foreach ($categories as $category) 
                                    <option value="{{$category->id}}" {{old('category') == $category->id ? 'selected' : ''}}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('category'))
                                <span class="text-danger">{{ $errors->first('category') }}</span>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="priority"
                                class="form-label">{{ localize('Priority') }}
                                <span class="text-danger ms-1">*</span></label>
                            <select class="form-select select2" id="priority" name="priority"
                                required>
                                <option value="">
                                    {{ localize('Select Priority') }}
                                </option>
                                @foreach ($priorities as $priority) 
                                    <option value="{{$priority->id}}" {{ old('priority') == $priority->id ? 'selected' : '' }}>{{ $priority->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('priority'))
                                <span class="text-danger">{{ $errors->first('priority') }}</span>
                            @endif
                        </div>
                    
                        <div class="mb-3">
                            <textarea class="editor" name="description">{{old('description')}} </textarea>
                            @if ($errors->has('description'))
                                <span class="text-danger">{{ $errors->first('description') }}</span>
                            @endif
                        </div>
                        
                        
                        <div class="file-drop-area file-upload text-center rounded-3 py-3 mb-4">
                                <input type="file" class="file-drop-input" 
                                name="files" />
                                <p class="text-dark fw-bold mb-2">
                                <i data-feather="image" class="me-2"></i> {{ localize('Drop your files here or') }}
                                <a href="#" class="text-primary">{{ localize('Browse') }}</a>
                                </p>
                                <p class="mb-0 file-name text-muted">
                                    <small>* (Only .jpg, .png, will be accepted) </small>
                                
                                </p>
                                @if ($errors->has('files'))
                                <span class="text-danger">{{ $errors->first('files') }}</span>
                            @endif
                        </div>
        
                        <button class="btn btn-primary btn-create-content" type="submit">
                            <span class="me-2">{{localize('Post a Ticket')}}</span>
                        </button>
                        </form>
                    </div>
                  </div>
                </div>
              </div>
        </div>
    </section>
@endsection