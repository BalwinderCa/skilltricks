<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Payment gateway server-to-server callbacks (must verify signatures in controllers)
        'midtrans/payment/payment-notification',
        'midtrans/payment/pay-account-notification',
        'midtrans/payment/recurring-notification',
        'duitku/payment/callback',
        'paytm/callback',
        // Stripe Checkout create-session is POST from JS; success/cancel are GET redirects
        'stripe/create-session',
        // Webhooks must verify Stripe/PayPal signatures in handleWebhook()
        'webhooks/paypal',
        'webhooks/stripe',
    ];
}
