<?php

use App\Models\ExpectedState;
use App\Services\RoleGoalLinker;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which department a role goal is for, when the role is shared.
 *
 * Two people can hold one role in different departments -- a Vice President in
 * Sales and another in Marketing -- and the chat writes each a goal of their
 * own, titled "Vice President (Sales)". Goals were routed by role alone, so
 * both saw both. Null means the goal is for everyone holding the role.
 *
 * Goals already written with a department in their title are filled in here,
 * through the same code that links new ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expected_states')) {
            return;
        }

        if (! Schema::hasColumn('expected_states', 'department_id')) {
            Schema::table('expected_states', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->index();
            });
        }

        $linker = app(RoleGoalLinker::class);

        ExpectedState::whereNull('department_id')->where('role', 'like', '%(%)')
            ->with('searchUserChat')->chunkById(200, function ($goals) use ($linker) {
                foreach ($goals as $goal) {
                    if ($goal->searchUserChat) {
                        $linker->setDepartment($goal, $goal->searchUserChat);
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('expected_states') && Schema::hasColumn('expected_states', 'department_id')) {
            Schema::table('expected_states', function (Blueprint $table) {
                $table->dropColumn('department_id');
            });
        }
    }
};
