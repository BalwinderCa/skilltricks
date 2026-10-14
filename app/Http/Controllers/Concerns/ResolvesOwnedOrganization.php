<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Organization;

/**
 * The guard shared by every organization-management screen: the owner, or a
 * member holding a Super Admin role.
 *
 * Teams and Roles both write organization-scoped data, and both are owner-only.
 * Keeping the check here rather than copying it into the second controller is
 * the point: a per-action copy is a per-action chance to forget it.
 */
trait ResolvesOwnedOrganization
{
    /**
     * The caller's organization, or a 403.
     */
    protected function ownedOrganization(): Organization
    {
        $user = auth()->user();
        $org = $user->organization;

        if (! $org || ! $org->isAdmin($user)) {
            abort(403);
        }

        return $org;
    }
}
