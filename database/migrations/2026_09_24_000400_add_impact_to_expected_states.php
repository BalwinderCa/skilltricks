<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Impact ranking and action weights (Features spec, phase 7 / Notion Epic 1). */
    public function up(): void
    {
        Schema::table('expected_states', function (Blueprint $table) {
            $table->unsignedTinyInteger('impact_score')->nullable();
            $table->string('impact_reason', 300)->nullable();
            $table->unsignedTinyInteger('weight')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('expected_states', function (Blueprint $table) {
            $table->dropColumn(['impact_score', 'impact_reason', 'weight']);
        });
    }
};
