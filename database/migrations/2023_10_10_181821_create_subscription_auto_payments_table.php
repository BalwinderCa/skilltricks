<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionAutoPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_auto_payments', function (Blueprint $table) {
            $table->id();
            $table->string('gateway')->nullable();
            $table->string('name')->nullable();
            $table->double('price')->nullable()->default(0);
            $table->string('currency')->nullable()->default('USD');
            $table->string('recurring')->nullable()->default('monthly');
            $table->string('product_id')->nullable();
            $table->boolean('is_active')->nullable()->default(true);          
            $table->timestamps();
        });
        Schema::table('subscription_histories', function (Blueprint $table) {            
            if(!schema::hasColumn('subscription_histories', 'is_recurring')) {
                $table->boolean('is_recurring')->nullable()->default(false);
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
        Schema::dropIfExists('subscription_auto_payments');
        Schema::table('subscription_histories', function (Blueprint $table) {
            $columns = ['is_recurring'];
            $table->dropColumn($columns);
        });
    }
}
