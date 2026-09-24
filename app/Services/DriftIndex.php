<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalResponse;
use App\Models\SearchUserChat;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Notion's "Live Strategic Drift Engine" (Features spec, phase 6):
 * drift % = (expected baseline − observed progress) ÷ expected baseline × 100,
 * per goal, averaged per strategy; green < 5, yellow 5–15, red > 15.
 */
class DriftIndex
{
    /** A goal is measured once this share (%) of its window has passed. */
    public const GRACE = 10.0;

    public const YELLOW = 5.0;

    public const RED = 15.0;

    public function __construct(protected StrategyAlerts $alerts) {}

    public static function levelFor(?float $index): ?string
    {
        return match (true) {
            $index === null => null,
            $index > self::RED => 'red',
            $index >= self::YELLOW => 'yellow',
            default => 'green',
        };
    }

    /**
     * @param  Collection<int, GoalResponse>  $responses
     * @return array<string, mixed>
     */
    public function goalMetrics(ExpectedState $goal, SearchUserChat $chat, Collection $responses): array
    {
        $reported = $responses->filter(fn (GoalResponse $r) => $r->progress_pct !== null);
        $observed = $reported->isEmpty() ? 0.0 : (float) $reported->avg(fn (GoalResponse $r) => $r->progress_status === 'completed' ? 100 : (int) $r->progress_pct);
        $metrics = ['expected' => null, 'observed' => $observed, 'drift' => null, 'measured' => false, 'projected_completion' => null, 'projected_value' => null, 'days_behind' => null];

        $start = $chat->published_at;
        $end = $goal->target_date ? Carbon::parse($goal->target_date)->endOfDay() : null;
        if (! $start || ! $end || $end->lte($start)) {
            return $metrics;
        }

        $window = $end->getTimestamp() - $start->getTimestamp();
        $elapsed = max(0, min($window, now()->getTimestamp() - $start->getTimestamp()));
        $expected = 100 * $elapsed / $window;
        $metrics['expected'] = round($expected, 1);
        // Inside the grace period there is too little history to project from:
        // 20% after a few hours would "finish" in two days.
        if ($expected < self::GRACE) {
            return $metrics;
        }
        if ($observed > 0) {
            $metrics['projected_completion'] = $start->copy()->addSeconds((int) round($elapsed * 100 / $observed));
        }

        $metrics['measured'] = true;
        $metrics['drift'] = round(max(0, ($expected - $observed) / $expected * 100), 1);
        $metrics['days_behind'] = $expected > $observed ? (int) round(($expected - $observed) / 100 * $window / 86400) : 0;
        // Only a plainly numeric target ("25", "1,500", "$50k" is not): the first
        // digit in "Q3 2026 launch" or "from 8% to 5%" is not a target.
        $target = str_replace([',', '%', '$', ' '], '', trim((string) $goal->target_value));
        if (is_numeric($target)) {
            $metrics['projected_value'] = round((float) $target * min(1, $observed / $expected), 1);
        }

        return $metrics;
    }

    /** @return array<string, mixed> index, level, worst, projected, goals */
    public function evaluate(SearchUserChat $chat, bool $alert = true): array
    {
        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->with('orgRole:id,name')->orderBy('id')->get();
        $responses = GoalResponse::whereIn('expected_state_id', $goals->pluck('id'))->get()->groupBy('expected_state_id');
        $rows = $goals->map(fn (ExpectedState $g) => ['goal' => $g] + $this->goalMetrics($g, $chat, $responses->get($g->id, collect())));

        $measured = $rows->where('measured', true);
        // Notion Epic 1 weights (phase 7): heavier goals move the index more; unweighted = 1.
        $weightOf = fn (array $row) => max(1, (int) ($row['goal']->weight ?? 1));
        $index = $measured->isEmpty() ? null
            : round($measured->sum(fn (array $row) => $row['drift'] * $weightOf($row)) / $measured->sum($weightOf), 1);
        $level = self::levelFor($index);
        $worst = $measured->sortByDesc('drift')->first()['goal'] ?? null;
        // A strategy finishes when its last dated goal does; while any dated goal
        // has no projection yet, there is no honest date to show.
        $dated = $rows->whereNotNull('expected');
        $projected = $dated->isNotEmpty() && $dated->every(fn (array $row) => $row['projected_completion'] !== null)
            ? $dated->pluck('projected_completion')->max()
            : null;

        if ($chat->isPublished()) {
            $chat->forceFill(['drift_index' => $index, 'drift_level' => $level, 'drift_checked_at' => now()])->save();
            if ($alert) {
                $this->alertOnChange($chat, $index, $level, $worst);
            }
        }

        return ['index' => $index, 'level' => $level, 'worst' => $worst, 'projected' => $projected, 'goals' => $rows];
    }

    /**
     * Notion's threshold actions, once per level change: yellow nudges the
     * people holding the worst-drifting goal; red alerts the strategy's author.
     * Back to green re-arms them.
     */
    private function alertOnChange(SearchUserChat $chat, ?float $index, ?string $level, ?ExpectedState $worst): void
    {
        if ($level === $chat->drift_alerted_level) {
            return;
        }
        if ($level === null || $level === 'green') {
            $chat->forceFill(['drift_alerted_level' => $level])->save();

            return;
        }
        // Claim the alert first, so two concurrent evaluations cannot both send it.
        $claimed = SearchUserChat::whereKey($chat->id)
            ->where(fn ($q) => $q->whereNull('drift_alerted_level')->orWhere('drift_alerted_level', '!=', $level))
            ->update(['drift_alerted_level' => $level]);
        if (! $claimed) {
            return;
        }

        $role = $worst ? ($worst->orgRole->name ?? $worst->role) : '';
        if ($level === 'yellow' && $worst && $worst->org_role_id) {
            $holders = User::where('organization_id', $chat->organization_id)->where('org_role_id', $worst->org_role_id)->get();
            foreach ($holders as $holder) {
                $this->alerts->send($holder, localize('Your goal is falling behind').': '.$role, 'dashboard',
                    'A published strategy is drifting ('.$index.'%), and your goal "'.Str::limit((string) $worst->recommended_action, 120).'" is furthest behind its baseline. Post an update, or flag what is blocking you.',
                    'drift_nudge');
            }
        }
        if ($level === 'red' && ($author = User::find($chat->user_id))) {
            $this->alerts->send($author, localize('Severe strategy drift').': '.$index.'%', 'dashboard/strategies/'.$chat->id,
                'Execution is more than '.self::RED.'% behind its baseline'.($worst ? ', furthest on the '.$role.' goal' : '').'. Open the executive view for the breakdown and AI-suggested recourse.',
                'drift_red');
        }
    }
}
