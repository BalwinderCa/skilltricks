<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBlogWizardColumnsToSubscriptionPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_packages', function (Blueprint $table) { 
            if(!Schema::hasColumn($table->getTable(), 'allow_custom_templates')) {
                $table->tinyInteger('allow_custom_templates')->default(0);
            }
            if(!Schema::hasColumn($table->getTable(), 'show_open_ai_model')) {
                $table->tinyInteger('show_open_ai_model')->default(1);
            }
            $table->tinyInteger('allow_blog_wizard')->after('allow_custom_templates')->default(1); 
            $table->tinyInteger('show_blog_wizard')->after('show_custom_templates')->default(1); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            //
        });
    }
}
