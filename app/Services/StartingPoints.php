<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalResponse;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;

/**
 * Where to Begin (Features spec, phase 4): the shared starting options for a
 * goal, generated once, and each person's committed pick.
 */
class StartingPoints
{
    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected MyGoals $goals,
    ) {}

    /** True when the goal has options, generating them the first time. */
    public function ensureOptions(ExpectedState $goal, User $member): bool
    {
        if (! empty($goal->starting_options)) {
            return true;
        }

        $chat = $goal->searchUserChat;
        $line = fn ($value) => trim((string) preg_replace('/\s+/', ' ', (string) $value));
        // The options are shared by everyone holding the role, so they are built
        // from organization context only, never from this member's own uploads.
        $system = "You are StrategiStudio's execution guide. Return ONLY valid JSON. No markdown, no code fences, no commentary."
            .$this->docs->orgContextBlock($member);
        $prompt = 'Company objective: "'.$line($this->goals->companyGoal($chat))."\"\n"
            .'Selected strategy path: "'.$line($chat->selected_strategy)."\"\n"
            .'User role: "'.$line($goal->orgRole->name ?? $goal->role)."\"\n"
            .'User assigned goal: "'.$line($goal->recommended_action)."\"\n\n"
            ."Based strictly on the assigned goal, generate 3 or 4 concise, highly actionable starting options for \"Where to begin\", tailored to this role.\n"
            .'Output exactly: {"options":["...","..."]}'."\n"
            .'Each option under 15 words.';

        try {
            $response = $this->ai->generate($system, $prompt, 600, 0.5, true);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
        if (! $response->successful()) {
            return false;
        }

        $parsed = $this->ai->parseJson($this->ai->extractText($response));
        $options = collect(is_array($parsed['options'] ?? null) ? $parsed['options'] : [])
            ->filter(fn ($option) => is_string($option) && trim($option) !== '')
            ->map(fn (string $option) => mb_substr(trim($option), 0, 150))
            ->unique()->take(4)->values()->all();
        if (count($options) < 2) {
            return false;
        }

        // Another holder of the role may have generated a list meanwhile: the
        // first one stays, so everyone chooses from the same options.
        ExpectedState::whereKey($goal->id)->whereNull('starting_options')->update(['starting_options' => json_encode($options)]);
        $goal->refresh();

        return ! empty($goal->starting_options);
    }

    /** Commit the member to option $index; false when there is no such option. */
    public function commit(ExpectedState $goal, User $member, int $index): bool
    {
        $options = $goal->starting_options ?? [];
        if (! array_key_exists($index, $options)) {
            return false;
        }
        $choice = $options[$index];

        $response = GoalResponse::firstOrNew(['expected_state_id' => $goal->id, 'user_id' => $member->id]);
        if ($response->starting_point !== $choice) {
            if ($response->starting_point !== null) {
                $response->starting_history = array_merge($response->starting_history ?? [], [[
                    'from' => $response->starting_point, 'to' => $choice, 'at' => now()->toIso8601String(),
                ]]);
            }
            $response->starting_point = $choice;
            $response->committed_at = now();
        }
        if ($response->decision !== 'act_on_it') {
            $response->decision = 'act_on_it';
            $response->decided_at = now();
        }
        $response->save();

        return true;
    }
}
