<?php

namespace App\Models;

use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use App\Models\FeatureCategoryLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeatureCategory extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $with = ['feature_category_localizations'];
    public function collectLocalization($entity = '', $lang_key = '')
    {
        $lang_key = $lang_key ==  '' ? App::getLocale() : $lang_key;
        $feature_category_localizations = $this->feature_category_localizations->where('lang_key', $lang_key)->first();
        return $feature_category_localizations != null && $feature_category_localizations->$entity ? $feature_category_localizations->$entity : $this->$entity;
    }

    public function feature_category_localizations()
    {
        return $this->hasMany(FeatureCategoryLocalization::class);
    }

    public function feature_category_detail()
    {
        return $this->hasMany(FeatureCategoryDetail::class, 'feature_category_id', 'id');
    }
}
