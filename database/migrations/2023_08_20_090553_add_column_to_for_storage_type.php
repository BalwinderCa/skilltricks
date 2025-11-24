<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToForStorageType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('text_to_speeches', function (Blueprint $table) {          
            $table->string('storage_type')->nullable()->default('local');            
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->string('storage_type')->nullable()->default('local');            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('text_to_speeches', function (Blueprint $table) {
            $columns = ['storage_type'];
            $table->dropColumn($columns);
        });
        Schema::table('projects', function (Blueprint $table) {
            $columns = ['storage_type'];
            $table->dropColumn($columns);
        });
    }
}
