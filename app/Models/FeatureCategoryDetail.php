<?php

namespace App\Models;

use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use App\Models\FeatureCategoryDetailLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeatureCategoryDetail extends Model
{
    use HasFactory;
    protected $with = ['feature_category_detail_localizations'];
    protected $guarded = ['id'];

    public function collectLocalization($entity = '', $lang_key = '')
    {
        $lang_key = $lang_key ==  '' ? App::getLocale() : $lang_key;
        $feature_category_detail_localizations = $this->feature_category_detail_localizations->where('lang_key', $lang_key)->first();
        return $feature_category_detail_localizations != null && $feature_category_detail_localizations->$entity ? $feature_category_detail_localizations->$entity : $this->$entity;
    }

    public function feature_category_detail_localizations()
    {
        return $this->hasMany(FeatureCategoryDetailLocalization::class);
    }

    public function category()
    {
        return $this->belongsTo(FeatureCategory::class, 'feature_category_id', 'id')->withDefault();
    }
    
}
