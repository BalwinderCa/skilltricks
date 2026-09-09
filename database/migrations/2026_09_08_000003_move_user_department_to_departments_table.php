<?php

use App\Models\Department;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the free-text users.department into a row per department.
 *
 * The string column goes: two places holding the same fact drift, and every
 * read would have to decide which one wins.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable();
                $table->index('department_id');
            });
        }

        if (Schema::hasColumn('users', 'department')) {
            $this->backfill();

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }
    }

    /** One row per distinct (organization, department name) that is actually in use. */
    private function backfill(): void
    {
        $pairs = DB::table('users')
            ->whereNotNull('organization_id')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->select('organization_id', 'department')
            ->distinct()
            ->orderBy('organization_id')
            ->orderBy('department')
            ->get();

        $seen = [];

        foreach ($pairs as $pair) {
            $orgId = (int) $pair->organization_id;
            $seen[$orgId] = ($seen[$orgId] ?? -1) + 1;

            $id = DB::table('departments')->insertGetId([
                'organization_id' => $orgId,
                'name' => $pair->department,
                'color' => Department::PALETTE[$seen[$orgId] % count(Department::PALETTE)],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')
                ->where('organization_id', $orgId)
                ->where('department', $pair->department)
                ->update(['department_id' => $id]);
        }
    }

    public function down()
    {
        if (! Schema::hasColumn('users', 'department')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('department')->nullable();
            });

            // Names are recoverable from the rows the up() built, so the drop
            // above is not a one-way door.
            foreach (DB::table('departments')->get() as $department) {
                DB::table('users')->where('department_id', $department->id)
                    ->update(['department' => $department->name]);
            }
        }

        if (Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('department_id');
            });
        }
    }
};
