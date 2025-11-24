@extends('frontend.theme1.layouts.master')

@section('title')
    {{ localize('Home') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('content')
    <section class="breadcrumb-section">
        <div class="container">
            <div class="row">

            </div>
        </div>

    </section>
    <div class="section-space-sm-top section-space-sm-bottom">
        <div class="container">

            <div class="row gy-4">
                @forelse ($blogs as $blog)
                    <div class="col-md-6 col-xl-4">
                        <div class="bg-neutral-4 rounded-4">

                            @if ($blog->banner)
                                <img src="{{ uploadedAsset($blog->banner) }}" loading="lazy"
                                    alt="{{ $blog->collectLocalization('title') }}"
                                    class="img-fluid w-100 object-fit-cover rounded-top-start-4 rounded-top-end-4" />
                            @elseif ($blog->thumbnail_image)
                                <img src="{{ staticAsset($blog->thumbnail_image) }}" alt="Image Not Found"
                                    class="img-fluid w-100 object-fit-cover rounded-top-start-4 rounded-top-end-4">
                            @else
                                <img src="{{ asset('public/frontend/theme1/') }}/assets/img/insight-1.png"
                                    class="img-fluid w-100 object-fit-cover rounded-top-start-4 rounded-top-end-4"
                                    alt="Image Not Found : {{ noImage() }}">
                            @endif
                            <div class="p-8">

                                <h4 class="fs-20 fw-extrabold tt-line-clamp tt-clamp-2"><a href="{{ route('home.blogs.show', $blog->slug) }}"
                                        class="clr-neutral-90 link :clr-primary-key">{{ $blog->collectLocalization('title') }}</a>
                                </h4>
                                <p class="clr-neutral-80 mb-6 tt-line-clamp tt-clamp-2">
                                    @if ($blog->is_wizard_blog == 1)
                                        {{ preg_replace('/\*\*(.*?)\*\*/', '', $blog->description) }}
                                    @else
                                        {{ $blog->short_description }}
                                    @endif
                                </p>
                                <ul class="list list-row gap-5">
                                    <li class="fs-14 clr-neutral-80 d-flex gap-2">
                                        <i class="bi bi-calendar2-date"></i>
                                        {{ date('d M, Y', strtotime($blog->created_at)) }}
                                    </li>

                                </ul>
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
    </div>
@endsection
