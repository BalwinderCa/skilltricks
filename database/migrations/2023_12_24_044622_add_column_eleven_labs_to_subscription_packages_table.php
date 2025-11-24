<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnElevenLabsToSubscriptionPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'show_eleven_labs')) {
                $table->integer('show_eleven_labs')->nullable()->default(0);
            }
            if(!Schema::hasColumn($table->getTable(), 'allow_eleven_labs')) {
                $table->integer('allow_eleven_labs')->nullable()->default(0);
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
        Schema::table('subscription_packages', function (Blueprint $table) {
            $columns = ['show_eleven_labs', 'allow_eleven_labs'];
            $table->dropColumn($columns);
        });
    }
}
