<?php

namespace App\Services;

use App\Models\SubscribedUser;
use App\Models\SubscriptionHistory;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function findByColumnsWithValues(array  $columnsWithValues, $isFirst = true)
    {
        $query = User::query()->where([$columnsWithValues]);

        return $isFirst ? $query->first() : $query->exists();
    }

    public function setReferredBy()
    {
        if (getSetting('enable_affiliate_system') == appStatic()::ENABLE_AFFILIATE_SYSTEM) {

            $referral_code = setReferralCode();

            if (!empty($referral_code)) {

                $referredByUser = $this->findByColumnsWithValues(['referral_code', $referral_code]);

                if (!empty($referredByUser)) {
                    return $referredByUser->id;

                }
                return null;
            }
        }

        return null;
    }

    public function storeUser(array $payloads)
    {
        return User::query()->create($payloads);
    }


    public function storeUserAsSubscriber(array $payloads)
    {
        return SubscribedUser::query()->create($payloads);
    }


    public function updateUserAsVerified($user)
    {
        $user->update([
            "email_or_otp_verified" => 1,
            "email_verified_at"     => now(),
        ]);

        return $user;
    }

    public function updateUserWords($tokens, $user = null)
    {
        Log::info("I am from User Service : Update User Words === ".json_encode($tokens));

        if (isCustomer()) {
            $user = is_null($user) ? user() : $user;
            updateDataBalance('words', $tokens, $user);
        }
    }


    public function storeUserSubscription(object $starter, $user, $start_date, $end_date = null)
    {
        $payloads = [
            "start_date"                  => $start_date,
            "end_date"                    => $end_date,
            "user_id"                     => $user->id,
            "subscription_package_id"     => $starter->id,
            "new_word_balance"            => $starter->total_words_per_month,
            "new_image_balance"           => $starter->total_images_per_month,
            "new_s2t_balance"             => $starter->total_speech_to_text_per_month,
            "this_month_available_words"  => (int) $starter->total_words_per_month,
            "this_month_available_images" => (int) $starter->total_images_per_month,
            "this_month_available_s2t"    => (int) $starter->total_speech_to_text_per_month,
            "payment_status"              => 1,
            "subscription_status"         => 1
        ];


        return SubscriptionHistory::query()->create($payloads);

    }

}
