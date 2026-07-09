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
        Schema::table('interventions', function (Blueprint $table) {
            $table->string('intervention_type')->nullable()->after('ai_recommendation'); // drift type that triggered it
            $table->string('priority')->nullable()->after('intervention_type'); // High, Medium, Low
            $table->string('owner')->nullable()->after('priority'); // accountable role
            $table->date('due_date')->nullable()->after('owner');
            $table->json('ranked_interventions')->nullable()->after('due_date'); // Studio-ranked [{priority, action}]
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropColumn(['intervention_type', 'priority', 'owner', 'due_date', 'ranked_interventions']);
        });
    }
};
