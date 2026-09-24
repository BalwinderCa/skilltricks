<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;

/**
 * Notion Epic 1: role actions "mathematically ranked by their potential impact
 * on realizing the parent intent". Scores 1–10, organization context only.
 */
class ImpactRanking
{
    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected MyGoals $goals,
    ) {}

    public static function defaultWeight(int $score): int
    {
        return max(1, min(5, (int) ceil($score / 2)));
    }

    /** Score and store every goal; false when the AI gave nothing usable. */
    public function rank(SearchUserChat $chat, User $author): bool
    {
        $scores = $this->score($chat, $author);

        return $scores !== null && $this->apply($chat, $scores) > 0;
    }

    /**
     * Ask the AI for scores. Nothing is written, so a caller can store them under
     * a lock after this (slow) call returns.
     *
     * @return array<int, array{score: int, reason: ?string}>|null keyed by goal id; null when unusable
     */
    public function score(SearchUserChat $chat, User $author): ?array
    {
        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->with('orgRole:id,name')->orderBy('id')->get();
        if ($goals->isEmpty()) {
            return null;
        }

        $line = fn ($v) => str_replace('---', '--', trim((string) preg_replace('/\s+/', ' ', (string) $v)));
        $list = $goals->map(fn (ExpectedState $g) => '- id '.$g->id.' | '.$line($g->orgRole->name ?? $g->role).': "'.$line($g->recommended_action).'"')->implode("\n");
        $system = 'You are an executive strategy analyst. Return ONLY valid JSON. No markdown, no code fences, no commentary.'.$this->docs->orgContextBlock($author);
        $prompt = 'Company goal: "'.$line($this->goals->companyGoal($chat))."\"\n"
            .'Strategy path: "'.$line($chat->selected_strategy)."\"\n"
            .'Scenario: "'.$line($chat->selected_scenario)."\"\n\n"
            ."Role actions:\n{$list}\n\n"
            ."Score each action's expected impact on achieving the company goal, from 1 (marginal) to 10 (decisive). Score them relative to each other and use the full range.\n"
            .'Output exactly: {"scores":[{"id":<id>,"score":<1-10>,"reason":"<one sentence>"}]}';

        try {
            $response = $this->ai->generate($system, $prompt, 1200, 0.2, true);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
        if (! $response->successful()) {
            return null;
        }

        $parsed = $this->ai->parseJson($this->ai->extractText($response));
        $ids = $goals->pluck('id')->map(fn ($id) => (int) $id);
        $scores = [];
        foreach (is_array($parsed['scores'] ?? null) ? $parsed['scores'] : [] as $row) {
            if (! is_array($row) || ! $ids->contains((int) ($row['id'] ?? 0))) {
                continue;
            }
            $score = filter_var($row['score'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
            if ($score === false) {
                continue;
            }
            $reason = mb_substr(trim(is_scalar($row['reason'] ?? null) ? (string) $row['reason'] : ''), 0, 300);
            $scores[(int) $row['id']] = ['score' => $score, 'reason' => $reason !== '' ? $reason : null];
        }

        return $scores === [] ? null : $scores;
    }

    /**
     * Store scores; a goal keeps a weight it already has.
     *
     * @param  array<int, array{score: int, reason: ?string}>  $scores
     */
    public function apply(SearchUserChat $chat, array $scores): int
    {
        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->whereIn('id', array_keys($scores))->get();
        foreach ($goals as $goal) {
            $s = $scores[(int) $goal->id];
            $goal->forceFill(['impact_score' => $s['score'], 'impact_reason' => $s['reason'], 'weight' => $goal->weight ?? self::defaultWeight($s['score'])])->save();
        }

        return $goals->count();
    }
}
