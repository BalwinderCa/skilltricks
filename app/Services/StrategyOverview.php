<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DriftEvent;
use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\GoalProgressUpdate;
use App\Models\GoalResponse;
use App\Models\GoalRevision;
use App\Models\Organization;
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

    public function __construct(protected MyGoals $goals, protected Alignment $alignment, protected DriftIndex $drift) {}

    public const DEFAULT_SETTINGS = ['hourly_rate' => 120.0, 'manual_hours' => 6.0, 'oi_minutes' => 30.0, 'token_cost' => 0.02];

    /** @return array{hourly_rate: float, manual_hours: float, oi_minutes: float, token_cost: float} */
    public static function settingsFor(?Organization $org): array
    {
        $saved = is_array($org?->command_settings) ? $org->command_settings : [];

        return array_map('floatval', array_merge(self::DEFAULT_SETTINGS, array_intersect_key($saved, self::DEFAULT_SETTINGS)));
    }

    /**
     * Notion's alignment-savings formula, with the owner's assumptions.
     *
     * @param  array{hourly_rate: float, manual_hours: float, oi_minutes: float, token_cost: float}  $s
     * @return array{manual_cost: float, oi_cost: float, saved: float, hours_saved: float}
     */
    public static function savings(int $participants, int $tokens, array $s): array
    {
        $manual = $participants * $s['manual_hours'] * $s['hourly_rate'];
        $oi = $participants * $s['oi_minutes'] / 60 * $s['hourly_rate'] + $tokens / 1000 * $s['token_cost'];

        return [
            'manual_cost' => round($manual, 2),
            'oi_cost' => round($oi, 2),
            'saved' => round($manual - $oi, 2),
            'hours_saved' => round($participants * ($s['manual_hours'] - $s['oi_minutes'] / 60), 1),
        ];
    }

    /** @return Collection<int, int> ids of the people holding one of these goals' roles */
    private function holders(SearchUserChat $chat, Collection $goals): Collection
    {
        $roleIds = $goals->pluck('org_role_id')->filter()->map(fn ($id) => (int) $id)->unique();

        return $roleIds->isEmpty() ? collect() : User::where('organization_id', $chat->organization_id)->whereIn('org_role_id', $roleIds)->pluck('id');
    }

    /** Goals someone holding them has flagged Blocked. @return Collection<int, int> */
    private function flaggedBlocked(Collection $goalIds): Collection
    {
        return GoalResponse::whereIn('expected_state_id', $goalIds)->where('progress_status', 'blocked')->pluck('expected_state_id')->map(fn ($id) => (int) $id)->unique();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function list(User $viewer): Collection
    {
        if (! $viewer->organization_id) {
            return collect();
        }

        // ponytail: a few queries per strategy; batch them if an organization
        // publishes dozens.
        $settings = self::settingsFor(Organization::find($viewer->organization_id));
        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $this->published($viewer)->with('publisher:id,name')->orderByDesc('published_at')->get()
            ->map(function (SearchUserChat $chat) use ($settings) {
                $ids = ExpectedState::where('search_user_chat_id', $chat->id)->pluck('id');
                $alignment = $this->alignment->forStrategy($chat)['overall'];
                $obstacles = GoalObstacle::whereIn('expected_state_id', $ids)->count();
                $latest = $this->latestDrift($ids);
                $state = $this->drift->evaluate($chat);
                $goals = ExpectedState::where('search_user_chat_id', $chat->id)->get(['id', 'org_role_id']);
                $blocked = $this->blockedGoals($latest) + $this->flaggedBlocked($ids)->count();

                return [
                    'chat' => $chat,
                    'company_goal' => $this->goals->companyGoal($chat),
                    'alignment' => $alignment,
                    'drift' => $this->drift($ids),
                    'badge' => self::badge($alignment['rate'], $obstacles, $blocked),
                    'drift_index' => $state['index'],
                    'drift_level' => $state['level'],
                    'savings' => self::savings($this->holders($chat, $goals)->push((int) $chat->user_id)->unique()->count(), (int) $chat->total_tokens, $settings),
                    'obstacles' => $obstacles,
                    'not_viable' => GoalResponse::whereIn('expected_state_id', $ids)->where('decision', 'not_viable')->count(),
                    'supports' => $chat->parent_chat_id && ($parent = SearchUserChat::find($chat->parent_chat_id)) ? $this->goals->companyGoal($parent) : null,
                    'supporting_count' => SearchUserChat::where('parent_chat_id', $chat->id)->where('status', 'published')->count(),
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
        $state = $this->drift->evaluate($chat);
        $metrics = $state['goals']->keyBy(fn (array $row) => (int) $row['goal']->id);
        $holderIds = $this->holders($chat, $goals);
        $flagged = $this->flaggedBlocked($ids);
        $settings = self::settingsFor(Organization::find($chat->organization_id));
        $holderDepts = User::whereIn('id', $holderIds)->whereNotNull('department_id')->distinct()->count('department_id');
        $contributors = GoalResponse::whereIn('expected_state_id', $ids)->distinct()->count('user_id');
        $lastUpdates = GoalProgressUpdate::whereIn('expected_state_id', $ids)->orderBy('id')->get()->keyBy('expected_state_id');
        $cascadeCounts = Cascades::countsFor($ids);

        return [
            'chat' => $chat,
            'company_goal' => $this->goals->companyGoal($chat),
            'alignment' => $alignment,
            'drift' => $this->drift($ids),
            'badge' => self::badge($alignment['overall']['rate'], $obstacles->count(), $this->blockedGoals($latest) + $flagged->count()),
            'goals' => $goals->map(function (ExpectedState $goal) use ($responses, $holders, $latest, $metrics) {
                $mine = $responses->get($goal->id, collect());

                return [
                    'goal' => $goal,
                    'role' => $goal->orgRole->name ?? $goal->role,
                    'holders' => (int) ($holders[(int) $goal->org_role_id] ?? 0),
                    'committed' => $mine->whereNotNull('starting_point')->count(),
                    'decisions' => collect(MyGoals::DECISIONS)->mapWithKeys(fn (string $d) => [$d => $mine->where('decision', $d)->count()])->all(),
                    'drift' => $latest->get($goal->id),
                    'metrics' => $metrics->get((int) $goal->id),
                ];
            }),
            'obstacles' => $obstacles,
            'revisions' => GoalRevision::whereIn('expected_state_id', $ids)->with(['user:id,name', 'goal.orgRole:id,name'])->orderByDesc('id')->get(),
            'command' => [
                'teams_involved' => $holderDepts,
                'teams_total' => Department::where('organization_id', $chat->organization_id)->count(),
                'contributors' => $contributors,
                'savings' => self::savings($holderIds->push((int) $chat->user_id)->unique()->count(), (int) $chat->total_tokens, $settings),
                'drift_index' => $state['index'],
                'drift_level' => $state['level'],
                'projected' => $state['projected'],
            ],
            'deliverables' => $goals->map(function (ExpectedState $goal) use ($responses, $metrics, $lastUpdates, $cascadeCounts) {
                $mine = $responses->get($goal->id, collect())->whereNotNull('progress_status');
                $status = match (true) {
                    $mine->contains('progress_status', 'blocked') => 'blocked',
                    $mine->isNotEmpty() && $mine->every(fn ($r) => $r->progress_status === 'completed') => 'completed',
                    $mine->whereIn('progress_status', ['in_progress', 'completed'])->isNotEmpty() => 'in_progress',
                    default => 'not_started',
                };

                return ['goal' => $goal, 'role' => $goal->orgRole->name ?? $goal->role, 'status' => $status,
                    'note' => $lastUpdates->get($goal->id)?->note, 'cascaded' => $cascadeCounts[(int) $goal->id] ?? null, 'days_behind' => $metrics->get((int) $goal->id)['days_behind'] ?? null];
            }),
            'blockers' => $goals->filter(fn (ExpectedState $g) => $flagged->contains((int) $g->id) && $goals->contains(fn ($o) => (int) $o->depends_on_id === (int) $g->id))
                ->map(fn (ExpectedState $g) => $g->orgRole->name ?? $g->role)->values(),
            'recourse' => $chat->recourse,
            'supporting' => SearchUserChat::where('parent_chat_id', $chat->id)->where('status', 'published')->with('user:id,name')->orderByDesc('published_at')->get()
                ->map(function (SearchUserChat $child) {
                    $childIds = ExpectedState::where('search_user_chat_id', $child->id)->pluck('id');
                    $alignment = $this->alignment->forStrategy($child)['overall'];

                    return [
                        'chat' => $child,
                        'company_goal' => $this->goals->companyGoal($child),
                        'owner' => $child->user?->name,
                        'alignment' => $alignment,
                        'badge' => self::badge($alignment['rate'], GoalObstacle::whereIn('expected_state_id', $childIds)->count(), $this->flaggedBlocked($childIds)->count()),
                        'drift_index' => $child->drift_index !== null ? (float) $child->drift_index : null,
                        'drift_level' => $child->drift_level,
                    ];
                }),
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

    /** The parent priority's author, or the organization owner. */
    public function canApprove(User $viewer, SearchUserChat $child): bool
    {
        $ownerId = Organization::whereKey((int) $child->organization_id)->value('owner_user_id');

        return (int) $viewer->id === (int) $ownerId || (int) $viewer->id === (int) $child->parentChat?->user_id;
    }

    /** @return Collection<int, array<string, mixed>> initiatives waiting for this viewer's approval */
    public function approvalsFor(User $viewer): Collection
    {
        if (! $viewer->organization_id) {
            return collect();
        }

        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = SearchUserChat::where('status', 'pending_approval')->where('organization_id', (int) $viewer->organization_id)
            ->with(['parentChat', 'publisher:id,name'])->orderBy('approval_requested_at')->get()
            ->filter(fn (SearchUserChat $c) => $this->canApprove($viewer, $c))
            ->map(fn (SearchUserChat $c) => [
                'chat' => $c,
                'company_goal' => $this->goals->companyGoal($c),
                'requester' => $c->publisher?->name,
                'supports' => $c->parentChat ? $this->goals->companyGoal($c->parentChat) : null,
                'score' => $c->correlation_score,
                'reason' => $c->correlation_reason,
                'budget' => (float) $c->resources()->sum('budget'),
                // The approver decides on the whole mandate, not just its total.
                'resources' => $c->resources()->orderBy('id')->get(['department_name', 'budget', 'fte', 'tools']),
                'goal_list' => ExpectedState::where('search_user_chat_id', $c->id)->with('orgRole:id,name')->orderBy('id')->get()
                    ->map(fn (ExpectedState $g) => ['role' => $g->orgRole->name ?? $g->role, 'action' => $g->recommended_action]),
                'goals' => ExpectedState::where('search_user_chat_id', $c->id)->count(),
            ])->values();

        return $rows;
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
