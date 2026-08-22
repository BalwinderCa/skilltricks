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

    public function test_a_corporate_domain_is_not_treated_as_free(): void
    {
        $org = $this->service->resolveForEmail('someone@notgmail.com');

        $this->assertSame('notgmail.com', $org->domain);
    }
}
