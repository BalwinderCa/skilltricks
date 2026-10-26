<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalVariant;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\StrategyResource;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use Illuminate\Support\Collection;

/**
 * Everyone in a goal's department sees it, and each of them gets it in words
 * that fit their role: the same goal, pitched at a head of department or at a
 * technician. One AI call per strategy, made when it is published.
 */
class GoalVariants
{
    /** Keeps one call's prompt and answer a sensible size. */
    private const MAX_PAIRS = 120;

    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected MyGoals $goals,
    ) {}

    /**
     * Write a version of each goal for every other role held in its department.
     * Never throws: a failure leaves members on the goal's own wording.
     *
     * @return int how many versions were stored
     */
    public function generate(SearchUserChat $chat): int
    {
        try {
            return $this->store($chat, $this->ask($chat, $this->pairs($chat)));
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }

    /**
     * Goal and role pairs to write: each goal with resources booked to a
     * department, against every role someone in that department holds,
     * other than the goal's own role and the admin roles.
     *
     * @return Collection<int, array{goal: ExpectedState, role: OrgRole}>
     */
    public function pairs(SearchUserChat $chat): Collection
    {
        $orgId = (int) $chat->organization_id;
        if (! $orgId) {
            return collect();
        }

        $rows = StrategyResource::where('search_user_chat_id', $chat->id)->whereNotNull('expected_state_id')
            ->whereNotNull('department_id')->with('goal')->get();
        $roleIdsByDept = User::where('organization_id', $orgId)
            ->whereIn('department_id', $rows->pluck('department_id')->unique())->whereNotNull('org_role_id')
            ->get(['department_id', 'org_role_id'])->groupBy('department_id')
            ->map(fn ($members) => $members->pluck('org_role_id')->unique());
        $roles = OrgRole::where('organization_id', $orgId)->where('is_admin', false)->get()->keyBy('id');

        return $rows->flatMap(fn (StrategyResource $row) => $row->goal
            ? $roleIdsByDept->get($row->department_id, collect())
                ->reject(fn ($roleId) => (int) $roleId === (int) $row->goal->org_role_id)
                ->filter(fn ($roleId) => $roles->has($roleId))
                ->map(fn ($roleId) => ['goal' => $row->goal, 'role' => $roles[$roleId]])
            : collect())
            ->take(self::MAX_PAIRS)->values();
    }

    /**
     * @param  Collection<int, array{goal: ExpectedState, role: OrgRole}>  $pairs
     * @return array<int, array{goal_id: int, role_id: int, text: string}>
     */
    private function ask(SearchUserChat $chat, Collection $pairs): array
    {
        if ($pairs->isEmpty()) {
            return [];
        }

        $line = fn ($v) => str_replace('---', '--', trim((string) preg_replace('/\s+/', ' ', (string) $v)));
        $goalLines = $pairs->pluck('goal')->unique('id')
            ->map(fn (ExpectedState $g) => '- goal '.$g->id.' (led by '.$line($g->role).'): "'.$line($g->recommended_action).'"')->implode("\n");
        $pairLines = $pairs->map(fn (array $p) => '- goal '.$p['goal']->id.' | role '.$p['role']->id.' | '.$line($p['role']->name))->implode("\n");
        $author = User::find($chat->user_id);

        $system = 'You are an executive strategy assistant. Return ONLY valid JSON. No markdown, no code fences, no commentary.'
            .($author ? $this->docs->orgContextBlock($author) : '');
        $prompt = 'Company goal: "'.$line($this->goals->companyGoal($chat))."\"\n\n"
            ."Department goals:\n{$goalLines}\n\n"
            .'Rewrite each goal for each role below: the same goal and the same outcome, in one sentence that fits what that role does day to day. '
            .'A senior role (director, head, manager) gets an oversight or decision version; a hands-on role (analyst, administrator, technician, developer) gets a concrete task they can start on. '
            ."Do not change the goal's target or invent new work.\n\n"
            ."Goal and role pairs:\n{$pairLines}\n\n"
            .'Output exactly: {"variants":[{"goal_id":<id>,"role_id":<id>,"text":"<one sentence>"}]}';

        $response = $this->ai->generate($system, $prompt, 4000, 0.4, true);
        if (! $response->successful()) {
            return [];
        }
        $this->ai->recordChatTokens($chat->id, $response);
        $parsed = $this->ai->parseJson($this->ai->extractText($response));

        $wanted = $pairs->mapWithKeys(fn (array $p) => [$p['goal']->id.':'.$p['role']->id => true]);
        $out = [];
        foreach (is_array($parsed['variants'] ?? null) ? $parsed['variants'] : [] as $row) {
            $goalId = (int) (is_array($row) ? ($row['goal_id'] ?? 0) : 0);
            $roleId = (int) (is_array($row) ? ($row['role_id'] ?? 0) : 0);
            $text = trim(is_array($row) && is_scalar($row['text'] ?? null) ? (string) $row['text'] : '');
            if ($text !== '' && $wanted->has($goalId.':'.$roleId)) {
                $out[] = ['goal_id' => $goalId, 'role_id' => $roleId, 'text' => mb_substr($text, 0, 1000)];
            }
        }

        return $out;
    }

    /** @param  array<int, array{goal_id: int, role_id: int, text: string}>  $variants */
    private function store(SearchUserChat $chat, array $variants): int
    {
        foreach ($variants as $v) {
            GoalVariant::updateOrCreate(
                ['expected_state_id' => $v['goal_id'], 'org_role_id' => $v['role_id']],
                ['text' => $v['text']],
            );
        }

        return count($variants);
    }

    /** The wording for this role, if one was written. */
    public function textFor(ExpectedState $goal, ?int $orgRoleId): ?string
    {
        return $orgRoleId
            ? GoalVariant::where('expected_state_id', $goal->id)->where('org_role_id', $orgRoleId)->value('text')
            : null;
    }
}
