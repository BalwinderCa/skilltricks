@extends('backend.layouts.master')

@section('title')
    {{ localize('Generate Text To Speech') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection
@push('styles')
    @include('backend.pages.elevenLabs.inc.css')
@endpush
@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Text To Speech') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a></li>
                                    <li class="breadcrumb-item">{{ localize('Text To Speech') }}</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 mb-5">
                    <div class="card flex-column h-100 pb-0">
                        <div class="card-header p-3 p-md-4 p-lg-5">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-md-9 col-12">


                                    <form action="{{ route('t2s.eleven-labs.generate-speech') }}"
                                        class="header-search-form generate-images-form" method="POST">
                                        @csrf
                                        <input type="hidden" class="input-type" name="type" value="text-to-speech">
                                        <!-- image generate form -->
                                        @include('backend.pages.elevenLabs.inc.text-to-speech-advance-options')
                                        <div class="mb-4">
                                            <label for="Title" class="form-label d-block">{{ localize('Title') }}
                                                <x-required-star /> </label>
                                            <input class="form-control" type="text" id="title"
                                                value="{{ old('title') }}" name="title"
                                                placeholder="{{ localize('title') }}" required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="Title" class="form-label d-block">{{ localize('Content') }}
                                                <x-required-star /> </label>
                                            <textarea class="form-control defaultcontent" name="content" id="input-textarea" rows="4"
                                                placeholder="{{ localize('Type your Text') }}" required> {{ old('content') }}</textarea>
                                            <div class="d-flex justify-content-between">
                                                <p class="fs-md"> <strong id="charac-count">0</strong> / 2500</p>
                                                @if (isAdmin())
                                                    <p class="fs-md"> {{ localize('Total quota remaining') }} : <strong>
                                                            {{ isset($user_info) && $user_info != null ? $user_info->subscription->character_limit - $user_info->subscription->character_count : '' }}</strong>
                                                    </p>
                                                @endif
                                            </div>

                                        </div>
                                        <div class="">
                                            <button type="submit"
                                                class="btn btn-link bg-primary border border-2 border-primary text-light "><i
                                                    class="flaticon-search translate-middle-y"></i>{{ localize('Generate') }}</button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>

                        <h5 class="mb-4 px-3 mt-4">{{ localize('Generated Speeches') }}</h5>
                        <div class="col-12">
                            <div id="voice_list_table">
                                @include('backend.pages.templates.inc.voice-list-eleven-labs', [
                                    'voiceLists' => $voiceLists,
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
    @include('backend.pages.elevenLabs.inc.eleven-labs-scripts')

@endsection
