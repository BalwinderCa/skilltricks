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

    /** Score every goal; false when the AI gave nothing usable. */
    public function rank(SearchUserChat $chat, User $author): bool
    {
        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->with('orgRole:id,name')->orderBy('id')->get();
        if ($goals->isEmpty()) {
            return false;
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

            return false;
        }
        if (! $response->successful()) {
            return false;
        }

        $parsed = $this->ai->parseJson($this->ai->extractText($response));
        $byId = $goals->keyBy(fn (ExpectedState $g) => (int) $g->id);
        $scored = 0;
        foreach (is_array($parsed['scores'] ?? null) ? $parsed['scores'] : [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $goal = $byId->get((int) ($row['id'] ?? 0));
            $score = filter_var($row['score'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
            if (! $goal || $score === false) {
                continue;
            }
            $reason = mb_substr(trim(is_scalar($row['reason'] ?? null) ? (string) $row['reason'] : ''), 0, 300);
            $goal->forceFill([
                'impact_score' => $score,
                'impact_reason' => $reason !== '' ? $reason : null,
                'weight' => $goal->weight ?? self::defaultWeight($score),
            ])->save();
            $scored++;
        }

        return $scored > 0;
    }
}
