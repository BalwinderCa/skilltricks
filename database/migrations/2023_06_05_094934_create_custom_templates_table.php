<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_templates', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('custom_template_category_id');
            $table->string('name');
            $table->string('slug');
            $table->string('code');
            $table->longText('description')->nullable();
            $table->longText('fields')->nullable();
            $table->longText('prompt');
            $table->string('icon')->nullable();
            $table->bigInteger('total_words_generated')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->string('created_by')->nullable()->comment('admin');
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
        Schema::dropIfExists('custom_templates');
    }
}
