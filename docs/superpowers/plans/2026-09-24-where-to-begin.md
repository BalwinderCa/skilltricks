# Where to Begin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** People who act on a goal pick an AI-suggested starting point. That pick is their commitment, and commitments roll up into an alignment rate per department, which the author sees.

**Architecture:** Columns on `expected_states` (shared options) and `goal_responses` (pick + history). A `StartingPoints` service (generate once, commit), an `Alignment` service (per-strategy rates), two new actions on `MyGoalController`, dashboard card additions, and `alignment` in the Publish card payload.

**Tech Stack:** Laravel 12, Blade, PHPUnit on SQLite, AI via `AiProviderService` (mocked in tests).

**Spec:** `docs/superpowers/specs/2026-09-24-where-to-begin-design.md`

## Global Constraints

- Options are generated at most once per goal. They are never overwritten once set: the write uses `whereNull('starting_options')`.
- An accepted reply has 2 or more non-empty string options; keep at most 4, each trimmed and capped at 150 characters.
- Every endpoint uses `MyGoals::visibleGoal()` and returns 404 for anything else.
- Alignment counts only members of the strategy's organization, and only commitments on their own role's goal.
- UI: teal `#36839b`, orange `#ec883f`, no purple; Blade `{{ }}` for all text.

## Review Focus

1. **Two holders of the same role choose Act on it at nearly the same moment** — one list of options survives, and both see it. Covered by the `whereNull` write and the "once" test in Task 1.
2. **A reply with duplicate options** — duplicates are dropped before the ≥ 2 check. Test in Task 1.
3. **Re-committing the same option** — no history entry, and `committed_at` is unchanged. Test in Task 1.
4. **A commitment someone made on another role's goal** (for example, before a role change) — not counted in alignment. Test in Task 3.
5. **An option index sent as a negative number or a string** — refused. Test in Task 2.

---

### Task 1: Columns and the StartingPoints service

**Files:**
- Create: `database/migrations/2026_09_24_000100_add_where_to_begin_columns.php`
- Create: `app/Services/StartingPoints.php`
- Modify: `app/Models/ExpectedState.php` (fillable + cast `starting_options`)
- Modify: `app/Models/GoalResponse.php` (fillable + casts)
- Modify: `app/Services/MyGoals.php` (`companyGoal` becomes public)
- Test: `tests/Feature/WhereToBeginTest.php`

**Interfaces:**
- Produces:
  - `StartingPoints::ensureOptions(ExpectedState, User): bool`;
  - `StartingPoints::commit(ExpectedState, User, int): bool`;
  - public `MyGoals::companyGoal(SearchUserChat): string`;
  - test helpers `world()`, `publishedGoals()`, `goal(string $role)`, `fakeAi(string $text, int $status = 200, ?int $times = null)`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/WhereToBeginTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\GoalResponse;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\StartingPoints;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
use Tests\TestCase;

/**
 * Where to Begin (Features spec, phase 4): AI-suggested starting points,
 * personal commitments, and alignment per department.
 */
class WhereToBeginTest extends TestCase
{
    use RefreshDatabase;

    private const OPTIONS = '{"options":["Audit the top 5 data fields","Cross-check last sprint logs","Book an engineering sync"]}';

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
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $product = OrgRole::create(['organization_id' => $org->id, 'name' => 'Product']);
        $salesDept = Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E']);
        $make = fn (string $email, ?OrgRole $role, ?Department $dept = null) => User::factory()->create([
            'email' => $email, 'user_type' => 'customer', 'organization_id' => $org->id,
            'org_role_id' => $role?->id, 'department_id' => $dept?->id,
        ]);

