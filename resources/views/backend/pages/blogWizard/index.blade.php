@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Blog Articles') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI Blog Articles') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AI Blog Articles') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <a href="{{ route('blog.wizard.create') }}" class="btn btn-sm btn-primary"><i
                                        data-feather="plus"></i> {{ localize('New Blog Article') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div class="card mb-4">
                        <table class="table tt-footable align-middle" data-use-parent-width="true">
                            <thead>
                                <tr>
                                    <th class="text-center">{{ localize('S/L') }}</th>
                                    <th>{{ localize('Blog Title') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Created Date') }}</th>
                                    <th data-breakpoints="xs sm">{{ localize('Generated') }}</th>
                                    <th data-breakpoints="xs sm md">{{ localize('Words') }}</th>
                                    <th data-breakpoints="xs sm md">{{ localize('Status') }}</th>
                                    <th data-breakpoints="xs sm md" class="text-end">{{ localize('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($blogs as $key => $blog)
                                    <tr>
                                        <td class="text-center fs-sm">
                                            {{ $key + 1 + ($blogs->currentPage() - 1) * $blogs->perPage() }}
                                        </td>

                                        <td>
                                            <div class="ms-1">
                                                <h6 class="fs-sm mb-0">
                                                    {{ optional($blog->aiBlogWizardArticle)->title ?? (optional($blog->aiBlogWizardOutline)->title ?? (optional($blog->aiBlogWizardTitle)->topic ?? '-')) }}
                                                </h6>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="fs-sm">{{ date('d M, Y', strtotime($blog->created_at)) }}</span>
                                        </td>

                                        <td>
                                            <span
                                                class="fs-sm">{{ optional($blog->aiBlogWizardArticle)->num_of_copies ?? 1 }}
                                                {{ localize('Times') }}</span>
                                        </td>

                                        <td>
                                            <span class="fs-sm fw-bold">
                                                {{ $blog->total_words }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ $blog->completed_step == 5 ? 'bg-soft-success' : 'bg-soft-primary' }} rounded-pill text-capitalize">
                                                {{ $blog->completed_step == 5 ? localize('Completed') : localize('Incomplete') }}</span>
                                                @if(isAdmin())
                                                <span
                                                    class="badge {{ $blog->is_blog_published == 1 ? 'bg-soft-success' : 'bg-soft-warning' }} rounded-pill text-capitalize">
                                                    {{ $blog->is_blog_published ? localize('Published') : localize('Not Published') }}
                                                </span>
                                                @endif
                                        </td>

                                        <td class="text-end">
                                            <div class="dropdown tt-tb-dropdown">
                                                <button type="button" class="btn p-0" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i data-feather="more-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end shadow" style="">

                                                    @if($blog->completed_step == 5 && $blog->is_blog_published != 1 && isAdmin())
                                                        <a href="javascript(void);" class="dropdown-item"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#categoryModal{{$blog->id}}">
                                                            <i data-feather="send" class="me-2"></i>
                                                            {{ localize(" Publish to Blog") }}
                                                        </a>
                                                    @endif


                                                    <a class="dropdown-item"
                                                        @if ($blog->completed_step == 5) href="{{ route('blog.wizard.view', $blog->uuid) }}"
                                                    @else
                                                    href="{{ route('blog.wizard.create') }}?uuid={{ $blog->uuid }}" @endif>
                                                        <i data-feather="edit-3" class="me-2"></i>
                                                        {{ $blog->completed_step == 5 ? localize('View Details') : localize('Continue Writing') }}
                                                    </a>

                                                    @if ($blog->completed_step == 5 )
                                                        <a class="dropdown-item"
                                                            href="{{ route('blog.wizard.edit', $blog->uuid) }} ">
                                                            <i data-feather="edit" class="me-2"></i>
                                                            {{ localize('Edit Blog') }}
                                                        </a>
                                                    @endif

                                                    <a href="#" class="dropdown-item confirm-delete"
                                                        data-href="{{ route('blog.wizard.delete', $blog->uuid) }}"
                                                        title="{{ localize('Delete') }}">
                                                        <i data-feather="trash-2" class="me-2"></i>
                                                        {{ localize('Delete') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    @if(isAdmin())
                                    <div class="modal fade" id="categoryModal{{ $blog->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('blog.wizard.publishToBlog',$blog->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="exampleModalLabel">
                                                           {{ localize('Are you going to publish the blog?')}}

                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">

                                                            <div class="row">
                                                                <div class="col-lg-12">
                                                                    <p class="text-left">
                                                                       <strong>{{ localize("Blog Title ") }} </strong> :  {{ optional($blog->aiBlogWizardArticle)->title ?? (optional($blog->aiBlogWizardOutline)->title ?? (optional($blog->aiBlogWizardTitle)->topic ?? '-')) }}
                                                                    </p>
                                                                </div>
                                                                <div class="col-lg-12 mb-4">
                                                                    <label for="">{{ localize("Select Blog Category") }} <x-required-star/></label>
                                                                    <select name="blog_category_id" class="mt-2 form-control">
                                                                        @forelse($blogCategories as $key=>$blogCategory)
                                                                            <option value="{{ $blogCategory->id }}">{{ $blogCategory->name }}</option>
                                                                        @empty
                                                                        @endforelse
                                                                    </select>
                                                                </div>

                                                                <div class="col-lg-12">
                                                                    <label for="">{{ localize("Select Blog Tags") }} <x-required-star/></label>
                                                                    <select name="tag_id[]" multiple class="mt-2 select2 form-control">
                                                                        @forelse($tags as $key=>$tag)
                                                                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                                                        @empty
                                                                        @endforelse
                                                                    </select>
                                                                </div>

                                                            </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{localize('Close')}}</button>
                                                        <button type="submit" class="btn btn-primary">{{localize('Save changes')}}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>


                        <!--pagination start-->
                        <x-pagination-component :list='$blogs' />
                        <!--pagination end-->
                    </div>
                </div>
            </div>




        </div>
    </section>
@endsection


@section('scripts')
    @include('backend.pages.blogWizard.inc.initScripts')

    <script>
        "use strict";

        wizardFormData = initWizardFormData;
        setLocalWizardData();
    </script>

    @include('backend.pages.blogWizard.inc.scripts')
@endsection
