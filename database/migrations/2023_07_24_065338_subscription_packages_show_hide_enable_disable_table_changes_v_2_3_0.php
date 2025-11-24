<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionPackagesShowHideEnableDisableTableChangesV230 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->tinyInteger('show_word_tools')->default(1);
            $table->tinyInteger('allow_word_tools')->default(1);
            $table->tinyInteger('allow_built_in_templates')->default(1);
            $table->tinyInteger('show_built_in_templates')->default(1);
            $table->tinyInteger('show_custom_templates')->default(1);
            $table->tinyInteger('show_ai_chat')->default(1);
            $table->tinyInteger('show_ai_code')->default(1);
            $table->tinyInteger('show_text_to_speech')->default(1);
            $table->tinyInteger('show_image_tools')->default(1);
            $table->tinyInteger('allow_image_tools')->default(1);
            $table->tinyInteger('show_images')->default(1);
            $table->tinyInteger('show_sd_images')->default(1);
            $table->tinyInteger('allow_sd_images')->default(1);
            $table->tinyInteger('show_speech_to_text_tools')->default(1);
            $table->tinyInteger('show_live_support')->default(1);
            $table->tinyInteger('show_free_support')->default(1);
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
        });
    }
}