        return [
            'org' => $org, 'sales' => $sales, 'product' => $product, 'salesDept' => $salesDept,
            'ceo' => $make('ceo@acme.com', null),
            'rep1' => $make('rep1@acme.com', $sales, $salesDept),
            'rep2' => $make('rep2@acme.com', $sales),
            'pm' => $make('pm@acme.com', $product),
        ];
    }

    private function publishedGoals(array $w, bool $draft = false): SearchUserChat
    {
        $chat = SearchUserChat::create(['user_id' => $w['ceo']->id, 'status1' => 0, 'selected_strategy' => 'Security upsell', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $w['ceo']->id, 'search' => 'Grow enterprise revenue', 'response' => 'ok']);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion', 'org_role_id' => $w['sales']->id]);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Product', 'recommended_action' => 'Ship SOC2 controls', 'org_role_id' => $w['product']->id]);
        if (! $draft) {
            $chat->forceFill(['status' => 'published', 'published_by' => $w['ceo']->id, 'published_at' => now(), 'organization_id' => $w['org']->id])->save();
        }

        return $chat;
    }

    private function goal(string $role): ExpectedState
    {
        return ExpectedState::where('role', $role)->firstOrFail();
    }

    private function fakeAi(string $text, int $status = 200, ?int $times = null): void
    {
        $ai = Mockery::mock(AiProviderService::class);
        $ai->shouldReceive('providerLabel')->andReturn('Fake');
        $generate = $ai->shouldReceive('generate');
        if ($times !== null) {
            $generate->times($times);
        }
        $generate->andReturn(new ClientResponse(new PsrResponse($status, [], json_encode(['candidates' => [['content' => ['parts' => [['text' => $text]]]]]]))));
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    public function test_options_are_generated_and_stored_on_the_goal(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->fakeAi(self::OPTIONS);

        $this->assertTrue(app(StartingPoints::class)->ensureOptions($this->goal('Sales'), $w['rep1']));

        $this->assertSame(['Audit the top 5 data fields', 'Cross-check last sprint logs', 'Book an engineering sync'], $this->goal('Sales')->starting_options);
    }

    public function test_options_are_generated_only_once_per_goal(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->fakeAi(self::OPTIONS, 200, 1);

        app(StartingPoints::class)->ensureOptions($this->goal('Sales'), $w['rep1']);
        $this->assertTrue(app(StartingPoints::class)->ensureOptions($this->goal('Sales'), $w['rep2']));
    }

    public function test_a_reply_with_fewer_than_two_distinct_options_is_refused(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->fakeAi('{"options":["Only this","Only this","  "]}');

        $this->assertFalse(app(StartingPoints::class)->ensureOptions($this->goal('Sales'), $w['rep1']));
        $this->assertNull($this->goal('Sales')->starting_options);
    }

    public function test_an_ai_failure_stores_nothing(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->fakeAi('error', 500);

        $this->assertFalse(app(StartingPoints::class)->ensureOptions($this->goal('Sales'), $w['rep1']));
        $this->assertNull($this->goal('Sales')->starting_options);
    }

    public function test_committing_records_the_pick_and_keeps_history_of_changes(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->goal('Sales')->update(['starting_options' => ['First', 'Second']]);
        $points = app(StartingPoints::class);

        $this->assertTrue($points->commit($this->goal('Sales'), $w['rep1'], 0));
        $response = GoalResponse::first();
        $this->assertSame('First', $response->starting_point);
        $this->assertSame('act_on_it', $response->decision);
        $this->assertNotNull($response->committed_at);

        $points->commit($this->goal('Sales'), $w['rep1'], 1);
        $points->commit($this->goal('Sales'), $w['rep1'], 1);

        $response = $response->fresh();
        $this->assertSame('Second', $response->starting_point);
        $this->assertCount(1, $response->starting_history);
        $this->assertSame(['First', 'Second'], [$response->starting_history[0]['from'], $response->starting_history[0]['to']]);
    }

    public function test_an_out_of_range_option_is_refused(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->goal('Sales')->update(['starting_options' => ['First', 'Second']]);

        $this->assertFalse(app(StartingPoints::class)->commit($this->goal('Sales'), $w['rep1'], 2));
        $this->assertFalse(app(StartingPoints::class)->commit($this->goal('Sales'), $w['rep1'], -1));
        $this->assertSame(0, GoalResponse::count());
    }
}
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=WhereToBeginTest`
Expected: FAIL with `Target class [App\Services\StartingPoints] does not exist.`

- [ ] **Step 3: Migration**

Create `database/migrations/2026_09_24_000100_add_where_to_begin_columns.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where to Begin (Features spec, phase 4): the goal's shared starting
     * options, and each person's pick with its history.
     */
    public function up(): void
    {
        Schema::table('expected_states', function (Blueprint $table) {
            if (! Schema::hasColumn('expected_states', 'starting_options')) {
                $table->json('starting_options')->nullable();
            }
        });
        Schema::table('goal_responses', function (Blueprint $table) {
            $table->string('starting_point', 255)->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->json('starting_history')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('goal_responses', function (Blueprint $table) {
            $table->dropColumn(['starting_point', 'committed_at', 'starting_history']);
        });
        Schema::table('expected_states', function (Blueprint $table) {
            $table->dropColumn('starting_options');
        });
    }
};
```

- [ ] **Step 4: Models**

`app/Models/ExpectedState.php`: add `'starting_options',` to `$fillable` after `'org_role_id',`, and `'starting_options' => 'array',` as the first entry of `$casts`.

`app/Models/GoalResponse.php`: replace `$fillable` and `$casts` with:

```php
    protected $fillable = ['expected_state_id', 'user_id', 'decision', 'decided_at', 'starting_point', 'committed_at', 'starting_history'];

    protected $casts = ['decided_at' => 'datetime', 'committed_at' => 'datetime', 'starting_history' => 'array'];
```

`app/Services/MyGoals.php`: change `private function companyGoal(` to `public function companyGoal(`.

- [ ] **Step 5: The service**

Create `app/Services/StartingPoints.php`:

```php
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
        $system = $this->docs->buildSystemMessage($member, "You are StrategiStudio's execution guide. Return ONLY valid JSON. No markdown, no code fences, no commentary.");
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
```

- [ ] **Step 6: Run to verify they pass**

Run: `php artisan test --filter=WhereToBeginTest`
Expected: 6 passed.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint database/migrations/2026_09_24_000100_add_where_to_begin_columns.php app/Services/StartingPoints.php app/Models/ExpectedState.php app/Models/GoalResponse.php app/Services/MyGoals.php tests/Feature/WhereToBeginTest.php
git add database/migrations/2026_09_24_000100_add_where_to_begin_columns.php app/Services/StartingPoints.php app/Models/ExpectedState.php app/Models/GoalResponse.php app/Services/MyGoals.php tests/Feature/WhereToBeginTest.php
git commit -m "feat(where-to-begin): shared starting options and committed picks"
```

---

### Task 2: The card's actions

**Files:**
- Modify: `app/Http/Controllers/Backend/MyGoalController.php`
- Modify: `routes/backend.php` (after the `my-goals.obstacle` route)
- Test: `tests/Feature/WhereToBeginTest.php`

**Interfaces:**
- Consumes: `StartingPoints` (Task 1); `MyGoals::visibleGoal`.
- Produces:
  - `decide` generates options after `act_on_it`;
  - route `my-goals.suggest` (POST `goal_id`);
  - route `my-goals.commit` (POST `goal_id`, `option`).

- [ ] **Step 1: Write the failing tests**

Append to `WhereToBeginTest`:

```php
    public function test_choosing_act_on_it_generates_options(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->fakeAi(self::OPTIONS);

        $this->actingAs($w['rep1'])->from('/dashboard')->post(route('my-goals.decide'), ['goal_id' => $this->goal('Sales')->id, 'decision' => 'act_on_it'])
            ->assertRedirect('/dashboard');

        $this->assertCount(3, $this->goal('Sales')->starting_options);
    }

    public function test_other_decisions_make_no_ai_call(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->fakeAi(self::OPTIONS, 200, 0);

        $this->actingAs($w['rep1'])->post(route('my-goals.decide'), ['goal_id' => $this->goal('Sales')->id, 'decision' => 'review_in_detail']);

        $this->assertNull($this->goal('Sales')->starting_options);
    }

    public function test_an_ai_failure_keeps_the_decision_and_suggest_retries(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->fakeAi('error', 500);

        $this->actingAs($w['rep1'])->post(route('my-goals.decide'), ['goal_id' => $this->goal('Sales')->id, 'decision' => 'act_on_it']);
        $this->assertSame('act_on_it', GoalResponse::first()->decision);
        $this->assertNull($this->goal('Sales')->starting_options);

        $this->fakeAi(self::OPTIONS);
        $this->actingAs($w['rep1'])->post(route('my-goals.suggest'), ['goal_id' => $this->goal('Sales')->id]);
        $this->assertCount(3, $this->goal('Sales')->starting_options);
    }

    public function test_committing_through_the_card(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->goal('Sales')->update(['starting_options' => ['First', 'Second']]);

        $this->actingAs($w['rep1'])->from('/dashboard')->post(route('my-goals.commit'), ['goal_id' => $this->goal('Sales')->id, 'option' => 1])
            ->assertRedirect('/dashboard');

        $this->assertSame('Second', GoalResponse::first()->starting_point);
    }

    public function test_a_bad_option_is_refused(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->goal('Sales')->update(['starting_options' => ['First', 'Second']]);

        foreach ([5, -1, 'first'] as $bad) {
            $this->actingAs($w['rep1'])->post(route('my-goals.commit'), ['goal_id' => $this->goal('Sales')->id, 'option' => $bad])
                ->assertSessionHasErrors('option');
        }
        $this->assertSame(0, GoalResponse::count());
    }

    public function test_committing_on_a_goal_you_cannot_see_is_not_found(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->goal('Sales')->update(['starting_options' => ['First', 'Second']]);

        $this->actingAs($w['pm'])->post(route('my-goals.commit'), ['goal_id' => $this->goal('Sales')->id, 'option' => 0])->assertNotFound();
        $this->actingAs($w['pm'])->post(route('my-goals.suggest'), ['goal_id' => $this->goal('Sales')->id])->assertNotFound();
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=WhereToBeginTest`
Expected: FAIL. `choosing_act_on_it_generates_options` finds `starting_options` null, and the other tests fail with `Route [my-goals.suggest] not defined.` / `Route [my-goals.commit] not defined.`

- [ ] **Step 3: Controller**

In `MyGoalController`:

1. Add `use App\Services\StartingPoints;`.
2. Change the constructor to:

```php
    public function __construct(protected MyGoals $goals, protected StartingPoints $starts) {}
```

3. In `decide`, replace
```php
        flash(localize('Your response has been saved'))->success();

        return back();
```
with
```php
        if ($data['decision'] === 'act_on_it' && ! $this->starts->ensureOptions($goal, $request->user())) {
            flash(localize('Saved. We could not suggest starting points just now, please try again.'))->warning();

            return back();
        }
        flash(localize('Your response has been saved'))->success();

        return back();
```

4. Add the two actions:

```php
    public function suggestStart(Request $request): RedirectResponse
    {
        $data = $request->validate(['goal_id' => 'required|integer']);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);

        if ($this->starts->ensureOptions($goal, $request->user())) {
            flash(localize('Here are some places to begin'))->success();
        } else {
            flash(localize('We could not suggest starting points just now, please try again.'))->warning();
        }

        return back();
    }

    public function commit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_id' => 'required|integer',
            'option' => 'required|integer|min:0',
        ]);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);

        if (! $this->starts->commit($goal, $request->user(), (int) $data['option'])) {
            return back()->withErrors(['option' => localize('Pick one of the suggested starting points.')]);
        }
        flash(localize('Your starting point is committed'))->success();

        return back();
    }
```

- [ ] **Step 4: Routes**

After the line containing `->name('my-goals.obstacle');` in `routes/backend.php`:

```php
                Route::post('/my-goals/suggest', [MyGoalController::class, 'suggestStart'])->name('my-goals.suggest');
                Route::post('/my-goals/commit', [MyGoalController::class, 'commit'])->name('my-goals.commit');
```

- [ ] **Step 5: Run to verify they pass**

Run: `php artisan test --filter='WhereToBeginTest|MyGoalCardTest'`
Expected: 28 passed (12 + 16).

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/MyGoalController.php routes/backend.php tests/Feature/WhereToBeginTest.php
git add app/Http/Controllers/Backend/MyGoalController.php routes/backend.php tests/Feature/WhereToBeginTest.php
git commit -m "feat(where-to-begin): suggest and commit a starting point from the card"
```

---

### Task 3: Alignment rates for the author

**Files:**
- Create: `app/Services/Alignment.php`
- Modify: `app/Http/Controllers/Backend/AI/StrategyPublishController.php` (`payload`)
- Modify: `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php`
- Test: `tests/Feature/WhereToBeginTest.php`

**Interfaces:**
- Produces: `Alignment::forStrategy(SearchUserChat): array{overall: array{people:int, committed:int, rate:?int}, departments: list<array{name:string, people:int, committed:int, rate:?int}>}`; `payload.alignment` (that array when published, else `null`).

- [ ] **Step 1: Write the failing tests**

Add `use App\Services\Alignment;` to the imports and append:

```php
    public function test_alignment_counts_commitments_per_department(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoals($w);
        GoalResponse::create(['expected_state_id' => $this->goal('Sales')->id, 'user_id' => $w['rep1']->id, 'decision' => 'act_on_it', 'starting_point' => 'X']);
        GoalResponse::create(['expected_state_id' => $this->goal('Product')->id, 'user_id' => $w['pm']->id, 'decision' => 'act_on_it', 'starting_point' => 'Y']);
        // rep2 holds Sales; a pick on the Product goal is not their commitment.
        GoalResponse::create(['expected_state_id' => $this->goal('Product')->id, 'user_id' => $w['rep2']->id, 'decision' => 'act_on_it', 'starting_point' => 'Z']);
        // An undecided response is not a commitment either.
        GoalResponse::create(['expected_state_id' => $this->goal('Sales')->id, 'user_id' => $w['ceo']->id, 'decision' => 'review_in_detail']);

        $alignment = app(Alignment::class)->forStrategy($chat);

        $this->assertSame(['people' => 3, 'committed' => 2, 'rate' => 67], $alignment['overall']);
        $this->assertSame([
            ['name' => 'No department', 'people' => 2, 'committed' => 1, 'rate' => 50],
            ['name' => 'Sales', 'people' => 1, 'committed' => 1, 'rate' => 100],
        ], $alignment['departments']);
    }

    public function test_alignment_ignores_other_organizations(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoals($w);
        $globex = Organization::create(['domain' => 'globex.com', 'name' => 'Globex']);
        User::factory()->create(['email' => 'x@globex.com', 'user_type' => 'customer', 'organization_id' => $globex->id, 'org_role_id' => $w['sales']->id]);

        $this->assertSame(3, app(Alignment::class)->forStrategy($chat)['overall']['people']);
    }

    public function test_a_strategy_nobody_holds_a_goal_for_has_no_rate(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoals($w);
        ExpectedState::query()->update(['org_role_id' => null]);

        $this->assertSame(['people' => 0, 'committed' => 0, 'rate' => null], app(Alignment::class)->forStrategy($chat)['overall']);
    }

    public function test_the_published_publish_card_carries_alignment(): void
    {
        $w = $this->world();
        $published = $this->publishedGoals($w);

        $this->actingAs($w['ceo'])->getJson(route('users-new-chat-resources.show', ['chat' => $published->id]))
            ->assertOk()->assertJsonPath('alignment.overall.people', 3);

        $draft = $this->publishedGoals($w, draft: true);
        $this->actingAs($w['ceo'])->getJson(route('users-new-chat-resources.show', ['chat' => $draft->id]))
            ->assertOk()->assertJsonPath('alignment', null);
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=WhereToBeginTest`
Expected: FAIL with `Target class [App\Services\Alignment] does not exist.`, and the payload test fails on the missing `alignment` key.

- [ ] **Step 3: The service**

Create `app/Services/Alignment.php`:

```php
<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalResponse;
use App\Models\SearchUserChat;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Where to Begin (Features spec, phase 4): how many of the people a published
 * strategy asks something of have committed to a starting point, per department.
 */
class Alignment
{
    /** @return array{overall: array{people: int, committed: int, rate: int|null}, departments: list<array{name: string, people: int, committed: int, rate: int|null}>} */
    public function forStrategy(SearchUserChat $chat): array
    {
        $roleIds = ExpectedState::where('search_user_chat_id', $chat->id)->whereNotNull('org_role_id')
            ->pluck('org_role_id')->map(fn ($id) => (int) $id)->unique()->values();

        $people = $chat->organization_id && $roleIds->isNotEmpty()
            ? User::where('organization_id', $chat->organization_id)->whereIn('org_role_id', $roleIds)
                ->with('department:id,name')->get(['id', 'org_role_id', 'department_id'])
            : collect();
        $roleOf = $people->mapWithKeys(fn (User $u) => [(int) $u->id => (int) $u->org_role_id]);

        // Only a pick on the person's own role's goal counts as their commitment.
        $committed = GoalResponse::whereNotNull('starting_point')
            ->whereIn('user_id', $people->pluck('id'))
            ->whereHas('goal', fn ($q) => $q->where('search_user_chat_id', $chat->id))
            ->with('goal:id,org_role_id')->get()
            ->filter(fn (GoalResponse $r) => (int) $r->goal->org_role_id === ($roleOf[(int) $r->user_id] ?? -1))
            ->pluck('user_id')->map(fn ($id) => (int) $id)->unique();

        $summary = function (string $name, Collection $group) use ($committed): array {
            $count = $group->count();
            $done = $group->filter(fn (User $u) => $committed->contains((int) $u->id))->count();

            return ['name' => $name, 'people' => $count, 'committed' => $done, 'rate' => $count ? (int) round(100 * $done / $count) : null];
        };

        $overall = $summary('', $people);
        unset($overall['name']);

        return [
            'overall' => $overall,
            'departments' => $people->groupBy(fn (User $u) => $u->department->name ?? 'No department')
                ->sortKeys()
                ->map(fn (Collection $group, string $name) => $summary($name, $group))
                ->values()->all(),
        ];
    }
}
```

- [ ] **Step 4: Payload and card**

In `StrategyPublishController`, add `use App\Services\Alignment;`, and add to the `payload()` return array after `'obstacles' => ...`:

```php
            'alignment' => $chat->isPublished() ? app(Alignment::class)->forStrategy($chat) : null,
```

In `publish-gate.blade.php`, add above `function obstaclesHtml()`:

```js
    function alignmentHtml() {
        const a = state.alignment;
        if (!a) return '';
        const pct = r => r === null ? '—' : r + '%';
        const rows = a.departments.map(d => `<tr><td>${esc(d.name)}</td><td>${d.committed} / ${d.people}</td><td>${pct(d.rate)}</td></tr>`).join('');
        return `<div class="pg-goals"><strong>Alignment</strong> — ${a.overall.committed} of ${a.overall.people} people committed (${pct(a.overall.rate)})
            ${rows ? `<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Department</th><th>Committed</th><th>Rate</th></tr></thead><tbody>${rows}</tbody></table></div>` : ''}</div>`;
    }
```

In `render()`'s template, insert `${alignmentHtml()}` directly before `${obstaclesHtml()}`.

- [ ] **Step 5: Run to verify they pass**

Run: `php artisan test --filter=WhereToBeginTest`
Expected: 16 passed.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint app/Services/Alignment.php app/Http/Controllers/Backend/AI/StrategyPublishController.php tests/Feature/WhereToBeginTest.php
git add app/Services/Alignment.php app/Http/Controllers/Backend/AI/StrategyPublishController.php resources/views/backend/pages/aiChat/inc/publish-gate.blade.php tests/Feature/WhereToBeginTest.php
git commit -m "feat(where-to-begin): alignment rates per department in the publish card"
```

---

### Task 4: Where to Begin on the dashboard card

**Files:**
- Modify: `resources/views/backend/pages/goals/my-goals.blade.php`
- Test: `tests/Feature/WhereToBeginTest.php`

**Interfaces:**
- Consumes: routes `my-goals.commit` and `my-goals.suggest` (Task 2); `starting_options` and `starting_point` (Task 1).

- [ ] **Step 1: Write the failing tests**

Append:

```php
    public function test_the_card_offers_starting_points_after_act_on_it(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->goal('Sales')->update(['starting_options' => ['Audit fields', 'Book a sync']]);
        GoalResponse::create(['expected_state_id' => $this->goal('Sales')->id, 'user_id' => $w['rep1']->id, 'decision' => 'act_on_it', 'starting_point' => 'Book a sync']);

        $this->actingAs($w['rep1'])->get('/dashboard')
            ->assertOk()
            ->assertSee('Where to begin')
            ->assertSee('Audit fields')
            ->assertSee(route('my-goals.commit'), false)
            ->assertSee('Change my starting point');
    }

    public function test_the_card_offers_to_suggest_when_there_are_no_options(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        GoalResponse::create(['expected_state_id' => $this->goal('Sales')->id, 'user_id' => $w['rep1']->id, 'decision' => 'act_on_it']);

        $this->actingAs($w['rep1'])->get('/dashboard')
            ->assertOk()
            ->assertSee('Suggest starting points')
            ->assertSee(route('my-goals.suggest'), false);
    }

    public function test_no_starting_points_before_act_on_it_and_the_obstacle_box_is_required(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);

        $this->actingAs($w['rep1'])->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Where to begin')
            ->assertSee('name="body" maxlength="2000" required', false);
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=WhereToBeginTest`
Expected: the first two fail on `Where to begin` / `Suggest starting points`, and the third fails on `required`.

- [ ] **Step 3: The card**

In `my-goals.blade.php`:

1. Replace `<input type="text" name="body" maxlength="2000" class=` with `<input type="text" name="body" maxlength="2000" required class=`.
2. Directly after the `</form>` that closes the decision form (the form with `action="{{ route('my-goals.decide') }}"`), insert:

```blade
                    @if ($card['response']?->decision === 'act_on_it')
                        <div class="mg-label mt-3">{{ localize('Where to begin') }}</div>
                        @if (! empty($card['goal']->starting_options))
                            <form method="POST" action="{{ route('my-goals.commit') }}">
                                @csrf
                                <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                                @foreach ($card['goal']->starting_options as $i => $option)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="option" value="{{ $i }}"
                                            id="start-{{ $card['goal']->id }}-{{ $i }}" required
                                            @checked($card['response']->starting_point === $option)>
                                        <label class="form-check-label small" for="start-{{ $card['goal']->id }}-{{ $i }}">{{ $option }}</label>
                                    </div>
                                @endforeach
                                <button type="submit" class="btn btn-sm mg-btn mt-1">
                                    {{ $card['response']->starting_point ? localize('Change my starting point') : localize('Commit') }}
                                </button>
                            </form>
                            @if ($card['response']->starting_point)
                                <div class="small mt-1">{{ localize('Committed') }}: <strong>{{ $card['response']->starting_point }}</strong></div>
                            @endif
                            @error('option')
                                <div class="small text-danger mt-1">{{ $message }}</div>
                            @enderror
                        @else
                            <form method="POST" action="{{ route('my-goals.suggest') }}">
                                @csrf
                                <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                                <button type="submit" class="btn btn-sm mg-btn">{{ localize('Suggest starting points') }}</button>
                            </form>
                        @endif
                    @endif
```

- [ ] **Step 4: Run to verify they pass**

Run: `php artisan test --filter=WhereToBeginTest`
Expected: 19 passed.

Run: `php artisan test`
Expected: the full suite passes.

- [ ] **Step 5: CI gates**

Run: `vendor/bin/pint --test $(git diff --name-only feat/my-goal-card...HEAD -- '*.php')`, `vendor/bin/phpstan analyse --memory-limit=1G --no-progress`, and the JS check (extract the publish card's `<script>`, `node --check`).
Expected: all clean.

- [ ] **Step 6: Commit**

```bash
git add resources/views/backend/pages/goals/my-goals.blade.php tests/Feature/WhereToBeginTest.php
git commit -m "feat(where-to-begin): starting points on the Your goals card"
```
