<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resources are committed per goal, not per department. A row with a goal
 * takes its department from that goal. Rows without one are the earlier
 * per-department allocations, kept as they were.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('strategy_resources') || Schema::hasColumn('strategy_resources', 'expected_state_id')) {
            return;
        }

        Schema::table('strategy_resources', function (Blueprint $table) {
            $table->unsignedBigInteger('expected_state_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('strategy_resources', 'expected_state_id')) {
            Schema::table('strategy_resources', function (Blueprint $table) {
                $table->dropIndex(['expected_state_id']);
                $table->dropColumn('expected_state_id');
            });
        }
    }
};
