<?php

namespace App\Http\Controllers\Backend\Appearance;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class FooterController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:footer'])->only('index');
    }

    # website footer configuration
    public function index(Request $request)
    {
        $pages = Page::latest()->get();
        $lang_key = $request->lang_key ?? env('DEFAULT_LANGUAGE');
        return view('backend.pages.appearance.footer', compact('pages', 'lang_key'));
    }
}
