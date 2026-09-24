<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Leader edits (Features spec, phase 5): change a goal's wording, keep the
 * history, and tell the reporting chain up to the strategy's author.
 */
class GoalRevisions
{
    public function __construct(protected OrganizationService $orgs, protected StrategyAlerts $alerts) {}

    /** False when the wording did not change: nothing is written or sent. */
    public function revise(ExpectedState $goal, User $leader, string $text, ?string $reason): bool
    {
        $old = (string) $goal->recommended_action;
        $text = trim($text);
        $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
        if ($text === $old) {
            return false;
        }

        DB::transaction(function () use ($goal, $leader, $old, $text, $reason) {
            GoalRevision::create(['expected_state_id' => $goal->id, 'user_id' => $leader->id, 'old_text' => $old, 'new_text' => $text, 'reason' => $reason]);
            // Only the wording changes. The OI revised_* fields mean "the author
            // calibrated this in Review in Detail" and stay the author's; the
            // leader's edit lives in goal_revisions. Clearing the options lets
            // the next "Act on it" suggest starting points for the new wording.
            $goal->forceFill(['recommended_action' => $text, 'starting_options' => null])->save();
        });

        $chat = $goal->searchUserChat;
        $role = $goal->orgRole->name ?? $goal->role;
        $recipients = $this->orgs->managerChain($leader)->pluck('id')
            ->push($chat->user_id)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => $id === (int) $leader->id);

        foreach (User::whereIn('id', $recipients->all())->get() as $recipient) {
            // Relative on purpose: the notification controller redirects to '/'.$url.
            $this->alerts->send(
                $recipient,
                localize('Goal changed').': '.$role,
                'dashboard/strategies/'.$chat->id,
                $leader->name.': "'.Str::limit($old, 120).'" → "'.Str::limit($text, 120).'"',
                'goal_revision',
            );
        }

        return true;
    }
}
