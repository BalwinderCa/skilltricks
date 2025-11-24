<?php

namespace App\Http\Controllers\Backend\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomTemplateCategoryRequestForm;
use App\Models\CustomTemplate;
use App\Models\CustomTemplateCategory;
use App\Models\CustomTemplateCategoryLocalization;
use App\Models\Language;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Str;

class CustomTemplateCategoriesController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_custom_templates') == '0') {
            redirect()->route('writebot.dashboard')->send();
        }
    }

    # category list
    public function index(Request $request)
    {
        $user = auth()->user();
        $searchKey = null;
        $categories = CustomTemplateCategory::oldest();

        if ($request->search != null) {
            $categories = $categories->where('name', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        if (auth()->user()->user_type == "admin" || auth()->user()->user_type == "staff") {
            if (!auth()->user()->can('custom_template_categories')) {
                abort(403);
            }
            $categories = $categories->where('user_id', auth()->user()->id)->orWhere('created_by', 'admin')->paginate(paginationNumber());
        } else {
            // subscription based
            if (optional(activePackageHistory())->subscription_package_id == null) {
                flash(localize('Please upgrade your subscription plan'))->error();
                return redirect()->route('writebot.dashboard');
            }

            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;

            //  check if allow_custom_templates is enabled
            if ((int) $package->allow_custom_templates == 0) {
                flash(localize('Custom template is not available in this package, please upgrade you plan'))->error();
                return redirect()->route('writebot.dashboard');
            }

            $categories = $categories->where('user_id', auth()->user()->id)->paginate(paginationNumber());
        }


        return view('backend.pages.templates.custom.categories.index', compact('categories', 'searchKey'));
    }

    # category store
    public function store(CustomTemplateCategoryRequestForm $request)
    {
        $user = auth()->user();
        if ($user->user_type == "admin" || $user->user_type == "staff") {
            if (!auth()->user()->can('custom_template_categories')) {
                abort(403);
            }
        } else {
            // subscription based
            if (optional(activePackageHistory())->subscription_package_id == null) {
                flash(localize('Please upgrade your subscription plan'))->error();
                return redirect()->route('writebot.dashboard');
            }

            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;

            //  check if allow_custom_templates is enabled
            if ((int) $package->allow_custom_templates == 0) {
                flash(localize('Custom template is not available in this package, please upgrade you plan'))->error();
                return redirect()->route('writebot.dashboard');
            }
        }

        $category               = new CustomTemplateCategory;
        $category->name         = $request->name;
        $category->slug         = preg_replace('/\s+/', '-', trim($request->name));
        $category->icon         = $request->icon;
        $category->user_id      = $user->id;
        $category->created_by   = $user->user_type != "customer" ? "admin" : '';
        $category->save();

        flash(localize('Category has been inserted successfully'))->success();
        return redirect()->route('custom.templateCategories.index');
    }

    # edit category
    public function edit(Request $request, $id)
    {
        $lang_key = $request->lang_key;
        $language = Language::isActive()->where('code', $lang_key)->first();
        if (!$language) {
            flash(localize('Language you are trying to translate is not available or not active'))->error();
            return redirect()->route('admin.blogs.index');
        }

        $user = auth()->user();
        $category = CustomTemplateCategory::where('id', $id);

        if (auth()->user()->user_type == "admin" || auth()->user()->user_type == "staff") {
            if (!auth()->user()->can('custom_template_categories')) {
                abort(403);
            }
            $category = $category->where(function ($query) {
                $query->where('user_id', auth()->user()->id)->orWhere('created_by', 'admin');
            });
            $category = $category->first();
        } else {
            // subscription based
            if (optional(activePackageHistory())->subscription_package_id == null) {
                flash(localize('Please upgrade your subscription plan'))->error();
                return redirect()->route('writebot.dashboard');
            }

            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;

            //  check if allow_custom_templates is enabled
            if ((int) $package->allow_custom_templates == 0) {
                flash(localize('Custom template is not available in this package, please upgrade you plan'))->error();
                return redirect()->route('writebot.dashboard');
            }
            $category = $category->where('user_id', auth()->user()->id)->first();
        }

        if (is_null($category)) {
            abort(404);
        }
        return view('backend.pages.templates.custom.categories.edit', compact('category', 'lang_key'));
    }

    # update category
    public function update(CustomTemplateCategoryRequestForm $request)
    {
        $user = auth()->user();
        $category = CustomTemplateCategory::where('id', (int) $request->id);

        if (auth()->user()->user_type == "admin" || auth()->user()->user_type == "staff") {
            if (!auth()->user()->can('custom_template_categories')) {
                abort(403);
            }
            $category = $category->where(function ($query) {
                $query->where('user_id', auth()->user()->id)->orWhere('created_by', 'admin');
            });
            $category = $category->first();
        } else {
            // subscription based
            if (optional(activePackageHistory())->subscription_package_id == null) {
                flash(localize('Please upgrade your subscription plan'))->error();
                return redirect()->route('writebot.dashboard');
            }

            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;

            //  check if allow_custom_templates is enabled
            if ((int) $package->allow_custom_templates == 0) {
                flash(localize('Custom template is not available in this package, please upgrade you plan'))->error();
                return redirect()->route('writebot.dashboard');
            }
            $category = $category->where('user_id', auth()->user()->id)->first();
        }

        if (is_null($category)) {
            abort(404);
        }

        if ($request->lang_key == env("DEFAULT_LANGUAGE")) {
            $category->name         = $request->name;
            $category->slug         = preg_replace('/\s+/', '-', trim($request->name));
            $category->icon         = $request->icon;
            $category->save();
        }

        $categoryLocalization = CustomTemplateCategoryLocalization::firstOrNew(['lang_key' => $request->lang_key, 'custom_template_category_id' => $category->id]);
        $categoryLocalization->name = $request->name;
        $categoryLocalization->save();

        flash(localize('Category has been updated successfully'))->success();
        return redirect()->route('custom.templateCategories.index');
    }

    # delete category
    public function delete($id)
    {
        $user = auth()->user();
        $category = CustomTemplateCategory::where('id', $id);

        if (auth()->user()->user_type == "admin" || auth()->user()->user_type == "staff") {
            if (!auth()->user()->can('custom_template_categories')) {
                abort(403);
            }
            $category = $category->where(function ($query) {
                $query->where('user_id', auth()->user()->id)->orWhere('created_by', 'admin');
            });
            $category = $category->first();
        } else {
            // subscription based
            if (optional(activePackageHistory())->subscription_package_id == null) {
                flash(localize('Please upgrade your subscription plan'))->error();
                return redirect()->route('writebot.dashboard');
            }

            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;

            //  check if allow_custom_templates is enabled
            if ((int) $package->allow_custom_templates == 0) {
                flash(localize('Custom template is not available in this package, please upgrade you plan'))->error();
                return redirect()->route('writebot.dashboard');
            }
            $category = $category->where('user_id', auth()->user()->id)->first();
        }

        CustomTemplate::where('custom_template_category_id', $category->id)->delete();
        $category->delete();
        flash(localize('Category has been deleted successfully'))->success();
        return back();
    }
}
