<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayPaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_razorpay_payment_requires_payment_id(): void
    {
        $response = $this->post(route('razorpay.payment'), []);

        $response->assertRedirect(route('subscriptions.index'));
    }

    public function test_mercadopago_redirect_requires_payment_id(): void
    {
        $response = $this->get(route('mercadopago.redirect', ['status' => 'approved']));

        $response->assertRedirect(route('subscriptions.index'));
    }

    public function test_yookassa_finish_requires_session_payment_id(): void
    {
        $response = $this->get(route('youkassa.finish'));

        $response->assertRedirect(route('subscriptions.index'));
    }
}
