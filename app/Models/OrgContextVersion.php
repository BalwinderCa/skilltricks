<?php

namespace App\Models;

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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
