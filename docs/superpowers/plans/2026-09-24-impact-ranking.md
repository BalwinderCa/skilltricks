# Impact Ranking Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Rank role goals by AI-scored impact, and let the author set weights (1–5) before publishing. The drift index becomes weighted.

**Architecture:**
- `ImpactRanking` service (AI scoring).
- Two actions on `StrategyPublishController`.
- Payload fields and card UI in `publish-gate.blade.php`.
- A weighted average in `DriftIndex::evaluate`.
- Score and weight columns in the executive detail view.

**Spec:** `docs/superpowers/specs/2026-09-24-impact-ranking-design.md`

## Global Constraints

- Scores are integers 1–10 (anything else is ignored). Weights are integers 1–5. `defaultWeight(score) = clamp(ceil(score/2), 1, 5)`.
- Organization context only in the prompt.
- Weights are locked after publishing (409), using Phase 1's `publishedUnderLock`.

## Review Focus

1. **A reply that scores only some goals** — those are stored; the others stay unscored.
2. **A reply with a string score like "8"** — accepted. `"8.5"` and `11` are ignored.
3. **Re-ranking after a manual weight** — the weight is kept.
4. **All weights null** — the drift index equals the unweighted average.
5. **The card's sort** — scored goals come first, highest first; unscored ones follow in id order.

---

### Task 1: Scores, weights, and the weighted drift index

**Files:** migration `2026_09_24_000400_add_impact_to_expected_states.php`; `app/Models/ExpectedState.php`; `app/Services/ImpactRanking.php`; `app/Services/DriftIndex.php`; test `tests/Feature/ImpactRankingTest.php`.

- [ ] **Step 1: Failing tests** — create `tests/Feature/ImpactRankingTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ExpectedState;
use App\Models\GoalResponse;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\DriftIndex;
use App\Services\ImpactRanking;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
use Tests\TestCase;

/**
 * Impact ranking and action weights (Features spec, phase 7 / Notion Epic 1).
 */
class ImpactRankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
    }

    /** @return array<string, mixed> */
    private function world(): array
    {
        $this->freezeTime();
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $product = OrgRole::create(['organization_id' => $org->id, 'name' => 'Product']);
        $ceo = User::factory()->create(['email' => 'ceo@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id]);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $rep = User::factory()->create(['email' => 'rep@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id, 'org_role_id' => $sales->id, 'manager_id' => $ceo->id]);
        $pm = User::factory()->create(['email' => 'pm@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id, 'org_role_id' => $product->id, 'manager_id' => $ceo->id]);
        $chat = SearchUserChat::create(['user_id' => $ceo->id, 'status1' => 0, 'selected_strategy' => 'Upsell', 'selected_scenario' => 'Expected', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $ceo->id, 'search' => 'Grow revenue 30%', 'response' => 'ok']);
        $due = now()->addDays(10)->toDateString();
        $salesGoal = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion', 'org_role_id' => $sales->id, 'target_date' => $due]);
        $productGoal = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Product', 'recommended_action' => 'Ship SOC2', 'org_role_id' => $product->id, 'target_date' => $due]);

        return compact('org', 'ceo', 'rep', 'pm', 'chat', 'salesGoal', 'productGoal');
    }

    private function publish(array $w): void
    {
        $end = now()->addDays(10)->endOfDay();
        $w['chat']->forceFill(['status' => 'published', 'published_by' => $w['ceo']->id, 'organization_id' => $w['org']->id,
            'published_at' => now()->subSeconds($end->getTimestamp() - now()->getTimestamp())])->save();
    }

    private function fakeAi(string $text, int $status = 200): void
    {
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturn(new ClientResponse(new PsrResponse($status, [], '{}')));
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    public function test_ranking_stores_scores_reasons_and_default_weights(): void
    {
        $w = $this->world();
        $this->fakeAi(json_encode(['scores' => [
            ['id' => $w['salesGoal']->id, 'score' => '8', 'reason' => 'Directly converts accounts'],
            ['id' => $w['productGoal']->id, 'score' => 11, 'reason' => 'Out of range'],
            ['id' => 99999, 'score' => 5, 'reason' => 'Unknown goal'],
        ]]));

        $this->assertTrue(app(ImpactRanking::class)->rank($w['chat'], $w['ceo']));

        $sales = $w['salesGoal']->fresh();
        $this->assertSame([8, 'Directly converts accounts', 4], [$sales->impact_score, $sales->impact_reason, $sales->weight]);
        $this->assertNull($w['productGoal']->fresh()->impact_score);
    }

    public function test_re_ranking_keeps_a_manual_weight(): void
    {
        $w = $this->world();
        $w['salesGoal']->update(['weight' => 1]);
        $this->fakeAi(json_encode(['scores' => [['id' => $w['salesGoal']->id, 'score' => 10, 'reason' => 'x']]]));

        app(ImpactRanking::class)->rank($w['chat'], $w['ceo']);

        $this->assertSame([10, 1], [$w['salesGoal']->fresh()->impact_score, $w['salesGoal']->fresh()->weight]);
    }

    public function test_an_unusable_reply_ranks_nothing(): void
    {
        $w = $this->world();
        $this->fakeAi('{"scores":[{"id":1,"score":"8.5"}]}');

        $this->assertFalse(app(ImpactRanking::class)->rank($w['chat'], $w['ceo']));
    }

    public function test_the_drift_index_is_weighted(): void
    {
        $w = $this->world();
        $this->publish($w);
        GoalResponse::create(['expected_state_id' => $w['salesGoal']->id, 'user_id' => $w['rep']->id, 'decision' => 'act_on_it', 'progress_status' => 'in_progress', 'progress_pct' => 30]); // drift 40
        GoalResponse::create(['expected_state_id' => $w['productGoal']->id, 'user_id' => $w['pm']->id, 'decision' => 'act_on_it', 'progress_status' => 'in_progress', 'progress_pct' => 50]); // drift 0
        $drift = app(DriftIndex::class);

        $this->assertEqualsWithDelta(20.0, $drift->evaluate($w['chat']->fresh(), alert: false)['index'], 0.1); // no weights → plain average

        $w['salesGoal']->update(['weight' => 5]);
        $w['productGoal']->update(['weight' => 1]);
        $this->assertEqualsWithDelta(33.3, $drift->evaluate($w['chat']->fresh(), alert: false)['index'], 0.1);
    }
}
```

