<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Models\GoalVariant;
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
            // A goal written for one department's holder of a shared role is
            // theirs alone; one that names no department is everyone's.
            ->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', (int) $user->department_id))
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
            'resources' => $this->teamResources($g->searchUserChat, $user, $g),
            'waiting_on' => $g->dependsOn,
            'waiting_on_you' => $g->dependents,
            'response' => $responses->get($g->id),
            'obstacles' => $obstacles->get($g->id, collect()),
            'last_revised_at' => $g->revisions->max('created_at'),
        ]);

        return $cards;
    }

    /**
     * Goals whose resources are allocated to the user's department but are not
     * theirs by role: everyone in the department sees them, led by the people
     * holding the goal's role. Read-only here; the role holders act on them.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forDepartment(User $user): Collection
    {
        if (! $user->organization_id || ! $user->department_id) {
            return collect();
        }

        $own = $user->org_role_id ? $this->query($user)->pluck('id') : collect();

        /** @var Collection<int, array<string, mixed>> $cards */
        $cards = ExpectedState::query()
            ->whereIn('id', StrategyResource::whereNotNull('expected_state_id')
                ->where('department_id', (int) $user->department_id)->select('expected_state_id'))
            ->whereNotIn('id', $own)
            ->whereHas('searchUserChat', fn ($q) => $q->where('status', 'published')
                ->where('organization_id', (int) $user->organization_id))
            ->with(['searchUserChat', 'orgRole:id,name'])
            ->get()
            ->sortByDesc(fn (ExpectedState $g) => $g->searchUserChat->published_at)
            ->values()
            ->map(fn (ExpectedState $g) => [
                'goal' => $g,
                'strategy' => $g->searchUserChat,
                'company_goal' => $this->companyGoal($g->searchUserChat),
                'role_name' => $g->orgRole->name ?? $g->role,
                // The goal worded for this member's role, when one was written at publish.
                'my_version' => $user->org_role_id
                    ? GoalVariant::where('expected_state_id', $g->id)->where('org_role_id', (int) $user->org_role_id)->value('text')
                    : null,
                'leads' => User::where('organization_id', (int) $user->organization_id)
                    ->where('org_role_id', (int) $g->org_role_id)
                    ->when($g->department_id, fn ($q) => $q->where('department_id', (int) $g->department_id))
                    ->orderBy('name')->pluck('name'),
                'resources' => StrategyResource::where('expected_state_id', $g->id)->first(),
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

    /**
     * This goal's committed resources. An older strategy committed them per
     * department: the user's department's row, else the whole-organization row.
     */
    private function teamResources(SearchUserChat $chat, User $user, ExpectedState $goal): ?StrategyResource
    {
        $rows = $chat->committedResources();
        if ($rows->whereNotNull('expected_state_id')->isNotEmpty()) {
            return $rows->firstWhere('expected_state_id', $goal->id);
        }

        return ($user->department_id ? $rows->firstWhere('department_id', $user->department_id) : null)
            ?? $rows->firstWhere('department_id', null);
    }
}
