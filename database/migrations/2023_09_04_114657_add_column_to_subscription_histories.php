<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToSubscriptionHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_histories', function (Blueprint $table) {
            $table->boolean('forcefully_active')->nullable()->default(false);
            
            if(!schema::hasColumn('subscription_histories', 'discount_type')) {
                $table->integer('discount_type')->nullable();
            }
            if(!schema::hasColumn('subscription_histories', 'discount')) {
                $table->integer('discount')->nullable();
            }
            if(!schema::hasColumn('subscription_histories', 'package_price')) {
                $table->integer('package_price')->nullable();
            }
        });
        Schema::table('subscription_packages', function (Blueprint $table) {
            if(!schema::hasColumn('subscription_packages', 'discount_price')) {
                $table->double('discount_price')->nullable()->after('discount');
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
        Schema::table('subscription_histories', function (Blueprint $table) {
            $columns = ['forcefully_active'];
            $table->dropColumn($columns);
        });
    }
}
