<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who someone reports to.
 *
 * Nullable: no manager means they sit at the top of their department's column,
 * which is where everyone starts.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('users', 'manager_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('manager_id')->nullable();
                $table->index('manager_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'manager_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('manager_id');
            });
        }
    }
};
