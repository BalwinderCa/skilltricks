@extends('frontend.default.layouts.master')

@section('title')
    {{ $blog->collectLocalization('title') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('page-header-title')
    {{ $blog->collectLocalization('title') }}
@endsection

@section('contents')
    <!--page header-->
    @include('frontend.default.inc.page-header', ['col' => 'col-lg-6', 'ptb' => 'ptb-80'])
    <!--blog details start-->
    <section class="tt-blog-section ptb-80 position-relative rounded-custom-top bg-light-subtle">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-8 col-xl-8 mx-auto">
                    <div class="blog-details-content bg-white rounded-2 p-4">
                        <div class="thumbnail rounded overflow-hidden">
                        
                            @if ($blog->banner)
                                <img src="{{ uploadedAsset($blog->banner) }}" loading="lazy"
                                    alt="{{ $blog->collectLocalization('title') }}" class="img-fluid" />
                            @elseif ($blog->thumbnail_image)
                                <img src="{{ staticAsset($blog->thumbnail_image) }}" alt="Image Not Found" class="img-fluid rounded">
                            @else
                                <img src="{{ noImage() }}" alt="Image Not Found : {{ noImage() }}">
                            @endif
                        </div>
                        <div class="blog-meta d-flex align-items-center gap-3 flex-wrap mt-3">
                            <span class="fs-xs fw-medium"><i
                                    class="fa-solid fa-tags me-1"></i>{{ optional($blog->blog_category)->name }}</span>
                            <span class="fs-xs fw-medium"><i
                                    class="fa-regular fa-clock me-1"></i>{{ date('M d, Y', strtotime($blog->created_at)) }}</span>
                        </div>
                        <span class="hr-line w-100 position-relative d-block my-4"></span>
                        @if($blog->is_wizard_blog == 1)
                        {!! preg_replace('/\*\*(.*?)\*\*/', '<h2 class="mb-0 mt-4 h5">$1</h2>', $blog->description) !!}
                        @else
                        {!! $blog->collectLocalization('description') !!}
                        @endif
                        @if (count($blog->tags) > 0)
                            <div class="tags-social d-flex align-items-center justify-content-between flex-wrap gap-4 mt-6">
                                <div class="tags-list d-flex align-items-center gap-2 flex-wrap">
                                    <span class="title fw-bold me-2">{{ localize('Tags') }}:</span>
                                    @foreach ($blog->tags as $tag)
                                        <a href="javacript:void(0);">{{ $tag->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </section>
    <!--blog details end-->
@endsection
