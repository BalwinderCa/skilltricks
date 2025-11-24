<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->integer('template_group_id');
            $table->string('name');
            $table->string('slug');
            $table->string('code');
            $table->longText('description')->nullable();
            $table->longText('fields')->nullable()->default(null);
            $table->bigInteger('icon')->nullable()->default(null);
            $table->bigInteger('total_words_generated')->nullable()->default(0);
            $table->tinyInteger('is_active')->default(1);
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
        Schema::dropIfExists('templates');
    }
}
