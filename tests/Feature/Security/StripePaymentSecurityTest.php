<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\Backend\Payments\Stripe\StripeWithAutoRecurringPaymentController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Checkout\Session;
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

        $session = (object) [
            'id' => 'cs_test_unpaid',
            'object' => 'checkout.session',
            'payment_status' => 'unpaid',
            'payment_intent' => null,
        ];

        Mockery::mock('alias:'.Session::class)
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

    /**
     * The webhook is CSRF-exempt and unauthenticated. Auto-recurring Stripe is not
     * implemented, so it must never acknowledge a payload -- a 200 here would let
     * anyone forge a "payment succeeded" event and grant themselves a subscription.
     */
    public function test_stripe_webhook_never_acknowledges_unverified_events(): void
    {
        $response = $this->postJson('/webhooks/stripe', [
            'id' => 'evt_forged',
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => ['subscription' => 'sub_forged']],
        ]);

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    /**
     * cancelSubscription() must report failure, not success. SubscriptionStatusController
     * only calls updateRecurringData() when this returns true, and marking a subscription
     * cancelled locally while Stripe keeps billing it is the expensive failure mode.
     */
    public function test_stripe_cancel_subscription_reports_failure(): void
    {
        $this->assertFalse(
            StripeWithAutoRecurringPaymentController::cancelSubscription('sub_123', 'test')
        );
    }
}
