<?php

namespace App\Http\Services;

use App\Models\Subscriptions as SubscriptionsModel;

class PaymentService
{
    public function index()
    {
    }
    
    public function isActiveSubscriptions($plan_id)
    {
        // $plan->stripe_product_id != null
        $userId = auth()->user()->id;
        // Get current active subscription
        $activeSubscription = SubscriptionsModel::where([['stripe_status', '=', 'active'], ['user_id', '=', $userId]])
                            ->orWhere([['stripe_status', '=', 'trialing'], ['user_id', '=', $userId]])
                            ->first();
        if ($activeSubscription != null) {
            $activeSubscriptionId = $activeSubscription->id;
        } else {
            $activeSubscriptionId = 0; //id can't be zero, so this will be easy to check instead of null
        }
        return $activeSubscriptionId == $plan_id;
    }
}
