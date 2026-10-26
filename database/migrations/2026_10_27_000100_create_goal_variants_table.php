<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A published goal reworded for one role in the department its resources
     * go to: the same goal, pitched at a technician or at a head of department.
     */
    public function up(): void
    {
        if (Schema::hasTable('goal_variants')) {
            return;
        }

        Schema::create('goal_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->unsignedBigInteger('org_role_id')->index();
            $table->text('text');
            $table->timestamps();
            $table->unique(['expected_state_id', 'org_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_variants');
    }
};
