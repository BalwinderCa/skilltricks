@php
    
    $templates = \App\Models\Template::orderBy('total_words_generated', 'DESC')->take(8);
    $templates = $templates->isActive()->get();
    $favoritesArray = [];
    $subscriptionTemplates = [];
    
    if (Auth::check() && Auth::user()->user_type == 'customer') {
        $package = Auth::user()->subscriptionPackage;
        // subscription package template based on template
        if($package) {
            $subscriptionTemplates = \App\Models\SubscriptionPackageTemplate::where('subscription_package_id', $package->id)
            ->pluck('template_id')
            ->toArray();
        }
     
    }
    
    if (Auth::check()) {
        $favoritesArray = \App\Models\FavoriteTemplate::where('user_id', auth()->user()->id)
            ->select('template_id')
            ->distinct()
            ->pluck('template_id')
            ->toArray();
    }
    
@endphp

<section class="pb-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="tt-section-heading text-center mb-5">
                    <h2 class="fw-bold fs-1">{{ localize('We Help You') }}
                        <div class="tt-text-gradient-primary">{{ localize('To Write Better Contents Faster') }}</div>
                    </h2>
                </div>
            </div>
        </div>
        <div class="row g-3">
            @foreach ($templates as $template)
                <div class="col-lg-3 col-sm-6">
                    @include('backend.pages.templates.inc.template-card', [
                        'template' => $template,
                        'subscriptionTemplates' => $subscriptionTemplates,
                        'favoritesArray' => $favoritesArray,
                    ])
                </div>
            @endforeach
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="text-center mt-5">
                    <a href="{{ route('templates.index') }}"
                        class="btn btn-primary">{{ localize('View All Templates') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
