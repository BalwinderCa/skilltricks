<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateElevenLabsModelVoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eleven_labs_model_voices', function (Blueprint $table) {
            $table->id();
            $table->string('voice_id');
            $table->string('name')->nullable();
            $table->string('accent')->nullable();
            $table->string('description')->nullable();
            $table->string('use_case')->nullable();
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
        Schema::dropIfExists('eleven_labs_model_voices');
    }
}
