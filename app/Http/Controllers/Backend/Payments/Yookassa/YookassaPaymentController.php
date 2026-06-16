<?php

namespace App\Http\Controllers\Backend\Payments\Yookassa;

use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\NotificationEventType;
use App\Http\Controllers\Backend\Payments\PaymentsController;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPackage;
use App\Models\User;
use Illuminate\Http\Request;
use Redirect;

class YookassaPaymentController extends Controller
{
    public function initPayment()
    {
        try {
            $package_title = '';
            if(session('package_id')) {
                $package_title = SubscriptionPackage::where('id', session('package_id'))->value('title');
            }
            $user = auth()->user();
            $client = $this->_getAuthClient();

            $amount = $this->_calculateAmount();
            $currency = $this->_getCurrency();

            $idempotenceKey = uniqid('', true);
            if(config('custom.yookassa_reciept') == 'on') {
                $formatData =  [
                    'amount' => [
                        'value' => $amount,
                        'currency' => $currency,
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => route('youkassa.finish'),
                    ],
                    'metadata' => [
                        'user_id' => auth()->id(),
                        'package_id' => session('package_id'),
                        'amount' => $amount
                    ],
                  
                    'capture' => true,

                    'receipt' => array(
                        'customer' => array(
                            'full_name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'inn' => ''
                        ),
                        'items' => array(
                            array(
                                'description' => $package_title,
                                'quantity' => '1.00',
                                'amount' => array(
                                    'value' => $amount,
                                    'currency' => $currency
                                ),
                                'vat_code' => config('custom.yookassa_vat') ?? '2',
                                'payment_mode' => 'full_payment',
                                
                            ),
                        )
                    )
                ];
            }else{
              $formatData =  [
                    'amount' => [
                        'value' => $amount,
                        'currency' => $currency,
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => route('youkassa.finish'),
                    ],
                    'metadata' => [
                        'user_id' => auth()->id(),
                        'package_id' => session('package_id'),
                        'amount' => $amount
                    ],
                  
                    'capture' => true
                ];
            }
            $response = $client->createPayment(
                $formatData,
                $idempotenceKey
            );

            session()->put('yookassa_payment_id', $response->id);

            return Redirect::to($response->getConfirmation()->getConfirmationUrl());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::info('Failed payment yookassa');
     
            return (new PaymentsController)->payment_failed();
        }
    }

    public function process(Request $request)
    {
        $source = file_get_contents('php://input');
        $requestBody = json_decode($source, true);

        try {
            if ($requestBody['event'] === NotificationEventType::PAYMENT_SUCCEEDED) {
                $notification = new NotificationSucceeded($requestBody);
                $payment = $notification->getObject();
                $metadata = $payment->getMetadata();

                $user = User::find($metadata['user_id']);
                $package_id = $metadata['package_id'];
                $amount = $metadata['amount'];

                $payment_id = $payment->getId();
                \Illuminate\Support\Facades\Log::info("Yookassa payment id: $payment_id");

                // SECURITY: This webhook is unauthenticated and the metadata
                // (user_id, package_id, amount) is attacker-controllable. Before
                // re-enabling payment_success below, re-fetch the payment from
                // Yookassa by $payment_id via _getAuthClient()->getPaymentInfo()
                // and confirm status === 'succeeded' and amount matches the
                // server-side package price. Do NOT trust $metadata as-is.

                // (new PaymentsController)->payment_success(
                //     json_encode(["status" => "Success"]),
                //     $user,
                //     $package_id,
                //     $amount,
                //     'yookassa'
                // );
            }
        } catch (Exception $e) {
            // todo	
        }

        return response()->json(['message' => 'Success'], 200);
    }

    public function finish(Request $request)
    {
        $client = $this->_getAuthClient();
        $payment = $client->getPaymentInfo(session('yookassa_payment_id'));

        if ($payment->getStatus() == 'succeeded') {
            return (new PaymentsController)->payment_success();
        } else {
            return (new PaymentsController)->payment_failed();
        }
    }

    private function _getAuthClient()
    {
        $shopId = config('custom.yookassa_shop_id');
        $secretKey = config('custom.yookassa_secret_key');

        $client = new \YooKassa\Client();
        $client->setAuth($shopId, $secretKey);

        return $client;
    }

    private function _getCurrency()
    {
        switch (config('custom.yookassa_currency_code')) {
            case 'rub':
                return \YooKassa\Model\CurrencyCode::RUB;
            case 'usd':
                return \YooKassa\Model\CurrencyCode::USD;
            default:
                // usd as a deafault currency
                return \YooKassa\Model\CurrencyCode::USD;
        }
    }

    private function _calculateAmount()
    {
        $amount = session('amount');
        $amount = str_replace(",", "", $amount);
        return $amount;
    }
}
