<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrgMembershipTest extends TestCase
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

    public function test_a_user_belongs_to_an_organization(): void
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $user = User::factory()->create([
            'user_type' => 'customer',
            'organization_id' => $org->id,
            'hierarchy_rank' => 40,
        ]);

        $this->assertSame($org->id, $user->organization->id);
        $this->assertSame(40, (int) $user->fresh()->hierarchy_rank);
        $this->assertCount(1, $org->fresh()->members);
    }

    public function test_a_new_user_starts_uncalibrated(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);

        $this->assertNull($user->organization_id);
        $this->assertNull($user->hierarchy_rank);
    }

    public function test_the_rank_ladder_is_seeded_onto_chat_role_categories(): void
    {
        $ranks = DB::table('chat_role_categories')->pluck('rank', 'name');

        $this->assertSame(50, (int) $ranks['C-Suite']);
        $this->assertSame(60, (int) $ranks['Board']);
        $this->assertSame(10, (int) $ranks['Individual Contributor']);
    }
}