- [ ] **Step 2: Run** → FAIL (`Target class [App\Services\ImpactRanking] does not exist.`).

- [ ] **Step 3: Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Impact ranking and action weights (Features spec, phase 7 / Notion Epic 1). */
    public function up(): void
    {
        Schema::table('expected_states', function (Blueprint $table) {
            $table->unsignedTinyInteger('impact_score')->nullable();
            $table->string('impact_reason', 300)->nullable();
            $table->unsignedTinyInteger('weight')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('expected_states', function (Blueprint $table) {
            $table->dropColumn(['impact_score', 'impact_reason', 'weight']);
        });
    }
};
```

- [ ] **Step 4: Model** — `ExpectedState`: add `'impact_score', 'impact_reason', 'weight',` to `$fillable` after `'starting_options',`, and add `'impact_score' => 'integer', 'weight' => 'integer',` to `$casts`.

- [ ] **Step 5: Service** — `app/Services/ImpactRanking.php`:

```php
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
```

- [ ] **Step 6: Weighted drift** — in `DriftIndex::evaluate`, replace

```php
        $index = $measured->isEmpty() ? null : round((float) $measured->avg('drift'), 1);
```

with

```php
        // Notion Epic 1 weights (phase 7): heavier goals move the index more; unweighted = 1.
        $weightOf = fn (array $row) => max(1, (int) ($row['goal']->weight ?? 1));
        $index = $measured->isEmpty() ? null
            : round($measured->sum(fn (array $row) => $row['drift'] * $weightOf($row)) / $measured->sum($weightOf), 1);
```

- [ ] **Step 7: Run** → 4 passed; `php artisan test --filter=CommandDashboardTest` still passes. **Commit** — `feat(impact): AI impact scores, action weights, weighted drift index`.

---

### Task 2: Endpoints, card and executive view

**Files:** `app/Http/Controllers/Backend/AI/StrategyPublishController.php`; `routes/backend.php`; `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php`; `resources/views/backend/pages/strategies/show.blade.php`; test.

- [ ] **Step 1: Failing tests** — append:

```php
    public function test_the_author_ranks_goals_from_the_publish_card(): void
    {
        $w = $this->world();
        $this->fakeAi(json_encode(['scores' => [['id' => $w['salesGoal']->id, 'score' => 9, 'reason' => 'Decisive'], ['id' => $w['productGoal']->id, 'score' => 3, 'reason' => 'Enabler']]]));

        $this->actingAs($w['ceo'])->postJson(route('users-new-chat-rank-goals.index'), ['chat_id' => $w['chat']->id])
            ->assertOk()
            ->assertJsonPath('goals.0.impact_score', 9)
            ->assertJsonPath('goals.0.weight', 5)
            ->assertJsonPath('goals.1.impact_reason', 'Enabler');
    }

    public function test_ranking_is_refused_after_publishing_and_reports_ai_failure(): void
    {
        $w = $this->world();
        $this->fakeAi('nope', 500);
        $this->actingAs($w['ceo'])->postJson(route('users-new-chat-rank-goals.index'), ['chat_id' => $w['chat']->id])->assertStatus(502);

        $this->publish($w);
        $this->actingAs($w['ceo'])->postJson(route('users-new-chat-rank-goals.index'), ['chat_id' => $w['chat']->id])->assertStatus(409);
        $this->actingAs($w['rep'])->postJson(route('users-new-chat-rank-goals.index'), ['chat_id' => $w['chat']->id])->assertForbidden();
    }

    public function test_the_author_sets_a_weight_until_publishing(): void
    {
        $w = $this->world();
        $other = SearchUserChat::create(['user_id' => $w['ceo']->id, 'status1' => 0]);
        $foreignGoal = ExpectedState::create(['search_user_chat_id' => $other->id, 'role' => 'X', 'recommended_action' => 'Y']);
        $post = fn (array $body) => $this->actingAs($w['ceo'])->postJson(route('users-new-chat-goal-weight.index'), $body + ['chat_id' => $w['chat']->id]);

        $post(['goal_id' => $w['salesGoal']->id, 'weight' => 3])->assertOk();
        $this->assertSame(3, $w['salesGoal']->fresh()->weight);
        $post(['goal_id' => $w['salesGoal']->id, 'weight' => 6])->assertStatus(422);
        $post(['goal_id' => $foreignGoal->id, 'weight' => 2])->assertStatus(422);

        $this->publish($w);
        $post(['goal_id' => $w['salesGoal']->id, 'weight' => 1])->assertStatus(409);
        $this->assertSame(3, $w['salesGoal']->fresh()->weight);
    }

    public function test_the_pages_show_rank_controls_and_scores(): void
    {
        $w = $this->world();
        $w['salesGoal']->update(['impact_score' => 8, 'weight' => 4]);

        $this->actingAs($w['ceo'])->get('/dashboard/users-new-chat/'.$w['chat']->id)
            ->assertOk()->assertSee(route('users-new-chat-rank-goals.index'), false)->assertSee(route('users-new-chat-goal-weight.index'), false);

        $this->publish($w);
        $this->actingAs($w['ceo'])->get(route('strategies.show', $w['chat']->id))
            ->assertOk()->assertSee('8/10')->assertSee('weight 4');
    }
```

- [ ] **Step 2: Run** → FAIL (route not defined).

- [ ] **Step 3: Controller** — in `StrategyPublishController`, add `use App\Services\ImpactRanking;`. In `payload()`'s `goals` map, add:

```php
                'impact_score' => $g->impact_score,
                'impact_reason' => $g->impact_reason,
                'weight' => $g->weight,
```

Add the actions after `assignRole`:

```php
    public function rankGoals(Request $request, ImpactRanking $ranking): JsonResponse
    {
        $chat = $this->ownChat($request, $request->input('chat_id'));
        if (! $chat) {
            return $this->denied();
        }
        if ($chat->isPublished()) {
            return response()->json(['error' => 'Rank before publishing; a published strategy is final.'], 409);
        }
        if (! ExpectedState::where('search_user_chat_id', $chat->id)->exists()) {
            return response()->json(['error' => 'This strategy has no goals to rank yet.'], 422);
        }
        if (! $ranking->rank($chat, $request->user())) {
            return response()->json(['error' => 'Could not rank the goals right now. Try again.'], 502);
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
```

Routes, after `users-new-chat-goal-role.index`:

```php
                Route::post('/users-new-chat-rank-goals', [StrategyPublishController::class, 'rankGoals'])->middleware('throttle:10,1')->name('users-new-chat-rank-goals.index');
                Route::post('/users-new-chat-goal-weight', [StrategyPublishController::class, 'setWeight'])->name('users-new-chat-goal-weight.index');
```

- [ ] **Step 4: Card** — in `publish-gate.blade.php`:
1. Add to `urls`:
```js
        rankGoals: '{{ route('users-new-chat-rank-goals.index') }}',
        goalWeight: '{{ route('users-new-chat-goal-weight.index') }}',
```
2. In `goalsHtml`, replace `const rows = state.goals.map(g => {` with:
```js
        // Notion Epic 1: once scored, highest impact first; unscored keep their order.
        const ordered = [...state.goals].sort((a, b) => (b.impact_score ?? -1) - (a.impact_score ?? -1));
        const rows = ordered.map(g => {
            const impact = g.impact_score === null || g.impact_score === undefined ? '<span class="pg-hint">—</span>'
                : `<strong title="${esc(g.impact_reason ?? '')}">${g.impact_score}/10</strong>`;
            const weight = published ? `weight ${g.weight ?? 1}`
                : `<select class="form-select form-select-sm" data-weight-goal="${g.id}" ${busy ? 'disabled' : ''} style="width:auto;display:inline-block">
                    ${[1, 2, 3, 4, 5].map(n => `<option value="${n}" ${(g.weight ?? 1) === n ? 'selected' : ''}>weight ${n}</option>`).join('')}
                   </select>`;
```
3. In that same function, change the row's return to add an impact cell:
```js
            return `<tr><td>${esc(g.action)}<div class="pg-role-text">AI role: ${esc(g.role_text)}</div></td><td style="min-width:180px">${picker}${flag}</td><td style="min-width:150px">${impact}<div>${weight}</div></td></tr>`;
```
4. Change the table head to `<thead><tr><th>Goal</th><th>Role</th><th>Impact</th></tr></thead>`, and change `<strong>Who gets which goal</strong>` to:
```js
<strong>Who gets which goal</strong>${published ? '' : ` <button type="button" class="btn btn-sm btn-outline-secondary ms-2" data-act="rank" ${busy ? 'disabled' : ''}>Rank by impact</button>`}
```
5. Add the two calls next to `assignRole`:
```js
    async function rankGoals() {
        busy = true; error = ''; render();
        try { state = await call(urls.rankGoals, { chat_id: chatId }); }
        catch (e) { error = e.message; }
        busy = false; render();
    }

    async function setWeight(goalId, weight) {
        busy = true; error = ''; render();
        try { state = await call(urls.goalWeight, { chat_id: chatId, goal_id: goalId, weight }); }
        catch (e) { error = e.message; }
        busy = false; render();
    }
```
6. In the click handler's action map, add `rank: rankGoals`: `({ suggest, save, publish, add: addRow, rank: rankGoals })`.
7. In the `change` listener, handle weights before the role select:
```js
        const w = e.target.closest && e.target.closest('select[data-weight-goal]');
        if (el && w && el.contains(w) && !busy) return setWeight(Number(w.dataset.weightGoal), Number(w.value));
```
(Insert right after `const el = card();` in that listener.)

- [ ] **Step 5: Executive detail** — in `show.blade.php`'s goals table, add a header cell `<th>{{ localize('Impact') }}</th>` after `Goal`, and after the goal cell:
```blade
                                    <td class="small">@if ($row['goal']->impact_score)<strong title="{{ $row['goal']->impact_reason }}">{{ $row['goal']->impact_score }}/10</strong><br>@endif weight {{ $row['goal']->weight ?? 1 }}</td>
```

- [ ] **Step 6: Run** — `php artisan test --filter='ImpactRankingTest|PublishGateTest|RoleGoalLinksTest'` → pass. Full suite, Pint, PHPStan, and `node --check` on the extracted card script. **Commit** — `feat(impact): rank goals and set weights in the publish card`.
