<?php

namespace Database\Seeders;

use App\Models\FeatureCategory;
use Illuminate\Database\Seeder;
use App\Models\FeatureCategoryDetail;
use App\Models\FeatureCategoryLocalization;
use App\Models\FeatureCategoryDetailLocalization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FeatureCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $featureCategories = [
            '1'  => [
                'name' => '72+ Pre Build Template',
                'icon' => '	bi bi-layers',
            ],
            '2'  => [ 
                'name' => 'Full Blog Generator',
                'icon' => 'bi bi-textarea-t',
            ],
            '3'  => [  
                'name' => 'AI Image Generator',
                'icon' => 'bi bi-images',
            ],
            '4'  => [
                'name' => 'Text To Voice',
                'icon' => '	bi bi-volume-up-fill',
            ],
            '5'  => [
                'name' => 'Chat with Expert',
                'icon' => 'bi bi-chat-square-dots-fill',
            ],
            '6'  => [
                'name' => 'Integrated Tools',
                'icon' => 'bi bi-bounding-box',
            ],
           
        ];
        foreach($featureCategories as $key=>$category)
        {
            FeatureCategory::updateOrCreate([
                'name' => $category['name']
            ],[
                'icon' => $category['icon']
            ]);
            FeatureCategoryLocalization::updateOrCreate([
                'feature_category_id' => $key,
                'name'                => $category['name'],
                'lang_key'            => 'en'
            ]);

        }
        $featureDetails = [
            '1'=>[
                'feature_category_id' => 1,
                'image'               => 35,
                'title'               => 'Social Media Template',
                'short_description'   => 'AI-powered robots can perform rule based tasks in business'
            
            ],
            '2'=>[
                'feature_category_id' => 1,
                'image'               => 101,
                'title'               => 'Fun and Quote',
                'short_description'   => 'AI-powered robots can perform rule based tasks in business'
           
            ],
            '3'=>[
                'feature_category_id' => 1,
                'image'               => 59,
                'title'               => 'Website Content & Summery',
                'short_description'   => 'AI-powered robots can perform rule based tasks in business'
           
            ],
            '4'=>[
                'feature_category_id' => 1,
                'image'               => 58,
                'title'               => 'Email Marketing Template',
                'short_description'   => 'Generate social media content easily using our application'
           
            ],
            '5'=>[
                'feature_category_id' => 2,
                'image'               => 60,
                'title'               => 'Generate Full Blog',
                'short_description'   => 'Generate full blog article using our writebot application'
            
            ],
            '6'=>[
                'feature_category_id' => 2,
                'image'               => 59,
                'title'               => 'Generate Blog Keyword',
                'short_description'   => 'Generated blog keyword based on your topics'
           
            ],
            '7'=>[
                'feature_category_id' => 2,
                'image'               => 58,
                'title'               => 'Create Blog Outline',
                'short_description'   => 'Generate blog outline based on your keyword and title'
           
            ],
            '8'=>[
                'feature_category_id' => 2,
                'image'               => 57,
                'title'               => 'Generated Blog Publish',
                'short_description'   => 'After generated blog article then publish to your blog'
           
            ],
            '9'=>[
                'feature_category_id' => 3,
                'image'               => 56,
                'title'               => 'Generate Dall-E 2 Image',
                'short_description'   => 'Generate dall-e 2 Images for using OpenAi'
            
            ],
            '10'=>[
                'feature_category_id' => 3,
                'image'               => 54,
                'title'               => 'Stable Diffusion Images',
                'short_description'   => 'Stable Diffusion is a deep learning, text-to-image model'
           
            ],
            '11'=>[
                'feature_category_id' => 3,
                'image'               => 55,
                'title'               => 'Generate Dall-E 3 Image',
                'short_description'   => 'Generate dall-e 3 Images for using OpenAi'
           
            ],
            '12'=>[
                'feature_category_id' => 4,
                'image'               => 53,
                'title'               => 'Google text to Speech',
                'short_description'   => 'Generate text to speech using Google TTS'
           
            ],
            '13'=>[
                'feature_category_id' => 4,
                'image'               => 51,
                'title'               => 'OpenAI Text to Speech',
                'short_description'   => 'Generate text to speech using OpenAi TTS'
           
            ],
            '14'=>[
                'feature_category_id' => 4,
                'image'               => 52,
                'title'               => 'ElevenLabs Text to Speech',
                'short_description'   => 'For real human voice using ElevenLabs Text to Speech'
           
            ],
            '15'=>[
                'feature_category_id' => 4,
                'image'               => 50,
                'title'               => 'Azure Text to Speech',
                'short_description'   => 'AI voice generators to speak naturally using Azure'
           
            ],
            '16'=>[
                'feature_category_id' => 5,
                'image'               => 49,
                'title'               => 'AI Chat with Expert',
                'short_description'   => 'Chat with different expertise more then 25'
           
            ],
            '17'=>[
                'feature_category_id' => 5,
                'image'               => 48,
                'title'               => 'AI Chat Images',
                'short_description'   => 'Ai chat for generating quality images'
           
            ],
            '18'=>[
                'feature_category_id' => 5,
                'image'               => 47,
                'title'               => 'Chat From Images',
                'short_description'   => 'Upload images for get content base on your images'
           
            ],
            '19'=>[
                'feature_category_id' => 5,
                'image'               => 46,
                'title'               => 'AI PDF Chat',
                'short_description'   => 'Collect data from your pdf file, chat with pdf'
           
            ],
            '20'=>[
                'feature_category_id' => 6,
                'image'               => 45,
                'title'               => 'Twilio Authentication',
                'short_description'   => 'Delight customers with frictionless authentication touch-point.'
           
            ],
            '21'=>[
                'feature_category_id' => 6,
                'image'               => 40,
                'title'               => 'Google & Microsoft Azure TTS',
                'short_description'   => 'AI voice generators to speak naturally using Azure and google'           
            ],
            '22'=>[
                'feature_category_id' => 6,
                'image'               => 42,
                'title'               => 'Storage Management',
                'short_description'   => 'AWS storage services Object, file, and block storage'           
            ],
            '23'=>[
                'feature_category_id' => 6,
                'image'               => 44,
                'title'               => 'Social Login Integrated',
                'short_description'   => 'Integrated Google and Facebook for signup and sign in'           
            ],
        ];
        foreach($featureDetails as $key=>$detail)
        {
            FeatureCategoryDetail::updateOrCreate([
                'title'                      => $detail['title'],
                'image'                      => $detail['image'],
                'short_description'          => $detail['short_description'],
                'feature_category_id'        => $detail['feature_category_id'],
                'icon'                       => 'bi bi-stars'
            ]);
            FeatureCategoryDetailLocalization::updateOrCreate([
                'feature_category_detail_id' => $key,
                'title'                      => $detail['title'],
                'short_description'          => $detail['short_description'],
                'lang_key'                   => 'en'
            ]);
        }
    }
}
