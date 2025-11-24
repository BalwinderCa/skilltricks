<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUnusedColumnFromSubscriptionHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('subscription_histories', 'old_word_balance')) {
            Schema::table('subscription_histories', function (Blueprint $table) {
                $table->dropColumn([
                    'old_word_balance',
                    'total_word_balance',
                    'old_image_balance',
                    'total_image_balance',
                    'old_s2t_balance',
                    'total_s2t_balance',
                    'old_t2s_balance',
                    'new_t2s_balance',
                    'total_t2s_balance'
                ]);
            });
        }

        Schema::table('subscription_histories', function (Blueprint $table) {
            $table->integer('active_by');
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
