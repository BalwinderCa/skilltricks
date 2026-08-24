<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgContextVersion extends Model
{
    /**
     * Append-only. The brief's "Full Input Persistence" rule says lower-level
     * responses are never hard-deleted, so mutation is blocked at the model
     * rather than left to convention.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'user_id',
        'rank',
        'profile',
        'transcript',
    ];

    protected $casts = [
        'profile' => 'array',
        'transcript' => 'array',
        'rank' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('org_context_versions is append-only; a context version cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('org_context_versions is append-only; a context version cannot be deleted.');
        });
    }

    /**
     * Model events only fire on loaded instances, so a query-builder mass
     * update or delete would slip past the guards in booted(). Routing this
     * model through a builder that refuses both closes every Eloquent path.
     *
     * ponytail: raw DB::table('org_context_versions') still bypasses this —
     * closing that would need a database trigger, which is disproportionate
     * for a table only this application writes.
     */
    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder
        {
            public function update(array $values)
            {
                throw new \RuntimeException('org_context_versions is append-only; a context version cannot be updated.');
            }

            public function delete()
            {
                throw new \RuntimeException('org_context_versions is append-only; a context version cannot be deleted.');
            }
        };
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
