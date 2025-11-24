<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Backend\Payments\PaymentsController;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Traits\SubscriptionHistoryTrait;
use Illuminate\Http\Request;

class SubscriptionsController extends Controller
{
    use SubscriptionHistoryTrait;
    #index
    public function index()
    {
        return redirect()->route('subscriptions.index');
    }
    # subscribe

    public function subscribe(Request $request)
    {
        if (!$request->isMethod('post')) {
            flash(localize('Operation Failed'))->error();
            return redirect()->back();
        }
       
        if ($this->limitPackagePurchase() == false) {
            flash(localize("Operation Failed. You Can't Purchase More Than 2 package"))->warning();
            return redirect()->back();
        }
        if ($request->payment_method == 'offline') {
            $data = $this->subscriptionHistoryStore($request);
            if ($data == true) {
                flash(localize('Operation successfully. Please Wait For Approval'))->success();
            } else {
                flash(localize('Operation Failed'))->error();
            }
            $msg = localize('Offline Payment Request');
            saveNotification($msg, 'dashboard/payment-request', 'admin', null, null, null, null);
            

            
            return redirect()->back();
        }

        $package = SubscriptionPackage::where('id', $request->package_id)->first(['price']);
        
        $active_now = false;
        if(activePackageHistory()) {
            $active_now = getSetting('new_package_purchase') == 1 || $request->active_now == 'on' ? true : false;
        }

        $request->session()->put('package_id', $request->package_id);

        $request->session()->put('amount', formatPrice(packageSellPrice($request->package_id), false, false, false, false));

        $request->session()->put('request_amount', formatPrice($request->offline_amount, false, false, false, false));

        $request->session()->put('payment_method', $request->payment_method);

        $request->session()->put('active_now', $active_now);

        # init payment
        try {
            $payment = new PaymentsController;
            return $payment->initPayment();
        } catch (\Throwable $th) {
            throw $th;
        }
        
    }
    public function offlinePayment($request)
    {
        $package = SubscriptionPackage::where('id', $request->package_id)->first();
    }
}
