<?php

use App\Http\Controllers\Backend\Payments\Paypal\PaypalController;
use App\Models\SubscriptionPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayProductHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paymentgateway_product_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('package_id')->default(0);
            $table->string('package_name')->nullable();
            $table->string('gateway')->nullable();          
            $table->string('old_product_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('old_billing_id')->nullable();  
            $table->string('new_billing_id')->nullable();
            $table->boolean('is_active')->nullable()->default(true);  
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paymentgateway_product_histories');
    }
}
