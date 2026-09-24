<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Cascading goals to direct reports (Features spec, phase 8 / Notion Epic 3). */
    public function up(): void
    {
        Schema::create('goal_cascades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('goal_cascades')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('assignee_user_id')->index();
            $table->text('text');
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 20)->nullable();
            $table->unsignedTinyInteger('pct')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('progress_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_cascades');
    }
};
