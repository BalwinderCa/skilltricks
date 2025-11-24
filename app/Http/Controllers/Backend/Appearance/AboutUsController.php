<?php

namespace App\Http\Controllers\Backend\Appearance;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\TemplateGroup;
use Illuminate\Http\Request;

class AboutUsController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:about_us_page'])->only(['index']);
    }

    # website header configuration
    public function index(Request $request)
    {
        $data = [];
        $data['pages'] = Page::latest()->get();
        $data['templateGroups'] = TemplateGroup::oldest()->get();
        $data += $this->languageData($request);
        return view('backend.pages.appearance.aboutUs', $data);
    }
    private function languageData($request): array
    {
        $data = [];
        $data['lang_key'] =  $request->lang_key ?? env('DEFAULT_LANGUAGE');
        return $data;
    }
}
