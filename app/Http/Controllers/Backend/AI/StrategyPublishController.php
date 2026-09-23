<?php

namespace App\Http\Controllers\Backend\AI;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\StrategyResource;
use App\Models\StrategyResourceChange;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use App\Services\OrganizationService;
use App\Services\RoleGoalLinker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Publish gate (Features spec, phase 1): a strategy stays Draft until a leader
 * commits per-department resources and publishes it to their organization.
 * Kept out of AiChatController, which is already past 2,000 lines.
 */
class StrategyPublishController extends Controller
{
    private const WHOLE_ORG = 'Whole organization';

    /** The largest values decimal(12,2) budget and decimal(6,2) fte can hold. */
    private const BUDGET_MAX = 9999999999;

    private const FTE_MAX = 9999;

    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected OrganizationService $orgs,
        protected RoleGoalLinker $linker,
    ) {}

    public function show(Request $request, $chat): JsonResponse
    {
        $record = $this->ownChat($request, $chat);
        if (! $record) {
            return $this->denied();
        }

        $this->linker->autoLink($record);

        return response()->json($this->payload($record, $request->user()));
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'required|integer',
            'rows' => 'present|array',
            'rows.*.id' => 'nullable|integer',
            'rows.*.department_id' => 'nullable|integer',
            'rows.*.department_name' => 'required|string|max:255',
            'rows.*.budget' => 'nullable|numeric|min:0|max:'.self::BUDGET_MAX,
            'rows.*.fte' => 'nullable|numeric|min:0|max:'.self::FTE_MAX,
            'rows.*.tools' => 'nullable|string|max:5000',
            'rows.*.notes' => 'nullable|string|max:5000',
        ]);

        $user = $request->user();
        $chat = $this->ownChat($request, $data['chat_id']);
        if (! $chat) {
            return $this->denied();
        }

        $departments = $this->departmentsFor($user)->keyBy('id');
        $stored = $chat->resources()->get()->keyBy('id');
        $rows = [];
        foreach ($data['rows'] as $row) {
            // A row that already exists keeps its department (and its name
            // snapshot), even if that department has since been deleted.
            $existing = isset($row['id']) ? $stored->get($row['id']) : null;
            if ($existing) {
                $deptId = $existing->department_id;
                $deptName = $existing->department_name;
            } else {
                $deptId = $row['department_id'] ?? null;
                if ($deptId !== null && ! $departments->has($deptId)) {
                    return response()->json(['error' => 'That department is not in your organization.'], 422);
                }
                $deptName = $deptId !== null ? $departments[$deptId]->name : self::WHOLE_ORG;
            }
            $rows[] = [
                'id' => $row['id'] ?? null,
                'department_id' => $deptId,
                'department_name' => $deptName,
                'budget' => $row['budget'] ?? null,
                'fte' => $row['fte'] ?? null,
                'tools' => $this->text($row['tools'] ?? null),
                'notes' => $this->text($row['notes'] ?? null),
            ];
        }

        if ($chat->isPublished()) {
            return $this->amend($chat, $user, $rows);
        }

        $saved = DB::transaction(function () use ($chat, $rows) {
            if ($this->publishedUnderLock($chat)) {
                return false;
            }
            $keep = [];
            foreach ($rows as $row) {
                $attrs = collect($row)->except('id')->all();
                $existing = $row['id'] ? $chat->resources()->whereKey($row['id'])->first() : null;
                if ($existing) {
                    $existing->update($attrs);
                    $keep[] = $existing->id;
                } else {
                    $keep[] = $chat->resources()->create($attrs)->id;
                }
            }
            $chat->resources()->whereNotIn('id', $keep)->delete();

            return true;
        });
        if (! $saved) {
            return $this->publishedMeanwhile();
        }

        return response()->json($this->payload($chat->fresh(), $user));
    }

    public function suggest(Request $request): JsonResponse
    {
        $user = $request->user();
        $chat = $this->ownChat($request, $request->input('chat_id'));
        if (! $chat) {
            return $this->denied();
        }
        if ($chat->isPublished()) {
            return response()->json(['error' => 'This strategy is already published.'], 409);
        }

        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->orderBy('id')->get(['role', 'recommended_action']);
        if ($goals->isEmpty() && empty($chat->leadership_brief)) {
            return response()->json(['error' => 'Finish the wizard first.'], 422);
        }

        $departments = $this->departmentsFor($user);
        $system = $this->docs->buildSystemMessage($user, 'You are an executive resource planner. Return ONLY valid JSON. No markdown, no code fences, no commentary.');

        try {
            $response = $this->ai->generate($system, $this->suggestPrompt($chat, $goals, $departments), 2000, 0.4, true);
        } catch (\Throwable $e) {
            report($e);

            return $this->aiFailed();
        }
        if (! $response->successful()) {
            return $this->aiFailed();
        }

        $this->ai->recordChatTokens($chat->id, $response);
        $parsed = $this->ai->parseJson($this->ai->extractText($response));
        $rows = $this->matchRows(is_array($parsed['rows'] ?? null) ? $parsed['rows'] : [], $departments);
        if (empty($rows)) {
            return $this->aiFailed();
        }

        // The AI call takes seconds; the strategy may have been published from
        // another tab meanwhile, and its committed rows must not be replaced.
        $replaced = DB::transaction(function () use ($chat, $rows) {
            if ($this->publishedUnderLock($chat)) {
                return false;
            }
            $chat->resources()->delete();
            foreach ($rows as $row) {
                $chat->resources()->create($row);
            }

            return true;
        });
        if (! $replaced) {
            return $this->publishedMeanwhile();
        }

        return response()->json($this->payload($chat->fresh(), $user));
    }

    public function publish(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $this->ownChat($request, $request->input('chat_id'))) {
            return $this->denied();
        }
        if (! $this->orgs->canPublish($user)) {
            return response()->json(['error' => 'Only department heads, managers and the organization owner can publish.'], 403);
        }

        // Re-read under a row lock so a double click cannot publish twice.
        $result = DB::transaction(function () use ($request, $user) {
            $chat = SearchUserChat::whereKey($request->input('chat_id'))->lockForUpdate()->firstOrFail();
            if ($chat->isPublished()) {
                return response()->json(['error' => 'This strategy is already published.'], 409);
            }
            if (! $chat->resources()->exists()) {
                return response()->json(['error' => 'Add at least one department\'s resources before publishing.'], 422);
            }
            if (ExpectedState::where('search_user_chat_id', $chat->id)->whereNull('org_role_id')->exists()) {
                return response()->json(['error' => 'Link every goal to a role before publishing.'], 422);
            }

            $chat->forceFill([
                'status' => 'published',
                'published_by' => $user->id,
                'published_at' => now(),
                'organization_id' => $user->organization_id,
            ])->save();

            return $chat;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json($this->payload($result->fresh(), $user));
    }

    public function assignRole(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'required|integer',
            'goal_id' => 'required|integer',
            'org_role_id' => 'required|integer',
        ]);

        $user = $request->user();
        $chat = $this->ownChat($request, $data['chat_id']);
        if (! $chat) {
            return $this->denied();
        }

        $goal = ExpectedState::whereKey($data['goal_id'])->where('search_user_chat_id', $chat->id)->first();
        if (! $goal) {
            return response()->json(['error' => 'That goal is not part of this strategy.'], 422);
        }

        // Links are final once published, like the resources.
        $result = DB::transaction(function () use ($chat, $goal, $data, $user) {
            if ($this->publishedUnderLock($chat)) {
                return 'published';
            }

            return $this->linker->assign($goal, (int) $data['org_role_id'], $user) ? 'ok' : 'foreign';
        });

        return match ($result) {
            'published' => $this->publishedMeanwhile(),
            'foreign' => response()->json(['error' => 'That role is not in your organization.'], 422),
            default => response()->json($this->payload($chat->fresh(), $user)),
        };
    }

    // -------------------------------------------------------------------------

    /**
     * Edit a published strategy's rows in place. The set of rows is fixed once
     * published; every field that actually changes is logged.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function amend(SearchUserChat $chat, User $user, array $rows): JsonResponse
    {
        if ((int) $chat->published_by !== (int) $user->id) {
            return response()->json(['error' => 'Only the person who published this strategy can change its resources.'], 403);
        }

        $existing = $chat->resources()->get()->keyBy('id');
        $submitted = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id)->sort()->values()->all();
        if (count($rows) !== count($submitted) || $submitted !== $existing->keys()->map(fn ($id) => (int) $id)->sort()->values()->all()) {
            return response()->json(['error' => 'Rows cannot be added or removed after publishing.'], 422);
        }

        DB::transaction(function () use ($rows, $existing, $user) {
            foreach ($rows as $row) {
                $record = $existing[(int) $row['id']];
                foreach (['budget', 'fte', 'tools', 'notes'] as $field) {
                    $old = $this->normalise($field, $record->getRawOriginal($field));
                    $new = $this->normalise($field, $row[$field]);
                    if ($old === $new) {
                        continue;
                    }
                    $record->changes()->create(['user_id' => $user->id, 'field' => $field, 'old_value' => $old, 'new_value' => $new]);
                    $record->{$field} = $new;
                }
                $record->save();
            }
        });

        return response()->json($this->payload($chat->fresh(), $user));
    }

    /** Compare amounts as fixed 2-dp strings and text as trimmed-or-null, so "50000" equals "50000.00". */
    private function normalise(string $field, $value): ?string
    {
        if (in_array($field, ['budget', 'fte'], true)) {
            return ($value === null || $value === '') ? null : number_format((float) $value, 2, '.', '');
        }

        return $this->text($value);
    }

    /** Re-read the strategy under a row lock; call inside a transaction. */
    private function publishedUnderLock(SearchUserChat $chat): bool
    {
        return SearchUserChat::whereKey($chat->id)->lockForUpdate()->firstOrFail()->isPublished();
    }

    private function publishedMeanwhile(): JsonResponse
    {
        return response()->json(['error' => 'This strategy was published in the meantime. Reload to see it.'], 409);
    }

    private function aiFailed(): JsonResponse
    {
        return response()->json(['error' => 'Could not suggest resources right now. Try again, or enter them yourself.'], 502);
    }

    /**
     * @param  Collection<int, ExpectedState>  $goals
     * @param  Collection<int, Department>  $departments
     */
    private function suggestPrompt(SearchUserChat $chat, Collection $goals, Collection $departments): string
    {
        $goalLines = $goals->map(fn ($g) => '- '.$g->role.': '.$g->recommended_action)->implode("\n") ?: '(none recorded)';
        $deptLines = $departments->isEmpty()
            ? '- '.self::WHOLE_ORG.' (this organization has no departments; return exactly one row with this name)'
            : $departments->map(fn ($d) => '- '.$d->name)->implode("\n");
        $brief = mb_substr((string) $chat->leadership_brief, 0, 4000);

        return <<<EOT
Estimate the resources needed to deliver this strategy, per department.

Strategy path: "{$chat->selected_strategy}"
Scenario: "{$chat->selected_scenario}"

Role goals:
{$goalLines}

Leadership brief:
{$brief}

Departments (use these names exactly; only include departments this strategy affects):
{$deptLines}

Output a JSON object with EXACTLY this shape:
{"rows":[{"department":"<department name from the list>","budget":<number, whole currency units>,"fte":<number of full-time people>,"tools":"<systems, tools or vendors needed>","rationale":"<one sentence>"}]}
EOT;
    }

    /**
     * Keep AI rows that name a real department (case-insensitive), once each,
     * with amounts coerced to numbers.
     *
     * @param  array<int, mixed>  $aiRows
     * @param  Collection<int, Department>  $departments
     * @return array<int, array<string, mixed>>
     */
    private function matchRows(array $aiRows, Collection $departments): array
    {
        $byName = $departments->keyBy(fn ($d) => mb_strtolower(trim($d->name)));
        $out = [];

        foreach ($aiRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ($departments->isEmpty()) {
                $deptId = null;
                $name = self::WHOLE_ORG;
            } else {
                $dept = $byName->get(mb_strtolower(trim((string) ($row['department'] ?? ''))));
                if (! $dept) {
                    continue;
                }
                $deptId = $dept->id;
                $name = $dept->name;
            }
            if (isset($out[$name])) {
                continue;
            }

            $tools = $row['tools'] ?? null;
            $tools = is_array($tools) ? implode(', ', array_filter(array_map('strval', $tools))) : $tools;
            $suggestion = [
                'budget' => $this->toAmount($row['budget'] ?? null, self::BUDGET_MAX),
                'fte' => $this->toAmount($row['fte'] ?? null, self::FTE_MAX),
                'tools' => $this->text(is_scalar($tools) ? (string) $tools : null),
                'rationale' => $this->text(is_scalar($row['rationale'] ?? null) ? (string) $row['rationale'] : null),
            ];

            $out[$name] = [
                'department_id' => $deptId,
                'department_name' => $name,
                'budget' => $suggestion['budget'],
                'fte' => $suggestion['fte'],
                'tools' => $suggestion['tools'],
                'ai_suggestion' => $suggestion,
            ];

            if ($departments->isEmpty()) {
                break;
            }
        }

        return array_values($out);
    }

    /**
     * "$50k" → 50000, "1,200,000" → 1200000, "2 FTE" → 2, "10 mentors" → 10.
     * A k/m suffix only counts when no letter follows it. Anything negative or
     * beyond what the column holds becomes null rather than a MySQL overflow.
     */
    private function toAmount($value, float $max): ?float
    {
        if (is_int($value) || is_float($value)) {
            $n = (float) $value;
        } elseif (is_string($value) && preg_match('/(\d+(?:\.\d+)?)\s*([km])?(?![a-z])/i', str_replace(',', '', $value), $m)) {
            $n = (float) $m[1] * ['' => 1, 'k' => 1000, 'm' => 1000000][strtolower($m[2] ?? '')];
        } else {
            return null;
        }

        return ($n < 0 || $n > $max) ? null : $n;
    }

    /** The chat, only if the signed-in user wrote it. */
    private function ownChat(Request $request, $chatId): ?SearchUserChat
    {
        if (! $chatId || ! SearchUserChat::where('id', $chatId)->where('user_id', $request->user()->id)->exists()) {
            return null;
        }

        return SearchUserChat::find($chatId);
    }

    private function denied(): JsonResponse
    {
        return response()->json(['error' => 'Strategy not found or access denied.'], 403);
    }

    /** @return Collection<int, Department> */
    private function departmentsFor(User $user): Collection
    {
        if (! $user->organization_id) {
            return collect();
        }

        return Department::where('organization_id', $user->organization_id)->orderBy('name')->get(['id', 'name']);
    }

    private function text($value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    /** @return array<string, mixed> */
    private function payload(SearchUserChat $chat, User $user): array
    {
        $rows = $chat->resources()->orderBy('id')->get();
        $changes = StrategyResourceChange::whereIn('strategy_resource_id', $rows->pluck('id'))
            ->with('user:id,name')->orderByDesc('id')->get();
        $names = $rows->pluck('department_name', 'id');

        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->with('orgRole:id,name')->orderBy('id')->get();
        $roles = $user->organization_id
            ? OrgRole::where('organization_id', $user->organization_id)->orderBy('name')->get(['id', 'name'])
            : collect();
        $holders = $user->organization_id
            ? User::where('organization_id', $user->organization_id)->whereNotNull('org_role_id')
                ->selectRaw('org_role_id, count(*) as n')->groupBy('org_role_id')->pluck('n', 'org_role_id')
            : collect();

        return [
            'status' => $chat->status ?? 'draft',
            'is_publisher' => $chat->isPublished() && (int) $chat->published_by === (int) $user->id,
            'can_publish' => $this->orgs->canPublish($user),
            'ready' => ! empty($chat->leadership_brief)
                || ExpectedState::where('search_user_chat_id', $chat->id)->exists(),
            'published_by' => $chat->publisher?->name,
            'published_at' => $chat->published_at?->toIso8601String(),
            'currency' => config('custom.default_currency_symbol') ?: '$',
            'departments' => $this->departmentsFor($user)->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->values(),
            'goals' => $goals->map(fn (ExpectedState $g) => [
                'id' => $g->id,
                'role_text' => $g->role,
                'action' => $g->recommended_action,
                'org_role_id' => $g->org_role_id !== null ? (int) $g->org_role_id : null,
                'org_role_name' => $g->orgRole?->name,
            ])->values(),
            'roles' => $roles->map(fn (OrgRole $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'member_count' => (int) ($holders[$r->id] ?? 0),
            ])->values(),
            'rows' => $rows->map(fn (StrategyResource $r) => [
                'id' => $r->id,
                'department_id' => $r->department_id,
                'department_name' => $r->department_name,
                'budget' => $r->budget !== null ? (float) $r->budget : null,
                'fte' => $r->fte !== null ? (float) $r->fte : null,
                'tools' => $r->tools,
                'notes' => $r->notes,
                'ai_suggestion' => $r->ai_suggestion,
            ])->values(),
            'changes' => $changes->map(fn (StrategyResourceChange $c) => [
                'department_name' => $names[$c->strategy_resource_id] ?? '',
                'field' => $c->field,
                'old_value' => $c->old_value,
                'new_value' => $c->new_value,
                'user' => $c->user?->name,
                'at' => $c->created_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
