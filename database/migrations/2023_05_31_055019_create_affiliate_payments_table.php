<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('affiliate_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->double('amount')->default(0.00);
            $table->string('payment_method');
            $table->longText('payment_document')->nullable();
            $table->string('status')->default('requested');
            $table->longText('additional_info')->nullable(); // for request
            $table->longText('remarks')->nullable(); // for status changes 
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
        Schema::dropIfExists('affiliate_payments');
    }
}
