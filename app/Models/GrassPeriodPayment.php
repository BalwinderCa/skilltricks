<?php

namespace App\Models;

use App\Models\User;
use App\Models\SubscriptionPackage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GrassPeriodPayment extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = ['response'=>'object'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function subscriptionPackage()
    {
        return $this->belongsTo(SubscriptionPackage::class)->withTrashed();
    }
}
