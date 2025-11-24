<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiBlogWizardArticleLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_blog_wizard_article_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index();
            $table->integer('ai_blog_wizard_id')->nullable()->index();
            $table->integer('ai_blog_wizard_article_id')->nullable()->index();
            $table->integer('subscription_history_id')->nullable()->index();
            $table->bigInteger('total_words')->default(0);
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
        Schema::dropIfExists('ai_blog_wizard_article_logs');
    }
}
