<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FeatureToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $featureTools = [
            '1' => [
                'entity' => 'hero_video',
                'value'  => 'public/wirtebot-video.mp4'
            ],
            '2' => [
                'entity' => 'feature_tools_1_image',
                'value' => '44'
            ],
            '3' => [
                'entity' => 'feature_tools_1_title',
                'value' => 'Social Media Integration'
            ],
            '3' => [
                'entity' => 'feature_tools_1_short_description',
                'value' => 'AI-powered translation tools Save per day on ad management'
            ],
            '4' => [
                'entity' => 'feature_tools_2_image',
                'value' => '71'
            ],
            '5' => [
                'entity' => 'feature_tools_2_title',
                'value' => 'Affiliate marketing'
            ],
            '6' => [
                'entity' => 'feature_tools_2_short_description',
                'value' => 'We have included Affiliate marketing system'
            ],
            '7' => [
                'entity' => 'feature_tools_3_image',
                'value' => '66'
            ],
            '8' => [
                'entity' => 'feature_tools_3_title',
                'value' => 'Multilingual Support'
            ],
            '9' => [
                'entity' => 'feature_tools_3_short_description',
                'value' => 'Writebot supported all language, you need to just add your language'
            ],
            '10' => [
                'entity' => 'feature_tools_4_image',
                'value' => '65'
            ],
            '11' => [
                'entity' => 'feature_tools_4_title',
                'value' => '12+ Payment Gateway Included'
            ],
            '12' => [
                'entity' => 'feature_tools_4_short_description',
                'value' => 'Writebot integrate 12+ Payment gateway'
            ],
            '13' => [
                'entity' => 'feature_tools_5_image',
                'value' => '45'
            ],
            '14' => [
                'entity' => 'feature_tools_5_title',
                'value' => 'Two factor Authentication'
            ],
            '15' => [
                'entity' => 'feature_tools_5_short_description',
                'value' => 'Two-factor authentication protection for your Twilio account. You will be required to enter in the code available'
            ],
        ];

        foreach ($featureTools as $tool) {
            $setting = SystemSetting::where('entity', $tool['entity'])->first();
            if ($setting != null) {
                $setting->value = $tool['value'];
                $setting->save();
            } else {
                $setting = new SystemSetting;
                $setting->entity = $tool['entity'];
                $setting->value = $tool['value'];
                $setting->save();
            }
        }
    }
}
