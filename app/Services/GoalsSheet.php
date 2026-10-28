<?php

namespace App\Services;

use App\Models\SearchUserChat;
use App\Models\User;

/**
 * One published strategy's goals, person by person, as each sees them on their
 * dashboard: the department goal, its wording for their role, and their own
 * goal. Built on MyGoals so the sheet never disagrees with the dashboard.
 */
class GoalsSheet
{
    public const HEADINGS = ['Name', 'Email', 'Department', 'Role', 'Company goal', 'Department goal', 'Role goal', 'Your goal'];

    public function __construct(protected MyGoals $goals) {}

    /** @return list<list<string>> */
    public function rows(SearchUserChat $chat): array
    {
        $orgId = $chat->organization_id ?: User::whereKey($chat->user_id)->value('organization_id');
        if (! $orgId) {
            return [];
        }
        $company = $this->goals->companyGoal($chat);
        $ofThisStrategy = fn (array $card) => (int) $card['strategy']->id === (int) $chat->id;

        $rows = [];
        $people = User::where('organization_id', (int) $orgId)->with(['department:id,name', 'orgRole:id,name'])->orderBy('id')->get();
        foreach ($people as $user) {
            $dept = $this->goals->forDepartment($user)->filter($ofThisStrategy)->values();
            $own = $this->goals->for($user)->filter($ofThisStrategy)->values();
            $person = [(string) $user->name, (string) $user->email, (string) $user->department?->name, (string) $user->orgRole?->name];

            // Everyone gets a row, so people with no goal in this strategy show up too.
            $count = max(1, $dept->count(), $own->count());
            for ($i = 0; $i < $count; $i++) {
                $d = $dept->get($i);
                $o = $own->get($i);
                $rows[] = array_merge($person, [
                    $d || $o ? $company : '',
                    (string) ($d['goal']->recommended_action ?? ''),
                    (string) ($d['my_version'] ?? ''),
                    (string) ($o['goal']->recommended_action ?? ''),
                ]);
            }
        }

        return $rows;
    }
}
