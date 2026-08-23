<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrgContextVersion;
use App\Models\User;
use Illuminate\Database\QueryException;
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
            return $this->firstOrCreateDomain($email);
        }

        $isFree = in_array($domain, config('organizations.free_domains', []), true);

        return $this->firstOrCreateDomain($isFree ? $email : $domain);
    }

    /**
     * firstOrCreate is read-then-write, so two people registering on the same
     * brand-new domain at once can both pass the SELECT and one hits the unique
     * index. This runs inside registration's transaction, where a raw
     * "Integrity constraint violation" would be flashed straight at the user.
     * The loser simply re-reads the row the winner just created.
     */
    private function firstOrCreateDomain(string $domain): Organization
    {
        try {
            return Organization::firstOrCreate(['domain' => $domain]);
        } catch (QueryException $e) {
            $existing = Organization::where('domain', $domain)->first();

            if ($existing) {
                return $existing;
            }

            throw $e;
        }
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
            // Serialise concurrent calibrations for this organization. Without the
            // lock, two members confirming at the same moment each decide against a
            // pre-commit snapshot, and the lower rank can land last and govern — the
            // exact failure this rule exists to prevent. Real on MySQL; Laravel
            // compiles it to an empty string on SQLite, so tests are unaffected.
            $locked = Organization::whereKey($org->id)->lockForUpdate()->first();

            $version = OrgContextVersion::create([
                'organization_id' => $org->id,
                'user_id' => $user->id,
                'rank' => $rank,
                'profile' => $profile,
                'transcript' => $transcript,
            ]);

            $user->forceFill(['hierarchy_rank' => $rank])->save();

            $active = $locked?->activeContext;

            if (! $active || $rank >= $active->rank) {
                // Written through the caller's instance so it stays in sync with
                // the database for the rest of the request.
                $org->forceFill(['active_context_id' => $version->id])->save();
            }

            return $version;
        });
    }

    /**
     * Resolve the organization for a user account.
     *
     * Registration permits phone-only signup ("email" => "nullable"), so a user
     * may have no address at all. Those users get their own singleton
     * organization keyed on their id rather than an exception: a colon cannot
     * appear in a domain, so "user:12" can never collide with a real one, and
     * the isolation guarantee holds exactly as it does for free-domain users.
     */
    public function resolveForUser(User $user): Organization
    {
        $email = strtolower(trim((string) $user->email));

        return $email === ''
            ? $this->firstOrCreateDomain('user:'.$user->id)
            : $this->resolveForEmail($email);
    }

    /**
     * Put a user in an organization, claiming ownership if it is unowned.
     *
     * Ownership goes to whoever registers first on the domain, independent of
     * who finishes calibrating first — so it is settled here, not in the
     * interview.
     */
    public function attachUser(User $user, Organization $org): void
    {
        // ponytail: the owner_user_id read-then-write is unlocked, unlike
        // recordContext(). Safe only because org creation and the ownership claim
        // happen inside one request's transaction today. If a caller ever
        // pre-creates an unowned org (invites, admin provisioning), wrap this in
        // a transaction with lockForUpdate() the way recordContext() does.
        $user->forceFill(['organization_id' => $org->id])->save();

        $updates = [];

        if (empty($org->owner_user_id)) {
            $updates['owner_user_id'] = $user->id;
        }

        if (empty($org->name) && ! empty($user->company_name)) {
            $updates['name'] = $user->company_name;
        }

        if ($updates !== []) {
            $org->forceFill($updates)->save();
        }
    }

    /**
     * Let an organization's owner correct a member's self-declared rank.
     *
     * A correction is not a new declaration, so no version row is written. The
     * member's existing inputs stay on record; only which one governs can change.
     */
    public function setMemberRank(User $owner, User $member, int $rank): void
    {
        // Known limitation: a corrected member who runs the interview again can
        // re-declare the higher rank, and recordContext() will take it. The owner
        // can correct it again, and every claim stays on record. Locking a
        // corrected rank is deferred until it is actually asked for.
        if (! in_array($rank, self::VALID_RANKS, true)) {
            throw new \InvalidArgumentException("Unrecognised hierarchy rank: {$rank}");
        }

        $org = $member->organization;

        if (! $org || (int) $org->owner_user_id !== (int) $owner->id) {
            throw new \RuntimeException('Only the organization owner can change a member rank.');
        }

        DB::transaction(function () use ($org, $member, $rank) {
            // Same lock recordContext() takes: serialises this correction against
            // any concurrent declaration or correction for the organization so the
            // recompute below is never racing a write it can't see.
            Organization::whereKey($org->id)->lockForUpdate()->first();

            $member->forceFill(['hierarchy_rank' => $rank])->save();

            $this->recomputeActiveContext($org);
        });
    }

    /**
     * Re-elect the governing context after a rank correction: the highest-ranked
     * version whose declarer still holds at least that rank today.
     */
    private function recomputeActiveContext(Organization $org): void
    {
        $ranks = User::where('organization_id', $org->id)
            ->pluck('hierarchy_rank', 'id');

        $winner = $org->versions()
            ->orderByDesc('rank')
            ->orderByDesc('id')
            ->get()
            ->first(fn ($version) => (int) ($ranks[$version->user_id] ?? 0) >= (int) $version->rank);

        $org->forceFill(['active_context_id' => $winner?->id])->save();
    }
}
