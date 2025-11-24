@php
    $user = auth()->user();
    $theme_mode = !empty($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
@endphp
<aside class="tt-sidebar bg-light-subtle {{ Route::is('blog.wizard.create') ? 'collapse' : '' }} " id="sidebar">
    <div class="tt-brand">
        <a href="{{ route('writebot.dashboard') }}" class="tt-brand-link">
            <img src="{{ uploadedAsset(getSetting('favicon')) }}" id="favicon_backend" class="tt-brand-favicon ms-1 d-none"
                alt="favicon" width="30" />

            @if ($theme_mode == 'dark')
                <img src="{{ uploadedAsset(getSetting('admin_panel_logo_dark') ?? getSetting('admin_panel_logo')) }}"
                    class="tt-brand-logo ms-2" alt="logo" />
            @else
                <img src="{{ uploadedAsset(getSetting('admin_panel_logo')) }}" class="tt-brand-logo ms-2"
                    alt="logo" />
            @endif
        </a>
        <a href="javascript:void(0);" class="tt-toggle-sidebar">
            <span><i data-feather="chevron-left"></i></span>
        </a>
    </div>

    <div class="tt-sidebar-nav pb-9 pt-3 d-flex flex-column h-100 justify-content-between">
        <nav class="navbar navbar-vertical navbar-expand-lg" data-simplebar>
            <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
                <div class="w-100" id="leftside-menu-container">
                    @if (isCustomer())
                        @include('backend.inc.userSidebarMenus')
                    @else
                        @include('backend.inc.sidebarMenus')
                    @endif
                </div>
            </div>
        </nav>


        <ul class="tt-side-nav m-3 tt-user-side-nav ">


            <!-- customer profile with affiliate link button -->
            @if (isCustomer())            
            {!! adSense_sidebar_customer_profile_top_section() !!}
                <li class="side-nav-item nav-item tt-sidebar-user rounded-3 bg-secondary-subtle">
                    <div class="side-nav-link flex-column justify-content-center py-4">
                        <div class="tt-user-avatar lh-1 mb-2">
                            <div class="avatar avatar-md status-online">
                                <img class="rounded-circle" src="{{ uploadedAsset($user->avatar) }}" alt=""
                                    onerror="this.onerror=null;this.src='{{ staticAsset('/backend/assets/img/avatar/1.jpg') }}';">
                            </div>
                        </div>
                        <div class="tt-nav-link-text text-center">
                            <h6 class="mb-0 lh-1 d-block">{{ $user->name }}</h6>
                            <span class="text-muted fs-sm">{{ optional(activePackageHistory())->title }}</span>

                            @if (getSetting('enable_affiliate_system') == '1')
                                <div class="mt-3">
                                    <p class="text-muted fs-md">
                                        💰 {{ localize('Invite your friends and earn money from their subscriptions') }}
                                    </p>
                                    <a href="{{ route('affiliate.overview') }}"
                                        class="btn btn-accent btn-sm rounded-pill shadow-sm">{{ localize('Invite Friends') }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </li>
            @else
                <!-- logout button for admin -->
                <li class="side-nav-item nav-item">
                    <a href="{{ route('logout') }}"
                        class="side-nav-link justify-content-center btn btn-accent rounded-pill text-center">
                        {{ localize('Logout') }} <i data-feather="log-out" class="icon-14 ms-2"></i>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</aside>
