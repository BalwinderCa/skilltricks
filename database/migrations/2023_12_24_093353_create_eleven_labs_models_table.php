<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateElevenLabsModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eleven_labs_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_id');
            $table->string('name')->nullable();
            $table->integer('can_do_text_to_speech')->nullable();
            $table->integer('can_do_voice_conversion')->nullable();
            $table->integer('can_be_finetuned')->nullable();
            $table->integer('can_use_style')->nullable();
            $table->longText('response')->nullable();
            $table->integer('is_active')->nullable()->default(1);
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
        Schema::dropIfExists('eleven_labs_models');
    }
}
