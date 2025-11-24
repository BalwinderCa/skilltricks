@php
    $user = auth()->user();
@endphp
<header class="tt-top-fixed bg-light-subtle">
    <div class="container-fluid">
        <nav class="navbar navbar-top navbar-expand" id="navbarDefault">
            <div class="collapse navbar-collapse justify-content-between">
                <div class="tt-mobile-toggle-brand d-lg-none d-md-none">
                    <a class="tt-toggle-sidebar pe-3" href="#offcanvasLeft" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasLeft">
                        <i data-feather="menu"></i>
                    </a>
                    <div class="tt-brand pe-3">
                        <a href="{{ route('writebot.dashboard') }}">
                            <img src="{{ uploadedAsset(getSetting('favicon')) }}" class="tt-brand-favicon"
                                alt="favicon" width="30" />
                        </a>
                    </div>
                </div>

                <div class="tt-search-box d-none d-md-block d-lg-block flex-grow-1 me-4" style="opacity: 0;">
                    <form action="">
                        <div class="input-group">
                            <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i
                                    data-feather="search"></i></span>
                            @php
                                $searchKey = request('search');
                            @endphp
                            <input class="form-control rounded-start w-100 border-0 bg-transparent" type="text"
                                name="search"
                                @isset($searchKey)
                                    value="{{ $searchKey }}"
                                @endisset
                                placeholder="{{ localize('Search') }}...">
                        </div>
                    </form>
                </div>
                <ul class="navbar-nav flex-row align-items-center tt-top-navbar">

                    @if (isCustomer())
                        <!-- <li class="nav-item me-2 d-none d-md-block d-lg-block">
                            <a href="{{ route('subscriptions.index') }}"
                                class="btn btn-sm {{ optional(activePackageHistory())->subscription_package_id == null ? 'btn-soft-danger' : 'btn-primary' }} text-capitalize rounded-pill">
                                <i data-feather="zap" class="w-15"></i>
                                @if (optional(activePackageHistory())->subscription_package_id == null)
                                    {{ localize('No active Subscription') }}
                                @else
                                    {!! html_entity_decode(optional(activePackageHistory())->subscriptionPackage->title) !!}/{{ optional(activePackageHistory())->subscriptionPackage->package_type == 'starter' ? localize('Monthly') : optional(activePackageHistory())->subscriptionPackage->package_type }}
                                @endif

                            </a>
                        </li> -->
                    @endif

                    <li class="nav-item">
                        <a href="javascript:void(0)" class="nav-link tt-theme-toggle">
                            <div class="tt-theme-light"><i data-feather="moon" class="fs-xm"></i></div>
                            <div class="tt-theme-dark"><i data-feather="sun" class="fs-xm"></i></div>
                        </a>
                    </li>

                    <x-navbar-notification />


                     <li class="nav-item">
                        <a href="{{ route('home') }}" class="nav-link tt-visit-store" target="_blank">
                            <i data-feather="monitor" class="me-2"></i>
                        </a>
                    </li> 
                   <!--  <li class="nav-item">
                        <a href="https://aquamarine-rail-506472.hostingersite.com/login" class="nav-link tt-visit-store" target="_blank">
                            <i data-feather="monitor" class="me-2"></i>
                        </a>
                    </li> -->


                    @php
                        if (Session::has('locale')) {
                            $locale = Session::get('locale', Config::get('app.locale'));
                        } else {
                            $locale = env('DEFAULT_LANGUAGE');
                        }
                        $currentLanguage = \App\Models\Language::where('code', $locale)->first();

                        if (is_null($currentLanguage)) {
                            $currentLanguage = \App\Models\Language::where('code', 'en')->first();
                        }
                    @endphp

                    {{--<!-- <li class="nav-item dropdown tt-curency-lang-dropdown d-none d-md-block">
                        <a href="javascript:void(0);" class="nav-link" data-bs-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">
                            <img src="{{ staticAsset('backend/assets/img/flags/' . $currentLanguage->flag . '.png') }}"
                                alt="country" class="img-fluid me-1"> {{ $currentLanguage->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end py-0 shadow border-0">
                            @foreach (\App\Models\Language::where('is_active', 1)->get() as $key => $language)
                                <li>
                                    <a href="javascript:void(0);"
                                        class="dropdown-item @if ($currentLanguage->code == $language->code) active @endif"
                                        onclick="changeLocaleLanguage(this)" data-flag="{{ $language->code }}">
                                        <img src="{{ staticAsset('backend/assets/img/flags/' . $language->flag . '.png') }}"
                                            alt="{{ $language->code }}" class="img-fluid me-1"> <span
                                            class="align-middle">{{ $language->name }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li> -->--}}


                    @php
                        if (Session::has('currency_code')) {
                            $currency_code = Session::get('currency_code', Config::get('app.currency_code'));
                        } else {
                            $currency_code = env('DEFAULT_CURRENCY');
                        }
                        $currentCurrency = \App\Models\Currency::where('code', $currency_code)->first();

                        if (is_null($currentCurrency)) {
                            $currentCurrency = \App\Models\Currency::where('code', 'usd')->first();
                        }

                    @endphp

                    {{--<!-- <li class="nav-item dropdown tt-curency-lang-dropdown d-none d-md-block">
                        <a href="#" class="nav-link text-uppercase" data-bs-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">{{ $currentCurrency->symbol }}
                            {{ $currentCurrency->code }}</a>
                        <ul class="dropdown-menu dropdown-menu-end py-0 shadow border-0">

                            @foreach (\App\Models\Currency::where('is_active', 1)->get() as $key => $currency)
                                <li>
                                    <a class="dropdown-item fs-xs text-uppercase" href="javascript:void(0);"
                                        onclick="changeLocaleCurrency(this)" data-currency="{{ $currency->code }}">
                                        {{ $currency->symbol }} {{ $currency->code }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li> -->--}}

                    <li class="nav-item dropdown tt-user-dropdown">
                        <a class="nav-link lh-1 pe-0" id="navbarDropdownUser" href="#!" role="button"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                            aria-expanded="true">
                            <div class="avatar avatar-sm status-online">
                                <img class="rounded-circle" src="{{ uploadedAsset($user->avatar) }}" alt="avatar"
                                    onerror="this.onerror=null;this.src='{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}';">
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end py-0 shadow-sm border-0"
                            aria-labelledby="navbarDropdownUser">
                            <div class="card position-relative border-0">
                                <div class="card-body py-2">
                                    <ul class="tt-user-nav list-unstyled d-flex flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link px-0" href="{{ route('dashboard.profile') }}">
                                                <i data-feather="user" class="me-1 fs-sm"></i>
                                                {{ localize('My Account') }}
                                            </a>
                                        </li>
                                        {{--<!-- <li class="nav-item">
                                            <a class="nav-link px-0" href="{{ route('subscriptions.index') }}">
                                                <i data-feather="zap" class="me-1 fs-sm"></i>
                                                {{ localize('Subscriptions') }}
                                            </a>
                                        </li> -->--}}
                                        <li class="nav-item">
                                            <a class="nav-link px-0" href="{{ route('logout') }}">
                                                <i data-feather="log-out"
                                                    class="me-1 fs-sm"></i>{{ localize('Sign out') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>

                </ul>
            </div>
            {!! adSense_dashboard_header_bottom_section() !!}
        </nav>
        
    </div>
</header>
@php
    $theme_mode = !empty($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
@endphp
<!--mobile offcanvas menu start-->
<div class="offcanvas offcanvas-start tt-aside-navbar" id="offcanvasLeft" tabindex="-1">
    <div class="offcanvas-header border-bottom">
        <div class="tt-brand">
            <a href="{{ route('writebot.dashboard') }}" class="tt-brand-link">
                <img src="{{ uploadedAsset(getSetting('favicon')) }}" id="favicon_backend"
                    class="tt-brand-favicon ms-1 d-none" alt="favicon" width="30" />

                @if ($theme_mode == 'dark')
                    <img src="{{ uploadedAsset(getSetting('admin_panel_logo_dark') ?? getSetting('admin_panel_logo')) }}"
                        class="tt-brand-logo ms-2" alt="logo" />
                @else
                    <img src="{{ uploadedAsset(getSetting('admin_panel_logo')) }}" class="tt-brand-logo ms-2"
                        alt="logo" />
                @endif
            </a>
        </div>
        <button class="btn-close" type="button" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-2 pb-9" data-simplebar>
        <div class="tt-sidebar-nav">
            <nav class="navbar navbar-vertical">
                <div class="w-100">
                    @if (isCustomer())
                        @include('backend.inc.userSidebarMenus')
                    @else
                        @include('backend.inc.sidebarMenus')
                    @endif
                </div>
            </nav>
        </div>
    </div>
</div>
<!--mobile offcanvas menu end-->
