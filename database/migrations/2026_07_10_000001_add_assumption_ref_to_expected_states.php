<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('expected_states', function (Blueprint $table) {
            // The pathway execution assumption this KPI tests, so Assumption
            // Drift can be evaluated per-KPI instead of chat-wide.
            $table->text('assumption_ref')->nullable()->after('depends_on_id');
        });
    }

    public function down()
    {
        Schema::table('expected_states', function (Blueprint $table) {
            $table->dropColumn('assumption_ref');
        });
    }
};
