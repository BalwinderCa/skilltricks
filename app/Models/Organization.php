<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'domain',
        'name',
        'owner_user_id',
        'active_context_id',
    ];

    /** @return BelongsTo<OrgContextVersion, $this> */
    public function activeContext(): BelongsTo
    {
        return $this->belongsTo(OrgContextVersion::class, 'active_context_id');
    }

    /** @return HasMany<OrgContextVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(OrgContextVersion::class);
    }

    /** @return HasMany<User, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
