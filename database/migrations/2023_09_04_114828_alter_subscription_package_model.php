<?php

use App\Models\SubscriptionPackage;
use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterSubscriptionPackageModel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    { 
        $oldModels = [1,2,3,4];
        $subscriptionPackages = SubscriptionPackage::whereIn('openai_model_id', $oldModels)->get();
        foreach ($subscriptionPackages as $key => $package) {
            $package->openai_model_id = 5; // gtp-3.5-turbo
            $package->save();
        }
        
        // $setting = SystemSetting::where('entity', 'default_open_ai_model')->first();
        SystemSetting::updateOrCreate([
            'entity'=>'default_open_ai_model'
        ], [
            'value'=>'gpt-3.5-turbo'
        ]);
        // $setting->value = 'gpt-3.5-turbo';
        // $setting->save();
        cacheClear();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            //
        });
    }
}
