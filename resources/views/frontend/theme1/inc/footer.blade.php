<footer class="footer-7">
    <div class="footer-7-wrapper">
        <div class="container">
            <div class="row gap-4 align-items-center justify-content-between">
                <div class="col-xl-5">
                    <ul class="list list-row flex-wrap gap-6 justify-content-center justify-content-xl-start">
                        <li>
                            <a href="{{ getSetting('facebook_link') }}" class="link clr-neutral-80 d-flex gap-2 :clr-fb">
                                <i class="bi bi-facebook"></i> {{ localize('Facebook') }} </a>
                        </li>
                        <li>
                            <a href="{{ getSetting('instagram_link') }}"
                                class="link clr-neutral-80 d-flex gap-2 :clr-ins">
                                <i class="bi bi-instagram"></i> {{ localize('Instagram') }} </a>
                        </li>
                        <li>
                            <a href="{{ getSetting('twitter_link') }}" class="link clr-neutral-80 d-flex gap-2 :clr-tw">
                                <i class="bi bi-twitter"></i> {{ localize('Twitter') }} </a>
                        </li>

                    </ul>
                </div>
                <div class="col-xl-5">
                    <ul class="list list-row flex-wrap gap-6 justify-content-xl-end justify-content-center">
                        <li>
                            <a href="{{ route('home.pages.aboutUs') }}"
                                class="link clr-neutral-80 :clr-primary-key">{{ localize('About Us') }}</a>
                        </li>
                        @if (!getSetting('is_hide_contact_us'))
                            <li>
                                <a href="{{ route('home.pages.contactUs') }}"
                                    class="link clr-neutral-80 :clr-primary-key">{{ localize('Contact Us') }}</a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route('privacy-policy') }}"
                                class="link clr-neutral-80 :clr-primary-key">{{ localize('Privacy Policy') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('terms-of-conditions') }}"
                                class="link clr-neutral-80
                             :clr-primary-key">
                                {{ localize('Terms of Service') }}</a>
                        </li>
                        @php
                            $quick_links =
                                getSetting('quick_links') != null ? json_decode(getSetting('quick_links')) : [];
                            $pages = \App\Models\Page::whereIn('id', $quick_links)->get();
                        @endphp
                        @foreach ($pages as $page)
                            <li>
                                <a href="{{ route('home.pages.show', $page->slug) }}"
                                    class="fs-md">{{ $page->collectLocalization('title') }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-xl-5">
                    <p class="fs-14 fw-semibold mb-0 text-center text-xl-start clr-neutral-80">
                        {!! systemSettingsLocalization('copyright_text') !!}
                    </p>
                </div>
                <div class="col-xl-5">
                    <form action="{{ route('subscribe.store') }}" method="POST">
                        @csrf

                        <div class="input-group input--group input--group-inverse-surface max-text-60 mx-auto">
                            <input type="email" class="form-control form--control" required
                                placeholder="{{ localize('Enter Email Address') }}">
                            <span class="input-group-text">
                                <button type="submit"
                                    class="newsletter-1__btn d-inline-block py-3 px-4 fw-bold clr-white bg-grad-1 fs-14">
                                    {{ localize('Subscribe') }} </button>
                            </span>

                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <div class="radar-logo-wrapper">
        <x-rader-component />
        <div class="logo-wrapper d-flex justify-content-center align-items-center">
            <img src="{{ uploadedAsset(getSetting('favicon')) ?? asset('public/frontend/theme1/assets/img/hero-7-logo.png') }}"
                alt="image" class="img-fluid hero-7-logo">
        </div>
    </div>
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/footer-7-shape-bottom-circle.png" alt="image"
        class="img-fluid object-fit-cover footer-7-shape footer-7-shape-bottom-circle">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/footer-7-shape-left.png" alt="image"
        class="img-fluid footer-7-shape footer-7-shape-left">
    <img src="{{ asset('public/frontend/theme1/') }}/assets/img/footer-7-shape-right.png" alt="image"
        class="img-fluid footer-7-shape footer-7-shape-right">
</footer>
