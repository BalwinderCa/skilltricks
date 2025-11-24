<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AiApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $aiApplications = array(
            array('id' => '184','entity' => 'feature_integration_1_image','value' => '44','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:31:55','deleted_at' => NULL),
            array('id' => '185','entity' => 'feature_integration_1_title','value' => 'Social Login Configurations','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:31:55','deleted_at' => NULL),
            array('id' => '186','entity' => 'feature_integration_1_short_description','value' => 'We are using Google and Facebook for Sign in & Sign up','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:31:55','deleted_at' => NULL),
            array('id' => '187','entity' => 'feature_integration_2_image','value' => '42','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:35:03','deleted_at' => NULL),
            array('id' => '188','entity' => 'feature_integration_2_title','value' => 'AWS Storage Management','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:35:03','deleted_at' => NULL),
            array('id' => '189','entity' => 'feature_integration_2_short_description','value' => 'Amazon AWS S3 storage management for Image & contents','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:35:03','deleted_at' => NULL),
            array('id' => '190','entity' => 'feature_integration_3_image','value' => '45','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:33:33','deleted_at' => NULL),
            array('id' => '191','entity' => 'feature_integration_3_title','value' => 'Two-factor Authentication','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:33:33','deleted_at' => NULL),
            array('id' => '192','entity' => 'feature_integration_3_short_description','value' => 'Two-factor authentication protection for your Twilio account.','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:33:33','deleted_at' => NULL),
            array('id' => '193','entity' => 'feature_integration_4_image','value' => '40','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:35:03','deleted_at' => NULL),
            array('id' => '194','entity' => 'feature_integration_4_title','value' => 'Google & Microsoft Azure TTS','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:35:03','deleted_at' => NULL),
            array('id' => '195','entity' => 'feature_integration_4_short_description','value' => 'Microsoft Azure and Google for Text to Speech Features','created_at' => '2024-03-20 10:31:55','updated_at' => '2024-03-20 10:35:03','deleted_at' => NULL)
        );

        foreach ($aiApplications as $tool) {
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
