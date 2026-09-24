<?php

namespace App\Services;

use App\Models\GoalObstacle;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;

/**
 * Notion's red-alert card: AI-suggested recourse for a drifting strategy.
 * Organization context only — never anyone's private documents.
 */
class Recourse
{
    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected DriftIndex $drift,
        protected MyGoals $goals,
    ) {}

    /** @return list<array{action: string, why: string}>|null */
    public function suggest(SearchUserChat $chat, User $viewer): ?array
    {
        $state = $this->drift->evaluate($chat, alert: false);
        $line = fn ($v) => str_replace('---', '--', trim((string) preg_replace('/\s+/', ' ', (string) $v)));

        $goalLines = $state['goals']->map(fn (array $row) => '- '.$line($row['goal']->orgRole->name ?? $row['goal']->role).': "'.$line($row['goal']->recommended_action).'"'
            .' — expected '.($row['expected'] ?? 'n/a').'%, observed '.round($row['observed']).'%, drift '.($row['drift'] ?? 'not measured').'%')->implode("\n");
        $resourceLines = $chat->resources()->get()->map(fn ($r) => '- '.$line($r->department_name).': budget '.($r->budget ?? 'n/a').', '.($r->fte ?? 'n/a').' FTE')->implode("\n") ?: '(none)';
        $obstacleLines = GoalObstacle::whereIn('expected_state_id', $state['goals']->pluck('goal.id'))->orderByDesc('id')->limit(10)->pluck('body')
            ->map(fn ($b) => '- '.$line($b))->implode("\n") ?: '(none)';

        $system = 'You are an executive strategy advisor. Return ONLY valid JSON. No markdown, no code fences, no commentary.'.$this->docs->orgContextBlock($viewer);
        $prompt = 'Company goal: "'.$line($this->goals->companyGoal($chat))."\"\n"
            .'Strategy path: "'.$line($chat->selected_strategy)."\"\n"
            .'Drift index: '.($state['index'] ?? 'n/a')."% (above 15% is severe)\n\n"
            ."Goals against their baseline:\n{$goalLines}\n\nCommitted resources:\n{$resourceLines}\n\nReported obstacles:\n{$obstacleLines}\n\n"
            ."Suggest 2 or 3 concrete recourse options an executive could take now, such as reallocating a specific budget amount between departments, extending a specific target date by a number of days, or adding FTE.\n"
            .'Output exactly: {"options":[{"action":"<one sentence>","why":"<one sentence>"}]}';

        try {
            $response = $this->ai->generate($system, $prompt, 1000, 0.4, true);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
        if (! $response->successful()) {
            return null;
        }

        $parsed = $this->ai->parseJson($this->ai->extractText($response));
        $options = collect(is_array($parsed['options'] ?? null) ? $parsed['options'] : [])
            ->filter(fn ($o) => is_array($o) && is_string($o['action'] ?? null) && trim($o['action']) !== '')
            ->map(fn (array $o) => ['action' => mb_substr(trim($o['action']), 0, 300), 'why' => mb_substr(trim((string) ($o['why'] ?? '')), 0, 300)])
            ->take(3)->values()->all();
        if ($options === []) {
            return null;
        }

        $chat->forceFill(['recourse' => ['level' => $state['level'], 'index' => $state['index'], 'generated_at' => now()->toIso8601String(), 'options' => $options]])->save();

        return $options;
    }
}
