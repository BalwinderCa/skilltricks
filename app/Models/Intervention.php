<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $expected_state_id
 * @property string $ai_recommendation
 * @property string $status
 * @property string|null $activated_at
 */
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

    public function expectedState(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }
}
