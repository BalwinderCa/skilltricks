<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiBlogWizardImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_blog_wizard_images', function (Blueprint $table) {
            $table->id();
            $table->integer('ai_blog_wizard_id');
            $table->longText('title')->nullable(); 
            $table->longText('value')->nullable();
            $table->string('resolution')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
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
        Schema::dropIfExists('ai_blog_wizard_images');
    }
}
