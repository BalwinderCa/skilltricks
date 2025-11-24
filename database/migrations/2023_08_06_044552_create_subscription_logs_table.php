<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('subscription_history_id');
            $table->integer('subscription_package_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('type');
            $table->bigInteger('total_text');
            $table->bigInteger('before_balance')->nullable();
            $table->bigInteger('after_balance')->nullable();
            $table->bigInteger('promptsToken')->nullable();
            $table->bigInteger('completionToken')->nullable();
            $table->boolean('is_active')->default(1);
            $table->integer('created_by')->nullable();
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
        Schema::dropIfExists('subscription_logs');
    }
}
