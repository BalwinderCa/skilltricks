<?php

namespace App\Models;

use App\Notifications\EmailVerificationNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use Billable, HasApiTokens, HasFactory, HasRoles, Notifiable,SoftDeletes;

    // email verification notification
    public function sendVerificationNotification()
    {
        $this->notify(new EmailVerificationNotification);
    }

    // registration notification
    public function registrationNotification()
    {
        $this->notify(new WelcomeNotification);
    }

    // guarded — use explicit fillable to prevent privilege escalation via mass assignment
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'postal_code',
        'provider_id',
        'email_or_otp_verified',
        'verification_code',
        'new_email_verification_code',
        'email_verified_at',
        'remember_token',
        'company',
        'organization_id',
        'hierarchy_rank',
        'company_name',
        'company_address',
        'number_employess',
        'chat_role_categories',
        'company_category',
        'about_company',
        'referral_code',
    ];

    // hidden for serializations
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // should be casted
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // role
    public function role()
    {
        return $this->belongsTo(SpatieRole::class);
    }

    // organization
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // subscriptionPackage
    public function subscriptionPackage()
    {
        return $this->belongsTo(SubscriptionPackage::class)->withTrashed();
    }

    // subscriptionHistories
    public function subscriptionHistories()
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    // referred users
    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by', 'id');
    }

    // created users
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')->withDefault([
            'name' => 'not found',
        ]);
    }

    // referred users earnings
    public function referredUserEarnings()
    {
        return $this->hasMany(AffiliateEarning::class, 'referred_by', 'id');
    }

    // affiliatePayoutAccounts
    public function affiliatePayoutAccounts()
    {
        return $this->hasMany(AffiliatePayoutAccount::class);
    }

    // non paid subscriptionHistories
    public function nonPaidSubscriptionHistories()
    {
        return $this->subscriptionHistories()->where('payment_status', '!=', 1)->where('payment_method', 'offline');
    }

    // active package
    public function currentPackage()
    {
        return $this->hasOne(SubscriptionHistory::class, 'user_id', 'id')->where('subscription_status', 1);
    }

    // subscriped package
    public function subscribeds()
    {
        return $this->hasMany(SubscriptionHistory::class, 'user_id', 'id')->where('subscription_status', 3);
    }

    // avatar image
    public function profileImage()
    {
        return $this->belongsTo(MediaManager::class, 'avatar', 'id')->withDefault();
    }
}
