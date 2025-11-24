@if (getSetting('google_login') == '1' || getSetting('facebook_login') == '1')

    <div class="action-btns">
        @if (getSetting('google_login') == '1')
            <a href="{{ route('social.login', ['provider' => 'google']) }}"
                class="btn google-btn bg-white shadow-sm mt-4 d-block d-flex align-items-center text-decoration-none justify-content-center">
                <img src="{{ staticAsset('frontend/default/assets/img/website/google-icon.svg') }}" alt="google"
                    class="me-3">
                <span>{{ localize('Connect With Google') }}</span>
            </a>
        @endif

        @if (getSetting('facebook_login') == '1')
            <a href="{{ route('social.login', ['provider' => 'facebook']) }}"
                class="btn google-btn bg-white shadow-sm mt-2 d-block d-flex align-items-center text-decoration-none justify-content-center">
                <img src="{{ staticAsset('frontend/default/assets/img/website/facebook-icon.svg') }}" alt="facebook"
                    class="me-3">
                <span>{{ localize('Connect With Facebook') }}</span>
            </a>
        @endif
    </div>

    {{--<!-- <div class="position-relative d-flex align-items-center justify-content-center mt-4 py-4">
        <span class="divider-bar"></span>
        <h6 class="position-absolute text-center divider-text bg-secondary-subtle px-3 mb-0">
            {{ localize('or Continue With') }}</h6>
    </div> -->--}}
@endif
