<?php

use App\Models\User;
use App\Models\SubscriptionHistory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToSubscriptionHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_histories', function (Blueprint $table) {

            if (!Schema::hasColumn($table->getTable(), 'start_date')) {
                $table->date('start_date')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn($table->getTable(), 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn($table->getTable(), 'expire_by_admin_date')) {
                $table->date('expire_by_admin_date')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn($table->getTable(), 'subscription_status')) {
                $table->integer('subscription_status')->nullable()->comment('1=active, 2=expired, 3=subscribed')->after('expire_by_admin_date');
            }
            if (!Schema::hasColumn($table->getTable(), 'payment_status')) {
                $table->integer('payment_status')->nullable()->comment('1=paid, 2=Pending, 3=Rejected 4=Re-Submit')->after('subscription_status');
            }
            if (!Schema::hasColumn($table->getTable(), 'file')) {
                $table->text('file')->nullable();
            }
            if (!Schema::hasColumn($table->getTable(), 'offline_payment_id')) {
                $table->integer('offline_payment_id')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn($table->getTable(), 'feedback_note')) {
                $table->text('feedback_note')->nullable();
            }
        });
        $histories = SubscriptionHistory::all();
        foreach ($histories as $history) {
            try {
                $start_date = date('Y-m-d', strtotime($history->created_at));
                if ($history->subscriptionPackage->package_type == 'monthly') {
                    $end_date =  date('Y-m-d', strtotime($start_date . ' + 1 months'));
                } elseif ($history->subscriptionPackage->package_type == 'yearly') {
                    $end_date =  date('Y-m-d', strtotime($start_date . ' + 1 years'));
                } else {
                    $end_date = null;
                }
                $subscription_status = 1;
                if ($history->user_id && activePackageHistory($history->user_id)) {
                    if (activePackageHistory($history->user_id)->subscription_package_id == $history->subscription_package_id) {
                        $subscription_status = 1;
                    } else {
                        $subscription_status = 2;
                    }
                }
                $subscription_status = $subscription_status;
                $history->update(['start_date' => $start_date, 'end_date' => $end_date, 'subscription_status' => $subscription_status, 'payment_status' => 1]);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_histories', function (Blueprint $table) {
            $columns = ['start_date', 'end_date', 'expire_by_admin_date', 'subscription_status', 'payment_status', 'file', 'offline_payment_id', 'feedback_note'];
            $table->dropColumn($columns);
        });
    }
}
