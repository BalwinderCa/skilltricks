<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;

class CustomTemplate extends Model
{
    use HasFactory;

    protected $with = ['custom_template_localizations'];

    public function collectLocalization($entity = '', $lang_key = '')
    {
        $lang_key = $lang_key ==  '' ? App::getLocale() : $lang_key;
        $custom_template_localizations = $this->custom_template_localizations->where('lang_key', $lang_key)->first();
        return $custom_template_localizations != null && $custom_template_localizations->$entity ? $custom_template_localizations->$entity : $this->$entity;
    }

    public function custom_template_localizations()
    {
        return $this->hasMany(CustomTemplateLocalization::class);
    }

    public function templateUsage()
    {
        return $this->hasMany(TemplateUsage::class, 'custom_template_id', 'id');
    }
}
