<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('slug');
            $table->integer('openai_model_id');
            $table->string('package_type')->default('monthly')->comment('starter/monthly/yearly/lifetime');
            $table->double('price')->default('0.00');

            $table->bigInteger('total_words_per_month')->default(0);
            $table->bigInteger('total_images_per_month')->default(0);
            $table->bigInteger('total_speech_to_text_per_month')->default(0);
            $table->bigInteger('total_text_to_speech_per_month')->default(0); // future update

            $table->bigInteger('speech_to_text_filesize_limit')->default(-1);

            $table->tinyInteger('allow_images')->default(0);
            $table->tinyInteger('allow_ai_code')->default(0);
            $table->tinyInteger('allow_speech_to_text')->default(0);
            $table->tinyInteger('allow_ai_chat')->default(0); // future update
            $table->tinyInteger('allow_text_to_speech')->default(0); // future update
            $table->tinyInteger('allow_custom_templates')->default(0);
            $table->tinyInteger('show_open_ai_model')->default(1);

            $table->tinyInteger('has_live_support')->default(0);
            $table->tinyInteger('has_free_support')->default(0);
            $table->tinyInteger('is_featured')->default(0);
            $table->tinyInteger('is_active')->default(1);

            $table->longText('other_features')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_packages');
    }
}
