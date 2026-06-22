<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObservedState extends Model
{
    protected $table = 'observed_states';

    protected $fillable = [
        'expected_state_id',
        'actual_value',
        'status',
        'observation_date',
        'source',
        'status_notes',
    ];

    protected $casts = [
        'observation_date' => 'date',
    ];

    public function expectedState()
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }
}
