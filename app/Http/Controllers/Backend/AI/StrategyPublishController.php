<?php

namespace App\Http\Controllers\Backend\AI;

use App\Exports\GoalsSheetExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\StrategyResource;
use App\Models\StrategyResourceChange;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use App\Services\Alignment;
use App\Services\Correlation;
use App\Services\GoalsSheet;
use App\Services\GoalVariants;
use App\Services\ImpactRanking;
use App\Services\MyGoals;
use App\Services\OrganizationService;
use App\Services\RoleGoalLinker;
use App\Services\StrategyAlerts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        protected Correlation $correlation,
    ) {}

    public function show(Request $request, $chat): JsonResponse
    {
        $record = $this->ownChat($request, $chat);
        if (! $record) {
            return $this->denied();
        }

        // Links are final once published: only a draft is linked by name.
        if (! $record->isLocked()) {
            $this->linker->autoLink($record);
        }

        return response()->json($this->payload($record, $request->user()));
    }

    /** Excel of who sees which goal of this strategy, once it is published. */
    public function export(Request $request, $chat, GoalsSheet $sheet): BinaryFileResponse|JsonResponse
    {
        $record = $this->ownChat($request, $chat);
        if (! $record) {
            return $this->denied();
        }
        if (! $record->isPublished()) {
            return response()->json(['error' => 'Publish the strategy first: until then nobody has its goals.'], 409);
        }
        $name = Str::slug(Str::limit(app(MyGoals::class)->companyGoal($record), 50, '')) ?: 'strategy';

        return Excel::download(new GoalsSheetExport($sheet->rows($record)), 'goals-'.$name.'-'.date('Y-m-d').'.xlsx');
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'required|integer',
            'rows' => 'present|array',
            'rows.*.id' => 'nullable|integer',
            'rows.*.expected_state_id' => 'required|integer',
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

        // Resources go to a goal; the goal's department is worked out, not picked.
        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->get()->keyBy('id');
        $orgId = $chat->organization_id ?: User::whereKey($chat->user_id)->value('organization_id');
        $rows = [];
        foreach ($data['rows'] as $row) {
            $goal = $goals->get((int) $row['expected_state_id']);
            if (! $goal) {
                return response()->json(['error' => 'That goal is not part of this strategy.'], 422);
            }
            $department = $this->linker->departmentFor($goal, $orgId ? (int) $orgId : null);
            $rows[] = [
                'id' => $row['id'] ?? null,
                'expected_state_id' => $goal->id,
                'department_id' => $department?->id,
                'department_name' => $department->name ?? self::WHOLE_ORG,
            ] + $this->submittedFields($row);
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
                $keep[] = $chat->resources()->updateOrCreate(
                    ['expected_state_id' => $row['expected_state_id']],
                    collect($row)->except(['id', 'expected_state_id'])->all(),
                )->id;
            }
            // Per-department rows from before are left alone: hidden, not deleted.
            $chat->resources()->whereNotNull('expected_state_id')->whereNotIn('id', $keep)->delete();

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
        if ($chat->isLocked()) {
            return response()->json(['error' => 'This strategy is published or awaiting approval.'], 409);
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
            if ($chat->isLocked()) {
                return response()->json(['error' => 'This strategy is already published or awaiting approval.'], 409);
            }
            $unlinked = ExpectedState::where('search_user_chat_id', $chat->id)
                ->where(fn ($q) => $q->whereNull('org_role_id')
                    ->orWhereDoesntHave('orgRole', fn ($r) => $r->where('organization_id', $user->organization_id)))
                ->exists();
            if ($unlinked) {
                return response()->json(['error' => 'Link every goal to a role before publishing.'], 422);
            }
            // The role goals are written by "Complete Intelligence Cycle"; publishing
            // before that would send nobody anything.
            if (! ExpectedState::where('search_user_chat_id', $chat->id)->exists()) {
                return response()->json(['error' => 'Finish the strategy (Complete Intelligence Cycle) so its role goals exist before publishing.'], 422);
            }
            if (! $chat->resources()->whereNotNull('expected_state_id')
                ->where(fn ($q) => $q->whereNotNull('budget')->orWhereNotNull('fte'))->exists()) {
                return response()->json(['error' => 'Add a budget to at least one goal before publishing.'], 422);
            }
            // Drift, projections and nudges only measure goals with a target date.
            if (ExpectedState::where('search_user_chat_id', $chat->id)->whereNull('target_date')->exists()) {
                return response()->json(['error' => 'Set a target date for every goal before publishing.'], 422);
            }

            // Notion Illustration 2: a director's or VP's initiative rolls up into a
            // C-suite priority, and goes live only once that priority's owner approves.
            if ($this->correlation->requiresApproval($user)) {
                if (! $chat->parent_chat_id || ! $this->correlation->isCandidate($user, $chat, (int) $chat->parent_chat_id)) {
                    return response()->json(['error' => 'Link this initiative to a corporate priority before sending it for approval.'], 422);
                }
                $chat->forceFill([
                    'status' => 'pending_approval',
                    'published_by' => $user->id,
                    'approval_requested_at' => now(),
                    'approval_note' => null,
                    'organization_id' => $user->organization_id,
                ])->save();

                return $chat;
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

        // Live now: word each goal for every role in its department.
        if ($result->status === 'published') {
            app(GoalVariants::class)->generate($result);
        }

        if ($result->status === 'pending_approval' && ($approver = User::find($result->parentChat?->user_id))) {
            app(StrategyAlerts::class)->send($approver, localize('Approval requested').': '.app(MyGoals::class)->companyGoal($result),
                'dashboard/strategies', $user->name.' asks you to approve an initiative supporting "'.app(MyGoals::class)->companyGoal($result->parentChat).'".',
                'approval_request');
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

    public function rankGoals(Request $request, ImpactRanking $ranking): JsonResponse
    {
        $chat = $this->ownChat($request, $request->input('chat_id'));
        if (! $chat) {
            return $this->denied();
        }
        if ($chat->isLocked()) {
            return response()->json(['error' => 'Rank before publishing; a published strategy is final.'], 409);
        }
        if (! ExpectedState::where('search_user_chat_id', $chat->id)->exists()) {
            return response()->json(['error' => 'This strategy has no goals to rank yet.'], 422);
        }
        $scores = $ranking->score($chat, $request->user());
        if ($scores === null) {
            return response()->json(['error' => 'Could not rank the goals right now. Try again.'], 502);
        }
        // The AI call takes seconds: store only if it is still a draft, under the
        // same lock publishing takes, so weights never change after publishing.
        $stored = DB::transaction(fn () => $this->publishedUnderLock($chat) ? false : $ranking->apply($chat, $scores) > 0);
        if (! $stored) {
            return $this->publishedMeanwhile();
        }

        return response()->json($this->payload($chat->fresh(), $request->user()));
    }

    public function setWeight(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'required|integer',
            'goal_id' => 'required|integer',
            'weight' => 'required|integer|min:1|max:5',
        ]);
        $chat = $this->ownChat($request, $data['chat_id']);
        if (! $chat) {
            return $this->denied();
        }
        $goal = ExpectedState::whereKey($data['goal_id'])->where('search_user_chat_id', $chat->id)->first();
        if (! $goal) {
            return response()->json(['error' => 'That goal is not part of this strategy.'], 422);
        }

        $saved = DB::transaction(function () use ($chat, $goal, $data) {
            if ($this->publishedUnderLock($chat)) {
                return false;
            }
            $goal->forceFill(['weight' => (int) $data['weight']])->save();

            return true;
        });

        return $saved ? response()->json($this->payload($chat->fresh(), $request->user())) : $this->publishedMeanwhile();
    }

    public function setTargetDate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'required|integer',
            'goal_id' => 'required|integer',
            'target_date' => 'required|date|after:today',
        ]);
        $chat = $this->ownChat($request, $data['chat_id']);
        if (! $chat) {
            return $this->denied();
        }
        $goal = ExpectedState::whereKey($data['goal_id'])->where('search_user_chat_id', $chat->id)->first();
        if (! $goal) {
            return response()->json(['error' => 'That goal is not part of this strategy.'], 422);
        }

        $saved = DB::transaction(function () use ($chat, $goal, $data) {
            if ($this->publishedUnderLock($chat)) {
                return false;
            }
            $goal->forceFill(['target_date' => $data['target_date']])->save();

            return true;
        });

        return $saved ? response()->json($this->payload($chat->fresh(), $request->user())) : $this->publishedMeanwhile();
    }

    public function matchParent(Request $request): JsonResponse
    {
        $chat = $this->ownChat($request, $request->input('chat_id'));
        if (! $chat) {
            return $this->denied();
        }
        if ($chat->isLocked()) {
            return $this->publishedMeanwhile();
        }
        if ($this->correlation->candidates($request->user(), $chat)->isEmpty()) {
            return response()->json(['error' => 'There is no corporate priority to link to yet.'], 422);
        }
        $best = $this->correlation->match($chat, $request->user());
        if (! $best) {
            return response()->json(['error' => 'Could not find a matching priority right now. Try again, or pick one.'], 502);
        }

        return $this->storeParent($chat, $request->user(), $best['id'], $best['score'], $best['reason']);
    }

    public function setParent(Request $request): JsonResponse
    {
        $data = $request->validate(['chat_id' => 'required|integer', 'parent_chat_id' => 'required|integer']);
        $chat = $this->ownChat($request, $data['chat_id']);
        if (! $chat) {
            return $this->denied();
        }
        if ($chat->isLocked()) {
            return $this->publishedMeanwhile();
        }
        if (! $this->correlation->isCandidate($request->user(), $chat, (int) $data['parent_chat_id'])) {
            return response()->json(['error' => 'Pick one of the corporate priorities listed.'], 422);
        }

        return $this->storeParent($chat, $request->user(), (int) $data['parent_chat_id'], null, null);
    }

    private function storeParent(SearchUserChat $chat, User $user, int $parentId, ?int $score, ?string $reason): JsonResponse
    {
        $stored = DB::transaction(function () use ($chat, $parentId, $score, $reason) {
            if ($this->publishedUnderLock($chat)) {
                return false;
            }
            $chat->forceFill(['parent_chat_id' => $parentId, 'correlation_score' => $score, 'correlation_reason' => $reason])->save();

            return true;
        });

        return $stored ? response()->json($this->payload($chat->fresh(), $user)) : $this->publishedMeanwhile();
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
        if ((int) $chat->published_by !== (int) $user->id && ! $user->organization?->isAdmin($user)) {
            return response()->json(['error' => 'Only the person who published this strategy can change its resources.'], 403);
        }

        $existing = $chat->resources()->whereNotNull('expected_state_id')->get()->keyBy('id');
        $submitted = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id)->sort()->values()->all();
        if (count($rows) !== count($submitted) || $submitted !== $existing->keys()->map(fn ($id) => (int) $id)->sort()->values()->all()) {
            return response()->json(['error' => 'Rows cannot be added or removed after publishing.'], 422);
        }

        DB::transaction(function () use ($rows, $existing, $user) {
            foreach ($rows as $row) {
                $record = $existing[(int) $row['id']];
                // Only fields the card still shows are sent; hidden ones keep their values.
                foreach (array_intersect(['budget', 'fte', 'tools', 'notes'], array_keys($row)) as $field) {
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

    /**
     * The resource fields a row actually sent. The card hides some of them for
     * now (people, tools, notes); a field that was not sent is left as it is.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function submittedFields(array $row): array
    {
        $fields = [];
        foreach (['budget', 'fte'] as $field) {
            if (array_key_exists($field, $row)) {
                $fields[$field] = $row[$field];
            }
        }
        foreach (['tools', 'notes'] as $field) {
            if (array_key_exists($field, $row)) {
                $fields[$field] = $this->text($row[$field]);
            }
        }

        return $fields;
    }

    /** Compare amounts as fixed 2-dp strings and text as trimmed-or-null, so "50000" equals "50000.00". */
    private function normalise(string $field, $value): ?string
    {
        if (in_array($field, ['budget', 'fte'], true)) {
            return ($value === null || $value === '') ? null : number_format((float) $value, 2, '.', '');
        }

        return $this->text($value);
    }

    /** Is this goal linked to a role that still exists in the author's organization? */
    private function linkedRole(ExpectedState $goal, User $user): bool
    {
        return $goal->orgRole !== null && (int) $goal->orgRole->organization_id === (int) $user->organization_id;
    }

    /** Re-read the strategy under a row lock; call inside a transaction. */
    private function publishedUnderLock(SearchUserChat $chat): bool
    {
        return SearchUserChat::whereKey($chat->id)->lockForUpdate()->firstOrFail()->isLocked();
    }

    private function publishedMeanwhile(): JsonResponse
    {
        return response()->json(['error' => 'This strategy has been published or sent for approval. Reload to see it.'], 409);
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

    /** The chat, if the signed-in user wrote it or is an admin of its author's organization. */
    private function ownChat(Request $request, $chatId): ?SearchUserChat
    {
        return $chatId ? SearchUserChat::whereKey($chatId)->accessibleBy($request->user())->first() : null;
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
        // Per-goal rows only: the older per-department table is hidden for now.
        $rows = $chat->resources()->whereNotNull('expected_state_id')->with('goal:id,role')->orderBy('id')->get();
        $changes = StrategyResourceChange::whereIn('strategy_resource_id', $rows->pluck('id'))
            ->with('user:id,name')->orderByDesc('id')->get();
        $names = $rows->mapWithKeys(fn (StrategyResource $r) => [$r->id => $r->label()]);
        $orgId = $chat->organization_id ?: User::whereKey($chat->user_id)->value('organization_id');

        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->with('orgRole:id,name,organization_id')->orderBy('id')->get();
        $roles = $user->organization_id
            ? OrgRole::where('organization_id', $user->organization_id)->orderBy('name')->get(['id', 'name'])
            : collect();
        $holders = $user->organization_id
            ? User::where('organization_id', $user->organization_id)->whereNotNull('org_role_id')
                ->selectRaw('org_role_id, count(*) as n')->groupBy('org_role_id')->pluck('n', 'org_role_id')
            : collect();

        $obstacles = $chat->isPublished()
            ? GoalObstacle::whereIn('expected_state_id', $goals->pluck('id'))->with('user:id,name')->orderByDesc('id')->get()
            : collect();
        $goalRoles = $goals->mapWithKeys(fn (ExpectedState $g) => [$g->id => $g->orgRole->name ?? $g->role]);

        return [
            'status' => $chat->status ?? 'draft',
            // An organization admin may amend any published strategy, like its publisher.
            'is_publisher' => $chat->isPublished() && ((int) $chat->published_by === (int) $user->id || (bool) $user->organization?->isAdmin($user)),
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
                // A link to a role that was deleted, or that belongs to another
                // organization, is no link at all.
                'org_role_id' => $this->linkedRole($g, $user) ? (int) $g->org_role_id : null,
                'org_role_name' => $this->linkedRole($g, $user) ? $g->orgRole->name : null,
                'impact_score' => $g->impact_score,
                'impact_reason' => $g->impact_reason,
                'weight' => $g->weight,
                'target_date' => $g->target_date?->toDateString(),
                'department_name' => ($department = $this->linker->departmentFor($g, $orgId ? (int) $orgId : null))->name ?? self::WHOLE_ORG,
                // How many people are in that department (everyone, for the whole organization).
                'department_people' => $orgId
                    ? User::where('organization_id', $orgId)->when($department, fn ($q) => $q->where('department_id', $department->id))->count()
                    : 0,
            ])->values(),
            'roles' => $roles->map(fn (OrgRole $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'member_count' => (int) ($holders[$r->id] ?? 0),
            ])->values(),
            'obstacles' => $obstacles->map(fn (GoalObstacle $o) => [
                'goal_id' => $o->expected_state_id,
                'role' => $goalRoles[$o->expected_state_id] ?? '',
                'user' => $o->user?->name,
                'body' => $o->body,
                'at' => $o->created_at?->toIso8601String(),
            ])->values(),
            'alignment' => $chat->isPublished() ? app(Alignment::class)->forStrategy($chat) : null,
            'requires_approval' => $this->correlation->requiresApproval($user),
            'parent_candidates' => $this->correlation->candidates($user, $chat)
                ->map(fn (SearchUserChat $c) => ['id' => (int) $c->id, 'goal' => app(MyGoals::class)->companyGoal($c)])->values(),
            'parent' => $chat->parent_chat_id ? ['id' => (int) $chat->parent_chat_id, 'score' => $chat->correlation_score, 'reason' => $chat->correlation_reason] : null,
            'approval' => $chat->status === 'pending_approval'
                ? ['requested_at' => $chat->approval_requested_at?->toIso8601String(), 'approver' => User::whereKey($chat->parentChat?->user_id)->value('name')]
                : null,
            'rejection' => $chat->status === 'draft' && $chat->approval_note
                ? ['note' => $chat->approval_note, 'at' => $chat->approval_decided_at?->toIso8601String()]
                : null,
            'rows' => $rows->map(fn (StrategyResource $r) => [
                'id' => $r->id,
                'expected_state_id' => $r->expected_state_id,
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
