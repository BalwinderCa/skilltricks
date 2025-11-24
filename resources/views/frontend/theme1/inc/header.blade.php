<header class="header header--7 header--fixed py-lg-4">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <nav class="menu d-lg-flex justify-content-lg-between align-items-lg-center py-3 py-lg-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <a href="{{ route('home') }}" class="logo link d-inline-flex align-items-center flex-shrink-0">
                            <img src="{{ uploadedAsset(getSetting('navbar_logo_white')) }}" alt="logo"
                                class="img-fluid object-fit-contain">
                        </a>

                        <button
                            class="menu-toggle w-10 h-10 p-0 border-0 lh-1 bg-primary-50 clr-neutral-100 :clr-neutral-100 transition :bg-primary-300 rounded flex-shrink-0 d-lg-none order-sm-3 fs-24">
                            <i class="bi bi-list"></i>
                        </button>

                    </div>
                    <div
                        class="menu-nav d-flex align-items-lg-center flex-column flex-lg-row flex-grow-1 gap-4 pb-4 pb-lg-0 rounded">
                        <ul class="list list-lg-row mx-lg-auto rounded-pill align-items-center">
                            <li class="menu-list">
                                <a href="{{ route('home') }}" class="link menu-link  menu-link-active">
                                    {{ localize('Home') }} </a>

                            </li>

                            <li class="menu-list">
                                <a href="{{ route('home.pricing') }}" class="link menu-link">
                                    {{ localize('Pricing') }}
                                </a>
                            </li>
                            <li class="menu-list">
                                <a href="{{ route('home.blogs') }}" class="link menu-link"> {{ localize('Blog') }}
                                </a>

                            </li>
                            <li class="menu-list">
                                <a href="#" class="link menu-link has-sub"> {{ localize('Pages') }} </a>
                                <ul class="list menu-sub">
                                    <li class="menu-sub-list">
                                        <a href="{{ route('home.pages.aboutUs') }}" class="link menu-sub-link">
                                            {{ localize('About Us') }} </a>
                                    </li>
                                    @if (!getSetting('is_hide_contact_us'))
                                        <li class="menu-sub-list">
                                            <a href="{{ route('home.pages.contactUs') }}" class="link menu-sub-link">
                                                {{ localize('Contact Us') }} </a>
                                        </li>
                                    @endif
                                    <li class="menu-sub-list">
                                        <a href="{{ route('home.faq') }}" class="link menu-sub-link">
                                            {{ localize('F.A.Q') }} </a>
                                    </li>
                                    @php
                                        $pages = [];
                                        if (getSetting('navbar_pages') != null) {
                                            $pages = \App\Models\Page::whereIn(
                                                'id',
                                                json_decode(getSetting('navbar_pages')),
                                            )->get();
                                        }
                                    @endphp

                                    @foreach ($pages as $navbarPage)
                                        <li class="menu-sub-list">
                                            <a href="{{ route('home.pages.show', $navbarPage->slug) }}"
                                                class="link menu-sub-link">{{ $navbarPage->collectLocalization('title') }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                            @php
                                if (Session::has('locale')) {
                                    $locale = Session::get('locale', Config::get('app.locale'));
                                } else {
                                    $locale = env('DEFAULT_LANGUAGE');
                                }
                                $currentLanguage = \App\Models\Language::where('code', $locale)->first();

                                if ($currentLanguage == null) {
                                    $currentLanguage = \App\Models\Language::where('code', 'en')->first();
                                }
                            @endphp
                            <li class="menu-list">

                                <a href="#" class="nav-link ps-1 ps-md-3 link menu-link has-sub"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <img src="{{ staticAsset('backend/assets/img/flags/' . $currentLanguage->flag . '.png') }}"
                                        alt="" class="img-fluid">
                                </a>
                                <ul class="list menu-sub">
                                    @foreach (\App\Models\Language::where('is_active', 1)->get() as $key => $language)
                                        <li class="menu-sub-list">
                                            <a class="link menu-sub-link" href="javascript:void(0);"
                                                onclick="changeLocaleLanguage(this)" data-flag="{{ $language->code }}">
                                                <img src="{{ staticAsset('backend/assets/img/flags/' . $language->flag . '.png') }}"
                                                    alt="country" class="img-fluid me-1"> {{ $language->name }} </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                            @php
                                if (Session::has('currency_code')) {
                                    $currency_code = Session::get('currency_code', Config::get('app.currency_code'));
                                } else {
                                    $currency_code = env('DEFAULT_CURRENCY');
                                }
                                $currentCurrency = \App\Models\Currency::where('code', $currency_code)->first();

                                if ($currentCurrency == null) {
                                    $currentCurrency = \App\Models\Currency::where('code', 'usd')->first();
                                }
                            @endphp
                            <li class="menu-list">
                                <a href="#" class="nav-link ps-2 ps-md-3 text-uppercase link menu-link has-sub"
                                    data-bs-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">{{ $currentCurrency->symbol }}
                                    {{ $currentCurrency->code }}</a>
                                <ul class="list menu-sub">
                                    @foreach (\App\Models\Currency::where('is_active', 1)->get() as $key => $currency)
                                        <li class="menu-sub-list">
                                            <a class="link menu-sub-link fs-xs text-uppercase"
                                                href="javascript:void(0);" onclick="changeLocaleCurrency(this)"
                                                data-currency="{{ $currency->code }}">
                                                {{ $currency->symbol }} {{ $currency->code }}
                                            </a>
                                        </li>
                                    @endforeach

                                </ul>
                            </li>
                        </ul>
                        <ul class="list list-lg-row gap-4 gap-lg-6 rounded-pill py-lg-2 px-lg-3">
                            @auth
                                <li class="menu-list mx-4 mx-lg-0">
                                    <a href="{{ route('writebot.dashboard') }}"
                                        class="link d-inline-flex justify-content-center py-2 px-4 border border-neutral-30 :border-primary-30 :bg-primary-30
                                        rounded-pill fs-14 fw-bold text-center clr-white">{{ localize('Dashboard') }}
                                    </a>
                                </li>
                            @endauth
                            @guest
                                <li class="menu-list mx-4 mx-lg-0">
                                    <a href="{{ route('login') }}"
                                        class="link d-inline-flex justify-content-center py-2 px-4 border border-neutral-30 :border-primary-30 :bg-primary-30
                                     rounded-pill fs-14 fw-bold text-center clr-white">
                                        {{ localize('Login') }} </a>
                                </li>
                                <li class="menu-list mx-4 mx-lg-0">
                                    <a href="{{ route('register') }}"
                                        class="link d-inline-flex justify-content-center align-items-center gap-2 py-2 px-4 border border-primary-key :border-primary-30 bg-primary-key :bg-primary-30 rounded-pill fs-14 fw-bold text-center clr-white">
                                        <span class="d-block">{{ localize('Sign Up') }} </span>
                                        <span class="d-block fs-10">
                                            <i class="bi bi-arrow-up-right"></i>
                                        </span>
                                    </a>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</header>
