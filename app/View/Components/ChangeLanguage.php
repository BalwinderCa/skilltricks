<?php

namespace App\View\Components;

use App\Models\Language;
use Illuminate\View\Component;

class ChangeLanguage extends Component
{
    private $lang_key;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($langkey)
    {
        $this->lang_key = $langkey;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $data['lang_key'] = $this->lang_key;
        $data['languages'] = Language::isActive()->get();
        return view('components.change-language', $data);
    }
}
