@extends('backend.layouts.master')

@section('title')
    {{ localize('Prompts Configuration') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Prompt Localizations') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Prompts') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card mb-4" id="section-1">
                        <form class="app-search d-none" action="{{ Request::fullUrl() }}" method="GET">
                            <div class="card-header border-bottom-0">
                                <div class="row justify-content-between g-3">
                                    <div class="col-auto flex-grow-1">
                                        <div class="tt-search-box">
                                            <div class="input-group">
                                                <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i
                                                        data-feather="search"></i></span>
                                                <input class="form-control rounded-start w-100" type="text"
                                                    id="search" name="search" placeholder="{{ localize('Search') }}..."
                                                    @isset($searchKey)
                                        value="{{ $searchKey }}"
                                        @endisset>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary">
                                            <i data-feather="search" width="18"></i>
                                            {{ localize('Search') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <table class="table tt-footable table-hover border-top mb-0" data-use-parent-width="true"
                            id="localization-table">
                            <thead class="py-3">
                                <tr>
                                    <th class="text-center py-3" width="5%">{{ localize('S/L') }}
                                    </th>
                                    <th width="40%" class="py-3">{{ localize('Prompt Key') }}</th>
                                    <th data-breakpoints="xs sm" class="text-end py-3">{{ localize('Localizations') }}</th>
                                </tr>
                            </thead>
                            <tbody>

                                @php
                                    $prompts = [
                                        localize('Write a complete article on this topic', null, false),
                                        localize('Use following keywords in the article', null, false),
                                        localize('Write interesting blog ideas and outline about', null, false),
                                        localize('Generate 10 appropriate blog titles for', null, false),
                                        localize('Write an interesting blog post intro about', null, false),
                                        localize('Blog post title', null, false),
                                        localize('Write an interesting blog conclusion about', null, false),
                                        localize('Write blog tags about', null, false),
                                        localize('Write blog summary about', null, false),
                                        localize('Write a confirmation email about', null, false),
                                        localize('Write a discount email about', null, false),
                                        localize('Write a testimonial email about', null, false),
                                        localize('Write a promotional email about', null, false),
                                        localize('Write a follow up email about', null, false),
                                        localize('Write a follow up email about', null, false),
                                        localize('Write a catchy promotional article to give discount about', null, false),
                                        localize('Title of the promotion is', null, false),
                                        localize('Write bio for social media using following keywords', null, false),
                                        localize('Write a Facebook Ads description that makes your ad stand out and generates leads. Target audience', null, false),
                                        localize('Product name', null, false),
                                        localize('Product description', null, false),
                                        localize('Grab attention with catchy captions for this Instagram post', null, false),
                                        localize('Write a complete social media post about', null, false),
                                        localize('Write a catchy promotional article for this event about', null, false),
                                        localize('Event title', null, false),
                                        localize('Write catchy 30-character headlines to promote your product with Google Ads. Target audience', null, false),
                                        localize('Write a Google Ads description that makes your ad stand out and generates leads. Target audience', null, false),
                                        localize('Write compelling YouTube video title for the provided video description to get people interested in watching', null, false),
                                        localize('Generate SEO-optimized YouTube tags and keywords for', null, false),
                                        localize('Generate list of 10 frequently asked questions based on description', null, false),
                                        localize('Write answer for this faq question', null, false),
                                        localize('Write review to submit on a website based on description', null, false),
                                        localize('Write title for a website based on description', null, false),
                                        localize('Write meta tags for a website based on description', null, false),
                                        localize('Write seo friendly meta description for a website based on description', null, false),
                                        localize('Generate about us content for a website based on description', null, false),
                                        localize('Write a complete article on this topic', null, false),
                                        localize('Write paragraph on this topic', null, false),
                                        localize('Rewrite this content', null, false),
                                        localize('Focus on the following keywords while generating the content', null, false),
                                        localize('Write a long creative product description for', null, false),
                                        localize('Create creative product names based on the description', null, false),
                                        localize('Summarize this text in a short concise way', null, false),
                                        localize('Check and correct grammar of this text', null, false),
                                        localize('Generate an interesting creative story based on the description', null, false),
                                        localize('Generate start up names based on the description', null, false),
                                        localize('Write pros and cons of the topic', null, false),
                                        localize('Write job description based on the requirements', null, false),
                                        localize('Job position', null, false),
                                        localize('Write a rejection letter about', null, false),
                                        localize('Recipient name', null, false),
                                        localize('Write an offer letter about', null, false),
                                        localize('Write a promotion letter.', null, false),
                                        localize('Previous position', null, false),
                                        localize('New position', null, false),
                                        localize('Write inspiring motivational quotes to overcome the given situations', null, false),
                                        localize('Write full song lyrics of', null, false),
                                        localize('Write a creative short story based on the description', null, false),
                                        localize('Write lovely wedding quotes based on the keywords', null, false),
                                        localize('Write birthday wish quotes based on the keywords', null, false),
                                        localize('Write the outline of the story for medium.com based on the description', null, false),
                                        localize('Write the title & subtitle of the story for medium.com based on the description', null, false),
                                        localize('Write interesting story ideas for medium.com based on the keywords', null, false),
                                        localize('Write interesting tiktok video script based on the keywords', null, false),
                                        localize('Grab attention with catchy captions for this tiktok video', null, false),
                                        localize('Write interesting video ideas based on the keywords', null, false),
                                        localize('Write interesting instagram story ideas based on the description', null, false),
                                        localize('Write interesting instagram post ideas based on the description', null, false),
                                        localize('Write interesting instagram reel ideas based on the description', null, false),
                                        localize('Generate hashtags for instagram based on the description', null, false),
                                        localize('Write success story of career based on the description', null, false),
                                        localize('Write success story of business based on the description', null, false),
                                        localize('Write success story of start up based on the description', null, false),
                                        localize('Write success story for matrimonial website based on the description', null, false),
                                        localize('Partners name', null, false),
                                        localize('Output result should sound like', null, false),
                                        localize('Generate terms and conditions for this website', null, false),
                                        localize('Generate privacy policy for this website', null, false),
                                        localize('Generate vision for this company named', null, false),
                                        localize('Company details', null, false),
                                        localize('Generate mission for this company named', null, false),
                                        localize('Write a tweet to post in twitter about', null, false),
                                        localize('Write an academic essay for the title or topic', null, false),
                                    ];
                                @endphp

                                @foreach ($prompts as $key => $prompt)
                                    <tr>
                                        <td class="text-center align-middle">
                                            {{ $key + 1 }}.
                                        </td>

                                        <td class="align-middle w-75">
                                            <a class="d-flex align-items-center">
                                                <h6 class="fs-sm mb-0 key prompt-string">
                                                    {{ $prompt }}
                                                </h6>
                                            </a>
                                        </td>

                                        <td class="align-middle text-end">
                                            @if (count($languages) > 0)
                                                <ul class="list-unstyled mb-0">
                                                    @foreach ($languages as $language)
                                                        <li class="list-inline-item tt-curency-lang-dropdown">
                                                            <a href="javascript:void(0);" class="nav-link"
                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                data-bs-title="{{ $language->name }}"
                                                                onclick="showPromptsModal('{{ $prompt }}', '{{ $language->code }}')">
                                                                <img src="{{ staticAsset('backend/assets/img/flags/' . $language->flag . '.png') }}"
                                                                    alt="country" class="img-fluid me-1 rounded">
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <small class="fw-bold text-uppercase">
                                                    {{ localize('N/A') }}
                                                </small>
                                            @endif
                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('modals')
    <!-- Vertically centered scrollable modal -->
    <div class="modal fade" id="promptModal" data-bs-keyboard="false" tabindex="-1" aria-labelledby="promptModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form class="form-horizontal update-template-prompt" action="{{ route('templates.prompts.store') }}"
                    method="POST">
                    @csrf
                    <div class="prompt-modal-content">
                        <!--append via ajax response-->
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        "use strict";

        // show prompt modal
        function showPromptsModal(prompt, lang_key) {
            $('.prompt-modal-content').empty();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('templates.prompts.show') }}',
                data: {
                    prompt,
                    lang_key
                },
                success: function(data) {
                    $('.prompt-modal-content').append(data.prompt);
                    $('#promptModal').modal('show');
                },
                error: function(data) {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            });
        }

        // update prompt localizations
        $('.update-template-prompt').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);

            $('.update-prompt-submit-btn').prop('disabled', true);
            $.ajax({
                method: 'POST',
                url: '{{ route('templates.prompts.store') }}',
                data: form.serialize(),
                success: function(data) {
                    $('.update-prompt-submit-btn').prop('disabled', false);
                    notifyMe('success', '{{ localize('Prompt updated successfully') }}');
                },
                error: function(data) {
                    $('.update-prompt-submit-btn').prop('disabled', false);
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            });
        })
    </script>
@endsection
