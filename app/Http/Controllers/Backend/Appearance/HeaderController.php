<?php

namespace App\Http\Controllers\Backend\Appearance;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\TemplateGroup;
use Illuminate\Http\Request;

class HeaderController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:header'])->only('index');
    }

    # website header configuration
    public function index()
    {
        $pages = Page::latest()->get();
        $templateGroups = TemplateGroup::oldest()->get();
        return view('backend.pages.appearance.header', compact('pages', 'templateGroups'));
    }
}
