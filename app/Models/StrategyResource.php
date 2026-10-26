<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One goal's committed resources for a strategy. Rows without a goal are the
 * earlier per-department allocations.
 *
 * @property int $id
 * @property int $search_user_chat_id
 * @property int|null $expected_state_id
 * @property int|null $department_id
 * @property string $department_name
 * @property string|null $budget
 * @property string|null $fte
 * @property string|null $tools
 * @property string|null $notes
 * @property array<string, mixed>|null $ai_suggestion
 */
class StrategyResource extends Model
{
    protected $fillable = [
        'search_user_chat_id',
        'expected_state_id',
        'department_id',
        'department_name',
        'budget',
        'fte',
        'tools',
        'notes',
        'ai_suggestion',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'fte' => 'decimal:2',
        'ai_suggestion' => 'array',
    ];

    /** @return BelongsTo<SearchUserChat, $this> */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(SearchUserChat::class, 'search_user_chat_id');
    }

    /** @return BelongsTo<ExpectedState, $this> */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }

    /** "CIO — IT Leadership" for a goal's row, the department alone for an older row. */
    public function label(): string
    {
        $role = $this->expected_state_id ? $this->goal?->role : null;

        return $role ? $role.' — '.$this->department_name : $this->department_name;
    }

    /** @return HasMany<StrategyResourceChange, $this> */
    public function changes(): HasMany
    {
        return $this->hasMany(StrategyResourceChange::class);
    }
}
