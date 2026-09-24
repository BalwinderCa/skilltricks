<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sub-goal a manager cascaded to a direct report (Notion Epic 3). The root is
 * always the published role goal; parent is set when a sub-goal is cascaded on.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int|null $parent_id
 * @property int $created_by
 * @property int $assignee_user_id
 * @property string $text
 * @property string|null $status
 * @property int|null $pct
 * @property string|null $note
 */
class GoalCascade extends Model
{
    protected $fillable = ['expected_state_id', 'parent_id', 'created_by', 'assignee_user_id', 'text', 'sent_at', 'status', 'pct', 'note', 'progress_at'];

    protected $casts = ['sent_at' => 'datetime', 'progress_at' => 'datetime', 'pct' => 'integer'];

    /** @return BelongsTo<ExpectedState, $this> */
    public function root(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }

    /** @return BelongsTo<GoalCascade, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(GoalCascade::class, 'parent_id');
    }

    /** @return HasMany<GoalCascade, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(GoalCascade::class, 'parent_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }
}
