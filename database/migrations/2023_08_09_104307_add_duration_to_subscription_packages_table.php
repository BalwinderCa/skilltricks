<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDurationToSubscriptionPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->integer('duration')->nullable()->default(30);
            $table->integer('discount_type')->nullable()->comment('1=fixed, 2=percentage');
            $table->double('discount')->nullable()->after('price');
            $table->double('discount_price')->nullable()->after('discount');
            $table->integer('discount_status')->nullable()->after('discount_price');
            $table->date('discount_start_date')->nullable();
            $table->date('discount_end_date')->nullable();
        });
        Schema::table('subscription_histories', function (Blueprint $table) {
            $table->integer('discount_type')->nullable();
            $table->double('discount')->nullable();
            $table->double('package_price')->nullable()->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $columns = ['duration', 'discount_type', 'discount', 'discount_price', 'discount_status', 'discount_start_date', 'discount_end_date'];
            $table->dropColumn($columns);
        });
        Schema::table('subscription_histories', function (Blueprint $table) {
            $histories_columns = ['discount_type', 'discount', 'package_price'];
            $table->dropColumn($histories_columns);
        });
    }
}
