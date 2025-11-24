@php

    $templates = \App\Models\Template::orderBy('total_words_generated', 'DESC')->take(12);
    $templates = $templates->isActive()->get();
    $favoritesArray = [];
    $subscriptionTemplates = [];

    if (Auth::check() && Auth::user()->user_type == 'customer') {
        $package = Auth::user()->subscriptionPackage;
        // subscription package template based on template
        if ($package) {
            $subscriptionTemplates = \App\Models\SubscriptionPackageTemplate::where(
                'subscription_package_id',
                $package->id,
            )
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
<section class="ai-template-section ">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center">
                    <span
                        class="rounded-1 bg-primary-key bg-opacity-2 clr-white fs-12 fw-bold px-4 py-2 d-inline-block mb-4 fadeIn_bottom">{{localize('Templated')}}</span>
                    <h3 class="clr-neutral-90 fw-bold animate-line-3d">{{localize('AI Templates')}}</h3>
                </div>
            </div>
        </div>
        <div class="mt-6">
            <div class="row">
                <div class="col-lg-12">
                    <ul class="nav nav-tabs ai-temaplate-tabs justify-content-center gap-3 gap-xl-4 rounded-1 border-0"
                        id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active clr-neutral-90" id="ai-template-all-tab" data-bs-toggle="tab"
                                data-bs-target="#ai-template-all-tab-pane" type="button" role="tab"
                                aria-controls="ai-template-all-tab-pane" aria-selected="true"><i
                                    class="bi bi-bookmarks-fill"></i>
                                {{localize('All')}}</button>
                        </li>

                         @foreach ($templateCategories as $templateCategory)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link clr-neutral-90" id="ai-template-default-{{$templateCategory->slug}}-tab" data-bs-toggle="tab"
                                data-bs-target="#ai-template-default-{{$templateCategory->slug}}-tab-pane" type="button" role="tab"
                                aria-controls="ai-template-default-{{$templateCategory->slug}}-tab-pane" aria-selected="false"><i
                                    class="bi bi-newspaper"></i>
                                    {{ $templateCategory->name }}</button>
                        </li>
                         @endforeach
                        @foreach ($customTemplateCategories as $customTemplateCategory)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link clr-neutral-90" id="ai-template-custom-{{$customTemplateCategory->slug}}-tab" data-bs-toggle="tab"
                                data-bs-target="#ai-template-custom-{{$customTemplateCategory->slug}}-tab-pane" type="button" role="tab"
                                aria-controls="ai-template-custom-{{$customTemplateCategory->slug}}-tab-pane" aria-selected="false"><i class="bi bi-cart2"></i>
                                {{ $customTemplateCategory->name }}</button>
                        </li>
                        @endforeach
                    </ul>
                    <div class="tab-content mb-10 mt-6" id="myTabContent">
                        <div class="tab-pane fade show active" id="ai-template-all-tab-pane" role="tabpanel"
                            aria-labelledby="ai-template-all-tab" tabindex="0">
                            <div id="ai-template-card-wrapper">
                                <div class="row gy-4">

                                    @foreach ($templates as $allTemplate)
                                        @include('frontend.theme1.pages.inc.template-cards', ['template'=>$allTemplate, 'favoritesArray'=>$favoritesArray])
                                    @endforeach
                                    
                                </div>
                               
                                <div class="d-flex flex-wrap gap-6 justify-content-center align-items-center position-relative z-index-1 fadeIn_bottom my-5">
                                    <a href="{{ route('templates.index') }}" target="_blank" class="link d-inline-flex justify-content-center align-items-center gap-2 py-4 px-6 border border-primary-key :border-primary-30 bg-primary-key :bg-primary-30 rounded-1 fw-bold clr-white :arrow-btn">
                                        <span>{{localize('See More')}}</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>

                                <div class="ai-template-card-wrapper-overlay"></div>
                            </div>
                        </div>
                        
                        @foreach ($templateCategories as $templateCategoryTab)
                        <div class="tab-pane fade" id="ai-template-default-{{$templateCategoryTab->slug}}-tab-pane" role="tabpanel"
                            aria-labelledby="ai-template-default-{{$templateCategoryTab->slug}}-tab" tabindex="0">
                            <div class="row gy-4">
                                @foreach ($templateCategoryTab->templates as $template)
                                      
                                        @include('frontend.theme1.pages.inc.template-cards', ['template'=>$template, 'favoritesArray'=>$favoritesArray])

                                    @endforeach
                            </div>
                        </div>
                        @endforeach
                        @foreach ($customTemplateCategories as $customTemplateCategoryTab)
                        <div class="tab-pane fade" id="ai-template-custom-{{$customTemplateCategoryTab->slug}}-tab-pane" role="tabpanel"
                            aria-labelledby="ai-template-custom-{{$customTemplateCategoryTab->slug}}-tab" tabindex="0">
                            <div class="row gy-4">
                                @foreach ($customTemplateCategoryTab->customTemplates as $customTemplate)
                                      
                                @include('frontend.theme1.pages.inc.template-cards', ['template'=>$customTemplate, 'favoritesArray'=>$favoritesArray])

                            @endforeach
                                
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>
    <img src="{{asset('public/frontend/theme1/')}}/assets/img/ai-template-shape-left.png" alt="image"
        class="img-fluid ai-template-section-shape ai-template-section-shape-left">
</section>
