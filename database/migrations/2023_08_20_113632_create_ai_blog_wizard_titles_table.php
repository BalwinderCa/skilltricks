<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiBlogWizardTitlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_blog_wizard_titles', function (Blueprint $table) {
            $table->id();
            $table->integer('ai_blog_wizard_id');
            $table->longText('topic')->nullable();
            $table->longText('keywords')->nullable();
            $table->longText('values')->nullable();
            $table->bigInteger('total_words')->default(0);
            $table->integer('num_of_copies')->default(1);
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
        Schema::dropIfExists('ai_blog_wizard_titles');
    }
}
