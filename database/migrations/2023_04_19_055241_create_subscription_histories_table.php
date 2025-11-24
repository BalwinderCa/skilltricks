<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('subscription_package_id');
            $table->integer('old_subscription_package_id')->nullable();
            $table->bigInteger('old_word_balance')->default(0);
            $table->bigInteger('new_word_balance')->default(0);
            $table->bigInteger('total_word_balance')->default(0);
            $table->bigInteger('old_image_balance')->default(0);
            $table->bigInteger('new_image_balance')->default(0);
            $table->bigInteger('total_image_balance')->default(0);
            $table->bigInteger('old_s2t_balance')->default(0);
            $table->bigInteger('new_s2t_balance')->default(0);
            $table->bigInteger('total_s2t_balance')->default(0);
            $table->bigInteger('old_t2s_balance')->default(0);
            $table->bigInteger('new_t2s_balance')->default(0);
            $table->bigInteger('total_t2s_balance')->default(0);
            $table->double('price')->default(0.00);
            $table->string('payment_method')->nullable();
            $table->longText('payment_details')->nullable();
            $table->longText('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_histories');
    }
}
