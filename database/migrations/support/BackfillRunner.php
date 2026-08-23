<?php

namespace Database\Migrations;

use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Support\Facades\DB;

class BackfillRunner
{
    /** The eight fields the old profile gate required. */
    private const REQUIRED = [
        'name', 'phone', 'company_name', 'company_address',
        'number_employess', 'chat_role_categories', 'company_category', 'about_company',
    ];

    public function run(): void
    {
        $service = app(OrganizationService::class);
        $ladder = DB::table('chat_role_categories')->whereNotNull('rank')->pluck('rank', 'name');

        User::whereNull('organization_id')->orderBy('id')->chunkById(200, function ($users) use ($service, $ladder) {
            foreach ($users as $user) {
                if (! empty($user->organization_id)) {
                    continue;
                }

                // One transaction per user. attachUser() commits organization_id
                // immediately, and the skip guard above keys on that column — so
                // without this wrapper, a recordContext() failure (a deadlock
                // against live traffic taking the same row lock, a dropped
                // connection) would leave the user with an organization and no
                // rank, and every future run would skip them forever. Nothing
                // could repair that row. Atomic per user: either both land or
                // neither does, and the next run retries cleanly.
                DB::transaction(function () use ($service, $ladder, $user) {
                    // resolveForUser handles email-less accounts (phone-only
                    // signup) by giving them their own singleton organization.
                    $org = $service->resolveForUser($user);
                    $service->attachUser($user, $org);

                    if (! $this->profileIsComplete($user)) {
                        // Membership is assigned but rank is not, so the gate
                        // routes them into the interview on next login.
                        return;
                    }

                    $rank = (int) ($ladder[$user->chat_role_categories] ?? 10);
                    $rank = in_array($rank, OrganizationService::VALID_RANKS, true) ? $rank : 10;

                    $service->recordContext($org, $user, $rank, [
                        'role' => (string) $user->chat_role_categories,
                        'rank' => $rank,
                        'scale' => trim($user->number_employess.' employees — '.$user->company_category),
                        'governance' => '',
                        'frictions' => [],
                        'summary_bullets' => array_values(array_filter([
                            $user->company_name ? 'Company: '.$user->company_name : null,
                            $user->company_address ? 'Based in: '.$user->company_address : null,
                            $user->about_company ? 'About: '.$user->about_company : null,
                        ])),
                    ], null); // a null transcript marks this row as backfilled, not interviewed
                });
            }
        });
    }

    private function profileIsComplete(User $user): bool
    {
        foreach (self::REQUIRED as $field) {
            if (empty($user->{$field})) {
                return false;
            }
        }

        return true;
    }
}
