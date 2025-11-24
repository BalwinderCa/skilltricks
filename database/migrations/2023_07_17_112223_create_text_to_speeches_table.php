<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('text_to_speeches', function (Blueprint $table) {
            $table->id();
            $table->string('language')->nullable();
            $table->string('voice')->nullable();
            $table->string('speed')->nullable();
            $table->string('break')->nullable();
            $table->text('title')->nullable();
            $table->text('slug')->nullable();
            $table->longText('text')->nullable();
            $table->text('response')->nullable();
            $table->text('speech')->nullable();
            $table->text('file_path')->nullable();
            $table->text('hash')->nullable();
            $table->string('credits')->nullable();
            $table->string('words')->nullable();
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
        Schema::dropIfExists('text_to_speeches');
    }
};
