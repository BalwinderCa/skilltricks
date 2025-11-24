<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;

class CustomTemplateCategory extends Model
{
    use HasFactory;

    protected $with = ['category_localizations'];

    public function collectLocalization($entity = '', $lang_key = '')
    {
        $lang_key = $lang_key ==  '' ? App::getLocale() : $lang_key;
        $category_localizations = $this->category_localizations->where('lang_key', $lang_key)->first();
        return $category_localizations != null && $category_localizations->$entity ? $category_localizations->$entity : $this->$entity;
    }

    public function category_localizations()
    {
        return $this->hasMany(CustomTemplateCategoryLocalization::class);
    }
    public function customTemplates()
    {
        return $this->hasMany(CustomTemplate::class);
    }
}
