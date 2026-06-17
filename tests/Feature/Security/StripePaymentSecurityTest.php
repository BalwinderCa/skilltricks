<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripePaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_stripe_success_requires_checkout_session_id(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'user_type' => 'customer',
        ]);

        $response = $this->actingAs($user)->get(route('stripe.success'));

        $response->assertRedirect(route('subscriptions.index'));
    }

    public function test_stripe_success_rejects_unpaid_checkout_session(): void
    {
        config(['custom.stripe_secret' => 'sk_test_fake']);

        $session = \Stripe\Checkout\Session::constructFrom([
            'id' => 'cs_test_unpaid',
            'object' => 'checkout.session',
            'payment_status' => 'unpaid',
            'payment_intent' => null,
        ]);

        Mockery::mock('alias:' . \Stripe\Checkout\Session::class)
            ->shouldReceive('retrieve')
            ->once()
            ->with('cs_test_unpaid')
            ->andReturn($session);

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'user_type' => 'customer',
        ]);

        $response = $this->actingAs($user)->get(route('stripe.success', ['session_id' => 'cs_test_unpaid']));

        $response->assertRedirect(route('subscriptions.index'));
    }
}
