<?php

namespace Tests\Feature;

use App\Http\Controllers\Backend\CustomersController;
use App\Models\Organization;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
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

    public function test_a_social_login_signup_is_attached_to_an_organization(): void
    {
        // OnboardingController::confirm() would catch an unattached user, but by
        // then ownership of the domain has gone to whoever finished calibrating
        // first — not to whoever registered first, which is the spec's rule.
        $earlier = User::factory()->create(['email' => 'earlier@socialco.com', 'user_type' => 'customer']);
        app(OrganizationService::class)->attachUser($earlier, app(OrganizationService::class)->resolveForUser($earlier));

        $socialUser = new SocialiteUser;
        $socialUser->id = 'provider-abc';
        $socialUser->name = 'Social Sam';
        $socialUser->email = 'sam@socialco.com';

        $driver = \Mockery::mock(GithubProvider::class);
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($socialUser);
        Socialite::shouldReceive('driver')->with('github')->andReturn($driver);

        $this->get(route('social.callback', 'github'));

        $user = User::where('email', 'sam@socialco.com')->first();

        $this->assertNotNull($user, 'Social login did not create the user.');
        $this->assertNotNull($user->organization_id, 'Social login did not attach an organization.');
        $this->assertSame('socialco.com', Organization::find($user->organization_id)->domain);
        $this->assertSame(
            $earlier->id,
            (int) Organization::find($user->organization_id)->owner_user_id,
            'Ownership must stay with the earlier registrant.'
        );
    }

    public function test_an_admin_created_customer_is_attached_to_an_organization(): void
    {
        $earlier = User::factory()->create(['email' => 'earlier@adminco.com', 'user_type' => 'customer']);
        app(OrganizationService::class)->attachUser($earlier, app(OrganizationService::class)->resolveForUser($earlier));

        $admin = User::factory()->create(['user_type' => 'admin', 'email_verified_at' => now()]);

        $package = SubscriptionPackage::create([
            'title' => 'Starter',
            'slug' => 'starter-'.time(),
            'description' => 'Test',
            'package_type' => 'monthly',
            'price' => 0,
            'is_active' => 1,
            'openai_model_id' => 5,
        ]);

        // The subscription-history side of store() is stubbed: it fails on a
        // freshly migrated database for an unrelated, pre-existing reason
        // (subscription_histories.active_by is NOT NULL with no default and is
        // never written). Everything else in store(), including the attach, is real.
        $controller = \Mockery::mock(CustomersController::class.'[subscriptionHistoryStore,paymentApprove]')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $controller->shouldReceive('subscriptionHistoryStore')->andReturn(1);
        $controller->shouldReceive('paymentApprove')->andReturn(true);
        $this->instance(CustomersController::class, $controller);

        $this->actingAs($admin)->post(route('admin.customers.store'), [
            'name' => 'Managed Mia',
            'email' => 'mia@adminco.com',
            'phone' => '+15550300',
            'password' => 'secret123',
            'package' => $package->id,
        ]);

        $user = User::where('email', 'mia@adminco.com')->first();

        $this->assertNotNull($user, 'Admin customer creation did not create the user.');
        $this->assertNotNull($user->organization_id, 'Admin customer creation did not attach an organization.');
        $this->assertSame('adminco.com', Organization::find($user->organization_id)->domain);
        $this->assertSame(
            $earlier->id,
            (int) Organization::find($user->organization_id)->owner_user_id,
            'Ownership must stay with the earlier registrant.'
        );
    }

    public function test_a_malformed_address_cannot_resolve_into_a_real_organization(): void
    {
        // "foo@bar@acme.com" has acme.com as its last domain segment, so an
        // unvalidated address would join the real acme.com organization.
        $this->post(route('register'), [
            'name' => 'Impostor',
            'email' => 'foo@bar@acme.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('email');

        $this->assertNull(Organization::where('domain', 'acme.com')->first());
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
