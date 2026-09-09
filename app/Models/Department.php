<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $color
 * @property int|null $head_user_id
 */
class Department extends Model
{
    /**
     * The colours a department can wear. A fixed set rather than a free colour
     * input: every swatch here is legible against both themes' card
     * backgrounds, which an arbitrary hex is not.
     */
    public const PALETTE = [
        '#4F46E5', // indigo
        '#22C55E', // green
        '#F59E0B', // amber
        '#A855F7', // purple
        '#3B82F6', // blue
        '#EC4899', // pink
        '#14B8A6', // teal
        '#EF4444', // red
    ];

    protected $fillable = [
        'organization_id',
        'name',
        'color',
        'head_user_id',
    ];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The chosen lead, if there is one.
     *
     * @return BelongsTo<User, $this>
     */
    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    /** @return HasMany<User, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** The next unused colour for an organization, so a new one stands apart. */
    public static function nextColorFor(int $organizationId): string
    {
        $used = static::where('organization_id', $organizationId)->count();

        return self::PALETTE[$used % count(self::PALETTE)];
    }
}
