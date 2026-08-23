<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingGateTest extends TestCase
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

    public function test_an_uncalibrated_customer_is_sent_to_onboarding(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create([
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => null,
        ]);

        $this->actingAs($user)
            ->get(route('writebot.dashboard'))
            ->assertRedirect(route('onboarding.index'));
    }

    public function test_a_calibrated_customer_reaches_the_dashboard(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create([
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => 30,
        ]);

        $this->actingAs($user)
            ->get(route('writebot.dashboard'))
            ->assertOk();
    }

    public function test_a_calibrated_customer_with_an_empty_profile_form_is_not_blocked(): void
    {
        // The eight profile fields no longer gate anything.
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create([
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => 10,
            'company_name' => null,
            'about_company' => null,
        ]);

        $this->actingAs($user)
            ->get(route('writebot.dashboard'))
            ->assertOk();
    }

    public function test_the_profile_page_no_longer_claims_the_form_gates_the_dashboard(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create([
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => 10,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.profile'));

        $response->assertOk();
        $response->assertDontSee('complete your profile to access the dashboard', false);
    }
}
