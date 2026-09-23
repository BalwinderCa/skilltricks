<?php

namespace App\Http\Controllers\Backend\AI;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\SearchUserChat;
use App\Models\StrategyResource;
use App\Models\StrategyResourceChange;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use App\Services\OrganizationService;
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

    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected OrganizationService $orgs,
    ) {}

    public function show(Request $request, $chat): JsonResponse
    {
        $record = $this->ownChat($request, $chat);
        if (! $record) {
            return $this->denied();
        }

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
            'rows.*.budget' => 'nullable|numeric|min:0|max:9999999999',
            'rows.*.fte' => 'nullable|numeric|min:0|max:9999',
            'rows.*.tools' => 'nullable|string|max:5000',
            'rows.*.notes' => 'nullable|string|max:5000',
        ]);

        $user = $request->user();
        $chat = $this->ownChat($request, $data['chat_id']);
        if (! $chat) {
            return $this->denied();
        }

        $departments = $this->departmentsFor($user)->keyBy('id');
        $rows = [];
        foreach ($data['rows'] as $row) {
            $deptId = $row['department_id'] ?? null;
            if ($deptId !== null && ! $departments->has($deptId)) {
                return response()->json(['error' => 'That department is not in your organization.'], 422);
            }
            $rows[] = [
                'id' => $row['id'] ?? null,
                'department_id' => $deptId,
                'department_name' => $deptId !== null ? $departments[$deptId]->name : self::WHOLE_ORG,
                'budget' => $row['budget'] ?? null,
                'fte' => $row['fte'] ?? null,
                'tools' => $this->text($row['tools'] ?? null),
                'notes' => $this->text($row['notes'] ?? null),
            ];
        }

        DB::transaction(function () use ($chat, $rows) {
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
        });

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

        DB::transaction(function () use ($chat, $rows) {
            $chat->resources()->delete();
            foreach ($rows as $row) {
                $chat->resources()->create($row);
            }
        });

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

    // -------------------------------------------------------------------------

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
                'budget' => $this->toAmount($row['budget'] ?? null),
                'fte' => $this->toAmount($row['fte'] ?? null),
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

    /** "$50k" → 50000, "1,200,000" → 1200000, "2 FTE" → 2; anything else → null. */
    private function toAmount($value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return $value < 0 ? null : (float) $value;
        }
        if (! is_string($value)) {
            return null;
        }

        $s = strtolower(str_replace([',', ' '], '', $value));
        if (! preg_match('/(\d+(?:\.\d+)?)([km]?)/', $s, $m)) {
            return null;
        }

        return (float) $m[1] * ['' => 1, 'k' => 1000, 'm' => 1000000][$m[2]];
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
