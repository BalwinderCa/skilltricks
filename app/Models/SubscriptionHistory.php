<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionHistory extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function subscriptionPackage()
    {
        return $this->belongsTo(SubscriptionPackage::class)->withTrashed();
    }
    public function offlinePaymentMethod()
    {
        return $this->belongsTo(OfflinePaymentMethod::class, 'offline_payment_id', 'id')->withDefault([
            'name' => 'not found'
        ]);
    }
    public function recurringPayment()
    {
        return $this->belongsTo(SubscriptionRecurringPayment::class, 'subscription_history_id', 'id');
    }
}
