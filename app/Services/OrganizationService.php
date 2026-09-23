<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Organization;
use App\Models\OrgContextVersion;
use App\Models\OrgRole;
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
     * The six-rung ladder, named. Lives here rather than in a Blade file so the
     * roster and the CSV import can never disagree about what a rank is called.
     */
    public const RANK_LABELS = [
        10 => 'Individual Contributor',
        20 => 'Manager',
        30 => 'Director',
        40 => 'Vice President',
        50 => 'C-Suite',
        60 => 'Board',
    ];

    /**
     * Find or create the organization an email address belongs to.
     *
     * A shared email domain is the trust boundary: you cannot join an
     * organization whose email you cannot receive. Free consumer domains are
     * keyed to the full address so their users stay isolated.
     *
     * That boundary is only as strong as the site's
     * `registration_verification_with` setting. With verification disabled,
     * addresses are never confirmed, so organization membership is effectively
     * self-assigned — anyone can claim any domain by typing it at signup.
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
            $org = Organization::firstOrCreate(['domain' => $domain]);

            // Only on the create half: seeding is idempotent anyway, but running
            // it on every lookup would be six queries on every registration.
            if ($org->wasRecentlyCreated) {
                $this->seedDefaultRoles($org);
            }

            return $org;
        } catch (QueryException $e) {
            $existing = Organization::where('domain', $domain)->first();

            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }

    /**
     * Give an organization the six default roles.
     *
     * Named exactly after RANK_LABELS so the ladder an organization starts with
     * is the one it already had: a CSV whose role column says "Manager" keeps
     * importing, and a backfilled member lands on the role matching the rank they
     * already held.
     *
     * Least privilege: readable, not writable. Nothing enforces either flag yet,
     * and a default that grants write would be the wrong habit to start.
     *
     * Idempotent -- firstOrCreate on the same unique key the table carries, so
     * the backfill and a re-registration can both call it safely.
     */
    public function seedDefaultRoles(Organization $org): void
    {
        foreach (self::RANK_LABELS as $level => $name) {
            OrgRole::firstOrCreate(
                ['organization_id' => $org->id, 'name' => $name],
                ['level' => $level, 'can_read' => true, 'can_write' => false],
            );
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

        // Every caller today passes a matched pair, but this method is the
        // enforcement point for the tenancy boundary: a mismatched pair would
        // write one organization's baseline from another organization's member,
        // into a row that can never be deleted.
        if ((int) $user->organization_id !== (int) $org->id) {
            throw new \InvalidArgumentException(
                "User {$user->id} does not belong to organization {$org->id}."
            );
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
     * May this user publish a strategy to their organization? The org chart
     * decides, not seniority levels: the owner, a department head, or anyone
     * with a direct report.
     */
    public function canPublish(User $user): bool
    {
        $orgId = $user->organization_id;
        if (! $orgId) {
            return false;
        }

        return Organization::where('id', $orgId)->where('owner_user_id', $user->id)->exists()
            || Department::where('organization_id', $orgId)->where('head_user_id', $user->id)->exists()
            || User::where('organization_id', $orgId)->where('manager_id', $user->id)->exists();
    }

    /**
     * Put a user in an organization, claiming ownership if it is unowned.
     *
     * Ownership goes to whoever registers first on the domain, independent of
     * who fills in their role first — so it is settled here, not by rank.
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
        // Known limitation: a corrected member who re-saves their profile Role
        // re-declares the higher rank, and recordContext() will take it. The owner
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
     * Take a member off an organization's roster.
     *
     * The account survives — only the membership is cleared, so the person can
     * register or be re-added later, and their context versions stay on record.
     *
     * Detach first, re-elect second, both inside the lock: recomputeActiveContext()
     * elects from the members the organization still has, so a version declared
     * by someone who has just left can no longer win. Doing it the other way
     * round leaves an evicted member's declaration governing.
     */
    public function detachMember(User $owner, User $member): void
    {
        $org = $member->organization;

        if (! $org || (int) $org->owner_user_id !== (int) $owner->id) {
            throw new \RuntimeException('Only the organization owner can remove a member.');
        }

        if ((int) $member->id === (int) $org->owner_user_id) {
            throw new \RuntimeException('The organization owner cannot be removed.');
        }

        DB::transaction(function () use ($org, $member) {
            Organization::whereKey($org->id)->lockForUpdate()->first();

            $member->forceFill([
                'organization_id' => null,
                'hierarchy_rank' => null,
                'department_id' => null,
            ])->save();

            $this->recomputeActiveContext($org);
        });
    }

    /**
     * Re-elect the governing context after a rank correction: the highest-ranked
     * version whose declarer still holds at least that rank today. Demotion is
     * why this exists — recordContext() only ever raises the active pointer.
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
