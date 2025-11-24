<?php

namespace App\Http\Controllers\Backend\Subscription;

use App\Http\Controllers\Backend\Payments\Paypal\PaypalController;
use App\Http\Controllers\Controller;
use App\Models\OfflinePaymentMethod;
use App\Models\OpenAiModel;
use App\Models\PaymentGateway;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Str;

class SubscriptionsController extends Controller
{
    # all subscription packages
    public function index(Request $request)
    {

        $user = auth()->user();
        $type = null;

        if (!SubscriptionPackage::exists()) {
            $this->__createStarterPackage();
        }
        $packages       = SubscriptionPackage::query();
        $openAiModels = OpenAiModel::all();
        $payments   = PaymentGateway::where('is_active', 1)->get(['gateway', 'id', 'image']);
        $offlinePaymentMethods = OfflinePaymentMethod::where('is_active', 1)->get();

        if ($user->user_type == "admin" || $user->user_type == "staff") {
            if ($user->can('subscriptions')) {
                if ($request->type != null && $request->type != "monthly") {
                    $type     = $request->type;
                    $packages = $packages->where('package_type', $type);
                } else {
                    $packages = $packages->where('package_type', 'monthly')->orWhere('package_type', 'starter');
                }

                $packages = $packages->oldest()->get();
                return view('backend.pages.subscriptions.index', compact('packages', 'openAiModels', 'type', 'offlinePaymentMethods', 'payments'));
            } else {
                abort(403);
            }
        } else {
            $packages = $packages->isActive()->get();
            return view('backend.pages.subscriptions.user-index', compact('packages', 'openAiModels', 'type', 'offlinePaymentMethods', 'payments'));
        }
    }

    # index History
    public function indexHistory(Request $request)
    {
        $searchKey = null;
        $histories = SubscriptionHistory::with('recurringPayment', 'subscriptionPackage', 'user')->latest()->when(auth()->user()->user_type != 'customer', function ($q) {
            $q->where('payment_status', 1);
        });
        if (isCustomer()) {
            $histories = $histories->where('user_id', auth()->user()->id);
        } else {
            if (!auth()->user()->can('subscriptions_histories')) {
                abort(403);
            }
        }

        if ($request->search != null) {
            $userIds = User::where('name', 'like', '%' . $request->search . '%')->pluck('id');
            $histories = $histories->whereIn('user_id', $userIds);
            $searchKey = $request->search;
        }

        $histories = $histories->paginate(paginationNumber());
        return view('backend.pages.subscriptions.histories', compact('histories', 'searchKey'));
    }

    # get packages on type change
    public function indexTypePackages(Request $request)
    {
        $type = null;
        $packages       = SubscriptionPackage::query();

        if ($request->type != null && $request->type != "monthly") {
            $type = $request->type;
            $packages = $packages->where('package_type', $type);
        } else {
            $packages = $packages->where('package_type', 'monthly')->orWhere('package_type', 'starter');
        }

        $openAiModels = OpenAiModel::orderBy('order', 'asc')->get();

        if (auth()->user()->user_type == "admin" || auth()->user()->user_type == "staff") {
            $packages = $packages->oldest()->get();
            return view('backend.pages.subscriptions.inc.packages-list', compact('packages', 'openAiModels', 'type'))->render();
        } else {
            $packages = $packages->isActive()->oldest()->get();
            return view('backend.pages.subscriptions.inc.packages-list', compact('packages', 'openAiModels', 'type'))->render();
        }
    }

    # get templates
    public function getTemplates(Request $request)
    {
        $package = SubscriptionPackage::findOrFail((int) $request->package_id);
        $packageTemplateIds = $package->subscription_package_templates()->pluck('template_id')->toArray();
        $templates      = Template::query();
        $templateGroups = TemplateGroup::get();
        if (auth()->user()->user_type == "admin" || auth()->user()->user_type == "staff") {
            $templates = $templates->get();
        } else {
            $templates = $templates->isActive()->get();
        }

        return view('backend.pages.subscriptions.inc.packages-listing-contents', compact('package', 'packageTemplateIds', 'templateGroups', 'templates'))->render();
    }

