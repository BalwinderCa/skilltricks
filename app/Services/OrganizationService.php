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

        if ($email === '') {
            // Refuse rather than bucket. An empty key would become a shared
            // organization that every other empty input joins — the exact
            // cross-tenant merge this method exists to prevent.
            throw new \InvalidArgumentException('Cannot resolve an organization from an empty email address.');
        }

        $at = strrpos($email, '@');
        // Trailing DNS root dot: acme.com. and acme.com are the same domain.
        $domain = $at === false ? '' : rtrim(substr($email, $at + 1), '.');

        if ($domain === '') {
            // No '@' at all, or nothing after it ('alice@'). Not addressable, so
            // not verifiable: key on the whole string so each such address gets
            // its own singleton org instead of sharing one.
            return Organization::firstOrCreate(['domain' => $email]);
        }

        $isFree = in_array($domain, config('organizations.free_domains', []), true);

        return Organization::firstOrCreate(['domain' => $isFree ? $email : $domain]);
    }
}
