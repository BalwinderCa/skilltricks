<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds three columns used by the GoalSync wizard:
     *   selected_strategy  - the strategy name the user chose in the wizard
     *   selected_scenario  - the scenario label the user chose
     *   leadership_brief   - the generated Leadership Alignment Brief (markdown)
     *
     * Previously these were added via ALTER TABLE inside request handlers, which
     * caused table-level locks and race conditions under concurrent load.
     */
    public function up()
    {
        Schema::table('search_user_chat', function (Blueprint $table) {
            if (!Schema::hasColumn('search_user_chat', 'selected_strategy')) {
                $table->string('selected_strategy')->nullable()->after('status2');
            }
            if (!Schema::hasColumn('search_user_chat', 'selected_scenario')) {
                $table->string('selected_scenario')->nullable()->after('selected_strategy');
            }
            if (!Schema::hasColumn('search_user_chat', 'leadership_brief')) {
                $table->text('leadership_brief')->nullable()->after('selected_scenario');
            }
        });
    }

    public function down()
    {
        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->dropColumn(['selected_strategy', 'selected_scenario', 'leadership_brief']);
        });
    }
};
