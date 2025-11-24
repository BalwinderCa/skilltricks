<?php

namespace App\Http\Controllers\Backend\Subscription;

use App\Http\Controllers\Backend\Payments\Paypal\PaypalController;
use App\Http\Controllers\Backend\Payments\Stripe\StripeWithAutoRecurringPaymentController;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionRecurringPayment;
use Illuminate\Http\Request;

class SubscriptionStatusController extends Controller
{
    public function subscriptionStatusRecurringPayment(Request $request)
    {
        try {
            $response = false;
            $reason = $request->reason;
            $status = $request->status == 'active' ? 1 : 0;
            $package_history_id = $request->package_history_id;            
            $packageHistory = SubscriptionHistory::findOrFail($package_history_id);
            $gateway = $packageHistory->payment_method;            
            $message = ' Subscription Package Not Found';
            $recurringPayment = $this->recurringSubscription($package_history_id, $gateway, $request->status);
            if($recurringPayment) {
                # check cancel permission
                if(getSetting('auto_subscription_deactive') == 1 && $request->status == 'cancel') {

                    # deactive subscription package                 
                    if($recurringPayment && $packageHistory->payment_method == 'paypal' && $recurringPayment->gateway_subscription_id && $request->status =='cancel'){
                        $response = PaypalController::cancelSubscription($recurringPayment->gateway_subscription_id, $reason);               
                    }
                    # deActive customer auto subscription
                    if($recurringPayment && $packageHistory->payment_method == 'stripe'  && $request->status =='cancel'){
                        $response = StripeWithAutoRecurringPaymentController::cancelSubscription($recurringPayment->gateway_subscription_id, $reason);               
                    }
                }
                # check active permission
                if(getSetting('auto_subscription_active') == 1 && $request->status == 'active') {
                    # active subscription package
                    if($recurringPayment && $packageHistory->payment_method == 'paypal' && $recurringPayment->gateway_subscription_id && $request->status =='active'){
                        $response = PaypalController::activeSubscription($recurringPayment->gateway_subscription_id, $reason);               
                    }
                }
                # check response
                if($response == false) {
                    flash(localize('Operation Failed'))->error();
                    return redirect()->back();
                }
                if($response == true) {
                    $this->updateRecurringData($package_history_id, $reason, $gateway, $status);
                }
                $message = $request->status == 'active' 
                            ? 'Package Activated Successfully' : 'Package DeActivated Successfully';
            }

            flash(localize($message))->success();
            return redirect()->back();
        } catch (\Throwable $th) {
            throw $th;
            return redirect()->back();
        }
    }

    # get recurring data
    private function recurringSubscription($package_history_id, $gateway, $status)
    {   
        $user_id = auth()->user()->id;       
        $recurringPayment = SubscriptionRecurringPayment::where('subscription_history_id', $package_history_id)
                            ->where('user_id', $user_id)
                            ->where('gateway', $gateway)
                            ->when($status == 'cacnel', function($q){
                                $q->where('is_active', 1);
                            })
                            ->when($status == 'active', function($q){
                                $q->where('is_active','!=', 1);
                            })
                            ->first();
                         
        if($recurringPayment){
            return $recurringPayment;
        }
        return null;
    }

    # update recurring payment
    private function updateRecurringData($package_history_id, $reason, $gateway, $status)
    {
        $recurringPaymentHistory = SubscriptionRecurringPayment::where('subscription_history_id', $package_history_id)
                                    ->where('gateway', $gateway)->first();
      
        $recurringPaymentHistory->is_active = $status;
        $recurringPaymentHistory->reason = $reason;
        $recurringPaymentHistory->cancel_by = auth()->user()->id;
        $recurringPaymentHistory->save();
    }
}
