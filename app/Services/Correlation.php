<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use Illuminate\Support\Collection;

/**
 * Notion Illustration 2: an operational initiative is matched to the C-suite
 * priority it advances, and needs that priority's approval to go live.
 */
class Correlation
{
    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected MyGoals $goals,
    ) {}

    /** @return Collection<int, SearchUserChat> published, owner-authored, unlinked strategies in the user's organization */
    public function candidates(User $user, ?SearchUserChat $except = null): Collection
    {
        $org = $user->organization_id ? Organization::find($user->organization_id) : null;
        if (! $org || ! $org->owner_user_id) {
            return collect();
        }

        return SearchUserChat::where('status', 'published')->where('organization_id', $org->id)
            ->where('user_id', $org->owner_user_id)->whereNull('parent_chat_id')
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->orderByDesc('published_at')->get();
    }

    public function isCandidate(User $user, SearchUserChat $chat, int $parentId): bool
    {
        return $this->candidates($user, $chat)->contains(fn (SearchUserChat $c) => (int) $c->id === $parentId);
    }

    /** Leaders other than the owner go through approval once there is a priority to roll up into. */
    public function requiresApproval(User $user): bool
    {
        $ownerId = $user->organization_id ? Organization::whereKey($user->organization_id)->value('owner_user_id') : null;

        return $ownerId !== null && (int) $ownerId !== (int) $user->id && $this->candidates($user)->isNotEmpty();
    }

    /** @return array{id: int, score: int, reason: string|null}|null the best-scoring candidate */
    public function match(SearchUserChat $chat, User $user): ?array
    {
        $candidates = $this->candidates($user, $chat);
        if ($candidates->isEmpty()) {
            return null;
        }

        $line = fn ($v) => str_replace('---', '--', trim((string) preg_replace('/\s+/', ' ', (string) $v)));
        $list = $candidates->map(fn (SearchUserChat $c) => '- id '.$c->id.' | goal: "'.$line($this->goals->companyGoal($c)).'" | path: "'.$line($c->selected_strategy).'"')->implode("\n");
        $roleGoals = ExpectedState::where('search_user_chat_id', $chat->id)->pluck('recommended_action')->map(fn ($a) => '- '.$line($a))->implode("\n") ?: '(none)';
        $system = 'You are an executive strategy analyst. Return ONLY valid JSON. No markdown, no code fences, no commentary.'.$this->docs->orgContextBlock($user);
        $prompt = "Operational initiative:\n"
            .'Goal: "'.$line($this->goals->companyGoal($chat))."\"\n"
            .'Path: "'.$line($chat->selected_strategy)."\"\n"
            ."Role goals:\n{$roleGoals}\n\n"
            ."Corporate priorities:\n{$list}\n\n"
            ."Score how strongly the initiative advances each corporate priority, from 0 (unrelated) to 100 (directly advances it).\n"
            .'Output exactly: {"matches":[{"id":<id>,"score":<0-100>,"reason":"<one sentence>"}]}';

        try {
            $response = $this->ai->generate($system, $prompt, 800, 0.2, true);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
        if (! $response->successful()) {
            return null;
        }

        $parsed = $this->ai->parseJson($this->ai->extractText($response));
        $ids = $candidates->pluck('id')->map(fn ($id) => (int) $id);
        $best = null;
        foreach (is_array($parsed['matches'] ?? null) ? $parsed['matches'] : [] as $row) {
            if (! is_array($row) || ! $ids->contains((int) ($row['id'] ?? 0))) {
                continue;
            }
            $score = filter_var($row['score'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
            if ($score === false || ($best !== null && $score <= $best['score'])) {
                continue;
            }
            $reason = mb_substr(trim(is_scalar($row['reason'] ?? null) ? (string) $row['reason'] : ''), 0, 300);
            $best = ['id' => (int) $row['id'], 'score' => $score, 'reason' => $reason !== '' ? $reason : null];
        }

        return $best;
    }
}
