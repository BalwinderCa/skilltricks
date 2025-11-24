<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Services\Engine\ModelEngine;

class CreateFineTunesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fine_tunes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->comment("owner of the model - user_id")->constrained('users');
            $table->string('title')->comment("NB: aim of the fine-tuning. Ex. Office Assistant");
            $table->enum("model_engine",[ModelEngine::DAVINCI_002, ModelEngine::GPT_3_5_TURBO])->comment("Ex. gpt-3.5-turbo/ davinci");
            $table->string("ft_model_id")->nullable()->comment("Ex. ft-123456789xyz");
            $table->string("file_model_id")->nullable()->comment("Ex. file-123456789xyz");
            $table->tinyInteger("active_status")->default(1)->comment("1 = active, 0 = inactive");
            $table->string("status")->nullable();
            $table->longText("status_details")->nullable();

            $table->longText("file_upload_response")->nullable();
            $table->longText("fine_tune_job_response")->nullable();

            $table->softDeletes();
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
        Schema::dropIfExists('fine_tunes');
    }
}
