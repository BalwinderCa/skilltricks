<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTextToSpeechSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('text_to_speech_settings', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->nullable();
            $table->string('path')->nullable();
            $table->string('project_id')->nullable();
            $table->string('project_name')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->string('key')->nullable();
            $table->string('type')->nullable();
            $table->string('value')->nullable();
            $table->string('region')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('text_to_speech_settings');
    }
}
