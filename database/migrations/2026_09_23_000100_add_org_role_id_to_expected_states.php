<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role goals linked to real roles (Features spec, phase 2). The AI's role
     * text stays in `role`; this is the link to the organization's own role.
     */
    public function up(): void
    {
        Schema::table('expected_states', function (Blueprint $table) {
            if (! Schema::hasColumn('expected_states', 'org_role_id')) {
                $table->unsignedBigInteger('org_role_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expected_states', function (Blueprint $table) {
            $table->dropIndex(['org_role_id']);
            $table->dropColumn('org_role_id');
        });
    }
};
