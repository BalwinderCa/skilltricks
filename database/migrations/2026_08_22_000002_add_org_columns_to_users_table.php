<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('users', 'organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('company');
                $table->index('organization_id');
            });
        }

        if (! Schema::hasColumn('users', 'hierarchy_rank')) {
            Schema::table('users', function (Blueprint $table) {
                // Null means "not yet calibrated" — this is what the dashboard gate reads.
                $table->integer('hierarchy_rank')->nullable()->after('organization_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'hierarchy_rank')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('hierarchy_rank');
            });
        }

        if (Schema::hasColumn('users', 'organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['organization_id']);
                $table->dropColumn('organization_id');
            });
        }
    }
};
