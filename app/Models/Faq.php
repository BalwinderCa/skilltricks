<?php

namespace App\Models;

use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Faq extends Model
{
    use HasFactory;
    protected $with = ['faq_localizations'];

    public function collectLocalization($entity = '', $lang_key = '')
    {
        $lang_key = $lang_key ==  '' ? App::getLocale() : $lang_key;
        $faq_localizations = $this->faq_localizations->where('lang_key', $lang_key)->first();
        return $faq_localizations != null && $faq_localizations->$entity ? $faq_localizations->$entity : $this->$entity;
    }

    public function faq_localizations()
    {
        return $this->hasMany(FaqLocalization::class);
    }
}
