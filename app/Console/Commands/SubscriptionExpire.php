<?php

namespace App\Console\Commands;

use App\Models\SubscriptionHistory;
use Illuminate\Console\Command;

class SubscriptionExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscription Expire';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = date('Y-m-d');
        $allActivePackageHistory = SubscriptionHistory::where('subscription_status', 1)->whereNotNull('end_date')->get();
        foreach ($allActivePackageHistory as $activePackageHistory) {
            if ($today > $activePackageHistory->end_date) {
                $activePackageHistory->update(['subscription_status' => 2]);
            }
        }
        $allSubscriptPackageHistory = SubscriptionHistory::where('subscription_status', 3)->where('payment_status', 1)->whereNotNull('start_date')->get();
        foreach ($allSubscriptPackageHistory as $activePackageHistory) {
            $package = $activePackageHistory->subscriptionPackage;
            $packageType = '';
            if (!is_null($package)) {
                $packageType = $package->package_type;
            }
            if (!empty($packageType) && in_array($packageType, ["prepaid", "lifetime"]) && $today >= $activePackageHistory->start_date) {
                $activePackageHistory->update(['subscription_status' => 1]);
            } else if (!empty($packageType) && !in_array($packageType, ["prepaid", "lifetime"]) && $today >= $activePackageHistory->start_date && $today <= $activePackageHistory->end_date) {
                $activePackageHistory->update(['subscription_status' => 1]);
            }
        }
    }
}
