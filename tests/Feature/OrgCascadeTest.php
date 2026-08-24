<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgCascadeTest extends TestCase
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

    private function member(Organization $org, string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'user_type' => 'customer',
            'organization_id' => $org->id,
        ]);
    }

    public function test_the_first_context_becomes_active(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $analyst = $this->member($org, 'analyst@acme.com');

        $version = $this->service->recordContext($org, $analyst, 10, ['role' => 'Business Analyst']);

        $this->assertSame($version->id, $org->fresh()->active_context_id);
    }

    public function test_a_higher_rank_overwrites_the_active_baseline(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $analyst = $this->member($org, 'analyst@acme.com');
        $ceo = $this->member($org, 'ceo@acme.com');

        $draft = $this->service->recordContext($org, $analyst, 10, ['role' => 'Business Analyst']);
        $executive = $this->service->recordContext($org, $ceo, 50, ['role' => 'CEO']);

        $this->assertSame($executive->id, $org->fresh()->active_context_id);
        $this->assertSame('CEO', $org->fresh()->activeContext->profile['role']);

        // The superseded draft is still readable — nothing is hard-deleted.
        $this->assertDatabaseHas('org_context_versions', ['id' => $draft->id, 'rank' => 10]);
        $this->assertCount(2, $org->fresh()->versions);
    }

    public function test_a_lower_rank_does_not_overwrite_the_active_baseline(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $ceo = $this->member($org, 'ceo@acme.com');
        $analyst = $this->member($org, 'analyst@acme.com');

        $executive = $this->service->recordContext($org, $ceo, 50, ['role' => 'CEO']);
        $junior = $this->service->recordContext($org, $analyst, 10, ['role' => 'Business Analyst']);

        $this->assertSame($executive->id, $org->fresh()->active_context_id);
        $this->assertNotSame($junior->id, $org->fresh()->active_context_id);
    }

    public function test_a_lower_rank_still_persists_its_own_input(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $ceo = $this->member($org, 'ceo@acme.com');
        $analyst = $this->member($org, 'analyst@acme.com');

        $this->service->recordContext($org, $ceo, 50, ['role' => 'CEO']);
        $junior = $this->service->recordContext($org, $analyst, 10, ['role' => 'Business Analyst']);

        // "Never lose data": the input is stored even though it does not govern.
        $this->assertDatabaseHas('org_context_versions', [
            'id' => $junior->id,
            'user_id' => $analyst->id,
            'rank' => 10,
        ]);
    }

    public function test_an_equal_rank_refreshes_the_active_baseline(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $first = $this->member($org, 'vp1@acme.com');
        $second = $this->member($org, 'vp2@acme.com');

        $this->service->recordContext($org, $first, 40, ['role' => 'VP Sales']);
        $newer = $this->service->recordContext($org, $second, 40, ['role' => 'VP Product']);

        $this->assertSame($newer->id, $org->fresh()->active_context_id);
    }

    public function test_recording_a_context_sets_the_users_rank(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $ceo = $this->member($org, 'ceo@acme.com');

        $this->service->recordContext($org, $ceo, 50, ['role' => 'CEO']);

        $this->assertSame(50, (int) $ceo->fresh()->hierarchy_rank);
    }

    public function test_an_invalid_rank_is_rejected_and_writes_nothing(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = $this->member($org, 'someone@acme.com');

        try {
            $this->service->recordContext($org, $user, 99, ['role' => 'Emperor']);
            $this->fail('Expected an InvalidArgumentException for rank 99.');
        } catch (\InvalidArgumentException $e) {
            // Validation runs before the transaction, so nothing may have been written.
            $this->assertDatabaseCount('org_context_versions', 0);
            $this->assertNull($user->fresh()->hierarchy_rank);
            $this->assertNull($org->fresh()->active_context_id);
        }
    }

    public function test_the_interview_transcript_is_persisted(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = $this->member($org, 'ceo@acme.com');

        $transcript = [
            ['question' => 'What is your role?', 'answer' => 'CEO'],
            ['question' => 'How large is the org?', 'answer' => '4,000 people'],
        ];

        $version = $this->service->recordContext($org, $user, 50, ['role' => 'CEO'], $transcript);

        $this->assertSame($transcript, $version->fresh()->transcript);
    }

    public function test_two_organizations_do_not_see_each_others_context(): void
    {
        $acme = Organization::create(['domain' => 'acme.com']);
        $globex = Organization::create(['domain' => 'globex.com']);

        $acmeCeo = $this->member($acme, 'ceo@acme.com');
        $globexCeo = $this->member($globex, 'ceo@globex.com');

        $this->service->recordContext($acme, $acmeCeo, 50, ['role' => 'Acme CEO']);
        $this->service->recordContext($globex, $globexCeo, 50, ['role' => 'Globex CEO']);

        $this->assertSame('Acme CEO', $acme->fresh()->activeContext->profile['role']);
        $this->assertSame('Globex CEO', $globex->fresh()->activeContext->profile['role']);
        $this->assertCount(1, $acme->fresh()->versions);
    }
}
