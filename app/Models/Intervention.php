<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intervention extends Model
{
    protected $table = 'interventions';

    protected $fillable = [
        'expected_state_id',
        'ai_recommendation',
        'status',
        'activated_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
    ];

    public function expectedState()
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }
}
