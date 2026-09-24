<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something in the way of a published goal, reported by someone who holds it.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int $user_id
 * @property string $body
 */
class GoalObstacle extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['expected_state_id', 'user_id', 'body'];

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
