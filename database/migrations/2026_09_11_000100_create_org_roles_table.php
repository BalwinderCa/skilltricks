<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('org_roles')) {
            Schema::create('org_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->string('name');
                $table->boolean('can_read')->default(false);
                $table->boolean('can_write')->default(false);
                $table->timestamps();

                // Per organization, not global: two tenants may both have an
                // "Editor", and inside one org two would be indistinguishable.
                $table->unique(['organization_id', 'name']);
            });
        }

        if (! Schema::hasColumn('users', 'org_role_id')) {
            Schema::table('users', function (Blueprint $table) {
                // No FK constraint: OrgRoleController refuses to delete a role
                // that still has holders, so there is nothing for a cascade or a
                // null-on-delete to do -- and users is already referenced by seven
                // constraints that make it awkward to maintain.
                $table->unsignedBigInteger('org_role_id')->nullable()->after('hierarchy_rank');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'org_role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('org_role_id');
            });
        }

        Schema::dropIfExists('org_roles');
    }
};
