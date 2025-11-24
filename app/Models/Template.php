<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $with = ['template_localizations'];

    # guarded
    protected $guarded = [
        ''
    ];

    public function collectLocalization($entity = '', $lang_key = '')
    {
        $lang_key = $lang_key ==  '' ? App::getLocale() : $lang_key;
        $template_localizations = $this->template_localizations->where('lang_key', $lang_key)->first();
        return $template_localizations != null && $template_localizations->$entity ? $template_localizations->$entity : $this->$entity;
    }

    public function template_localizations()
    {
        return $this->hasMany(TemplateLocalization::class);
    }


    public function scopeIsActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function templateUsage()
    {
        return $this->hasMany(TemplateUsage::class);
    }

    public function scopeCode($query, $code)
    {
        $query->where("code", $code);
    }
}
