<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Leader edits (Features spec, phase 5): every change to a goal's wording. */
    public function up(): void
    {
        Schema::create('goal_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('old_text');
            $table->text('new_text');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_revisions');
    }
};
