<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('expected_states', function (Blueprint $table) {
            // "Review in Detail" calibration: the user's revised scope, KPI and
            // date overwrite recommended_action / success_metric / target_value /
            // target_date so every existing drift calculation runs against the
            // new baseline unchanged. ai_original keeps the pre-revision values
            // so Studio can report Human-AI alignment variance.
            $table->json('ai_original')->nullable()->after('assumption_ref');
            $table->json('constraint_tags')->nullable()->after('ai_original');
            $table->text('revision_notes')->nullable()->after('constraint_tags');
            $table->unsignedBigInteger('revised_by')->nullable()->after('revision_notes');
            $table->string('revised_by_name')->nullable()->after('revised_by');
            $table->string('revised_by_role')->nullable()->after('revised_by_name');
            $table->timestamp('revised_at')->nullable()->after('revised_by_role');
        });
    }

    public function down()
    {
        Schema::table('expected_states', function (Blueprint $table) {
            $table->dropColumn([
                'ai_original', 'constraint_tags', 'revision_notes',
                'revised_by', 'revised_by_name', 'revised_by_role', 'revised_at',
            ]);
        });
    }
};
