@extends('backend.layouts.master')

@section('title')
    {{ localize('Email Template Settings') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">


            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Email Template Settings') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Email Template Settings') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-between mb-5">

                <div class="col-xl-9">
                    <div class="tab-content" id="myTabContent">
                        @foreach ($templates as $template)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                id="{{ $template->slug }}-pane" role="tabpanel" aria-labelledby="{{ $template->slug }}"
                                tabindex="0">

                                <form action="" method="POST" id="emailTemplateForm{{ $template->id }}">
                                    @csrf
                                    <div id="editor">
                                        <h4>{{ $template->name }}</h4>
                                        <input type="hidden" name="template[id]" value="{{ $template->id }}">
                                        <div class="mb-4">
                                            <label for="Variables">{{ localize('Variables') }}</label>
                                            <span><strong>{{ $template->variables }}</strong></span>
                                        </div>
                                        <div class="row align-items-center">
                                            <div class="col-lg-8">
                                                <div class="mb-4">
                                                    <label for="subject_{{ $template->id }}"
                                                        class="form-label">{{ localize('Subject') }}
                                                        <x-required-star /></label>
                                                    <input class="form-control" type="text"
                                                        id="subject_{{ $template->id }}" name="template[subject]"
                                                        placeholder="{{ localize('Type Subject') }}"
                                                        value="{{ $template->subject }}" required>
                                                    <x-error :name="'subject'" />

                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="is_active_{{ $template->id }}" class="form-label">
                                                    <input type="checkbox" name="template[is_active]"
                                                        class="form-check-input me-2" id="is_active_{{ $template->id }}"
                                                        @if ($template->is_active) checked @endif
                                                        value="{{ $template->id }}"> {{ localize('Is Active ?') }}</label>

                                            </div>
                                        </div>

                                        <textarea name="template[code]" id="content_{{ $template->id }}" class="editor form-control" cols="30"
                                            rows="10">
                                        {{ $template->code }}
                                        </textarea>
                                    </div>
                                    <div class="d-flex justify-content-center mt-4">
                                        <button class="btn btn-primary saveEmailtemplate" type="button"
                                            data-id={{ $template->id }}>
                                            {{ localize('Save Changes') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endforeach

                    </div>

                </div>
                <div class="col-xl-3">
                    <div class="card tt-sticky-sidebar">
                        <div class="card-body">
                            <h5 class="mb-4">{{ localize('Template Information') }}</h5>
                            <div class="tt-vertical-step-two">
                                <ul class="list-unstyled" id="myTab" role="tablist">
                                    @foreach ($templates as $template)
                                        <li>
                                            <a class="nav-link {{ $loop->first ? 'active' : '' }}"
                                                id="{{ $template->slug }}" data-bs-toggle="tab"
                                                data-bs-target="#{{ $template->slug }}-pane" type="button" role="tab"
                                                aria-controls="{{ $template->slug }}-pane"
                                                aria-selected="true">{{ $template->name }}</a>
                                        </li>
                                    @endforeach


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

        $(document).ready(function() {
            $('div.note-editable').height(500);

        });
        $(document).on('click', '.saveEmailtemplate', function(e) {
            e.preventDefault();

            let id = $(this).data('id');
            let subject = $('#subject_' + id).val();
            let content = $('#content_' + id).val();
            var is_active = 0;
            if ($('#is_active_'+id).is(":checked")) {
               var  is_active = 1;
            }

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                type: "POST",
                url: "{{ route('admin.email-template.update') }}",
                data: {
                    id: id,
                    subject: subject,
                    content: content,
                    is_active: is_active,
                },
                dataType: "JSON",
                success: function(response) {
                    if(response.status == 200) {
                        notifyMe("success", response.message ?? "{{ localize('Update Successfully') }}");
                    }else{
                        notifyMe("error", response.message ?? "{{ localize('Update Failed') }}");
                    }
                 
                },
            })
        });
    </script>
@endsection
