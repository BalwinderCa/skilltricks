<?php

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Super Admin role: whoever holds it manages the organization exactly as
 * its owner does.
 *
 * Marked by a flag rather than by name, so renaming the role on the Roles page
 * cannot quietly strip anyone's access.
 *
 * Existing organizations get the role, and their owner is put on it when they
 * hold no role yet -- an owner who was given one keeps it, and loses nothing:
 * ownership alone still grants everything the role does. Idempotent, since it
 * runs automatically on deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('org_roles')) {
            return;
        }

        if (! Schema::hasColumn('org_roles', 'is_admin')) {
            Schema::table('org_roles', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false);
            });
        }

        $service = app(OrganizationService::class);

        Organization::orderBy('id')->chunkById(100, function ($organizations) use ($service) {
            foreach ($organizations as $org) {
                $role = $service->adminRole($org);

                if ($org->owner_user_id) {
                    User::whereKey($org->owner_user_id)->whereNull('org_role_id')->update(['org_role_id' => $role->id]);
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('org_roles') && Schema::hasColumn('org_roles', 'is_admin')) {
            // The role rows stay: they may be assigned, and a role is harmless
            // without the flag.
            Schema::table('org_roles', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }
};
