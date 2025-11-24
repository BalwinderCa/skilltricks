<?php

namespace App\Http\Controllers\Backend\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\PaymentGatewayDetail;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Settings;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        return '';
    }

    # update gateway details
    public function updateGatewayDetails(Request $request)
    {
        $gateway = $request->payment_method;
        $paymentGateway = PaymentGateway::where('gateway', $gateway)->first();
        
        if($paymentGateway){
            $types = $request->types;  
          
            foreach($types as $key=>$value) {
               
                PaymentGatewayDetail::updateOrCreate([
                    'payment_gateway_id'=>$paymentGateway->id,
                    'key'=>$key
                ],
                [                    
                    'value'=>$value
                ]);
                writeToEnvFile($key, $value);
                
            }
            if($gateway == 'paypal' && $request->payment_type) {
                writeToEnvFile('PAYPAL_MODE', $request->payment_type);
            }
            $paymentGateway->is_active = $request->is_active;
            $paymentGateway->is_recurring = $request->is_recurring;
            
            $paymentGateway->sandbox = $request->sandbox ? 1 : 0;
            $paymentGateway->type = $request->payment_type;
           
            $paymentGateway->save();
           
        }
        if(isset($request['payment_methods'])) {
            $setting = SystemSetting::where('entity', 'enable_offline')->first();
            if(!$setting) {
                $setting = new SystemSetting;
                $setting->entity = 'enable_offline';
                $setting->value = $request->enable_offline;
                $setting->save();
            }else{
                $setting->value = $request->enable_offline;
                $setting->save();
            }
        }
        if ($request->has('offline_image')) {
            $setting = SystemSetting::where('entity', 'offline_image')->first();
            $value = $request['offline_image'];
            if ($setting != null) {
                $setting->value = $value;
                $setting->save();
            } else {
                $setting = new SystemSetting;
                $setting->entity = 'offline_image';
                $setting->value = $value;
                $setting->save();
            }
        }
        cacheClear();
        flash(localize("Payment settings updated successfully"))->success();
        return back();
    }

}
