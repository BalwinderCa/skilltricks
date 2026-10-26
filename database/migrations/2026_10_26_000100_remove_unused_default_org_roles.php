<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The six ladder roles every organization used to be seeded with. Written
    // out rather than read from OrganizationService so this migration keeps
    // meaning what it meant if those labels are ever renamed.
    private const DEFAULTS = ['Board', 'C-Suite', 'Vice President', 'Director', 'Manager', 'Individual Contributor'];

    /**
     * Organizations no longer start with the default roles; remove the ones
     * already seeded. A default that a member holds or a goal points at stays:
     * deleting it would silently strip someone's role or orphan the goal.
     * The Super Admin role is never touched.
     */
    public function up()
    {
        if (! Schema::hasTable('org_roles')) {
            return;
        }

        $query = DB::table('org_roles')
            ->whereIn('name', self::DEFAULTS)
            ->where(fn ($q) => Schema::hasColumn('org_roles', 'is_admin') ? $q->where('is_admin', false) : $q)
            ->whereNotExists(fn ($q) => $q->from('users')->whereColumn('users.org_role_id', 'org_roles.id'));

        if (Schema::hasColumn('expected_states', 'org_role_id')) {
            $query->whereNotExists(fn ($q) => $q->from('expected_states')->whereColumn('expected_states.org_role_id', 'org_roles.id'));
        }

        $query->delete();
    }

    // Not restored: an organization that wants these roles adds them on the
    // Roles page, and recreating them everywhere would undo the point.
    public function down() {}
};
