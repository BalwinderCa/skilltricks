<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's response to a published goal.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int $user_id
 * @property string|null $decision
 */
class GoalResponse extends Model
{
    protected $fillable = ['expected_state_id', 'user_id', 'decision', 'decided_at', 'starting_point', 'committed_at', 'starting_history'];

    protected $casts = ['decided_at' => 'datetime', 'committed_at' => 'datetime', 'starting_history' => 'array'];

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
