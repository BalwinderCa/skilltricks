<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The backfill writes to every organization and every user and runs
 * automatically on deploy, so what it must never do matters more than what it
 * does: move somebody's rank, or double up on a second run.
 */
class OrgRoleBackfillTest extends TestCase
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

    /** Run the migration itself, not a copy of its logic. */
    private function runBackfill(): void
    {
        $migration = require database_path('migrations/2026_09_11_000101_backfill_org_roles.php');
        $migration->up();
    }

    /** An organization as it looked before this feature: ranks, no roles. */
    private function legacyOrg(): Organization
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);

        foreach ([50 => 'owner', 20 => 'manager', 10 => 'ic'] as $rank => $slug) {
            User::factory()->create([
                'email' => "{$slug}@acme.com", 'user_type' => 'customer',
                'organization_id' => $org->id, 'hierarchy_rank' => $rank,
            ]);
        }

        return $org;
    }

    public function test_it_seeds_the_ladder_and_places_every_member(): void
    {
        $org = $this->legacyOrg();
        $this->assertSame(0, $org->roles()->count());

        $this->runBackfill();

        $this->assertSame(6, $org->roles()->count());

        foreach (User::where('organization_id', $org->id)->get() as $member) {
            $this->assertSame(
                OrganizationService::RANK_LABELS[(int) $member->hierarchy_rank],
                $member->orgRole->name,
                "{$member->email} landed on the wrong role"
            );
        }
    }

    public function test_it_moves_nobody_rank(): void
    {
        $org = $this->legacyOrg();
        $before = User::where('organization_id', $org->id)->pluck('hierarchy_rank', 'id');

        $this->runBackfill();

        $this->assertEquals($before, User::where('organization_id', $org->id)->pluck('hierarchy_rank', 'id'));
    }

    public function test_running_it_twice_changes_nothing(): void
    {
        $org = $this->legacyOrg();

        $this->runBackfill();
        $first = User::where('organization_id', $org->id)->pluck('org_role_id', 'id');

        $this->runBackfill();

        $this->assertSame(6, $org->roles()->count());
        $this->assertEquals($first, User::where('organization_id', $org->id)->pluck('org_role_id', 'id'));
    }

    public function test_it_leaves_a_hand_assigned_role_alone(): void
    {
        $org = $this->legacyOrg();
        $this->runBackfill();

        // Somebody the owner has since moved to a role that is not their rung.
        $member = User::where('email', 'ic@acme.com')->first();
        $editor = OrgRole::create([
            'organization_id' => $org->id, 'name' => 'Editor', 'level' => 10, 'can_read' => true,
        ]);
        $member->forceFill(['org_role_id' => $editor->id])->save();

        $this->runBackfill();

        $this->assertSame($editor->id, $member->fresh()->org_role_id);
    }

    public function test_an_unranked_member_is_left_without_a_role(): void
    {
        $org = $this->legacyOrg();
        $unranked = User::factory()->create([
            'email' => 'new@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => null,
        ]);

        $this->runBackfill();

        $this->assertNull($unranked->fresh()->org_role_id);
    }

    public function test_an_off_ladder_rank_is_left_without_a_role(): void
    {
        $org = $this->legacyOrg();
        $odd = User::factory()->create([
            'email' => 'odd@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 35,
        ]);

        $this->runBackfill();

        // Guessing a role for an unrecognised rank would be worse than none.
        $this->assertNull($odd->fresh()->org_role_id);
        $this->assertSame(35, (int) $odd->fresh()->hierarchy_rank);
    }
}
