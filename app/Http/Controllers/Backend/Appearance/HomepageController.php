<?php

namespace App\Http\Controllers\Backend\Appearance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:homepage'])->only(['hero']);
    }

    # homepage hero configuration
    public function hero(Request $request)
    {
        $data = $this->languageData($request);
        return view('backend.pages.appearance.homepage.hero', $data);
    }

    # homepage trusted by configuration
    public function trustedBy(Request $request)
    {
        $data = $this->languageData($request);
        return view('backend.pages.appearance.homepage.trustedBy', $data);
    }

    # homepage howItWorks
    public function howItWorks(Request $request)
    {
        $data = $this->languageData($request);
        return view('backend.pages.appearance.homepage.howItWorks', $data);
    }

    # homepage featureImages
    public function featureImages(Request $request)
    {
        $data = $this->languageData($request);
        return view('backend.pages.appearance.homepage.featureImages', $data);
    }

    # homepage cta
    public function cta(Request $request)
    {
        $data = $this->languageData($request);
        return view('backend.pages.appearance.homepage.cta', $data);
    }
    
    # homepage social link
    public function socialLink(Request $request)
    {
        $data = $this->languageData($request);
        return view('backend.pages.appearance.homepage.social-link', $data);
    }

    public function featureTools(Request $request)
    {
        $data = $this->languageData($request);
        return view('backend.pages.appearance.homepage.feature-tools', $data);

    }
    public function aiImageGenerator(Request $request)
    {
        $data = $this->languageData($request);
        return view('backend.pages.appearance.homepage.ai-image-generator', $data);
    }
    public function featureIntegration(Request $request)
    {
        $data = $this->languageData($request);
        return view('backend.pages.appearance.homepage.feature-integration', $data);
    }


    private function languageData($request): array
    {
        $data = [];
        $data['lang_key'] =  $request->lang_key ?? env('DEFAULT_LANGUAGE');
        return $data;
    }

}
