<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A published goal reworded for one role in its department.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int $org_role_id
 * @property string $text
 */
class GoalVariant extends Model
{
    protected $fillable = ['expected_state_id', 'org_role_id', 'text'];

    /** @return BelongsTo<ExpectedState, $this> */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }
}
