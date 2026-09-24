<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where to Begin (Features spec, phase 4): the goal's shared starting
     * options, and each person's pick with its history.
     */
    public function up(): void
    {
        Schema::table('expected_states', function (Blueprint $table) {
            if (! Schema::hasColumn('expected_states', 'starting_options')) {
                $table->json('starting_options')->nullable();
            }
        });
        Schema::table('goal_responses', function (Blueprint $table) {
            $table->string('starting_point', 255)->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->json('starting_history')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('goal_responses', function (Blueprint $table) {
            $table->dropColumn(['starting_point', 'committed_at', 'starting_history']);
        });
        Schema::table('expected_states', function (Blueprint $table) {
            $table->dropColumn('starting_options');
        });
    }
};
