<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsCarriedOverInSubscriptionHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_histories', function (Blueprint $table) {
            $table->tinyInteger('is_carried_over')->default(0);
            $table->bigInteger('carried_over_words')->default(0);
            $table->bigInteger('carried_over_images')->default(0);
            $table->bigInteger('carried_over_speech_to_text')->default(0);
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
            //
        });
    }
}