    # get package templates
    public function getPackageTemplates(Request $request)
    {
        $package = SubscriptionPackage::findOrFail((int) $request->package_id);
        $packageTemplateIds = $package->subscription_package_templates()->pluck('template_id')->toArray();

        $templates      = Template::query();

        $templates = $templates->whereIn('id', $packageTemplateIds)->isActive();

        $templateGroupIdsInTemplates = $templates->pluck('template_group_id')->toArray();
        $templateGroups = TemplateGroup::whereIn('id', $templateGroupIdsInTemplates)->get();

        $templates = $templates->get();
        return getView('pages.partials.home.package-templates', compact('templateGroups', 'templates'))->render();
    }

    # update package
    public function update(Request $request)
    {

        $data = [
            'status'        => 200,
            'success'       => true,
            'message'       => ''
        ];

        $package = SubscriptionPackage::findOrFail((int) $request->package_id);

        // title
        if (strpos($request->name, "package-name") !== false) {
            if ($this->__ifValueIsNull($request->value)) {
                $data['status']     = 403;
                $data['success']    = false;
                $data['message']    = localize('Name can not be empty');
                return $data;
            }
            $package->title = $request->value;

        }

        // description
        if (strpos($request->name, "package-description") !== false) {
            $package->description = $request->value;

        }

        // model id
        if (strpos($request->name, "openai_model_id") !== false) {
            $package->openai_model_id = $request->value;
        }

        // price
        if (strpos($request->name, "package-price") !== false) {
            if ($this->__ifValueIsNull($request->value)) {
                $data['status']     = 403;
                $data['success']    = false;
                $data['message']    = localize('Price can not be empty');
                return $data;
            }
            $package->price = (float) $request->value;
            
        }

        // words per month
        if (strpos($request->name, "package-words") !== false) {
            if ($this->__ifValueIsNull($request->value)) {
                $data['status']     = 403;
                $data['success']    = false;
                $data['message']    = localize('Words limit can not be empty');
                return $data;
            }
            $package->total_words_per_month = (int) $request->value;
        }

        // images per month
        if (strpos($request->name, "package-images") !== false) {
            if ($this->__ifValueIsNull($request->value)) {
                $data['status']     = 403;
                $data['success']    = false;
                $data['message']    = localize('Images limit can not be empty');
                return $data;
            }
            $package->total_images_per_month = (int) $request->value;
        }

        // total_ speech_to_text per_month
        if (strpos($request->name, "package-speech-to-text") !== false) {
            if ($this->__ifValueIsNull($request->value)) {
                $data['status']     = 403;
                $data['success']    = false;
                $data['message']    = localize('Speeches limit can not be empty');
                return $data;
            }
            $package->total_speech_to_text_per_month = (int) $request->value;
        }

        // speech_to_text_filesize_limit
        if (strpos($request->name, "package-audio-size") !== false) {
            if ($this->__ifValueIsNull($request->value)) {
                $data['status']     = 403;
                $data['success']    = false;
                $data['message']    = localize('File size limit can not be empty');
                return $data;
            }
            $package->speech_to_text_filesize_limit = (int) $request->value;
        }

        # allow
        // allow_dall_e_2_image

        if (strpos($request->name, "allow_dall_e_2_image") !== false) {

            $package->allow_dall_e_2_image = !((int) $package->allow_dall_e_2_image);
        }
        // allow_dall_e_3_image

        if (strpos($request->name, "allow_dall_e_3_image") !== false) {

            $package->allow_dall_e_3_image = !((int) $package->allow_dall_e_3_image);
        }
        // allow_detector

        if (strpos($request->name, "allow_ai_detector") !== false) {

            $package->allow_ai_detector = !((int) $package->allow_ai_detector);
        }
        // allow_plagiarism

        if (strpos($request->name, "allow_ai_plagiarism") !== false) {

            $package->allow_ai_plagiarism = !((int) $package->allow_ai_plagiarism);
        }

        // allow_ai_code
        if (strpos($request->name, "allow_ai_code") !== false) {
            $package->allow_ai_code = !((int) $package->allow_ai_code);
        }

        // allow_speech_to_text
        if (strpos($request->name, "allow_speech_to_text") !== false) {
            $package->allow_speech_to_text = !((int) $package->allow_speech_to_text);
        }

        // allow_ai_chat
        if (strpos($request->name, "allow_ai_chat") !== false) {
            $package->allow_ai_chat = !((int) $package->allow_ai_chat);
        }
        // allow_ai_chat
        if (strpos($request->name, "allow_ai_rewriter") !== false) {
            $package->allow_ai_rewriter = !((int) $package->allow_ai_rewriter);
        }

        // allow_ai_vision
        if (strpos($request->name, "allow_ai_vision") !== false) {
            $package->allow_ai_vision = !((int) $package->allow_ai_vision);
        }

        // allow_ai_image_chat
        if (strpos($request->name, "allow_ai_image_chat") !== false) {
            $package->allow_ai_image_chat = !((int) $package->allow_ai_image_chat);
        }
        // allow_ai_pdf_chat
        if (strpos($request->name, "allow_ai_pdf_chat") !== false) {
            $package->allow_ai_pdf_chat = !((int) $package->allow_ai_pdf_chat);
        }

        // allow_text_to_speech
        if (strpos($request->name, "allow_text_to_speech") !== false) {
            $package->allow_text_to_speech = !((int) $package->allow_text_to_speech);
        }

        // allow_custom_templates
        if (strpos($request->name, "allow_custom_templates") !== false) {
            $package->allow_custom_templates = !((int) $package->allow_custom_templates);
        }

        // allow_blog_wizard
        if (strpos($request->name, "allow_blog_wizard") !== false) {
            $package->allow_blog_wizard = !((int) $package->allow_blog_wizard);
        }

        // allow_eleven_labs
        if (strpos($request->name, "allow_eleven_labs") !== false) {
            $package->allow_eleven_labs = !((int) $package->allow_eleven_labs);
        }

        // allow_word_tools
        if (strpos($request->name, "allow_word_tools") !== false) {
            $package->allow_word_tools = !((int) $package->allow_word_tools);
        }

        // allow_built_in_templates
        if (strpos($request->name, "allow_built_in_templates") !== false) {
            $package->allow_built_in_templates = !((int) $package->allow_built_in_templates);
        }

        // allow_image_tools
        if (strpos($request->name, "allow_image_tools") !== false) {
            $package->allow_image_tools = !((int) $package->allow_image_tools);
        }

        // allow_sd_images
        if (strpos($request->name, "allow_sd_images") !== false) {
            $package->allow_sd_images = !((int) $package->allow_sd_images);
        }
        // allow_real_time_data
        if (strpos($request->name, "allow_real_time_data") !== false) {
            $package->allow_real_time_data = !((int) $package->allow_real_time_data);
        }

        # show
        // show_open_ai_model
        if (strpos($request->name, "show_open_ai_model") !== false) {
            $package->show_open_ai_model = !((int) $package->show_open_ai_model);
        }

        // show_word_tools
        if (strpos($request->name, "show_word_tools") !== false) {
            $package->show_word_tools = !((int) $package->show_word_tools);
        }

        // show_built_in_templates
        if (strpos($request->name, "show_built_in_templates") !== false) {
            $package->show_built_in_templates = !((int) $package->show_built_in_templates);
        }

        // show_custom_templates
        if (strpos($request->name, "show_custom_templates") !== false) {
            $package->show_custom_templates = !((int) $package->show_custom_templates);
        }

        // show_blog_wizard
        if (strpos($request->name, "show_blog_wizard") !== false) {
            $package->show_blog_wizard = !((int) $package->show_blog_wizard);
        }

        // show_eleven_labs
        if (strpos($request->name, "show_eleven_labs") !== false) {
            $package->show_eleven_labs = !((int) $package->show_eleven_labs);
        }

        // show_ai_chat
        if (strpos($request->name, "show_ai_chat") !== false) {
            $package->show_ai_chat = !((int) $package->show_ai_chat);
        }
        // show_ai_rewriter
        if (strpos($request->name, "show_ai_rewriter") !== false) {
            $package->show_ai_rewriter = !((int) $package->show_ai_rewriter);
        }

        // show_ai_vision
        if (strpos($request->name, "show_ai_vision") !== false) {
            $package->show_ai_vision = !((int) $package->show_ai_vision);
        }

        // show_ai_image_chat
        if (strpos($request->name, "show_ai_image_chat") !== false) {
            $package->show_ai_image_chat = !((int) $package->show_ai_image_chat);
        }

        // show_ai_pdf_chat
        if (strpos($request->name, "show_ai_pdf_chat") !== false) {
            $package->show_ai_pdf_chat = !((int) $package->show_ai_pdf_chat);
        }

        // show_ai_code
        if (strpos($request->name, "show_ai_code") !== false) {
            $package->show_ai_code = !((int) $package->show_ai_code);
        }

        // show_ai_detector
        if (strpos($request->name, "show_ai_detector") !== false) {
            $package->show_ai_detector = !((int) $package->show_ai_detector);
        }

        // show_ai_plagiarism
        if (strpos($request->name, "show_ai_plagiarism") !== false) {
            $package->show_ai_plagiarism = !((int) $package->show_ai_plagiarism);
        }
        // show_real_time_data
        if (strpos($request->name, "show_real_time_data") !== false) {
            $package->show_real_time_data = !((int) $package->show_real_time_data);
        }

        // show_text_to_speech
        if (strpos($request->name, "show_text_to_speech") !== false) {
            $package->show_text_to_speech = !((int) $package->show_text_to_speech);
        }

        // show_image_tools
        if (strpos($request->name, "show_image_tools") !== false) {
            $package->show_image_tools = !((int) $package->show_image_tools);
        }

        // show_dall_e_3_image
        if (strpos($request->name, "show_dall_e_3_image") !== false) {
            $package->show_dall_e_3_image = !((int) $package->show_dall_e_3_image);
        }
        // dall_e_2_image
        if (strpos($request->name, "show_dall_e_2_image") !== false) {
            $package->show_dall_e_2_image = !((int) $package->show_dall_e_2_image);
        }

        // show_sd_images
        if (strpos($request->name, "show_sd_images") !== false) {
            $package->show_sd_images = !((int) $package->show_sd_images);
        }

        // show_speech_to_text_tools
        if (strpos($request->name, "show_speech_to_text_tools") !== false) {

            $package->show_speech_to_text_tools = !((int) $package->show_speech_to_text_tools);
        }

        // show_live_support
        if (strpos($request->name, "show_live_support") !== false) {
            $package->show_live_support = !((int) $package->show_live_support);
        }

        // show_free_support
        if (strpos($request->name, "show_free_support") !== false) {

            $package->show_free_support = !((int) $package->show_free_support);
        }

        # has
        // has_live_support
        if (strpos($request->name, "has_live_support") !== false) {

            $package->has_live_support = !((int) $package->has_live_support);
        }

        // has_free_support
        if (strpos($request->name, "has_free_support") !== false) {
            $package->has_free_support = !((int) $package->has_free_support);
        }

        // is_featured
        if (strpos($request->name, "is_featured") !== false) {
            $package->is_featured = !((int) $package->is_featured);
        }

        // is_active
        if (strpos($request->name, "is_active") !== false) {
            $package->is_active = !((int) $package->is_active);
        }

        # others

        // other_features
        if (strpos($request->name, "other_features") !== false) {
            $package->other_features = $request->value;
        }


        // starter package validate days
        if (strpos($request->name, "duration") !== false) {
            $package->duration = $request->value;
        }


        // discount option

        if (strpos($request->name, "allow_discount") !== false) {
            $package->discount_status = !((int) $package->discount_status);
        }
        if (strpos($request->name, "discount_amount") !== false) {
            $package->discount_type = $request->discount_type;
            $package->discount = $request->value;
            $package->discount_price = self::calculateDiscountPrice($request->discount_type, $request->amount, $request->value);
        }
        // unlimited balance
        // allow_unlimited_speech_to_text
        if (strpos($request->name, "allow_unlimited_speech_to_text") !== false) {

            $package->allow_unlimited_speech_to_text =  $request->value == "true" ? 1: 0;
        }
        // allow_unlimited_image
        if (strpos($request->name, "allow_unlimited_image") !== false) {
            $package->allow_unlimited_image =  $request->value == "true" ? 1: 0;
        }

        // allow_unlimited_word
        if (strpos($request->name, "allow_unlimited_word") !== false) {

            $package->allow_unlimited_word =  $request->value == "true" ? 1: 0;
        }
        $package->save();

        # create product and plan into paypal for recurring payment
        if ((strpos($request->name, "package-description") !== false) || strpos($request->name, "package-name") !== false) {
            try {
                PaypalController::createProduct($package->id);
            } catch (\Throwable $th) {
                //throw $th;
            }

        }
        # price plan change
        if ((strpos($request->name, "allow_discount") !== false) || strpos($request->name, "discount_amount") !== false || strpos($request->name, "package-price") !== false) {
            try {
                $pricePackage = $package->discount_status ? $package->discount_price : $package->price;
                PaypalController::updatePlanPrice($package->id, $pricePackage);
            } catch (\Throwable $th) {
                //throw $th;
            }

        }
        return $data;
    }

