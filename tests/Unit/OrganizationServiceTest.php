<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationServiceTest extends TestCase
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

    public function test_two_users_on_the_same_corporate_domain_share_an_organization(): void
    {
        $first = $this->service->resolveForEmail('anoop@acme.com');
        $second = $this->service->resolveForEmail('raghu@acme.com');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('acme.com', $first->domain);
        $this->assertSame(1, Organization::count());
    }

    public function test_domains_are_normalised_for_case_and_whitespace(): void
    {
        $first = $this->service->resolveForEmail('anoop@acme.com');
        $second = $this->service->resolveForEmail('  Raghu@ACME.CoM  ');

        $this->assertSame($first->id, $second->id);
    }

    public function test_free_domain_users_are_isolated_from_each_other(): void
    {
        $first = $this->service->resolveForEmail('alice@gmail.com');
        $second = $this->service->resolveForEmail('bob@gmail.com');

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('alice@gmail.com', $first->domain);
        $this->assertSame('bob@gmail.com', $second->domain);
    }

    public function test_malformed_addresses_do_not_share_an_organization(): void
    {
        // Every one of these previously keyed to domain '' and collided into a
        // single shared org — the cross-tenant merge this boundary must prevent.
        $a = $this->service->resolveForEmail('alice@');
        $b = $this->service->resolveForEmail('bob@');
        $c = $this->service->resolveForEmail('@');

        $this->assertNotSame($a->id, $b->id);
        $this->assertNotSame($b->id, $c->id);
        $this->assertNotSame($a->id, $c->id);
        $this->assertSame(3, Organization::count());
    }

    public function test_an_empty_address_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->resolveForEmail('   ');
    }

    public function test_a_trailing_dot_does_not_split_an_organization(): void
    {
        $withDot = $this->service->resolveForEmail('anoop@acme.com.');
        $without = $this->service->resolveForEmail('raghu@acme.com');

        $this->assertSame($withDot->id, $without->id);
    }

    public function test_a_corporate_domain_is_not_treated_as_free(): void
    {
        $org = $this->service->resolveForEmail('someone@notgmail.com');

        $this->assertSame('notgmail.com', $org->domain);
    }
}
