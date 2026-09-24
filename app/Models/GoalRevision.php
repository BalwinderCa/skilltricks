<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One change a leader made to a goal's wording.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int $user_id
 * @property string $old_text
 * @property string $new_text
 * @property string|null $reason
 */
class GoalRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['expected_state_id', 'user_id', 'old_text', 'new_text', 'reason'];

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
