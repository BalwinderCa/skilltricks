<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentGatewayProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_gateway_products', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('package_id')->default(0);
            $table->string('package_name')->nullable();
            $table->string('gateway')->nullable();          
            $table->string('product_id')->nullable();
            $table->string('billing_id')->nullable();
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
        Schema::dropIfExists('payment_gateway_products');
    }
}
