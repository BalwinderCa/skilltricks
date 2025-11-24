<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenAiKey extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function scopeIsActive($query)
    {
        return $query->where('is_active', 1);
    }
}
