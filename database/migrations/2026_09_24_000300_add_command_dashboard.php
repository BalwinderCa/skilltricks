<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executive command dashboard (Features spec, phase 6): progress telemetry,
     * the drift index, cached recourse, and the owner's savings assumptions.
     */
    public function up(): void
    {
        Schema::table('goal_responses', function (Blueprint $table) {
            $table->string('progress_status', 20)->nullable();
            $table->unsignedTinyInteger('progress_pct')->nullable();
            $table->text('progress_note')->nullable();
            $table->timestamp('progress_at')->nullable();
        });

        Schema::create('goal_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('status', 20);
            $table->unsignedTinyInteger('pct');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->decimal('drift_index', 6, 2)->nullable();
            $table->string('drift_level', 10)->nullable();
            $table->string('drift_alerted_level', 10)->nullable();
            $table->timestamp('drift_checked_at')->nullable();
            $table->json('recourse')->nullable();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->json('command_settings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', fn (Blueprint $table) => $table->dropColumn('command_settings'));
        Schema::table('search_user_chat', fn (Blueprint $table) => $table->dropColumn(['drift_index', 'drift_level', 'drift_alerted_level', 'drift_checked_at', 'recourse']));
        Schema::dropIfExists('goal_progress_updates');
        Schema::table('goal_responses', fn (Blueprint $table) => $table->dropColumn(['progress_status', 'progress_pct', 'progress_note', 'progress_at']));
    }
};
