<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrgContextVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgContextVersionTest extends TestCase
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

    public function test_a_context_version_stores_its_profile_and_transcript(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);

        $version = OrgContextVersion::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'rank' => 30,
            'profile' => ['role' => 'Director of Ops', 'rank' => 30, 'frictions' => ['handoffs']],
            'transcript' => [['question' => 'What is your role?', 'answer' => 'Director of Ops']],
        ]);

        $this->assertSame(30, $version->rank);
        $this->assertSame('Director of Ops', $version->profile['role']);
        $this->assertSame('handoffs', $version->profile['frictions'][0]);
        $this->assertSame('Director of Ops', $version->transcript[0]['answer']);
        $this->assertSame($org->id, $version->organization->id);
    }

    public function test_a_context_version_cannot_be_updated(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $org = Organization::create(['domain' => 'acme.com']);
        $version = OrgContextVersion::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'rank' => 30,
            'profile' => ['role' => 'Director of Ops'],
        ]);

        try {
            $version->update(['rank' => 60]);
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
            $this->assertSame(30, (int) OrgContextVersion::find($version->id)->rank);
        }
    }

    public function test_a_context_version_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $org = Organization::create(['domain' => 'acme.com']);
        $version = OrgContextVersion::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'rank' => 30,
            'profile' => ['role' => 'Director of Ops'],
        ]);

        try {
            $version->delete();
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
            $this->assertNotNull(OrgContextVersion::find($version->id));
        }
    }

    public function test_an_organization_points_at_its_active_context(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $org = Organization::create(['domain' => 'acme.com']);
        $version = OrgContextVersion::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'rank' => 50,
            'profile' => ['role' => 'CEO'],
        ]);

        $org->update(['active_context_id' => $version->id]);

        $this->assertSame($version->id, $org->fresh()->activeContext->id);
        $this->assertCount(1, $org->fresh()->versions);
    }

    public function test_a_context_version_cannot_be_mass_updated(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $org = Organization::create(['domain' => 'acme.com']);
        OrgContextVersion::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'rank' => 30,
            'profile' => ['role' => 'Director of Ops'],
        ]);

        $this->expectException(\RuntimeException::class);
        OrgContextVersion::where('organization_id', $org->id)->update(['rank' => 60]);
    }

    public function test_a_context_version_cannot_be_mass_deleted(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $org = Organization::create(['domain' => 'acme.com']);
        OrgContextVersion::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'rank' => 30,
            'profile' => ['role' => 'Director of Ops'],
        ]);

        $this->expectException(\RuntimeException::class);
        OrgContextVersion::where('organization_id', $org->id)->delete();
    }
}
