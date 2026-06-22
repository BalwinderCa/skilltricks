<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaypalPaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_paypal_subscription_success_requires_subscription_id(): void
    {
        $response = $this->post(route('paypal.success'), []);

        $response->assertRedirect(route('subscriptions.index'));
    }

    public function test_paypal_capture_requires_order_id(): void
    {
        $response = $this->post(route('capture.payPal.order'), []);

        $response->assertRedirect(route('subscriptions.index'));
    }
}
