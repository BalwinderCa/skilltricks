<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgRankOverrideTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        $this->service = app(OrganizationService::class);
    }

    /** @return array{0: Organization, 1: User, 2: User} */
    private function orgWithOwnerAndMember(): array
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $owner = User::factory()->create(['email' => 'owner@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id]);
        $member = User::factory()->create(['email' => 'member@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id]);
        $org->forceFill(['owner_user_id' => $owner->id])->save();

        return [$org, $owner, $member];
    }

    public function test_the_owner_can_correct_a_members_rank(): void
    {
        [$org, $owner, $member] = $this->orgWithOwnerAndMember();
        $this->service->recordContext($org, $member, 50, ['role' => 'Claimed CEO']);

        $this->service->setMemberRank($owner, $member, 10);

        $this->assertSame(10, (int) $member->fresh()->hierarchy_rank);
    }

    public function test_correcting_a_rank_downward_demotes_their_context(): void
    {
        [$org, $owner, $member] = $this->orgWithOwnerAndMember();
        $this->service->recordContext($org, $owner, 30, ['role' => 'Director']);
        $this->service->recordContext($org, $member, 50, ['role' => 'Claimed CEO']);

        // The overclaimed context is active before the correction.
        $this->assertSame('Claimed CEO', $org->fresh()->activeContext->profile['role']);

        $this->service->setMemberRank($owner, $member, 10);

        // After the correction the highest legitimate rank governs again.
        $this->assertSame('Director', $org->fresh()->activeContext->profile['role']);
        // The overclaimed input itself is still on record — nothing is deleted.
        $this->assertDatabaseHas('org_context_versions', ['user_id' => $member->id, 'rank' => 50]);
    }

    public function test_a_non_owner_cannot_change_a_rank(): void
    {
        [$org, , $member] = $this->orgWithOwnerAndMember();
        $impostor = User::factory()->create(['user_type' => 'customer', 'organization_id' => $org->id]);
        $member->forceFill(['hierarchy_rank' => 20])->save();

        $this->expectException(\RuntimeException::class);
        $this->service->setMemberRank($impostor, $member, 60);
    }

    public function test_an_owner_cannot_change_a_rank_in_another_organization(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();
        $otherOrg = Organization::create(['domain' => 'globex.com']);
        $outsider = User::factory()->create(['user_type' => 'customer', 'organization_id' => $otherOrg->id]);

        $this->expectException(\RuntimeException::class);
        $this->service->setMemberRank($owner, $outsider, 10);
    }

    public function test_an_invalid_rank_is_rejected(): void
    {
        [, $owner, $member] = $this->orgWithOwnerAndMember();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->setMemberRank($owner, $member, 77);
    }

    public function test_the_endpoint_rejects_a_non_owner(): void
    {
        [$org, , $member] = $this->orgWithOwnerAndMember();
        $impostor = User::factory()->create([
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => 10,
        ]);

        $this->actingAs($impostor)
            ->post(route('organization.member-rank'), ['user_id' => $member->id, 'rank' => 60])
            ->assertForbidden();
    }
}
