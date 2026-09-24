<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\StrategyResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * What a person sees of published strategies: the goals for their role, in
 * their organization. The one place that visibility rule lives.
 */
class MyGoals
{
    public const DECISIONS = ['act_on_it', 'review_in_detail', 'not_viable'];

    /** @return Builder<ExpectedState> */
    public function query(User $user): Builder
    {
        // A null role never matches: unlinked goals also have a null role.
        return ExpectedState::query()
            ->whereNotNull('org_role_id')
            ->where('org_role_id', (int) $user->org_role_id)
            ->whereHas('searchUserChat', fn ($q) => $q->where('status', 'published')
                ->where('organization_id', (int) $user->organization_id));
    }

    public function visibleGoal(User $user, int $goalId): ?ExpectedState
    {
        if (! $user->organization_id || ! $user->org_role_id) {
            return null;
        }

        return $this->query($user)->whereKey($goalId)->first();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function for(User $user): Collection
    {
        if (! $user->organization_id || ! $user->org_role_id) {
            return collect();
        }

        $goals = $this->query($user)
            ->with(['searchUserChat', 'orgRole:id,name', 'dependsOn.orgRole:id,name', 'dependents.orgRole:id,name', 'revisions'])
            ->get()
            ->sortByDesc(fn (ExpectedState $g) => $g->searchUserChat->published_at)
            ->values();
        $ids = $goals->pluck('id');
        $responses = GoalResponse::where('user_id', $user->id)->whereIn('expected_state_id', $ids)->get()->keyBy('expected_state_id');
        $obstacles = GoalObstacle::where('user_id', $user->id)->whereIn('expected_state_id', $ids)->orderByDesc('id')->get()->groupBy('expected_state_id');

        // ponytail: two queries per card (company goal, resources); fine for a
        // handful of published strategies, batch them if a dashboard shows dozens.
        /** @var Collection<int, array<string, mixed>> $cards */
        $cards = $goals->map(fn (ExpectedState $g) => [
            'goal' => $g,
            'strategy' => $g->searchUserChat,
            'company_goal' => $this->companyGoal($g->searchUserChat),
            'role_name' => $g->orgRole?->name,
            'resources' => $this->teamResources($g->searchUserChat, $user),
            'waiting_on' => $g->dependsOn,
            'waiting_on_you' => $g->dependents,
            'response' => $responses->get($g->id),
            'obstacles' => $obstacles->get($g->id, collect()),
            'last_revised_at' => $g->revisions->max('created_at'),
        ]);

        return $cards;
    }

    /** The strategy's first question; the chat row has no goal column. */
    public function companyGoal(SearchUserChat $chat): string
    {
        $first = SearchUserChatData::where('search_user_chat_id', $chat->id)->orderBy('id')->value('search');
        if (filled($first)) {
            return trim((string) $first);
        }

        return trim(ltrim((string) strtok((string) $chat->leadership_brief, "\n"), '# '));
    }

    /** The user's department's committed resources, else the whole-organization row. */
    private function teamResources(SearchUserChat $chat, User $user): ?StrategyResource
    {
        $rows = $chat->resources()->get();

        return ($user->department_id ? $rows->firstWhere('department_id', $user->department_id) : null)
            ?? $rows->firstWhere('department_id', null);
    }
}
