<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrgContextVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    /**
     * The only ranks this feature recognises. LLM output is untrusted, so any
     * rank crossing into persistence is checked against this list.
     */
    public const VALID_RANKS = [10, 20, 30, 40, 50, 60];

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

    /**
     * Append a context declaration and re-evaluate which one governs.
     *
     * The insert is unconditional — every input from every hierarchy level is
     * preserved, per the brief's "Full Input Persistence" rule. Only the active
     * pointer is contested, and the highest rank wins it. Ties go to the newer
     * declaration so a peer refreshing a stale baseline needs no escalation.
     */
    public function recordContext(
        Organization $org,
        User $user,
        int $rank,
        array $profile,
        ?array $transcript = null
    ): OrgContextVersion {
        if (! in_array($rank, self::VALID_RANKS, true)) {
            throw new \InvalidArgumentException("Unrecognised hierarchy rank: {$rank}");
        }

        return DB::transaction(function () use ($org, $user, $rank, $profile, $transcript) {
            $version = OrgContextVersion::create([
                'organization_id' => $org->id,
                'user_id' => $user->id,
                'rank' => $rank,
                'profile' => $profile,
                'transcript' => $transcript,
            ]);

            $user->forceFill(['hierarchy_rank' => $rank])->save();

            $active = $org->fresh()->activeContext;

            if (! $active || $rank >= $active->rank) {
                $org->forceFill(['active_context_id' => $version->id])->save();
            }

            return $version;
        });
    }
}
