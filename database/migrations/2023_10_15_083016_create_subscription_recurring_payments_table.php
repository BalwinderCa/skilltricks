<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionRecurringPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_recurring_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('subscription_history_id')->nullable();
            $table->string('billing_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('gateway_subscription_id')->nullable();
            $table->string('gateway')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->text('reason')->nullable();
            $table->string('status')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('cancel_by')->nullable();
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
        Schema::dropIfExists('subscription_recurring_payments');
    }
}
