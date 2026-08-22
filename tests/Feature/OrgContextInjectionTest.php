<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\AI\DocumentContextService;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgContextInjectionTest extends TestCase
{
    use RefreshDatabase;

    private DocumentContextService $docs;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        $this->docs = app(DocumentContextService::class);
    }

    private function calibratedUser(): User
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $user = User::factory()->create([
            'email' => 'ceo@acme.com',
            'user_type' => 'customer',
            'organization_id' => $org->id,
        ]);

        app(OrganizationService::class)->recordContext($org, $user, 50, [
            'role' => 'Chief Executive Officer',
            'rank' => 50,
            'scale' => '4,000 employees across four regions',
            'governance' => 'Quarterly OKRs reviewed by the exec committee',
            'frictions' => ['Slow regional handoffs', 'Unclear ownership of adoption metrics'],
        ]);

        return $user->fresh();
    }

    public function test_the_block_carries_the_active_context(): void
    {
        $block = $this->docs->orgContextBlock($this->calibratedUser());

        $this->assertStringContainsString('ORGANIZATIONAL CONTEXT', $block);
        $this->assertStringContainsString('Chief Executive Officer', $block);
        $this->assertStringContainsString('4,000 employees across four regions', $block);
        $this->assertStringContainsString('Quarterly OKRs', $block);
        $this->assertStringContainsString('Slow regional handoffs', $block);
    }

    public function test_an_uncalibrated_user_yields_an_empty_block(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);

        $this->assertSame('', $this->docs->orgContextBlock($user));
    }

    public function test_an_org_without_an_active_context_yields_an_empty_block(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create(['user_type' => 'customer', 'organization_id' => $org->id]);

        $this->assertSame('', $this->docs->orgContextBlock($user));
    }

    public function test_a_null_user_yields_an_empty_block(): void
    {
        $this->assertSame('', $this->docs->orgContextBlock(null));
    }

    public function test_build_system_message_includes_the_org_block(): void
    {
        $message = $this->docs->buildSystemMessage($this->calibratedUser());

        $this->assertStringContainsString('ORGANIZATIONAL CONTEXT', $message);
        $this->assertStringContainsString('Chief Executive Officer', $message);
        $this->assertStringContainsString('strategy assistant', $message);
    }

    public function test_build_system_message_is_unchanged_for_an_uncalibrated_user(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);

        $this->assertStringNotContainsString('ORGANIZATIONAL CONTEXT', $this->docs->buildSystemMessage($user));
    }
}
