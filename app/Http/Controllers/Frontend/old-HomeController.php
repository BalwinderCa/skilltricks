<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Faq;
use App\Models\Blog;
use App\Models\Page;
use App\Services\WriteBotService;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Models\GeneralSetupLocalization;
use App\Models\OfflinePaymentMethod;

class HomeController extends Controller
{
    # set theme
    public function theme($name = "")
    {
        session(['theme' => $name]);
        return redirect()->route('home');
    }

    # homepage
    public function index(WriteBotService $writeBotService)
    {
        $data['packages']                 = $writeBotService->getSubscriptionPackages(true);
        $data['offlinePaymentMethods']    = $writeBotService->getOfflinePaymentMethods();
        $data['payments']                 = $writeBotService->getPaymentGateways();
        $data['templateCategories']       = $writeBotService->templateCategories();
        $data['customTemplateCategories'] = $writeBotService->customTemplateCategories();
        $data['clientFeedback']           = $writeBotService->clientFeedback();
        $data['oddNumberClientFeedback']  = $writeBotService->getClientFeedback(true);
        $data['evenNumberClientFeedback'] = $writeBotService->getClientFeedback(false);
        $data['clientFeedback']           = $writeBotService->clientFeedback();
        $data['projectImages']            = $writeBotService->publishedImages();
        $data['randomPublishedImages']    = $writeBotService->randomPublishedImages();
        $data['featureCategories']        = $writeBotService->featureCategories();
        $data['featureCategoryDetails']   = $writeBotService->featureCategoryDetails();
        $date['faqs']                     = $writeBotService->getFaqs();
        return getView('pages.home', $data);
    }

    # pricing
    public function pricing(WriteBotService $writeBotService)
    {
        $packages              = $writeBotService->getSubscriptionPackages(true);
        $offlinePaymentMethods = $writeBotService->getOfflinePaymentMethods();
        $payments              = $writeBotService->getPaymentGateways();
        $faqs                  = $writeBotService->getFaqs();

        return getView('pages.pricing', ['packages' => $packages, 'faqs' => $faqs, 'offlinePaymentMethods' => $offlinePaymentMethods, 'payments' => $payments]);
    }

    # pricing
    public function testimonials()
    {
        $client_feedback = [];
        if (getSetting('client_feedback') != null) {
            $client_feedback = json_decode(getSetting('client_feedback'));
            $lang = App::getLocale();
            $generalSetupLocalization = GeneralSetupLocalization::where('lang_key', $lang)->where('entity', 'client_feedback')->first();
            if ($generalSetupLocalization) {
                $client_feedback = json_decode($generalSetupLocalization->value);
            }
        }
        return getView('pages.testimonials', ['client_feedback' => $client_feedback]);
    }

    # all blogs
    public function allBlogs(Request $request, WriteBotService $writeBotService)
    {
        $searchKey  = null;
        $blogs = $writeBotService->getAllBlogs();

        if ($request->search != null) {
            $searchKey = $request->search;
        }

        return getView('pages.blogs.index', ['blogs' => $blogs, 'searchKey' => $searchKey]);
    }

    # blog details
    public function showBlog($slug)
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();

        return getView('pages.blogs.blogDetails', ['blog' => $blog]);
    }

    # about us page
    public function aboutUs()
    {
        $features = [];

        if (getSetting('about_us_features') != null) {
            $features = json_decode(getSetting('about_us_features'));
        }

        $why_choose_us = [];

        if (getSetting('about_us_why_choose_us') != null) {
            $why_choose_us = json_decode(getSetting('about_us_why_choose_us'));
        }

        return getView('pages.quickLinks.aboutUs', ['features' => $features, 'why_choose_us' => $why_choose_us]);
    }

    # contact us page
    public function contactUs()
    {
        if(getSetting('is_hide_contact_us')) return abort(404);
        return getView('pages.quickLinks.contactUs');
    }

    # quick link / dynamic pages
    public function showPage($slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        return getView('pages.quickLinks.index', ['page' => $page]);
    }

    public function faq(WriteBotService $writeBotService)
    {
        $data["faqs"] = $writeBotService->getFaqs();


        return getView("pages.faq", $data);
    }

    public function privacyPolicy(WriteBotService $writeBotService)
    {
        $page = $writeBotService->privacy();
        return getView('pages.quickLinks.privacy-policy', ['page' => $page]);
    }

    public function termsCondition(WriteBotService $writeBotService)
    {
        $page = $writeBotService->terms();
        return getView('pages.quickLinks.terms-of-conditions', ['page' => $page]);
    }
   

}
