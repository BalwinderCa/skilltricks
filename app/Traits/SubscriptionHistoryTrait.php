<?php

namespace App\Traits;

use App\Models\User;
use App\Models\AffiliateEarning;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use Facade\Ignition\Support\Packagist\Package;

trait SubscriptionHistoryTrait
{

    private function limitPackagePurchase($userId = null)
    {
        $user = $userId ? User::find((int)$userId) : auth()->user();
        if (isCustomer() || $userId) {
            $package_count = SubscriptionHistory::where('user_id', $user->id)->whereIn('subscription_status', [1, 3])->count();
            if ($package_count >= 2) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    public function subscriptionHistoryStore($request)
    {
        $package_id         = $request->package_id;
        $user_id            = $request->user_id;
        $payment_method     = $request->offline_payment_method ? 'offline' : $request->payment_method;
        $payment_details    = $request->payment_detail;
        $note               = $request->note;
        $file               = $request->file;
        $path               =  'public/uploads/offlinePayment/';
        $user               = $user_id  ? User::findOrFail($user_id) : auth()->user();
        $package_id         = $package_id ?? session('package_id');
        $payment_method     = $payment_method ?? session('payment_method');
        $forcefully_active  = false;

        if($request->active_now == 'on' && $request->filled('admin_assign_package')){
            $forcefully_active  = true;
        }elseif(getSetting('new_package_purchase') == 1 && !$request->admin_assign_package){
            $forcefully_active  = true;
        }
        
        $exitHistory = SubscriptionHistory::where('user_id', $user->id)
            ->where('subscription_package_id', $package_id)
            ->where('payment_status', '!=', 1)
            ->first();
        if ($exitHistory) {
            return false;
        }
        // update subscription package & others 
        $package = SubscriptionPackage::where('id', $package_id)->first();
        $amount = $request->amount ?? packageSellPrice($package->id);

        # subscription history
        $subscriptionHistory = new SubscriptionHistory;
        $subscriptionHistory->user_id = $user->id;
        $activeSubscriptionPackageHistory = activePackageHistory($user->id);

        if ($activeSubscriptionPackageHistory) {
            $subscriptionHistory->old_subscription_package_id = $activeSubscriptionPackageHistory->subscription_package_id;
        }

        $subscriptionHistory->subscription_package_id   = $package->id;
        $subscriptionHistory->price                     = $amount;
        $subscriptionHistory->package_price             = $package->price;
        if (packageDiscountStatus($package_id)) {
            $subscriptionHistory->discount_type         = $package->discount_type;
            $subscriptionHistory->discount              = $package->discount;
        }
        $subscriptionHistory->payment_method            = $payment_method;

        $subscriptionHistory->new_word_balance      = $package->allow_unlimited_word == 1 ? '-1' : $package->total_words_per_month;
        $subscriptionHistory->new_image_balance     = $package->allow_unlimited_image == 1 ? '-1' : $package->total_images_per_month;
        $subscriptionHistory->new_s2t_balance       = $package->allow_unlimited_speech_to_text == 1  ? '-1' : $package->total_speech_to_text_per_month;

        if ($request->admin_customer) {
            $subscriptionHistory->payment_status    = 1;
            $subscriptionHistory->created_by        = auth()->user()->id;
        } else {
            $subscriptionHistory->payment_status    = $request->offline_payment_method ?  2 : 1;
        }
        $subscriptionHistory->note                  = $note;
        if ($file) {
            $subscriptionHistory->file              = fileUpload($path, $file);
        }

        $subscriptionHistory->subscription_status = 3;

        $subscriptionHistory->forcefully_active = $forcefully_active;

        $request->session()->put('admin_customer', $request->admin_customer);

        $subscriptionHistory->offline_payment_id        = $request->offline_payment_method ?? null;
        $subscriptionHistory->payment_details           = !is_null($payment_details) ? json_encode($payment_details) : null;
        $subscriptionHistory->currency_code = session()->get('currency_code');
        $subscriptionHistory->save();

        if ($payment_method ==  'offline') {
            $customer = $user;
            if ($customer) {
                $data = [];
                $data['name'] = $customer->name;
                $data['email'] = $customer->email;
                $data['phone'] = $customer->phone;
                if ($subscriptionHistory) {
                    $package = $subscriptionHistory->subscriptionPackage->package_type == 'starter'
                        ? localize('Monthly') : localize($subscriptionHistory->subscriptionPackage->package_type);
                    $data['package'] = html_entity_decode($subscriptionHistory->subscriptionPackage->title) . '/' . $package;
                    $data['price'] = $subscriptionHistory->price;
                    $data['start_date'] = $subscriptionHistory->start_date;
                    $data['end_date'] = $subscriptionHistory->end_date;
                    $data['method'] = $subscriptionHistory->offline_payment_id ? $subscriptionHistory->offlinePaymentMethod->name : $subscriptionHistory->payment_method;
                }
            }
            if ($customer->email) {
                sendMail($customer->email,  $customer->name, 'offline-payment-request', $data);
            }
        }

        // check affiliate & calculate commissions
        if ($request->admin_customer) {
            return $subscriptionHistory->id;
        }

        return true;
    }

    public function paymentApprove($historyId, $forceCarry = false, $activeNow = false)
    {
        
        $subscriptionHistory = SubscriptionHistory::findOrFail($historyId);
        $forcefully_active   = $subscriptionHistory->forcefully_active == 1 ? true : false;
        // start date and end date
        $start_date = date('Y-m-d');
        $end_date = null;
        if ($subscriptionHistory->subscriptionPackage->package_type == 'monthly') {
            $end_date = date('Y-m-d', strtotime($start_date . ' + 1 months'));
        } elseif ($subscriptionHistory->subscriptionPackage->package_type == 'yearly') {
            $end_date = date('Y-m-d', strtotime($start_date . ' + 1 years'));
        } elseif ($subscriptionHistory->duration) {
            $end_date = date('Y-m-d', strtotime($start_date . $subscriptionHistory->subscriptionPackage->duration . ' days'));
        }

        $activePackage       = activePackageHistory($subscriptionHistory->user_id);
        $subscriptionPackage = SubscriptionPackage::where('id', $subscriptionHistory->subscription_package_id)->first();

        $carry_forward = getSetting('carry_forward') && getSetting('carry_forward') == 1 ? true : false;

        if ($activePackage) {

            $start_date = $activeNow ? date('Y-m-d') : date('Y-m-d', strtotime($activePackage->end_date . ' + 1 days'));
            if ($subscriptionHistory->subscriptionPackage->package_type == 'monthly') {
                $end_date = date('Y-m-d', strtotime($start_date . ' + 1 months'));
            } elseif ($subscriptionHistory->subscriptionPackage->package_type == 'yearly') {
                $end_date = date('Y-m-d', strtotime($start_date . ' + 1 years'));
            }

            if ((session('admin_customer') == true && $activeNow) || $forcefully_active == true) {
                $subscriptionHistory->subscription_status = 1;
            }

            // when force carry forward by admin
            if ($forceCarry) {
                $carry_forward = true;
            }
        } else {
            // there is no active previous subscription
            $subscriptionHistory->subscription_status = 1;
        }

        $subscriptionHistory->payment_status = 1;
        $subscriptionHistory->start_date = $start_date;
        $subscriptionHistory->end_date = $end_date;

        $oldSubscription = $activePackage;

        $carriedWords = 0;
        $carriedImages = 0;
        $carriedS2t = 0;

        if ($activePackage && $activePackage->subscriptionPackage->package_type != $subscriptionPackage->package_type) {
            $carry_forward = false;
        }

        if ($carry_forward) {
            $subscriptionHistory->is_carried_over = 1;
            if ($oldSubscription) {
                // if (session('admin_customer') == true) {
                //     $oldSubscription->subscription_status = 2;
                //     $oldSubscription->end_date = date('Y-m-d');
                // }
                $carriedWords = $oldSubscription->this_month_available_words;
                $carriedImages = $oldSubscription->this_month_available_images;
                $carriedS2t = $oldSubscription->this_month_available_s2t;

                if ($oldSubscription->subscriptionPackage->allow_unlimited_word == 1) {
                    $carriedWords = 0;
                }
                if ($oldSubscription->subscriptionPackage->allow_unlimited_image == 1) {
                    $carriedImages = 0;
                }
                if ($oldSubscription->subscriptionPackage->allow_unlimited_speech_to_text == 1) {
                    $carriedS2t = 0;
                }
            }
        }

        $subscriptionHistory->carried_over_words                = $carriedWords;
        $subscriptionHistory->carried_over_images               = $carriedImages;
        $subscriptionHistory->carried_over_speech_to_text       = $carriedS2t;

        $subscriptionHistory->new_word_balance      =  $subscriptionPackage->allow_unlimited_word == 1 ? '-1' : $subscriptionPackage->total_words_per_month;
        $subscriptionHistory->new_image_balance     =  $subscriptionPackage->allow_unlimited_image == 1 ? '-1' : $subscriptionPackage->total_images_per_month;
        $subscriptionHistory->new_s2t_balance       =  $subscriptionPackage->allow_unlimited_speech_to_text == 1 ? '-1' : $subscriptionPackage->total_speech_to_text_per_month;

        $subscriptionHistory->this_month_available_words   = $subscriptionPackage->allow_unlimited_word == 1 ? '-1' : (int) $subscriptionPackage->total_words_per_month + $carriedWords;
        $subscriptionHistory->this_month_available_images  = $subscriptionPackage->allow_unlimited_image == 1 ? '-1' : (int) $subscriptionPackage->total_images_per_month + $carriedImages;
        $subscriptionHistory->this_month_available_s2t     = $subscriptionPackage->allow_unlimited_speech_to_text == 1 ? '-1' : (int) $subscriptionPackage->total_speech_to_text_per_month + $carriedS2t;

        if ((session('admin_customer') == true  && $activeNow && $oldSubscription) || ($forcefully_active == true && $oldSubscription)) {
            $oldSubscription->subscription_status = 2;
            $oldSubscription->end_date = date('Y-m-d');
        }

        if ($oldSubscription) {
            $oldSubscription->save();
        };
        $subscriptionHistory->save();

        if (session('admin_customer') == false) {
            $this->affiliate_system($historyId, $subscriptionHistory->user_id, $subscriptionHistory->price);
        }
        clearPaymentSession();
    }

    public function affiliate_system($s_h_id, $user_id, $price)
    {
        $user = $user_id ? User::findOrFail($user_id) : auth()->user();
        if (getSetting('enable_affiliate_system') == '1') {
            if (!is_null($user->referred_by)) {

                $giveCommission = false;
                if (getSetting('enable_affiliate_continuous_commission') == "1") {
                    $giveCommission = true;
                    $user->is_commission_calculated = 0;
                } else if ($user->is_commission_calculated == 0) {
                    $giveCommission = true;
                }

                if ($giveCommission) {
                    $referredBy = User::where('id', $user->referred_by)->first();
                    if (!is_null($referredBy)) {
                        $earning = new AffiliateEarning;
                        $earning->user_id = $user->id;
                        $earning->referred_by = $referredBy->id;
                        $earning->subscription_history_id = $s_h_id;
                        $earning->amount = ((float) $price * (float) getSetting('affiliate_commission')) / 100;
                        $earning->commission_rate = getSetting('affiliate_commission');
                        $earning->save();

                        $referredBy->user_balance += (float) $earning->amount;
                        $referredBy->save();
                    }
                }
            }
        }
    }

    public function forceFullyExpire($newPackage)
    {
        // ->monthly->yearly->lifetime/prepaid 
        $activePackage = activePackageHistory();
        // active serial
        if ($activePackage) {
            $activePackage->expire_by_admin_date =  date('Y-m-d');
            $activePackage->save();
            return true;
        }
        return false;
    }
}
