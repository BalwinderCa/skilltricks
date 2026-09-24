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
    /*
     * Notion "Drift Status Logic". The page leaves friction undefined, so:
     * "high friction" = this many obstacles reported on the strategy, and a
     * "critical bottleneck" = a goal whose latest OI drift is Execution Blocked.
     */
    private const ON_TRACK_RATE = 85;

    private const MINOR_DRIFT_RATE = 60;

    private const HIGH_FRICTION = 3;

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
                $alignment = $this->alignment->forStrategy($chat)['overall'];
                $obstacles = GoalObstacle::whereIn('expected_state_id', $ids)->count();
                $latest = $this->latestDrift($ids);

                return [
                    'chat' => $chat,
                    'company_goal' => $this->goals->companyGoal($chat),
                    'alignment' => $alignment,
                    'drift' => $this->drift($ids),
                    'badge' => self::badge($alignment['rate'], $obstacles, $this->blockedGoals($latest)),
                    'obstacles' => $obstacles,
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
        $alignment = $this->alignment->forStrategy($chat);
        $obstacles = GoalObstacle::whereIn('expected_state_id', $ids)->with(['user:id,name', 'goal.orgRole:id,name'])->orderByDesc('id')->get();

        return [
            'chat' => $chat,
            'company_goal' => $this->goals->companyGoal($chat),
            'alignment' => $alignment,
            'drift' => $this->drift($ids),
            'badge' => self::badge($alignment['overall']['rate'], $obstacles->count(), $this->blockedGoals($latest)),
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
            'obstacles' => $obstacles,
            'revisions' => GoalRevision::whereIn('expected_state_id', $ids)->with(['user:id,name', 'goal.orgRole:id,name'])->orderByDesc('id')->get(),
        ];
    }

    /**
     * The Notion drift badge: On Track (alignment >= 85% and low friction),
     * Minor Drift (60-84% or high friction), Severe Drift (< 60% or a blocked goal).
     *
     * @return array{level: string, label: string, reasons: list<string>}
     */
    public static function badge(?int $rate, int $obstacles, int $blockedGoals): array
    {
        if ($rate === null) {
            return ['level' => 'none', 'label' => 'Not started', 'reasons' => ['Nobody holds a goal in this strategy yet']];
        }

        $reasons = ['Alignment '.$rate.'%'];
        if ($obstacles > 0) {
            $reasons[] = $obstacles.' '.Str::plural('obstacle', $obstacles).' reported';
        }
        if ($blockedGoals > 0) {
            $reasons[] = $blockedGoals.' '.Str::plural('goal', $blockedGoals).' blocked';
        }

        [$level, $label] = match (true) {
            $rate < self::MINOR_DRIFT_RATE || $blockedGoals > 0 => ['red', 'Severe Drift'],
            $rate < self::ON_TRACK_RATE || $obstacles >= self::HIGH_FRICTION => ['yellow', 'Minor Drift'],
            default => ['green', 'On Track'],
        };

        return ['level' => $level, 'label' => $label, 'reasons' => $reasons];
    }

    /** @param  Collection<int, string>  $latest */
    private function blockedGoals(Collection $latest): int
    {
        return $latest->filter(fn (string $type) => $type === 'Execution Blocked')->count();
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
            // The OI engine only records events once a goal has drifted, so no
            // events means no drift seen, not "never measured".
            return 'No drift recorded';
        }
        $drifting = $latest->reject(fn (string $type) => $type === '' || $type === 'None')->count();

        return $drifting === 0 ? 'On track' : 'Drift on '.$drifting.' '.Str::plural('goal', $drifting);
    }
}
