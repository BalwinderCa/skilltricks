<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalCascade;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Notion Epic 3's sub-cascade: a manager turns a goal they hold (or a sub-goal
 * cascaded to them) into sub-goals for their direct reports.
 */
class Cascades
{
    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected MyGoals $goals,
        protected StrategyAlerts $alerts,
    ) {}

    /** @return Collection<int, User> the user's direct reports, in their organization */
    public function reportsOf(User $leader): Collection
    {
        if (! $leader->organization_id) {
            return collect();
        }

        return User::where('organization_id', $leader->organization_id)->where('manager_id', $leader->id)
            ->with(['orgRole:id,name', 'department:id,name'])->orderBy('name')->get();
    }

    /**
     * What $user may cascade from: a goal they can see, or a sub-goal sent to them
     * under a strategy still published in their organization.
     *
     * @return array{0: ExpectedState, 1: GoalCascade|null}|null
     */
    public function source(User $user, ?int $goalId, ?int $cascadeId): ?array
    {
        if ($cascadeId) {
            $item = GoalCascade::whereKey($cascadeId)->where('assignee_user_id', $user->id)->whereNotNull('sent_at')->with('root.searchUserChat')->first();

            return $item && $this->isLive($item->root?->searchUserChat, $user) ? [$item->root, $item] : null;
        }
        $goal = $goalId ? $this->goals->visibleGoal($user, $goalId) : null;

        return $goal ? [$goal, null] : null;
    }

    public function companyGoal(SearchUserChat $chat): string
    {
        return $this->goals->companyGoal($chat);
    }

    /** @return Collection<int, GoalCascade> this cascader's drafts and sent sub-goals for one source */
    public function itemsFor(User $leader, ExpectedState $root, ?GoalCascade $parent): Collection
    {
        return GoalCascade::where('created_by', $leader->id)->where('expected_state_id', $root->id)
            ->where('parent_id', $parent?->id)->with('assignee:id,name')->orderBy('id')->get();
    }

    /** AI drafts, one per direct report; returns how many were drafted. */
    public function suggest(User $leader, ExpectedState $root, ?GoalCascade $parent): int
    {
        $reports = $this->reportsOf($leader);
        if ($reports->isEmpty()) {
            return 0;
        }

        $line = fn ($v) => str_replace('---', '--', trim((string) preg_replace('/\s+/', ' ', (string) $v)));
        $people = $reports->map(fn (User $u) => '- user_id '.$u->id.' | '.$line($u->name).' | role: '.$line($u->orgRole->name ?? 'n/a').' | department: '.$line($u->department->name ?? 'n/a'))->implode("\n");
        $system = "You are StrategiStudio's execution guide. Return ONLY valid JSON. No markdown, no code fences, no commentary.".$this->docs->orgContextBlock($leader);
        $prompt = 'Company goal: "'.$line($this->goals->companyGoal($root->searchUserChat))."\"\n"
            .'The manager\'s mandate: "'.$line($parent ? $parent->text : $root->recommended_action)."\"\n\n"
            ."Direct reports:\n{$people}\n\n"
            ."Write one concrete sub-goal for each direct report that advances the manager's mandate within that person's role. Each under 25 words.\n"
            .'Output exactly: {"items":[{"user_id":<id>,"text":"<sub-goal>"}]}';

        try {
            $response = $this->ai->generate($system, $prompt, 1200, 0.4, true);
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
        if (! $response->successful()) {
            return 0;
        }

        $parsed = $this->ai->parseJson($this->ai->extractText($response));
        $allowed = $reports->pluck('id')->map(fn ($id) => (int) $id);
        $items = collect(is_array($parsed['items'] ?? null) ? $parsed['items'] : [])
            ->filter(fn ($i) => is_array($i) && $allowed->contains((int) ($i['user_id'] ?? 0)) && is_string($i['text'] ?? null) && trim($i['text']) !== '')
            ->unique(fn (array $i) => (int) $i['user_id'])
            ->values();
        if ($items->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($leader, $root, $parent, $items) {
            GoalCascade::where('created_by', $leader->id)->where('expected_state_id', $root->id)->where('parent_id', $parent?->id)->whereNull('sent_at')->delete();
            foreach ($items as $i) {
                GoalCascade::create(['expected_state_id' => $root->id, 'parent_id' => $parent?->id, 'created_by' => $leader->id,
                    'assignee_user_id' => (int) $i['user_id'], 'text' => mb_substr(trim($i['text']), 0, 300)]);
            }
        });

        return $items->count();
    }

    /** A hand-written draft; false when the assignee is not a direct report. */
    public function add(User $leader, ExpectedState $root, ?GoalCascade $parent, int $assigneeId, string $text): bool
    {
        if (! $this->reportsOf($leader)->contains(fn (User $u) => (int) $u->id === $assigneeId)) {
            return false;
        }
        GoalCascade::create(['expected_state_id' => $root->id, 'parent_id' => $parent?->id, 'created_by' => $leader->id,
            'assignee_user_id' => $assigneeId, 'text' => mb_substr(trim($text), 0, 300)]);

        return true;
    }

    /**
     * Send the cascader's drafts for this source, with edits and removals applied.
     *
     * @param  array<int|string, mixed>  $texts
     * @param  array<int, mixed>  $remove
     */
    public function send(User $leader, ExpectedState $root, ?GoalCascade $parent, array $texts, array $remove): int
    {
        $drafts = $this->itemsFor($leader, $root, $parent)->whereNull('sent_at');
        $removeIds = collect($remove)->map(fn ($id) => (int) $id);
        $sent = collect();

        DB::transaction(function () use ($drafts, $texts, $removeIds, $sent) {
            foreach ($drafts as $draft) {
                $text = trim((string) ($texts[$draft->id] ?? $draft->text));
                if ($removeIds->contains((int) $draft->id) || $text === '') {
                    $draft->delete();

                    continue;
                }
                $draft->forceFill(['text' => mb_substr($text, 0, 300), 'sent_at' => now()])->save();
                $sent->push($draft);
            }
        });

        foreach ($sent as $item) {
            if ($assignee = User::find($item->assignee_user_id)) {
                $this->alerts->send($assignee, localize('New goal from').' '.$leader->name, 'dashboard',
                    $leader->name.' cascaded a goal to you: "'.Str::limit($item->text, 160).'"', 'goal_cascade');
            }
        }

        return $sent->count();
    }

    public function progress(GoalCascade $item, string $status, int $pct, ?string $note): void
    {
        $note = $note !== null && trim($note) !== '' ? trim($note) : null;
        $item->forceFill(['status' => $status, 'pct' => $status === 'completed' ? 100 : max(0, min(100, $pct)), 'note' => $note, 'progress_at' => now()])->save();
    }

    /** @return Collection<int, GoalCascade> sub-goals sent to $user under live strategies */
    public function forAssignee(User $user): Collection
    {
        return GoalCascade::where('assignee_user_id', $user->id)->whereNotNull('sent_at')
            ->with(['root.searchUserChat', 'root.orgRole:id,name', 'parent', 'creator:id,name'])->orderByDesc('id')->get()
            ->filter(fn (GoalCascade $c) => $this->isLive($c->root?->searchUserChat, $user))->values();
    }

    /**
     * Sent sub-goals under each root goal, all levels.
     *
     * @param  Collection<int, mixed>  $rootIds
     * @return array<int, array{total: int, completed: int}>
     */
    public static function countsFor(Collection $rootIds): array
    {
        return GoalCascade::whereIn('expected_state_id', $rootIds)->whereNotNull('sent_at')->get(['expected_state_id', 'status'])
            ->groupBy(fn (GoalCascade $c) => (int) $c->expected_state_id)
            ->map(fn (Collection $g) => ['total' => $g->count(), 'completed' => $g->where('status', 'completed')->count()])
            ->all();
    }

    private function isLive(?SearchUserChat $chat, User $user): bool
    {
        return $chat !== null && $chat->isPublished() && (int) $chat->organization_id === (int) $user->organization_id;
    }
}
