<?php

use Database\Migrations\BackfillRunner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        (new BackfillRunner)->run();
    }

    public function down()
    {
        // organizations and org_context_versions are dropped by their own
        // migrations; only the pointers on users need clearing here.
        DB::table('users')->update(['organization_id' => null, 'hierarchy_rank' => null]);
    }
};
