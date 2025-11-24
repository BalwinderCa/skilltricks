<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_system')->nullable()->default(false);
            $table->tinyInteger('is_active')->default(1);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('is_delete')->nullable()->default(1);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $columns = ['is_system', 'is_active', 'created_by', 'updated_by', 'is_delete'];
            $table->dropColumn($columns);
        });
        Schema::table('users', function (Blueprint $table) {
            $userColumns = ['created_by', 'updated_by'];
            $table->dropColumn($userColumns);
        });
    }
}
