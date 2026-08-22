<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgRegistrationTest extends TestCase
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

    public function test_the_first_registrant_on_a_domain_owns_the_organization(): void
    {
        $service = app(OrganizationService::class);
        $org = $service->resolveForEmail('anoop@acme.com');

        $first = User::factory()->create(['email' => 'anoop@acme.com', 'user_type' => 'customer']);
        $service->attachUser($first, $org);

        $this->assertSame($org->id, $first->fresh()->organization_id);
        $this->assertSame($first->id, $org->fresh()->owner_user_id);
    }

    public function test_a_later_registrant_joins_without_taking_ownership(): void
    {
        $service = app(OrganizationService::class);
        $org = $service->resolveForEmail('anoop@acme.com');

        $first = User::factory()->create(['email' => 'anoop@acme.com', 'user_type' => 'customer']);
        $service->attachUser($first, $org);

        $second = User::factory()->create(['email' => 'raghu@acme.com', 'user_type' => 'customer']);
        $service->attachUser($second, $org);

        $this->assertSame($org->id, $second->fresh()->organization_id);
        $this->assertSame($first->id, $org->fresh()->owner_user_id);
    }

    public function test_the_organization_name_is_seeded_from_the_first_members_company(): void
    {
        $service = app(OrganizationService::class);
        $org = $service->resolveForEmail('anoop@acme.com');

        $first = User::factory()->create([
            'email' => 'anoop@acme.com',
            'user_type' => 'customer',
            'company_name' => 'Acme Corporation',
        ]);
        $service->attachUser($first, $org);

        $this->assertSame('Acme Corporation', $org->fresh()->name);
    }

    public function test_a_user_without_an_email_gets_an_isolated_organization(): void
    {
        // Registration allows phone-only signup, so email may be null. Such a
        // user must still get an organization, and must not share one with any
        // other email-less user.
        $service = app(OrganizationService::class);

        $first = User::factory()->create(['email' => null, 'phone' => '+15550001', 'user_type' => 'customer']);
        $second = User::factory()->create(['email' => null, 'phone' => '+15550002', 'user_type' => 'customer']);

        $orgOne = $service->resolveForUser($first);
        $orgTwo = $service->resolveForUser($second);

        $this->assertNotSame($orgOne->id, $orgTwo->id);
        $this->assertSame('user:'.$first->id, $orgOne->domain);
    }

    public function test_a_phone_only_registration_gets_an_isolated_organization(): void
    {
        // Registration allows signup with no email at all. This exercises the real
        // controller path, not just the service, because that wiring is where an
        // empty address would otherwise reach resolveForEmail() and throw.
        $this->post(route('register'), [
            'name' => 'Phone Only',
            'phone' => '+15550100',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $user = User::where('phone', 'like', '%5550100%')->first();

        $this->assertNotNull($user, 'Phone-only registration did not create the user.');
        $this->assertNotNull($user->organization_id, 'Phone-only user got no organization.');
        $this->assertSame('user:'.$user->id, Organization::find($user->organization_id)->domain);
    }

    public function test_a_registered_user_is_attached_to_an_organization(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Anoop Kumar',
            'email' => 'anoop@newco.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $user = User::where('email', 'anoop@newco.com')->first();

        $this->assertNotNull($user, 'Registration did not create the user.');
        $this->assertNotNull($user->organization_id, 'Registration did not attach an organization.');
        $this->assertSame('newco.com', Organization::find($user->organization_id)->domain);
        $this->assertNull($user->hierarchy_rank, 'Rank is set at interview confirmation, not registration.');
    }
}
