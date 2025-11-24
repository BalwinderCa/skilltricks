<?php

namespace App\View\Components;

use Illuminate\View\Component;

class PackageFeature extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    protected $status;
    protected $title;
    public function __construct($status, $title)
    {
        $this->status = $status;
        $this->title  = $title;
    }
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $status = $this->status;
        $title  = $this->title;
        return view('components.package-feature', compact('title', 'status'));
    }
}
