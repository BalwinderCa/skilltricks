<?php

use App\Models\SubscriptionHistory;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUserTableSubscriptionBalanceToHistoryTableV230 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('subscription_histories', 'this_month_used_words')) {
            Schema::table('subscription_histories', function (Blueprint $table) {
                $table->bigInteger('this_month_used_words')->default(0);
                $table->bigInteger('this_month_available_words')->default(0);
                $table->bigInteger('total_used_words')->default(0);

                $table->bigInteger('this_month_used_images')->default(0);
                $table->bigInteger('this_month_available_images')->default(0);
                $table->bigInteger('total_used_images')->default(0);

                $table->bigInteger('this_month_used_s2t')->default(0);
                $table->bigInteger('this_month_available_s2t')->default(0);
                $table->bigInteger('total_used_s2t')->default(0);

                $table->bigInteger('this_month_used_t2s')->default(0);
                $table->bigInteger('this_month_available_t2s')->default(0);
                $table->bigInteger('total_used_t2s')->default(0);
            });
        }

        if (Schema::hasColumn('users', 'this_month_used_words')) {
            $users = User::all();
            foreach ($users as $user) {
                if (optional(activePackageHistory($user->id))->subscription_package_id != null) {
                    $subscriptionHistory = SubscriptionHistory::latest()->where('subscription_package_id', optional(activePackageHistory($user->id))->subscription_package_id)->where('user_id', $user->id)->first();
                    $subscriptionHistory->this_month_used_words             = $user->this_month_used_words;
                    $subscriptionHistory->this_month_available_words        = $user->this_month_available_words;
                    $subscriptionHistory->total_used_words                  = $user->total_used_words;
                    $subscriptionHistory->this_month_used_images            = $user->this_month_used_images;
                    $subscriptionHistory->this_month_available_images       = $user->this_month_available_images;
                    $subscriptionHistory->total_used_images                 = $user->total_used_images;
                    $subscriptionHistory->this_month_used_s2t               = $user->this_month_used_s2t;
                    $subscriptionHistory->this_month_available_s2t          = $user->this_month_available_s2t;
                    $subscriptionHistory->total_used_s2t                    = $user->total_used_s2t;
                    $subscriptionHistory->this_month_used_t2s               = $user->this_month_used_t2s;
                    $subscriptionHistory->this_month_available_t2s          = $user->this_month_available_t2s;
                    $subscriptionHistory->total_used_t2s                    = $user->total_used_t2s;
                    $subscriptionHistory->save();
                }
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'this_month_used_words',
                    'this_month_available_words',
                    'total_used_words',
                    'this_month_used_images',
                    'this_month_available_images',
                    'total_used_images',
                    'this_month_used_s2t',
                    'this_month_available_s2t',
                    'total_used_s2t',
                    'this_month_used_t2s',
                    'this_month_available_t2s',
                    'total_used_t2s',
                ]);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
