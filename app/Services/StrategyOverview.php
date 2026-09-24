<?php

namespace App\Services;

use App\Models\DriftEvent;
use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Models\GoalRevision;
use App\Models\SearchUserChat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Executive view (Features spec, phase 5): how every published strategy in the
 * viewer's organization is going.
 */
class StrategyOverview
{
    public function __construct(protected MyGoals $goals, protected Alignment $alignment) {}

    /** @return Collection<int, array<string, mixed>> */
    public function list(User $viewer): Collection
    {
        if (! $viewer->organization_id) {
            return collect();
        }

        // ponytail: a few queries per strategy; batch them if an organization
        // publishes dozens.
        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $this->published($viewer)->with('publisher:id,name')->orderByDesc('published_at')->get()
            ->map(function (SearchUserChat $chat) {
                $ids = ExpectedState::where('search_user_chat_id', $chat->id)->pluck('id');

                return [
                    'chat' => $chat,
                    'company_goal' => $this->goals->companyGoal($chat),
                    'alignment' => $this->alignment->forStrategy($chat)['overall'],
                    'drift' => $this->drift($ids),
                    'obstacles' => GoalObstacle::whereIn('expected_state_id', $ids)->count(),
                    'not_viable' => GoalResponse::whereIn('expected_state_id', $ids)->where('decision', 'not_viable')->count(),
                ];
            });

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function detail(User $viewer, int $chatId): ?array
    {
        if (! $viewer->organization_id) {
            return null;
        }
        $chat = $this->published($viewer)->with('publisher:id,name')->whereKey($chatId)->first();
        if (! $chat) {
            return null;
        }

        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->with('orgRole:id,name')->orderBy('id')->get();
        $ids = $goals->pluck('id');
        $responses = GoalResponse::whereIn('expected_state_id', $ids)->get()->groupBy('expected_state_id');
        $holders = User::where('organization_id', $chat->organization_id)->whereNotNull('org_role_id')
            ->selectRaw('org_role_id, count(*) as n')->groupBy('org_role_id')->pluck('n', 'org_role_id');
        $latest = $this->latestDrift($ids);

        return [
            'chat' => $chat,
            'company_goal' => $this->goals->companyGoal($chat),
            'alignment' => $this->alignment->forStrategy($chat),
            'drift' => $this->drift($ids),
            'goals' => $goals->map(function (ExpectedState $goal) use ($responses, $holders, $latest) {
                $mine = $responses->get($goal->id, collect());

                return [
                    'goal' => $goal,
                    'role' => $goal->orgRole->name ?? $goal->role,
                    'holders' => (int) ($holders[(int) $goal->org_role_id] ?? 0),
                    'committed' => $mine->whereNotNull('starting_point')->count(),
                    'decisions' => collect(MyGoals::DECISIONS)->mapWithKeys(fn (string $d) => [$d => $mine->where('decision', $d)->count()])->all(),
                    'drift' => $latest->get($goal->id),
                ];
            }),
            'obstacles' => GoalObstacle::whereIn('expected_state_id', $ids)->with(['user:id,name', 'goal.orgRole:id,name'])->orderByDesc('id')->get(),
            'revisions' => GoalRevision::whereIn('expected_state_id', $ids)->with(['user:id,name', 'goal.orgRole:id,name'])->orderByDesc('id')->get(),
        ];
    }

    /** @return Builder<SearchUserChat> */
    private function published(User $viewer): Builder
    {
        return SearchUserChat::where('status', 'published')->where('organization_id', (int) $viewer->organization_id);
    }

    /**
     * Each goal's most recent drift type, as the OI engine last recorded it.
     *
     * @param  Collection<int, mixed>  $goalIds
     * @return Collection<int, string>
     */
    private function latestDrift(Collection $goalIds): Collection
    {
        return DriftEvent::whereIn('expected_state_id', $goalIds)->orderBy('id')->get(['expected_state_id', 'drift_type'])
            ->keyBy(fn (DriftEvent $e) => (int) $e->expected_state_id)
            ->map(fn (DriftEvent $e) => (string) $e->drift_type);
    }

    /** @param  Collection<int, mixed>  $goalIds */
    private function drift(Collection $goalIds): string
    {
        $latest = $this->latestDrift($goalIds);
        if ($latest->isEmpty()) {
            return 'Not measured yet';
        }
        $drifting = $latest->reject(fn (string $type) => $type === '' || $type === 'None')->count();

        return $drifting === 0 ? 'On track' : 'Drift on '.$drifting.' '.Str::plural('goal', $drifting);
    }
}
