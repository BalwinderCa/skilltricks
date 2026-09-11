<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
    }

    /** @return array{0: Organization, 1: User, 2: User} */
    private function orgWithOwnerAndMember(string $domain = 'acme.com'): array
    {
        $org = Organization::create(['domain' => $domain, 'name' => 'Acme']);
        app(OrganizationService::class)->seedDefaultRoles($org);

        $owner = User::factory()->create([
            'email' => "owner@{$domain}", 'user_type' => 'customer', 'email_verified_at' => now(),
            'organization_id' => $org->id, 'hierarchy_rank' => 50,
        ]);
        $member = User::factory()->create([
            'email' => "member@{$domain}", 'user_type' => 'customer', 'email_verified_at' => now(),
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $org->forceFill(['owner_user_id' => $owner->id])->save();

        return [$org, $owner, $member];
    }

    public function test_an_organization_starts_with_the_six_default_roles(): void
    {
        [$org] = $this->orgWithOwnerAndMember();

        $this->assertEqualsCanonicalizing(
            array_values(OrganizationService::RANK_LABELS),
            $org->roles()->pluck('name')->all()
        );
    }

    public function test_seeding_twice_adds_nothing(): void
    {
        [$org] = $this->orgWithOwnerAndMember();

        app(OrganizationService::class)->seedDefaultRoles($org);

        $this->assertSame(6, $org->roles()->count());
    }

    public function test_the_owner_adds_a_role(): void
    {
        [$org, $owner] = $this->orgWithOwnerAndMember();

        $this->actingAs($owner)->post(route('organization.roles.store'), [
            'name' => 'Editor', 'can_read' => 1, 'can_write' => 1,
        ])->assertRedirect();

        $role = OrgRole::where('organization_id', $org->id)->where('name', 'Editor')->first();

        $this->assertNotNull($role);
        $this->assertTrue($role->can_read);
        $this->assertTrue($role->can_write);
    }

    public function test_the_roles_page_renders_for_the_owner(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();

        $response = $this->actingAs($owner)->get(route('organization.roles.index'));

        $response->assertOk();
        $response->assertSee('Individual Contributor');
        $response->assertSee('Board');
        // The permission checkboxes and the add/edit dialog both render.
        $response->assertSee('data-role-permission', false);
        $response->assertSee('roleDialog', false);
        // Roles carry no seniority: there is nowhere to set a level.
        $response->assertDontSee('roleLevel', false);
    }

    public function test_the_teams_page_still_renders_with_the_role_select(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();

        $response = $this->actingAs($owner)->get(route('organization.index'));

        $response->assertOk();
        // The member dialog's Role select is fed by the organization's roles now.
        $response->assertSee('org_role_id', false);
    }

    public function test_a_member_cannot_write_roles(): void
    {
        [, , $member] = $this->orgWithOwnerAndMember();

        $this->actingAs($member)->get(route('organization.roles.index'))->assertForbidden();
        $this->actingAs($member)->post(route('organization.roles.store'), [
            'name' => 'Sneaky',
        ])->assertForbidden();
    }

    public function test_the_owner_renames_a_role(): void
    {
        [$org, $owner] = $this->orgWithOwnerAndMember();
        $role = $org->roles()->where('name', 'Manager')->first();

        $this->actingAs($owner)->post(route('organization.roles.update'), [
            'role_id' => $role->id, 'name' => 'Team Lead',
        ])->assertRedirect();

        $this->assertSame('Team Lead', $role->fresh()->name);
    }

    public function test_a_duplicate_name_in_one_organization_is_rejected(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();

        $this->actingAs($owner)->post(route('organization.roles.store'), [
            'name' => 'Manager',
        ])->assertSessionHasErrors('name');
    }

    public function test_the_same_name_in_two_organizations_is_fine(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();
        [$globex] = $this->orgWithOwnerAndMember('globex.com');

        $this->actingAs($owner)->post(route('organization.roles.store'), [
            'name' => 'Editor',
        ])->assertRedirect();

        // Seeded separately, so both ladders already carry the same six names.
        $this->assertSame(6, $globex->roles()->count());
    }

    public function test_a_role_from_another_organization_is_a_404(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();
        [$globex] = $this->orgWithOwnerAndMember('globex.com');

        $foreign = $globex->roles()->first();

        $this->actingAs($owner)->post(route('organization.roles.update'), [
            'role_id' => $foreign->id, 'name' => 'Hijacked',
        ])->assertNotFound();

        $this->assertNotSame('Hijacked', $foreign->fresh()->name);
    }

    public function test_toggling_a_permission_persists(): void
    {
        [$org, $owner] = $this->orgWithOwnerAndMember();
        $role = $org->roles()->where('name', 'Manager')->first();

        $this->assertFalse($role->can_write);

        $this->actingAs($owner)->post(route('organization.roles.permission'), [
            'role_id' => $role->id, 'permission' => 'write', 'value' => 1,
        ])->assertOk();

        $this->assertTrue($role->fresh()->can_write);
    }

    public function test_an_unknown_permission_is_rejected(): void
    {
        [$org, $owner] = $this->orgWithOwnerAndMember();
        $role = $org->roles()->first();

        $this->actingAs($owner)->post(route('organization.roles.permission'), [
            'role_id' => $role->id, 'permission' => 'delete', 'value' => 1,
        ])->assertSessionHasErrors('permission');
    }

    public function test_a_role_in_use_cannot_be_deleted(): void
    {
        [$org, $owner, $member] = $this->orgWithOwnerAndMember();
        $role = $org->roles()->where('name', 'Manager')->first();
        $member->forceFill(['org_role_id' => $role->id])->save();

        $this->actingAs($owner)->post(route('organization.roles.destroy'), ['role_id' => $role->id])
            ->assertRedirect();

        $this->assertNotNull($role->fresh(), 'a role with holders must survive');
    }

    public function test_an_unused_role_is_deleted(): void
    {
        [$org, $owner] = $this->orgWithOwnerAndMember();
        $role = $org->roles()->where('name', 'Board')->first();

        $this->actingAs($owner)->post(route('organization.roles.destroy'), ['role_id' => $role->id])
            ->assertRedirect();

        $this->assertNull($role->fresh());
    }
}
