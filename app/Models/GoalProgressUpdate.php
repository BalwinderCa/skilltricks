<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One progress report on a goal: status, percent, note.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int $user_id
 * @property string $status
 * @property int $pct
 * @property string|null $note
 */
class GoalProgressUpdate extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['expected_state_id', 'user_id', 'status', 'pct', 'note'];

    protected $casts = ['pct' => 'integer'];

    /** @return BelongsTo<ExpectedState, $this> */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
