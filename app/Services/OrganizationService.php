<?php

namespace App\Services;

use App\Models\Organization;

class OrganizationService
{
    /**
     * Find or create the organization an email address belongs to.
     *
     * A shared email domain is the trust boundary: you cannot join an
     * organization whose email you cannot receive. Free consumer domains are
     * keyed to the full address so their users stay isolated.
     */
    public function resolveForEmail(string $email): Organization
    {
        $email = strtolower(trim($email));
        $at = strrpos($email, '@');

        if ($at === false) {
            // Not addressable, so not verifiable. Give it its own singleton org
            // rather than silently grouping it with anything else.
            return Organization::firstOrCreate(['domain' => $email]);
        }

        $domain = substr($email, $at + 1);
        $isFree = in_array($domain, config('organizations.free_domains', []), true);

        return Organization::firstOrCreate(['domain' => $isFree ? $email : $domain]);
    }
}
