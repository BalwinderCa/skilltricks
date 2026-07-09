<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('drift_events', function (Blueprint $table) {
            $table->unsignedBigInteger('observed_state_id')->nullable()->after('expected_state_id');
            $table->decimal('gap', 12, 2)->nullable()->after('magnitude'); // expected - observed
            $table->decimal('progress', 5, 2)->nullable()->after('gap'); // 0.00–1.00 achievement rate
            $table->string('status')->nullable()->after('severity'); // On Track, At Risk, Overdue
            $table->string('assumption_status')->nullable()->after('status'); // Holding, At Risk
            $table->json('roles_impacted')->nullable()->after('assumption_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('drift_events', function (Blueprint $table) {
            $table->dropColumn(['observed_state_id', 'gap', 'progress', 'status', 'assumption_status', 'roles_impacted']);
        });
    }
};
