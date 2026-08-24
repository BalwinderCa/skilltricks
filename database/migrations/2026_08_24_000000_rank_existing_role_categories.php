<?php

use App\Models\OrgContextVersion;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The rank ladder migration inserted six new categories instead of ranking
     * the ones already in the table. Real installs already had their own
     * categories (CEO, CFO, Engineering Leader, ...) with rank NULL, so every
     * backfilled user fell to the rank-10 floor — including CEOs. That inverts
     * the rule the feature exists to enforce: the highest-ranking calibrated
     * user's context is meant to govern, and instead a CEO would be outranked
     * by the first person to finish an interview.
     */
    private const RANKS = [
        'CEO' => 50,
        'CMO' => 50,
        'CSO' => 50,
        'CHRO' => 50,
        'CFO' => 50,
        'CGO' => 50,
        'Leadership Scope & Span' => 40,
        'Engineering Leader' => 30,
        'Universal' => 10,
    ];

    /** The orphaned rows the earlier migration added. */
    private const ORPHANS = [
        'Individual Contributor', 'Manager', 'Director',
        'Vice President', 'C-Suite', 'Board',
    ];

    public function up()
    {
        if (! Schema::hasTable('chat_role_categories') || ! Schema::hasColumn('chat_role_categories', 'rank')) {
            return;
        }

        $ranked = 0;

        foreach (self::RANKS as $name => $rank) {
            $ranked += DB::table('chat_role_categories')->where('name', $name)->update(['rank' => $rank]);
        }

        // Only clear the orphans on an install that has its own ranked taxonomy.
        // A fresh install has none of the names above, so the six rows ARE the
        // ladder there — deleting them would leave nothing rankable at all.
        if ($ranked > 0) {
            // Even then, only drop an orphan nothing points at. A category a
            // user selected is real data regardless of where it came from.
            foreach (self::ORPHANS as $name) {
                $row = DB::table('chat_role_categories')->where('name', $name)->first();

                if (! $row) {
                    continue;
                }

                if (! User::where('chat_role_categories', (string) $row->id)->exists()) {
                    DB::table('chat_role_categories')->where('id', $row->id)->delete();
                }
            }
        }

        $this->recalibrateBackfilledUsers();
    }

    /**
     * Re-declare context for users the backfill floored to rank 10.
     *
     * Their existing version has rank 10 written into the row, and the active
     * pointer is elected by the version's own rank — so raising the user's rank
     * alone would still leave them below any later interview. A new version at
     * the correct rank is appended instead. The original stays on record: that
     * is the audit trail working, not a workaround.
     */
    private function recalibrateBackfilledUsers(): void
    {
        $service = app(OrganizationService::class);
        $ladder = DB::table('chat_role_categories')->whereNotNull('rank')->pluck('rank', 'id');
        $names = DB::table('chat_role_categories')->pluck('name', 'id');

        User::whereNotNull('organization_id')->whereNotNull('chat_role_categories')->orderBy('id')
            ->chunkById(200, function ($users) use ($service, $ladder, $names) {
                foreach ($users as $user) {
                    $latest = OrgContextVersion::where('user_id', $user->id)->orderByDesc('id')->first();

                    // Only rows the backfill wrote (null transcript). A real
                    // interview's rank is the user's own declaration and is not
                    // ours to overwrite.
                    if (! $latest || $latest->transcript !== null) {
                        continue;
                    }

                    $key = (int) $user->chat_role_categories;
                    $rank = (int) ($ladder[$key] ?? 10);

                    if (! in_array($rank, OrganizationService::VALID_RANKS, true) || $rank === (int) $latest->rank) {
                        continue;
                    }

                    $profile = $latest->profile;
                    $profile['rank'] = $rank;
                    $profile['role'] = (string) ($names[$key] ?? ($profile['role'] ?? ''));

                    $service->recordContext($user->organization, $user, $rank, $profile, null);
                }
            });
    }

    public function down()
    {
        // Intentionally empty. Clearing these ranks would re-break the ladder,
        // and the appended context versions are append-only by design.
    }
};
