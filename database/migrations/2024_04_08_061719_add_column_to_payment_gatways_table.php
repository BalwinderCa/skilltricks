<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'webhook_secret')) {
                $table->string('webhook_secret')->nullable();
            }
        });
        
        Schema::table('subscription_recurring_payments', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'price_id')) {
                $table->string('price_id')->nullable()->after('product_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn([
               'webhook_secret'
            ]);
        });
        Schema::table('subscription_recurring_payments', function (Blueprint $table) {
            $table->dropColumn([
               'price_id'
            ]);
        });
    }
};
