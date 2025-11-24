@extends('frontend.default.layouts.master')

@section('title')
    {{ localize('Blogs') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('page-header-title')
    {{ localize('Our Blogs') }}
@endsection

@section('page-header-form')
    <form class="newsletter-form position-relative d-block d-lg-flex d-md-flex" action="{{ route('home.blogs') }}">
        <input type="text" class="input-newsletter form-control-lg w-100 me-2" name="search"
            placeholder="{{ localize('Search') }}..."
            @isset($searchKey)
            value="{{ $searchKey }}"
            @endisset>
        <input type="submit" value="Search" data-wait="Please wait..." class="btn btn-primary mt-3 mt-lg-0 mt-md-0">
    </form>
@endsection

@section('contents')
    <!--page header-->
    @include('frontend.default.inc.page-header', ['col' => 'col-lg-6', 'ptb' => 'ptb-80'])

    <!--blog section start-->
    <section class="tt-blog-section ptb-100 position-relative rounded-custom-top bg-light-subtle">
        <div class="container">
            <div class="row g-3">
                @forelse ($blogs as $blog)
                    <div class="col-lg-4 col-md-6">
                        <div class="card flex-column h-100 tt-single-blog-card border-2">
                            <div class="card-body">
                                <h3 class="h5 tt-blog-title"><a
                                        href="{{ route('home.blogs.show', $blog->slug) }}">{{ $blog->collectLocalization('title') }}</a>
                                </h3>
                                @if($blog->is_wizard_blog == 1)
                                <p class="tt-line-clamp tt-clamp-2 tt-blog-para">{{ preg_replace('/\*\*(.*?)\*\*/', '', $blog->description) }}</p>
                                @else
                                <p class="tt-line-clamp tt-clamp-2 tt-blog-para">{{ $blog->short_description }}</p>
                                @endif
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div class="tt-blog-category d-flex flex-column h-100">
                                        <p class="badge bg-secondary mb-1">{{ optional($blog->blog_category)->name }}</p>
                                        <span
                                            class="text-muted small">{{ date('d M, Y', strtotime($blog->created_at)) }}</span>
                                    </div>
                                    <a href="{{ route('home.blogs.show', $blog->slug) }}" class="tt-read-more">
                                        <i data-feather="arrow-right" class="icon-14"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-danger mt-5">
                        <img src="{{ staticAsset('backend/assets/img/nodata.png') }}" alt=""
                            class="img-fluid w-25">
                    </div>
                @endforelse

            </div>
        </div>
    </section>
    <!--blog section end-->

    <!--cta-->
    <section class="cta-action pb-100">
        @include('frontend.default.pages.partials.home.cta')
    </section>
@endsection
