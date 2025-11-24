<?php

namespace App\View\Components;

use Illuminate\View\Component;

class CheckUncheck extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    protected $status;
    public function __construct($status)
    {
        //
        $this->status = $status;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $status = $this->status;
        return view('components.check-uncheck', compact('status'));
    }
}
