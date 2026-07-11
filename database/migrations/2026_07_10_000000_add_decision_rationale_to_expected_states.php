<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('expected_states', function (Blueprint $table) {
            // Why a role rejected or deferred the Studio action — the
            // "Decision Audit Trail" the spec asks for on "Not viable for us".
            $table->text('decision_rationale')->nullable()->after('decision');
            $table->timestamp('decided_at')->nullable()->after('decision_rationale');
        });
    }

    public function down()
    {
        Schema::table('expected_states', function (Blueprint $table) {
            $table->dropColumn(['decision_rationale', 'decided_at']);
        });
    }
};
