<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where someone sits among the people they share a manager with.
 *
 * Zero for everyone until something is reordered, so the chart keeps falling
 * back to rank-then-name until a person is actually dragged into place. Named
 * sort_order rather than position: the latter is a function name in MySQL and
 * reads ambiguously next to the chart's pixel positions.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('users', 'sort_order')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('sort_order')->default(0);
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'sort_order')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
