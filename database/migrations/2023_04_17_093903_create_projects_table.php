<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('folder_id')->nullable();
            $table->integer('template_id')->nullable();
            $table->integer('custom_template_id')->nullable();
            $table->text('model_name')->nullable();
            $table->text('title')->nullable();
            $table->text('slug')->nullable();
            $table->longText('content')->nullable();
            $table->bigInteger('prompts')->nullable();
            $table->bigInteger('completion')->nullable();
            $table->bigInteger('words')->nullable();
            $table->string('content_type')->nullable()->comment('content/image/code/speech_to_text...');
            $table->string('resolution')->nullable();
            $table->text('audio_file')->nullable();
            $table->longText('text_to_speech_content')->nullable();
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
        Schema::dropIfExists('projects');
    }
}
