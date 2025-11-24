<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\Tag;
use App\Models\Blog;
use App\Models\Page;
use App\Models\BlogTag;
use App\Models\Project;
use App\Models\AiBlogWizard;
use App\Models\BlogCategory;
use App\Models\TemplateGroup;
use App\Models\AiChatCategory;
use App\Models\PaymentGateway;
use App\Models\FeatureCategory;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\App;
use App\Models\OfflinePaymentMethod;
use App\Models\FeatureCategoryDetail;
use App\Models\CustomTemplateCategory;

class WriteBotService
{

    public function getFaqs()
    {

        return Faq::query()->latest()->get();
    }


    public function getSubscriptionPackages($isActive = null)
    {
        $query = SubscriptionPackage::query()->with('subscription_package_templates');

        (!empty($isActive) ? $query->isActive() : null);

        return $query->get();
    }


    public function getOfflinePaymentMethods($isActive = 1)
    {
        return OfflinePaymentMethod::query()->where('is_active', $isActive)->get();
    }

    public function getPaymentGateways($isActive = 1)
    {
        return  PaymentGateway::where('is_active', $isActive)->get(['gateway', 'id', 'image']);
    }

    public function getAllBlogs($isActive = true, $paginateOrGet = true)
    {
        $query = Blog::query()->latest()->filters();

        ($isActive ? $query->isActive() : null);

        return $paginateOrGet ? $query->paginate(paginationNumber()) : $query->get();
    }

    public function getBlogCategories()
    {
        return BlogCategory::query()->get();
    }

    public function getTags()
    {
        return Tag::query()->latest()->get();
    }


    public function publishToBlog(object $aiBlogWizard, array $payloads)
    {

        $blog_category_id = $payloads["blog_category_id"];

        $aiBlogWizardArticle = $aiBlogWizard->aiBlogWizardArticle;

        $data = [
            "blog_category_id"  => $blog_category_id,
            "title"             => $aiBlogWizardArticle->title,
            "slug"              => convertToSlug($aiBlogWizardArticle->title),
            "short_description" => $aiBlogWizardArticle->outlines,
            "description"       => $aiBlogWizardArticle->value,
            "thumbnail_image"   => !empty($aiBlogWizardArticle->image) ? $aiBlogWizardArticle->image :  null,
            "is_active"         => 1,
            "is_wizard_blog"    => 1
        ];

        $blog = Blog::query()->create($data);

        // Blog Tags
        foreach ($payloads["tag_id"] as $tag_id) {
            BlogTag::query()->create([
                "blog_id" => $blog->id,
                "tag_id"  => $tag_id
            ]);
        }

        return $blog;
    }

    public function getAiBlogWizardById($id)
    {
        return AiBlogWizard::query()->with([
            "aiBlogWizardArticle",
            "aiBlogWizardKeyword",
        ])
            ->where('user_id', userId())
            ->latest()
            ->findOrFail($id);
    }


    public function getAiChatCategories(
        $isPluckOrGetData = null,
        $onlyActive = null,
        $conditions = []
    ) {
        $query = AiChatCategory::query()->latest();

        if (!empty($onlyActive)) {
            $query->where("is_active", $onlyActive);
        }
        if (!empty($conditions)) {
            $query->where($conditions);
        }
        if (is_null($isPluckOrGetData)) {
            return $query->pluck("id"); // Later pass title
        }

        return $isPluckOrGetData ? $query->get() : $query->get();
    }
    public function templateCategories()
    {
        return TemplateGroup::with('templates')->get();
    }
    public function customTemplateCategories()
    {
        return CustomTemplateCategory::with('customTemplates')->where('created_by', 'admin')->get();
    }
    public function clientFeedback()
    {
        $client_feedback = [];
        if (getSetting('client_feedback') != null) {
            $client_feedback = json_decode(getSetting('client_feedback'));
            $lang = App::getLocale();
            $generalSetupLocalization = \App\Models\GeneralSetupLocalization::where('lang_key', $lang)
                ->where('entity', 'client_feedback')
                ->first();
            if ($generalSetupLocalization) {
                $client_feedback = json_decode($generalSetupLocalization->value);
            }
        }
        return $client_feedback;
    }
    public function getClientFeedback($odd = true)
    {
        $oddFeedback  = array();
        $evenFeedback = array();
        foreach ($this->clientFeedback() as $k => $v) {
            if ($k % 2 == 0) {
                $evenFeedback[] = $v;
            } else {
                $oddFeedback[] = $v;
            }
        }
        if($odd){
            return $oddFeedback;
        }else{
            return $evenFeedback;
        }
    }
    public function terms()
    {
        return $page = Page::where('slug', 'terms-conditions')->first();
    }
    public function privacy()
    {
       return $page = Page::where('slug', 'privacy-policy')->first();
    }
    public function publishedImages()
    {
        return Project::where('is_published', 1)->where('content_type', 'image')->get();
    }
    public function randomPublishedImages()
    {
        return Project::where('is_published', 1)->where('content_type', 'image')->inRandomOrder()->get();
    }
    public function featureCategories()
    {
        return FeatureCategory::with('feature_category_detail')->where('is_active', 1)->get();
    }
    public function featureCategoryDetails()
    {
        return FeatureCategoryDetail::with('category')->where('is_active', 1)->get();
    }
}
