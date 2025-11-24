@extends('frontend.theme1.layouts.master')

@section('title')
    {{ $blog->collectLocalization('title') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('content')
    <section class="breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8">
                    <div class="d-inline-flex align-items-center py-2 px-4 bg-info-10 bg-opacity-3 rounded-1">
                        <a href="" class="link fs-12 clr-white">{{ localize('Home') }}</a>
                        <span class="fs-12 clr-white mx-1">-</span>
                        <span class="fs-12 clr-white">>{{ optional($blog->blog_category)->name }}</span>
                    </div>
                    <h2 class="h3 fw-bold clr-neutral-90 mt-4">{{ $blog->collectLocalization('title') }}</h2>
                    <ul class="list list-row gap-5 mt-6">
                        <li class="fs-14 clr-neutral-80 d-flex gap-2">
                            <i class="bi bi-calendar2-date"></i> {{ date('M d, Y', strtotime($blog->created_at)) }}
                        </li>
                        {{-- <li class="fs-14 clr-neutral-80 d-flex gap-2"><i class="bi bi-person-circle"></i> By Aminul Islam
                    </li> --}}
                    </ul>
                </div>
            </div>
        </div>
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-top.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-top">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-left.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-left">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-right.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-right">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-line-left.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-line-left">
        <img src="{{ asset('public/frontend/theme1/') }}/assets/img/breadcrumb-shape-line-right.png" alt="image"
            class="img-fluid breadcrumb-shape breadcrumb-shape-line-right">
    </section>
    <div class="section-space-sm-top section-space-sm-bottom">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8 clr-neutral-80">

                    @if ($blog->banner)
                        <img src="{{ uploadedAsset($blog->banner) ?? asset('public/frontend/theme1/assets/img/insight-1.png') }}" loading="lazy"
                            alt="{{ $blog->collectLocalization('title') }}" class="img-fluid rounded-1 object-fit-cover" />
                    @elseif ($blog->thumbnail_image)
                        <img src="{{ staticAsset($blog->thumbnail_image) ?? asset('public/frontend/theme1/assets/img/insight-1.png') }}" alt="Image Not Found"
                            class="img-fluid rounded-1 object-fit-cover">
                    @else
                        <img src="{{ noImage() ?? asset('public/frontend/theme1/assets/img/insight-1.png') }}" alt="Image Not Found ">
                    @endif
                    <p class="clr-neutral-80">
                        @if ($blog->is_wizard_blog == 1)
                            {!! preg_replace('/\*\*(.*?)\*\*/', '<h2 class="mb-0 mt-4 h5">$1</h2>', $blog->description) !!}
                        @else
                            {!! $blog->collectLocalization('description') !!}
                        @endif
                    </p>



                    <div class="d-flex flex-wrap align-items-center justify-content-between mt-10 gap-5">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 mt-10 w-100">
                            <div class="d-flex flex-wrap align-items-center gap-4">
                                <span class="clr-neutral-90 fw-extrabold">{{ localize('Tags') }}</span>
                                <ul class="list list-row flex-wrap gap-4">
                                    @if (count($blog->tags) > 0)
                                        <li class="d-flex gap-3">
                                            @foreach ($blog->tags as $tag)
                                                <a href="javacript:void(0);"
                                                    class="py-3 px-6 border border-neutral-17 rounded-2 clr-neutral-90 link fs-14 d-block :bg-primary-key :clr-white">
                                                    {{ $tag->name }}</a>
                                            @endforeach
                                        </li>
                                    @endif
                                </ul>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
