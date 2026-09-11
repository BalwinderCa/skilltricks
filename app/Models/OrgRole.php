<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A role an organization owner defines for their own organization.
 *
 * A role is a name plus permissions, and nothing else. It carries no seniority:
 * users.hierarchy_rank is set independently and still drives the org chart's
 * order, OI's "highest rank governs" election and the chat role lookup, so
 * changing somebody's role no longer moves their rank.
 *
 * can_read / can_write are stored but nothing enforces them yet.
 */
class OrgRole extends Model
{
    protected $fillable = ['organization_id', 'name', 'can_read', 'can_write'];

    protected $casts = [
        'can_read' => 'boolean',
        'can_write' => 'boolean',
    ];

    /** The two permissions the roles page offers today. */
    public const PERMISSIONS = ['read' => 'can_read', 'write' => 'can_write'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'org_role_id');
    }
}
