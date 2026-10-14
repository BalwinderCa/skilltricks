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
        'command_settings',
    ];

    protected $casts = ['command_settings' => 'array'];

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

    /** @return HasMany<Department, $this> */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /** @return HasMany<User, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<OrgRole, $this> */
    public function roles(): HasMany
    {
        return $this->hasMany(OrgRole::class);
    }

    /**
     * The domain every member's address ends in, or null when there is none to
     * share: a free-mail organization's "domain" is its owner's whole address,
     * and a domain-less one is "user:{id}".
     */
    public function emailDomain(): ?string
    {
        $domain = (string) $this->domain;

        return $domain === '' || str_contains($domain, '@') || str_contains($domain, ':') ? null : $domain;
    }

    /**
     * May this user manage the organization -- its team, roles, departments and
     * settings? The owner may, and so may anyone holding a Super Admin role.
     *
     * Cast, not compared strictly: MySQL hands these ids back as strings.
     */
    public function isAdmin(?User $user): bool
    {
        if (! $user || (int) $user->organization_id !== (int) $this->id) {
            return false;
        }

        if ((int) $this->owner_user_id === (int) $user->id) {
            return true;
        }

        $role = $user->orgRole;

        return $role !== null && $role->is_admin && (int) $role->organization_id === (int) $this->id;
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
