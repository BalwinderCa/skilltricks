<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVoiceLanguageDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voice_language_details', function (Blueprint $table) {
            $table->id();
            $table->string('language')->nullable();
            $table->string('language_code')->nullable();
            $table->string('voice_name')->nullable();
            $table->string('voice')->nullable();
            $table->string('gender')->nullable();
            $table->string('method')->comment('google, azure, aws')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->string('avatar_url')->nullable();
            $table->string('voice_type')->default('standard')->comment('standard|neural');
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
        Schema::dropIfExists('voice_language_details');
    }
}
