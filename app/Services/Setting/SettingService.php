<?php

namespace App\Services\Setting;

use App\Models\SystemSetting;

class SettingService
{
    public function updateEntityValues(array $types, array $values)
    {
        foreach ($types as $key => $type) {
            SystemSetting::updateOrCreate([
                'entity'=>$type
            ], [
                'value'=>html_entity_decode($values[$key])
            ]);
        }
    }
}
