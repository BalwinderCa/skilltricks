<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToSubscriptionPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->integer('allow_unlimited_speech_to_text')->nullable()->default(0);
            $table->integer('allow_unlimited_image')->nullable()->default(0);
            $table->integer('allow_unlimited_word')->nullable()->default(0);
        });
        Schema::table('subscription_histories', function (Blueprint $table) {
            $table->integer('allow_unlimited_speech_to_text')->nullable()->default(0);
            $table->integer('allow_unlimited_image')->nullable()->default(0);
            $table->integer('allow_unlimited_word')->nullable()->default(0);
        });

        
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
            $dropColumns =  ['allow_unlimited_speech_to_text','allow_unlimited_image', 'allow_unlimited_word'];
            $table->dropColumn($dropColumns);
        });
    }
}
