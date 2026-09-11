<?php

use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Give every existing organization the default ladder, and put every member on
 * the role matching the rank they already hold.
 *
 * Idempotent on both halves: seedDefaultRoles() is a firstOrCreate against the
 * table's unique key, and members are only touched while org_role_id is still
 * null. Re-running changes nothing, which matters because this writes to every
 * organization and every user and runs automatically on deploy.
 *
 * Rank is read, never written. A member's hierarchy_rank decides which role they
 * land on, so nobody's rank -- and therefore nobody's standing in the OI
 * governance election -- moves as a result of this migration.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('org_roles') || ! Schema::hasColumn('users', 'org_role_id')) {
            return;
        }

        $service = app(OrganizationService::class);

        Organization::orderBy('id')->chunkById(100, function ($organizations) use ($service) {
            foreach ($organizations as $org) {
                $service->seedDefaultRoles($org);

                // Keyed by the rung's name -- the seeded roles are named after
                // the ladder, which is what lets a rank find its role without the
                // roles themselves carrying a level.
                $roles = OrgRole::where('organization_id', $org->id)
                    ->whereIn('name', OrganizationService::RANK_LABELS)
                    ->pluck('id', 'name');

                User::where('organization_id', $org->id)
                    ->whereNull('org_role_id')
                    ->whereNotNull('hierarchy_rank')
                    ->chunkById(200, function ($members) use ($roles) {
                        foreach ($members as $member) {
                            $rung = OrganizationService::RANK_LABELS[(int) $member->hierarchy_rank] ?? null;
                            $roleId = $rung ? ($roles[$rung] ?? null) : null;

                            // An off-ladder rank matches no rung. Leaving it null
                            // is correct -- inventing one would be a guess, and
                            // the owner can assign it from the roster.
                            if ($roleId) {
                                $member->forceFill(['org_role_id' => $roleId])->save();
                            }
                        }
                    });
            }
        });
    }

    public function down()
    {
        // Deliberately empty. The roles may have been edited since, and members
        // may have been reassigned by hand; clearing either would destroy work
        // this migration did not do.
    }
};
