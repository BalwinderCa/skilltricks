<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiBlogWizardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_blog_wizards', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->longText('uuid');
            $table->bigInteger('total_words')->default(0);
            $table->integer('completed_step')->nullable();
            $table->integer('subscription_history_id')->nullable()->index(); 
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
        Schema::dropIfExists('ai_blog_wizards');
    }
}
