<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionRecurringPayment extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public function subscriptionHistory()
    {
        return $this->belongsTo(subscriptionHistory::class, 'subscription_hsitory_id', 'id');
    }
}
