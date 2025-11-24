<?php

namespace App\View\Components;

use Illuminate\View\Component;

class PaginationComponent extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    private $list;
    public function __construct($list)
    {
        $this->list = $list;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $data['list'] = $this->list;
        return view('components.pagination-component', $data);
    }
}