    # update package templates
    public function updateTemplates(Request $request)
    {
        $package = SubscriptionPackage::findOrFail((int) $request->package_id);
        $package->subscription_package_templates()->sync($request->templates);
    }

    # get packages to show in copy form
    public function copyPackage()
    {
        $packages = SubscriptionPackage::all();
        return view('backend.pages.subscriptions.inc.new-or-copy', compact('packages'))->render();
    }

    # newPackage
    public function newPackage(Request $request)
    {
        if ($request->package_id) {
            // copy from existing
            $package = SubscriptionPackage::findOrFail((int) $request->package_id);
            $newPackage = $package->replicate();
            $newPackage->package_type = $request->type;
            $newPackage->slug = $package->slug . '-' . strtotime(now());
            $newPackage->save();
            $templateIds = $package->subscription_package_templates()->pluck('template_id')->toArray();
            $newPackage->subscription_package_templates()->sync($templateIds);

            # create product and plan into paypal for recurring payment
            try {
                PaypalController::createProduct($package->id);
            } catch (\Throwable $th) {
                //throw $th;
            }

        } else {
            $package = new SubscriptionPackage;
            $package->title             = "New Package";
            $package->slug              = Str::slug("New Package") . '-' . strtotime(now());
            $package->description       = "Get started with our new package";
            $package->package_type      = $request->type;

            $defaultModelKey            = getSetting('default_open_ai_model');
            if (!is_null($defaultModelKey)) {
                $defaultModel = OpenAiModel::where('key', $defaultModelKey)->first();
                if (empty($defaultModel)) {
                    abort(404);
                }
                $package->openai_model_id   = $defaultModel->id;
            } else {
                $package->openai_model_id   = 5; // gpt 3
            }
            $package->speech_to_text_filesize_limit     = 0; // 0mb
            $package->save();
            # create product and plan into paypal for recurring payment
            try {
                PaypalController::createProduct($package->id);
            } catch (\Throwable $th) {
                //throw $th;
            }

        }

        return [
            'status'    => 200,
            'success'    => true,
        ];
    }

