<?php

namespace App\Http\Controllers\Backend\Templates;

use App\Http\Controllers\Controller;
use App\Models\CustomTemplate;
use App\Models\CustomTemplateCategory;
use App\Models\CustomTemplateLocalization;
use App\Models\Language;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Str;

class CustomTemplatesController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_custom_templates') == '0') {
            redirect()->route('writebot.dashboard')->send();
        }
    }

    # custom templates list
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->user_type == "admin" || $user->user_type == "staff") {
            if (!auth()->user()->can('custom_templates')) {
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

        $searchKey = null;
        $templates      = CustomTemplate::orderBy('created_by', 'DESC');

        if ($request->search != null) {
            $templates = $templates->where('name', 'like', '%' . $request->search . '%')
            ->orWhereHas('custom_template_localizations', function($q) use ($request){
                $q->where('name', 'like', '%' . $request->search . '%');
            });
            $searchKey = $request->search;
        }

        // $templates = $templates->where('user_id', auth()->user()->id)->orWhere('created_by', 'admin')->get();
        $templates = $templates->where(function ($query) {
            $query->where('user_id', auth()->user()->id)->orWhere('created_by', 'admin');
        });

        $templates = $templates->get();
        $categories = CustomTemplateCategory::where(function ($query) {
            $query->where('user_id', auth()->user()->id)->orWhere('created_by', 'admin');
        })->get(); // user wise 
        return view('backend.pages.templates.custom.templates.index', [
            'templates'         => $templates,
            'categories'       => $categories,
            'searchKey'         => $searchKey
        ]);
    }

    # add custom templates
    public function create()
    {
        $user = auth()->user();
        if ($user->user_type == "admin" || $user->user_type == "staff") {
            if (!$user->can('custom_templates')) {
                abort(403);
            }
            $categories = CustomTemplateCategory::where('user_id', $user->id)->orWhere('created_by', 'admin')->get();
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
            $categories = CustomTemplateCategory::where('user_id', auth()->user()->id)->get();
        }


        return view('backend.pages.templates.custom.templates.create', compact('categories'));
    }

    # custom templates store
    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->user_type == "admin" || $user->user_type == "staff") {
            if (!$user->can('custom_templates')) {
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

        $template = new CustomTemplate;
        $template->user_id = auth()->user()->id;
        $template->created_by = auth()->user()->user_type != "customer" ? 'admin' : null;

        $template->name = $request->name;
        $template->slug = preg_replace('/\s+/', '-', trim($request->name));
        $template->code = preg_replace('/\s+/', '-', trim($request->name));
        $template->custom_template_category_id = $request->category_id;
        $template->description = $request->description;
        $template->icon = $request->icon;
        $template->prompt = $request->prompt;

        $fields = [];

        foreach ($request->input_types as $key => $input_type) {
            $entry = new CustomTemplate;
            $entry->label = $request->input_labels[$key];
            $entry->is_required = true;

            $field = new CustomTemplate;
            $field->name = Str::slug($request->input_names[$key]);
            $field->type = $input_type;

            $entry->field = $field;

            array_push($fields, $entry);
        }

        $template->fields = json_encode($fields);

        $template->save();
        flash(localize('Template has been added successfully'))->success();
        return redirect()->route('custom.templates.index');
    }

    # edit custom templates
    public function edit(Request $request, $id)
    {
        $lang_key = $request->lang_key;
        if (!$lang_key) { 
            $language = Language::isActive()->first();
        } else{
            $language = Language::isActive()->where('code', $lang_key)->first();
        }
        if (!$language) {
            flash(localize('Language you are trying to translate is not available or not active'))->error();
            return redirect()->route('custom.templates.index');
        }


        $user = auth()->user();
        $template = CustomTemplate::where('id', $id);

        if ($user->user_type == "admin" || $user->user_type == "staff") {
            if (!$user->can('custom_templates')) {
                abort(403);
            }
            $template = $template->where(function ($query) {
                $query->where('user_id', auth()->user()->id)->orWhere('created_by', 'admin');
            });

            $template = $template->first();

            $categories = CustomTemplateCategory::where('user_id', $user->id)->orWhere('created_by', 'admin')->get();
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
            $template = $template->where('user_id', auth()->user()->id)->first();
            $categories = CustomTemplateCategory::where('user_id', auth()->user()->id)->get();
        }

        if (is_null($template)) {
            abort(404);
        }
        return view('backend.pages.templates.custom.templates.edit', compact('template', 'categories', 'lang_key'));
    }

    # update custom templates
    public function update(Request $request)
    {
        $user = auth()->user();
        $template = CustomTemplate::where('id', (int) $request->id);

        if ($user->user_type == "admin" || $user->user_type == "staff") {
            if (!$user->can('custom_template_categories')) {
                abort(403);
            }
            $template = $template->where(function ($query) {
                $query->where('user_id', auth()->user()->id)->orWhere('created_by', 'admin');
            });

            $template = $template->first();
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
            $template = $template->where('user_id', auth()->user()->id)->first();
        }

        if (is_null($template)) {
            abort(404);
        }

        if ($request->lang_key == config('custom.default_language')) {
            $template->name                         = $request->name;
            $template->slug                         = preg_replace('/\s+/', '-', trim($request->name));
            $template->code                         = preg_replace('/\s+/', '-', trim($request->name));
            $template->custom_template_category_id  = $request->category_id;
            $template->description                  = $request->description;
            $template->icon                         = $request->icon;
            $template->prompt                       = $request->prompt;

            $fields = [];

            foreach ($request->input_types as $key => $input_type) {
                $entry = new CustomTemplate;
                $entry->label = $request->input_labels[$key];
                $entry->is_required = true;

                $field = new CustomTemplate;
                $field->name = Str::slug($request->input_names[$key]);
                $field->type = $input_type;

                $entry->field = $field;

                array_push($fields, $entry);
            }

            $template->fields = json_encode($fields);

            $template->save();
        }

        if ($request->lang_key) { 
            $customTemplateLocalization = CustomTemplateLocalization::firstOrNew(['lang_key' => $request->lang_key, 'custom_template_id' => $template->id]);
            $customTemplateLocalization->name = $request->name;
            $customTemplateLocalization->description = $request->description;
            $customTemplateLocalization->save();
        }

        flash(localize('Template has been updated successfully'))->success();
        return back();
    }

    # delete custom templates
    public function delete($id)
    {
        $template = CustomTemplate::where('id', $id);

        if (auth()->user()->user_type == "admin" || auth()->user()->user_type == "staff") {
            if (!auth()->user()->can('custom_templates')) {
                abort(403);
            }
            $template = $template->where(function ($query) {
                $query->where('user_id', auth()->user()->id)->orWhere('created_by', 'admin');
            });

            $template = $template->first();
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
            $template = $template->where('user_id', auth()->user()->id)->first();
        }

        if (is_null($template)) {
            abort(404);
        }
        $template->delete();
        flash(localize('Template has been deleted successfully'))->success();
        return back();
    }
}
