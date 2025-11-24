<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IsBlogPublishedAtTableAiBlogWizard extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ai_blog_wizards', function (Blueprint $table) {
            $table->tinyInteger("is_blog_published")->default(0)->comment("1 = yes, 0 = no")->after("id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ai_blog_wizards', function (Blueprint $table) {
            $table->dropColumn(["is_blog_published"]);
        });
    }
}
