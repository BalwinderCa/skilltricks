<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who leads a department, when it should not be inferred.
 *
 * Nullable on purpose: with no head chosen the chart still falls back to the
 * highest-ranked member, so this is an override rather than a requirement.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('departments', 'head_user_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->unsignedBigInteger('head_user_id')->nullable();
                $table->index('head_user_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('departments', 'head_user_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('head_user_id');
            });
        }
    }
};
