<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalResponse;
use App\Models\SearchUserChat;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Where to Begin (Features spec, phase 4): how many of the people a published
 * strategy asks something of have committed to a starting point, per department.
 */
class Alignment
{
    /** @return array{overall: array{people: int, committed: int, rate: int|null}, departments: list<array{name: string, people: int, committed: int, rate: int|null}>} */
    public function forStrategy(SearchUserChat $chat): array
    {
        $roleIds = ExpectedState::where('search_user_chat_id', $chat->id)->whereNotNull('org_role_id')
            ->pluck('org_role_id')->map(fn ($id) => (int) $id)->unique()->values();

        $people = $chat->organization_id && $roleIds->isNotEmpty()
            ? User::where('organization_id', $chat->organization_id)->whereIn('org_role_id', $roleIds)
                ->with('department:id,name')->get(['id', 'org_role_id', 'department_id'])
            : collect();
        $roleOf = $people->mapWithKeys(fn (User $u) => [(int) $u->id => (int) $u->org_role_id]);

        // Only a pick on the person's own role's goal counts as their commitment.
        $committed = GoalResponse::whereNotNull('starting_point')
            ->where('decision', 'act_on_it')
            ->whereIn('user_id', $people->pluck('id'))
            ->whereHas('goal', fn ($q) => $q->where('search_user_chat_id', $chat->id))
            ->with('goal:id,org_role_id')->get()
            ->filter(fn (GoalResponse $r) => (int) $r->goal->org_role_id === ($roleOf[(int) $r->user_id] ?? -1))
            ->pluck('user_id')->map(fn ($id) => (int) $id)->unique();

        $summary = function (string $name, Collection $group) use ($committed): array {
            $count = $group->count();
            $done = $group->filter(fn (User $u) => $committed->contains((int) $u->id))->count();

            return ['name' => $name, 'people' => $count, 'committed' => $done, 'rate' => $count ? (int) round(100 * $done / $count) : null];
        };

        $overall = $summary('', $people);
        unset($overall['name']);

        return [
            'overall' => $overall,
            'departments' => $people->groupBy(fn (User $u) => $u->department->name ?? 'No department')
                ->sortKeys()
                ->map(fn (Collection $group, string $name) => $summary($name, $group))
                ->values()->all(),
        ];
    }
}
