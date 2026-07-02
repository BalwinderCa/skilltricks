<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $expected_state_id
 * @property string $drift_type
 * @property float|null $magnitude
 * @property string|null $severity
 * @property Carbon $detected_at
 * @property-read ExpectedState $expectedState
 */
class DriftEvent extends Model
{
    protected $table = 'drift_events';

    protected $fillable = [
        'expected_state_id',
        'drift_type',
        'magnitude',
        'severity',
        'detected_at',
    ];

    protected $casts = [
        'magnitude' => 'float',
        'detected_at' => 'datetime',
    ];

    public function expectedState(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }
}
