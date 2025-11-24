<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePayoutAccount;
use Illuminate\Http\Request;

class AffiliatePayoutConfigurationsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return view('backend.pages.affiliate.configurePayouts', compact('user'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $activeAffiliatePaymentMethods = getSetting('affiliate_payout_payment_methods') != null ? json_decode(getSetting('affiliate_payout_payment_methods')) : [];

        foreach ($activeAffiliatePaymentMethods as  $paymentMethod) {
            if ($request[$paymentMethod]) {
                $userPaymentMethod = $user->affiliatePayoutAccounts()->where('payment_method', $paymentMethod)->first();
                if (is_null($userPaymentMethod)) {
                    $userPaymentMethod = new AffiliatePayoutAccount;
                    $userPaymentMethod->user_id = $user->id;
                    $userPaymentMethod->payment_method = $paymentMethod;
                    $userPaymentMethod->account_details = $request[$paymentMethod];
                } else {
                    $userPaymentMethod->account_details = $request[$paymentMethod];
                }

                $userPaymentMethod->save();
            }
        }
        try {
            flash(localize('Payout account has been set successfully'))->success();
        } catch (\Throwable $th) {
            //throw $th;
        }
        return back();
    }
}
