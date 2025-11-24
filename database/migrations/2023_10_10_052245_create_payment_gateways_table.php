<?php

use App\Models\SystemSetting;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentGatewaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('gateway');
            $table->string('image')->nullable();
            $table->boolean('is_recurring')->nullable()->default(false);
            $table->string('webhook_id')->nullable();
            $table->boolean('sandbox')->nullable()->default(false);
            $table->string('type')->nullable()->comment('sandbox, live');
            $table->string('is_active')->nullable()->default(false);
            $table->string('service_charge')->nullable()->default(false);
            $table->string('charge_type')->nullable()->comment('1= flat, 2=percentage');
            $table->timestamps();
        });
        try {
            $gateways = [
                [
                    'name'=>'paypal', 
                    'path'=>'backend/assets/img/payments/paypal.svg'
                ],
                [
                    'name'=>'stripe',
                    'path'=>'backend/assets/img/payments/stripe.svg'
                ],
                [
                    'name'=>'paytm',
                    'path'=>'backend/assets/img/payments/paytm.svg'
                ],
                [
                    'name'=>'razorpay',
                    'path'=>'backend/assets/img/payments/razorpay.svg'
                ],
                [
                    'name'=>'iyzico',
                    'path'=>'backend/assets/img/payments/iyzico.svg'
                ],
                [
                    'name'=>'paystack',
                    'path'=>'backend/assets/img/payments/paystack.svg'
                ],
                [
                    'name'=>'flutterwave',
                    'path'=>'backend/assets/img/payments/flutterwave.svg'
                ],
                [
                    'name'=>'duitku',
                    'path'=>'backend/assets/img/payments/duitku.svg'
                ],
                [
                    'name'=>'yookassa',
                    'path'=>'backend/assets/img/payments/yookassa.svg'
                ],
                [
                    'name'=>'molile',
                    'path'=>'backend/assets/img/payments/molile.svg'
                ],
                [
                    'name'=>'mercadopago',
                    'path'=>'backend/assets/img/payments/mercadopago.svg'
                ],
                [
                    'name'=>'midtrans',
                    'path'=>'backend/assets/img/payments/midtrans.svg'
                ]
        ];
        foreach($gateways as $gateway){
            $value = 'enable_' . $gateway['name'];
            $exitGateway = SystemSetting::where('entity', $value)->first();
            $status = 0;
            if($exitGateway) {
                $status = $exitGateway->value ?? 0;
            }
            $sandbox = $gateway['name'] . '_sandbox';
            $sanboxSetting = SystemSetting::where('entity', $sandbox)->first();
            $sandboxStatus = 0;
            if($sanboxSetting) {
                $sandboxStatus = $sanboxSetting->value ?? 0;
            }
            PaymentGateway::updateOrCreate([
                'gateway'=>$gateway['name']
            ],[
                'sandbox'=>$sandboxStatus,
                'is_active'=>$status,
                'type'=>$sandboxStatus ? 'sandbox': 'live',
                'image'=>$gateway['path']
            ]);
        }
        } catch (\Throwable $th) {
      
            Log::info(' payment gateways migration table:'. $th->getMessage());
        }
       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_gateways');
    }
}
