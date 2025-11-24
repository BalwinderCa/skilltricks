<li>
    <a href="{{ route('admin.appearance.homepage.hero') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.hero']) }}">{{ localize('Hero Section') }}</a>
</li>
@if(getTheme()==appStatic()::theme1)
<li>
    <a href="{{ route('admin.appearance.homepage.feature-category') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.feature-category']) }}">{{ localize('Feature Category') }}</a>
</li>
<li>
    <a href="{{ route('admin.appearance.homepage.feature-category-detail') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.feature-category-detail']) }}">{{ localize('Feature  Category Detail') }}</a>
</li>
<li>
    <a href="{{ route('admin.appearance.homepage.feature-tools') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.feature-tools']) }}">{{ localize('Feature Tools') }}</a>
</li>
<li>
    <a href="{{ route('admin.appearance.homepage.ai-image-generator') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.ai-image-generator']) }}">{{ localize('AI Image Generator') }}</a>
</li>
<li>
    <a href="{{ route('admin.appearance.homepage.feature-images') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.feature-images']) }}">{{ localize('Image Section') }}</a>
</li>

<li>
    <a href="{{ route('admin.appearance.homepage.feature-integration') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.feature-integration']) }}">{{ localize('Feature  Integration') }}</a>
</li>
@endif
@if(getTheme()==appStatic()::defaultTheme)
<li>
    <a href="{{ route('admin.appearance.homepage.trustedBy') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.trustedBy']) }}">{{ localize('Trusted By') }}</a>
</li>

<li>
    <a href="{{ route('admin.appearance.homepage.howItWorks') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.howItWorks']) }}">{{ localize('How It Works?') }}</a>
</li>
<li>
    <a href="{{ route('admin.appearance.homepage.featureImages') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.featureImages']) }}">{{ localize('Feature Images') }}</a>
</li>
@endif

<li>
    <a href="{{ route('admin.appearance.homepage.clientFeedback') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.clientFeedback']) }}">{{ localize('Client Feedback') }}</a>
</li>

<li>
    <a href="{{ route('admin.appearance.homepage.cta') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.cta']) }}">{{ localize('CTA Section') }}</a>
</li>
@if(getTheme()==appStatic()::theme1)
<li>
    <a href="{{ route('admin.appearance.homepage.social-link') }}"
        class="{{ areActiveRoutes(['admin.appearance.homepage.social-link']) }}">{{ localize('Social Media Link') }}</a>
</li>
@endif