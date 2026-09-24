<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "My goal" card (Features spec, phase 3): each person's response to a
     * published goal, and the obstacles they report against it.
     */
    public function up(): void
    {
        Schema::create('goal_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('decision', 30)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['expected_state_id', 'user_id']);
        });

        Schema::create('goal_obstacles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('body');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_obstacles');
        Schema::dropIfExists('goal_responses');
    }
};
