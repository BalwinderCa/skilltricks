<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [''];

    protected $appends = ['sell_price'];
    public function scopeIsActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function subscription_package_templates()
    {
        return $this->belongsToMany(Template::class, 'subscription_package_templates', 'subscription_package_id', 'template_id');
    }

    public function openai_model()
    {
        return $this->belongsTo(OpenAiModel::class);
    }
    public function getSellPriceAttribute()
    {
        if($this->discount_status == 1 && $this->discount){
            return $this->discount_price;
        }
        return $this->price;
    }
}
