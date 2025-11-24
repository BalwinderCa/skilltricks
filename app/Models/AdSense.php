<?php

namespace App\Models;

use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdSense extends Model
{
    use HasFactory;
    protected $with = ['ad_sense_localizations'];
    protected $guarded = ['id'];
    public function collectLocalization($entity = '', $lang_key = '')
    {
        $lang_key = $lang_key ==  '' ? App::getLocale() : $lang_key;
        $ad_sense_localizations = $this->ad_sense_localizations->where('lang_key', $lang_key)->first();
        return $ad_sense_localizations != null && $ad_sense_localizations->$entity ? $ad_sense_localizations->$entity : $this->$entity;
    }

    public function ad_sense_localizations()
    {
        return $this->hasMany(AdSenseLocalization::class);
    }
    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) =>str_replace(' ', '-', strtolower($value)),
            // set: fn (string $value) => strtolower($value),
        );
    }
}
