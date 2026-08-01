<?php

namespace App\Http\Controllers\Backend\Payments\Stripe;

use App\Http\Controllers\Backend\Payments\PaymentsController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Stripe subscriptions with auto-recurring billing.
 *
 * ponytail: deliberately unimplemented, and fails closed.
 *
 * routes/web.php and three subscription controllers have referenced this class
 * since the initial import, but the file itself was never in the repository. Every
 * one of those paths therefore raised "Class ... does not exist" and returned a
 * 500, and it broke `php artisan route:list` for the whole application.
 *
 * This restores the class so routing resolves, without pretending to talk to
 * Stripe. Nothing here contacts the API, so it cannot half-charge a customer or
 * record a subscription that does not exist upstream. Each entry point refuses
 * loudly instead of failing silently.
 *
 * To implement for real you need: STRIPE_KEY / STRIPE_SECRET /
 * STRIPE_WEBHOOK_SECRET (all currently blank in .env), a product+price mapping
 * onto payment_gateway_products.price_id, subscription persistence into
 * subscription_recurring_payments.gateway_subscription_id, and genuine webhook
 * signature verification via \Stripe\Webhook::constructEvent(). Until then,
 * leave the stripe gateway inactive.
 */
class StripeWithAutoRecurringPaymentController extends Controller
{
    private const UNAVAILABLE = 'Stripe auto-recurring payments are not implemented on this installation.';

    /**
     * Entry point from StripePaymentController::initPayment() and GET /stripe/subscribe.
     * Static so both the `Class::subscribe()` call and the route action resolve.
     */
    public static function subscribe($package_id = null, $package = null)
    {
        Log::warning(self::UNAVAILABLE, ['entry' => 'subscribe', 'package_id' => $package_id]);

        return (new PaymentsController)->payment_failed();
    }

    /** GET /stripe/subscribePay */
    public function subscribePay(Request $request)
    {
        Log::warning(self::UNAVAILABLE, ['entry' => 'subscribePay']);

        return (new PaymentsController)->payment_failed();
    }

    /** GET /stripe/prepaidPay */
    public function prepaidPay(Request $request)
    {
        Log::warning(self::UNAVAILABLE, ['entry' => 'prepaidPay']);

        return (new PaymentsController)->payment_failed();
    }

    /**
     * POST /webhooks/stripe -- CSRF-exempt and unauthenticated, so anyone can reach it.
     *
     * Rejects everything. Without STRIPE_WEBHOOK_SECRET there is no way to tell a real
     * Stripe event from a forged one, and answering 200 to an unverified payload is how
     * an attacker grants themselves a paid subscription. Mirrors the reject branch of
     * PaypalController::handleWebhook().
     */
    public function handleWebhook(Request $request)
    {
        Log::warning(self::UNAVAILABLE, [
            'entry' => 'handleWebhook',
            'ip' => $request->ip(),
        ]);

        abort(404);
    }

    /**
     * Called by SubscriptionSettingsController::store() inside a try/catch.
     * Throws so the admin sees the "failed" flash rather than a false success.
     */
    public static function createProduct($package_id)
    {
        throw new RuntimeException(self::UNAVAILABLE);
    }

    /**
     * Called by SubscriptionSettingsController::view().
     * Throws rather than returning null: the view dereferences $details['id']
     * unconditionally, so null would surface as a confusing array-offset error.
     */
    public static function showPlanDetails($price_id)
    {
        throw new RuntimeException(self::UNAVAILABLE);
    }

    /**
     * Called by SubscriptionStatusController::changeStatus().
     *
     * Returns false on purpose. That caller treats false as "Operation Failed" and
     * skips updateRecurringData(), which is what we want: marking a subscription
     * cancelled locally while it still bills at Stripe would be worse than refusing.
     */
    public static function cancelSubscription($subscription_id, $reason = null)
    {
        Log::warning(self::UNAVAILABLE, [
            'entry' => 'cancelSubscription',
            'gateway_subscription_id' => $subscription_id,
        ]);

        return false;
    }
}
