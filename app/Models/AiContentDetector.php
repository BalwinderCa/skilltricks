<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiContentDetector extends Model
{
    use HasFactory;
    protected $casts = ['response'=>'object'];

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
