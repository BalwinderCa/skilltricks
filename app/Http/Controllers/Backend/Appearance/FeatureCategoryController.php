<?php

namespace App\Http\Controllers\Backend\Appearance;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeatureCategoryRequestForm;
use App\Models\FeatureCategory;
use App\Models\FeatureCategoryLocalization;
use Illuminate\Http\Request;

class FeatureCategoryController extends Controller
{
    public function index()
    {
        $featureCategories = FeatureCategory::all();
        return view('backend.pages.appearance.homepage.feature-category', compact('featureCategories'));
    }
    public function create()
    {
        $featureCategories = FeatureCategory::all();
        return view('backend.pages.appearance.homepage.feature-category', compact('featureCategories'));
    }
    public function store(FeatureCategoryRequestForm $request)
    {
        try {
            $model = FeatureCategory::create($this->formattedParams($request));
            $this->localizeDataStore($model->id, $request->name);
            flash(localize('Feature Category has been created successfully'))->success();
            return redirect()->route('admin.appearance.homepage.feature-category');
        } catch (\Throwable $th) {
            flash(localize('Feature Category has been created failed'))->error();
            return redirect()->back();
        }
    }
    public function edit(Request $request, $id)
    {
        $editFeatureCategory = FeatureCategory::findOrFail($id);
        $featureCategories = FeatureCategory::all();
        $lang_key =  $request->lang_key ?? env('DEFAULT_LANGUAGE');
        return view('backend.pages.appearance.homepage.feature-category-edit', compact('editFeatureCategory', 'featureCategories', 'lang_key'));
    }
    public function update(FeatureCategoryRequestForm $request)
    {
        try {
            $id = $request->id;
            $featureCategory = FeatureCategory::findOrFail($id);
            if ($featureCategory) {
                $featureCategory->update($this->formattedParams($request, $featureCategory));
                $this->localizeDataStore($id, $request->name);
            }
            flash(localize('Feature Category has been updated successfully'))->success();
            return redirect()->route('admin.appearance.homepage.feature-category');
        } catch (\Throwable $th) {
            flash(localize('Feature Category has been updated failed'))->error();
            return redirect()->back();
        }
    }
    private function formattedParams($request, $model = null)
    {
        $params = [
            'name' => $request->name,
            'icon' => $request->icon
        ];
        if ($model) {
            $params['updated_by'] = auth()->user()->id;
        } else {
            $params['created_by'] = auth()->user()->id;
        }
        return $params;
    }
    private function localizeDataStore($model_id, $name)
    {
        $blogLocalization = FeatureCategoryLocalization::firstOrNew(['lang_key' => env('DEFAULT_LANGUAGE'), 'feature_category_id' => $model_id]);
        $blogLocalization->name = $name;
        $blogLocalization->save();
    }
    public function delete($id)
    {
        try {
            $featureCategory = FeatureCategory::findOrFail($id);
            if ($featureCategory) {
                $featureCategory->delete();
            }
            flash(localize('Feature Category has been deleted successfully'))->success();
            return redirect()->route('admin.appearance.homepage.feature-category');
        } catch (\Throwable $th) {
            flash(localize('Feature Category has been deleted failed'))->error();
            return redirect()->back();
        }
    }
    private function languageData($request): array
    {
        $data = [];
        $data['lang_key'] =  $request->lang_key ?? env('DEFAULT_LANGUAGE');
        return $data;
    }
}
