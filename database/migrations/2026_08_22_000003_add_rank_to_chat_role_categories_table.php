<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The six bands the onboarding agent maps a stated role onto. Seeded onto the
     * existing chat_role_categories table so the platform keeps one notion of
     * "role" rather than growing a second, competing one.
     */
    private const LADDER = [
        'Individual Contributor' => 10,
        'Manager' => 20,
        'Director' => 30,
        'Vice President' => 40,
        'C-Suite' => 50,
        'Board' => 60,
    ];

    public function up()
    {
        if (! Schema::hasColumn('chat_role_categories', 'rank')) {
            Schema::table('chat_role_categories', function (Blueprint $table) {
                $table->integer('rank')->nullable();
            });
        }

        foreach (self::LADDER as $name => $rank) {
            $exists = DB::table('chat_role_categories')->where('name', $name)->exists();

            if ($exists) {
                DB::table('chat_role_categories')->where('name', $name)->update(['rank' => $rank]);

                continue;
            }

            DB::table('chat_role_categories')->insert([
                'name' => $name,
                'rank' => $rank,
                'status' => 1,
                'created_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Deliberately does NOT delete rows. up() cannot distinguish, on rollback,
        // a category it inserted from one that already existed, and destroying a
        // pre-existing row is far worse than leaving six harmless standard ones.
        // Dropping the column removes everything this migration actually added.
        if (Schema::hasColumn('chat_role_categories', 'rank')) {
            Schema::table('chat_role_categories', function (Blueprint $table) {
                $table->dropColumn('rank');
            });
        }
    }
};
