<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index();
            $table->integer('project_id')->nullable()->index();
            $table->integer('subscription_history_id')->nullable()->index();
            $table->integer('subscription_package_id')->nullable()->index(); //
            $table->integer('template_id')->nullable();
            $table->integer('custom_template_id')->nullable();
            $table->text('model_name')->nullable();
            $table->longText('content')->nullable();
            $table->bigInteger('words')->nullable();
            $table->bigInteger('prompt_words')->nullable(); //
            $table->bigInteger('completion_words')->nullable(); //
            $table->bigInteger('previous_balance')->nullable(); //
            $table->bigInteger('after_balance')->nullable(); //
            $table->string('resolution')->nullable();
            $table->string('content_type')->nullable()->comment('content/image/code/speech_to_text...')->index();
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
        Schema::dropIfExists('project_logs');
    }
}
