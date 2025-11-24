<?php

namespace App\Http\Controllers\Backend\Appearance;

use Illuminate\Http\Request;
use App\Models\FeatureCategory;
use App\Http\Controllers\Controller;
use App\Models\FeatureCategoryDetail;
use App\Models\FeatureCategoryDetailLocalization;
use App\Http\Requests\FeatureCategoryDetailRequestForm;

class FeatureCategoryDetailController extends Controller
{
    public function index()
    {
        $featureCategoryDetails = FeatureCategoryDetail::all();
        $featureCategories      = FeatureCategory::where('is_active', 1)->get();

        return view('backend.pages.appearance.homepage.feature-category-detail', compact('featureCategoryDetails', 'featureCategories'));
    }
    public function create()
    {
        $featureCategoryDetails = FeatureCategoryDetail::all();
        $featureCategories      = FeatureCategory::where('is_active', 1)->get();

        return view('backend.pages.appearance.homepage.feature-category-detail', compact('featureCategoryDetails', 'featureCategories'));
    }
    public function store(FeatureCategoryDetailRequestForm $request)
    {
        try {
            $model = FeatureCategoryDetail::create($this->formattedParams($request));
            $this->localizeDataStore($model->id, $request->title, $request->short_description);

            flash(localize('Feature Category detail has been created successfully'))->success();
            return redirect()->route('admin.appearance.homepage.feature-category-detail');

        } catch (\Throwable $th) {
            flash(localize('Feature Category detail has been created failed'))->error();
            return redirect()->back();
        }
    }
    public function edit(Request $request, $id)
    {
        $editFeatureCategoryDetail = FeatureCategoryDetail::findOrFail($id);
        $featureCategoryDetails    = FeatureCategoryDetail::all();
        $featureCategories         = FeatureCategory::where('is_active', 1)->get();
        $lang_key                  = $request->lang_key ?? env('DEFAULT_LANGUAGE');

        return view('backend.pages.appearance.homepage.feature-category-detail-edit', compact('editFeatureCategoryDetail', 'featureCategoryDetails', 'lang_key', 'featureCategories'));
    }
    public function update(FeatureCategoryDetailRequestForm $request)
    {
        try {
            $id = $request->id;
            $featureCategoryDetail = FeatureCategoryDetail::findOrFail($id);
            if ($featureCategoryDetail) {
                $featureCategoryDetail->update($this->formattedParams($request, $featureCategoryDetail));
                $this->localizeDataStore($id, $request->title, $request->short_description);
            }
            flash(localize('Feature Category detail has been updated successfully'))->success();
            return redirect()->route('admin.appearance.homepage.feature-category-detail');
        } catch (\Throwable $th) {
            dd($th->getMessage());
            flash(localize('Feature Category detail has been updated failed'))->error();
            return redirect()->back();
        }
    }
    private function formattedParams($request, $model = null)
    {
        $params = [
            'title'               => $request->title,
            'short_description'   => $request->short_description,
            'icon'                => $request->icon,
            'feature_category_id' => $request->feature_category_id,
            'image'               => $request->image,
            'is_active'           => $request->is_active,
        ];
        if ($model) {
            $params['updated_by'] = auth()->user()->id;
        } else {
            $params['created_by'] = auth()->user()->id;
        }
        return $params;
    }
    private function localizeDataStore($model_id, $name, $short_description)
    {
        $blogLocalization = FeatureCategoryDetailLocalization::firstOrNew(['lang_key' => env('DEFAULT_LANGUAGE'), 'feature_category_detail_id' => $model_id]);
        $blogLocalization->title             = $name;
        $blogLocalization->short_description = $short_description;
        $blogLocalization->save();
    }
    public function delete($id)
    {
        try {
            $featureCategoryDetail = FeatureCategoryDetail::findOrFail($id);
            if ($featureCategoryDetail) {
                $featureCategoryDetail->delete();
            }
            flash(localize('Feature Category detail has been deleted successfully'))->success();
            return redirect()->route('admin.appearance.homepage.feature-category-detail');
        } catch (\Throwable $th) {
            flash(localize('Feature Category detail has been deleted failed'))->error();
            return redirect()->back();
        }
    }
}
