<?php

namespace App\Http\Controllers\Backend\Templates;

use App\Http\Controllers\Controller;
use App\Imports\TemplatesImport;
use App\Models\CustomTemplate;
use App\Models\FavoriteTemplate;
use App\Models\Language;
use App\Models\SubscriptionPackage;
use App\Models\SubscriptionPackageTemplate;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Models\TemplateLocalization;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;

class TemplatesController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_built_in_templates') == '0' && Route::currentRouteName() != 'custom.templates.show') {
            redirect()->route('writebot.dashboard')->send();
        }
    }

    # all templates
    public function index(Request $request)
    {
        $searchKey = null;
        $templates      = Template::query();

        if ($request->search != null) {
            $templates = $templates->where('name', 'like', '%' . $request->search . '%')
            ->orWhereHas('template_localizations', function($q) use ($request){
                $q->where('name', 'like', '%' . $request->search . '%');
            });
            $searchKey = $request->search;
        }

        $templateGroups = TemplateGroup::get();
        if (isAdmin() || isStaff()) {
            if (!auth()->user()->can('templates')) {
                abort(403);
            }
            $templates = $templates->get();
        } else {
            // package
            $user = auth()->user();
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package && $package->allow_built_in_templates == 1) {

                $templates = $templates->isActive()->get();
            } else {
                abort(403);
            }
        }

        $favoritesArray = FavoriteTemplate::where('user_id', auth()->user()->id)->select('template_id')->distinct()->pluck('template_id')->toArray();

        return view('backend.pages.templates.index', [
            'templates'         => $templates,
            'templateGroups'    => $templateGroups,
            'favoritesArray'    => $favoritesArray,
            'searchKey'         => $searchKey
        ]);
    }

    # favorite popular
    public function indexPopular()
    {

        $templates = Template::orderBy('total_words_generated', 'DESC')->take(12);

        if (auth()->user()->user_type == "admin" || auth()->user()->user_type == "staff") {
            if (!auth()->user()->can('templates')) {
                abort(403);
            }
            $templates = $templates->get();
        } else {
            $templates = $templates->isActive()->get();
        }

        $favoritesArray = FavoriteTemplate::where('user_id', auth()->user()->id)->select('template_id')->distinct()->pluck('template_id')->toArray();

        return view('backend.pages.templates.popular', [
            'templates'         => $templates,
            'favoritesArray'    => $favoritesArray
        ]);
    }

    # favorite templates
    public function indexFavorite()
    {
        $favoritesArray = FavoriteTemplate::where('user_id', auth()->user()->id)->select('template_id')->distinct()->pluck('template_id')->toArray();

        $templates = Template::whereIn('id', $favoritesArray);

        if (auth()->user()->user_type == "admin" || auth()->user()->user_type == "staff") {
            if (!auth()->user()->can('templates')) {
                abort(403);
            }
            $templates = $templates->get();
        } else {
            $templates = $templates->isActive()->get();
        }

        return view('backend.pages.templates.favorites', [
            'templates'         => $templates,
            'favoritesArray'    => $favoritesArray
        ]);
    }

    # store / update templates from excel :: only for developers use
    public function store()
    {
        $file = public_path('/import/templates.xlsx');
        Excel::import(new TemplatesImport, $file);
    }


    # template view
    public function show($template_code)
    {
        $template = Template::where('code', $template_code)->first();

        if (empty($template)) {
            abort(404);
        }

        # check if template is available in subscription package
        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new \App\Models\SubscriptionPackage();
            $subscriptionTemplate = SubscriptionPackageTemplate::where('template_id', $template->id)->where('subscription_package_id', $package->id)->first();

            if ($package->allow_built_in_templates == 0) {
                flash(localize('This feature is not available in your subscription plan.'))->error();
                return redirect()->route('writebot.dashboard');
            }

            if (empty($subscriptionTemplate)) {
                flash(localize('This template is not available in your subscription plan, please upgrade to get access.'))->error();
                return redirect()->route('templates.index');
            }
        } else {
            if (!auth()->user()->can('templates')) {
                abort(403);
            }
        }

        # proceed to view
        $languages = Language::isActiveForTemplate()->latest()->get();
        return view('backend.pages.templates.generate-contents', [
            'template'  => $template,
            'languages' => $languages
        ]);
    }

    # template view
    public function showCustom($template_code)
    {
        $template = CustomTemplate::where('code', $template_code)->first();
        if (empty($template)) {
            abort(404);
        }

        $user = auth()->user();

        # check if template is available in subscription package
        if ($user->user_type == "customer") {

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
        } else {
            if (!auth()->user()->can('custom_templates')) {
                abort(403);
            }
        }

        # proceed to view
        $languages = Language::isActiveForTemplate()->latest()->get();
        return view('backend.pages.templates.custom-generate-contents', [
            'template'  => $template,
            'languages' => $languages
        ]);
    }

    # update favorite
    public function updateFavorite(Request $request)
    {
        $existing = FavoriteTemplate::where('template_id', $request->templateId)->where('user_id', auth()->user()->id)->first();
        $data = [];
        if (is_null($existing)) {
            $favorite = new FavoriteTemplate;
            $favorite->template_id = $request->templateId;
            $favorite->user_id = auth()->user()->id;
            $favorite->save();
            $data['message'] = localize('Added to favorite templates');
        } else {
            $existing->delete();
            $data['message'] = localize('Removed from favorite templates');
        }
        return $data;
    }

    # delete
    public function delete($id)
    {
        if (isCustomer()) {
            flash(localize('You can not do thi operation'))->warning();
            return back();
        }
        $template = Template::findOrFail($id);
        $template->delete();
        flash(localize('Template deleted successfully'))->success();
        return back();
    }

    # edit
    public function edit(Request $request, $id)
    {
        $lang_key = $request->lang_key;
        if (isCustomer()) {
            flash(localize('You can not do thi operation'))->warning();
            return back();
        }

        $template = Template::findOrFail($id);
        $categories = TemplateGroup::all();
        return view('backend.pages.templates.edit', [
            'template'  => $template,
            'lang_key' => $lang_key,
            'categories' => $categories
        ]);
    }

    # update
    public function update(Request $request)
    {
        $template = Template::findOrFail((int) $request->id);
        $template->template_group_id = $request->template_group_id;
        $template->icon              = $request->icon;
        if ($template->description == null) {
            $template->description   = $request->description;
        }
        $template->save();

        $templateLocalization = TemplateLocalization::firstOrNew(['lang_key' => $request->lang_key, 'template_id' => $template->id]);
        $templateLocalization->name = $request->name;
        $templateLocalization->description = $request->description;
        $templateLocalization->save();
        flash(localize('Template has been updated successfully'))->success();
        return back();
    }
}
