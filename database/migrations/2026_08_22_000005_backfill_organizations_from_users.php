<?php

use Database\Migrations\BackfillRunner;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        (new BackfillRunner)->run();
    }

    public function down()
    {
        // Intentionally empty.
        //
        // The obvious rollback — clearing organization_id and hierarchy_rank —
        // has no WHERE clause that can tell a backfilled row from one a real
        // interview populated afterwards. Running it against a live database
        // would de-calibrate every user on the platform, including signups that
        // never went through this migration at all. The organizations and
        // org_context_versions tables are dropped by their own migrations, so
        // rolling those back cleans up regardless; leaving the pointers alone is
        // strictly safer than a rollback that destroys live calibration.
    }
};
