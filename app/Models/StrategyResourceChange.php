<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One field changed on a published strategy's resources: who, when, old, new.
 *
 * @property int $id
 * @property int $strategy_resource_id
 * @property int $user_id
 * @property string $field
 * @property string|null $old_value
 * @property string|null $new_value
 */
class StrategyResourceChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'strategy_resource_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<StrategyResource, $this> */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(StrategyResource::class, 'strategy_resource_id');
    }
}