    # check null value
    private function __ifValueIsNull($value)
    {
        if ($value == "" || $value == null) {
            return true;
        }
        return false;
    }

    # create starter package
    private function __createStarterPackage()
    {
        $package = new SubscriptionPackage;
        $package->title             = "Starter";
        $package->slug              = Str::slug("Starter");
        $package->description       = "Get started with our starter package";
        $package->package_type      = "starter";

        $defaultModelKey            = getSetting('default_open_ai_model');
        if (!is_null($defaultModelKey)) {
            $defaultModel = OpenAiModel::where('key', $defaultModelKey)->first();
            if (empty($defaultModel)) {
                abort(404);
            }
            $package->openai_model_id   = $defaultModel->id;
        } else {
            $package->openai_model_id   = 5; // gpt 3
        }

        $package->total_words_per_month             = 1000;
        $package->total_images_per_month            = 10;
        $package->total_speech_to_text_per_month    = 2;
        $package->speech_to_text_filesize_limit     = 2; // 2mb

        $package->allow_images              = 1;
        $package->allow_ai_code             = 1;
        $package->allow_speech_to_text      = 1;

        $package->has_live_support      = 1;
        $package->has_free_support      = 1;
        $package->duration              = 30;
        $package->save();

        $templateIds = Template::query()->pluck('id');
        $package->subscription_package_templates()->sync($templateIds);
    }

    # delete a package
    public function delete($id)
    {
        $package = SubscriptionPackage::findOrFail($id);
        if ($package->package_type == "starter") {
            flash(localize('Starter package has been deleted successfully'))->error();
        } else {
            $package->delete();
            flash(localize('Package has been deleted successfully'))->success();
        }
        return back();
    }
    # calculate discount amount
    public static function calculateDiscountPrice($discount_type, $amount, $discount)
    {
        // 1 = fixed 2 = percentage
        if(!$discount_type) return $amount;
        if($discount_type == 1){
             $discountPrice = $amount - $discount;
        }elseif($discount_type == 2){
             $discountPrice = $amount - (($amount / 100) * $discount);
        }else{
            $discountPrice = $amount;
        }
        return $discountPrice;
    }
}
