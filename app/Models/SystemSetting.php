<?php

namespace App\Models;

use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemSetting extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $with = ['systemSettingsLocalization'];

    public function collectLocalization($entity = '', $lang_key = '')
    {
        $lang_key = $lang_key ==  '' ? App::getLocale() : $lang_key;
        $systemSettingsLocalization = $this->systemSettingsLocalization->where('lang_key', $lang_key)->first();

        return $systemSettingsLocalization != null && $systemSettingsLocalization->entity != null ? $systemSettingsLocalization->value : null;
    }

    public function systemSettingsLocalization()
    {
        return $this->hasMany(GeneralSetupLocalization::class, 'system_setting_id', 'id');
    }

    public function scopeEntity($query, $type)
    {
        $query->where("entity", $type);
    }
}
