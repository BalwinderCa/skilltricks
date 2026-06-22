<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $expected_state_id
 * @property string|null $actual_value
 * @property string $status
 * @property string $observation_date
 * @property string $source
 * @property string|null $status_notes
 */
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

    public function expectedState(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }
}
