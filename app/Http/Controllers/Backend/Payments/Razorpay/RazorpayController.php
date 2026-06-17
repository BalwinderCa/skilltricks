<?php

namespace App\Http\Controllers\Backend\Payments\Razorpay;

use Razorpay\Api\Api;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Backend\Payments\PaymentsController;

class RazorpayController extends Controller
{

    # init payment
    public function initPayment()
    {
        $user = auth()->user();

        $title = '';
        $amount = session('amount');
        $supportedCurrency = [
            "INR",   # Indian Rupee
            "USD",   # United States Dollar $
            "EUR",   # European Euro €
            "GBP",   # Pound Sterling  £          
            "SGD",   # Singapore Dollar S$
            "AED",   # United Arab Emirates Dirham د.إ
            "AUD",   # Australian Dollar AU$
            "CAD",   # Canadian Dollar CA$
            "CNY"    # Chinese Yuan Renminbi ¥
        ];
        if (Session::has('currency_code')) {
            if (in_array(strtoupper(Session::get('currency_code')), $supportedCurrency)) {
                $currencyCode = strtoupper(Session::get('currency_code'));
            } else {
                $currencyCode = 'USD';
                $amount = priceToUsd($amount);
            }
        } else {
            $currencyCode = 'USD';
            $amount = priceToUsd($amount);
        }
        $data = [
            'amount' => $amount * 100,
            'currency' => $currencyCode,
            'name' => $user->name,
            'email' => $user->email,
            'app_name' => config('custom.app_name'),
            'app_logo' => uploadedAsset(getSetting('navbar_logo')),
            'payment_title' => $title
        ];
        return view('payments.razorpay', compact('data'));
    }


    # make payment
    public function payment(Request $request)
    {
        $input = $request->all();

        if (empty($input['razorpay_payment_id'])) {
            return (new PaymentsController)->payment_failed();
        }

        $api = new Api(config('custom.razorpay_key'), config('custom.razorpay_secret'));

        try {
            if (! empty($input['razorpay_order_id']) && ! empty($input['razorpay_signature'])) {
                $api->utility->verifyPaymentSignature([
                    'razorpay_order_id' => $input['razorpay_order_id'],
                    'razorpay_payment_id' => $input['razorpay_payment_id'],
                    'razorpay_signature' => $input['razorpay_signature'],
                ]);
            }

            $payment = $api->payment->fetch($input['razorpay_payment_id']);

            if ($payment['status'] === 'authorized') {
                $response = $payment->capture(['amount' => $payment['amount']]);
            } else {
                $response = $payment;
            }

            if (! in_array($response['status'], ['captured', 'paid'], true)) {
                return (new PaymentsController)->payment_failed();
            }

            $payment_details = json_encode([
                'id' => $response['id'],
                'method' => $response['method'],
                'amount' => $response['amount'],
                'currency' => $response['currency'],
            ]);

            return (new PaymentsController)->payment_success(json_encode($payment_details));
        } catch (\Exception $e) {
            return (new PaymentsController)->payment_failed();
        }
    }
}
