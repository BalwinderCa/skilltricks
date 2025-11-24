<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\FeatureCategory;

class RaderComponent extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $featureCategories = FeatureCategory::with('feature_category_detail')->where('is_active', 1)->get();

        return view('components.rader-component', compact('featureCategories'));
    }
}
