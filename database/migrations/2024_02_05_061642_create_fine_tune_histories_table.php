<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFineTuneHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fine_tune_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId("fine_tune_id")->constrained("fine_tunes");
            $table->string("ft_model_id")->nullable()->comment("Ex. ft-123456789xyz");
            $table->string("file_model_id")->nullable()->comment("Ex. file-123456789xyz");
            $table->string('title')->comment("NB: aim of the fine-tuning. Ex. Office Assistant");
            $table->longText('description')->comment("NB: Description of the fine-tuning. Ex. Office Assistant Prompt & Answering");
            $table->string("trained_file_path")->comment("Trained JSONL file path");
            $table->longText("training_data")->nullable()->comment("JSON L fine contents");
            $table->string("status")->nullable();
            $table->longText("status_details")->nullable();

            $table->longText("file_upload_response")->nullable();
            $table->longText("fine_tune_job_response")->nullable();

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
        Schema::dropIfExists('fine_tune_histories');
    }
}
