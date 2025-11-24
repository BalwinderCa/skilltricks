<?php

namespace Database\Seeders;

use App\Models\AdSense;
use App\Models\adSenseLocalization;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdSenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ads = [
            'Header Top',
            'Bottom trusted by',
            'Top Best Feature',
            'Top Template Section',
            'Top Review Section',
            'Top Subscription Package',
            'Top Trail Banner Section',
            'Top Footer Section',
            'Header Bottom',
            'Footer Top',
            'Dashboard Profile Bottom',
            'Recent Project Top',
            'Sidebar Customer Profile Top'
        ];
        foreach ($ads as $key => $item) {
           $model = AdSense::updateOrCreate([
                'name' => $item,
            ], [
                'slug' => $item,
                'size' => '728x90',
            ]);

            $localize = new AdSenseLocalization();
            $localize->ad_sense_id = $model->id;
            $localize->name        = $item;
            $localize->lang_key    = 'en';
            $localize->save();
        }
    }
}
