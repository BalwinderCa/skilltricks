# My Goal Card Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Each user's dashboard shows their role's goal from every published strategy in their organization, where they can respond and report obstacles. The strategy's author sees those obstacles.

**Architecture:** Two tables (`goal_responses`, `goal_obstacles`), a `MyGoals` service that owns the visibility rule, a small `MyGoalController` with two form-post actions, and a server-rendered Blade partial on the dashboard. The Phase 1 Publish card payload gains an `obstacles` list.

**Tech Stack:** Laravel 12, Blade, PHPUnit feature tests on SQLite.

**Spec:** `docs/superpowers/specs/2026-09-24-my-goal-card-design.md`

## Global Constraints

- Visibility is exactly: strategy `published`, strategy `organization_id` = user `organization_id`, goal `org_role_id` = user `org_role_id` (not null). One implementation only: `MyGoals::query()`.
- Every write endpoint returns 404 for a goal outside that rule.
- Decisions are exactly `act_on_it`, `review_in_detail`, `not_viable`.
- UI colours: teal `#36839b`, orange `#ec883f`. No purple. All user text goes through Blade `{{ }}`.
- The author's Action Table and OI flow are not touched.

## Review Focus

1. **A user whose `org_role_id` is null** — must see no goals, even though unlinked goals also have a null role. Test in Task 1.
2. **A strategy published in another organization with a same-named role** — invisible. Test in Task 1.
3. **Changing a decision twice** — one row per person, updated. Test in Task 2.
4. **An obstacle that is whitespace only** — rejected. Test in Task 2.
5. **A goal whose strategy has no resource rows at all** — the card renders without a resources line. Test in Task 3.

---

### Task 1: Tables, models and the MyGoals service

**Files:**
- Create: `database/migrations/2026_09_24_000000_create_goal_responses_and_obstacles.php`
- Create: `app/Models/GoalResponse.php`, `app/Models/GoalObstacle.php`
- Create: `app/Services/MyGoals.php`
- Modify: `app/Models/ExpectedState.php` (add `responses()`, `obstacles()`)
- Test: `tests/Feature/MyGoalCardTest.php`

**Interfaces:**
- Produces:
  - `MyGoals::DECISIONS` (array of the three values).
  - `MyGoals::query(User): Builder<ExpectedState>`.
  - `MyGoals::for(User): Collection` of arrays with keys `goal, strategy, company_goal, role_name, resources, waiting_on, waiting_on_you, response, obstacles`.
  - `MyGoals::visibleGoal(User, int): ?ExpectedState`.
  - Models `GoalResponse` (fillable `expected_state_id, user_id, decision, decided_at`) and `GoalObstacle` (fillable `expected_state_id, user_id, body`; `created_at` only).
  - Test helpers `world()`, `publishedGoal()`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/MyGoalCardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\MyGoals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "My goal" dashboard card (Features spec, phase 3): each user sees their
 * role's goal from every published strategy in their organization.
 */
class MyGoalCardTest extends TestCase
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

    /**
     * Acme with a CEO (author), a Sales rep, a Product lead and a role-less
     * member; roles Sales and Product; department Sales.
     *
     * @return array{org: Organization, ceo: User, rep: User, pm: User, nobody: User, sales: OrgRole, product: OrgRole, salesDept: Department}
     */
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
            'rep' => $make('rep@acme.com', $sales, $salesDept),
            'pm' => $make('pm@acme.com', $product),
            'nobody' => $make('nobody@acme.com', null),
        ];
    }

    /** A strategy by $author with one goal per role; published unless $draft. */
    private function publishedGoal(array $w, bool $draft = false): SearchUserChat
    {
        $chat = SearchUserChat::create([
            'user_id' => $w['ceo']->id, 'status1' => 0,
            'selected_strategy' => 'Security upsell', 'selected_scenario' => 'Expected',
            'leadership_brief' => "# Brief\nConvert mid-market accounts.",
        ]);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $w['ceo']->id, 'search' => 'Grow enterprise revenue 30%', 'response' => 'ok']);
        $product = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Product', 'recommended_action' => 'Ship SOC2 controls', 'org_role_id' => $w['product']->id]);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion', 'org_role_id' => $w['sales']->id, 'depends_on_id' => $product->id]);
        $chat->resources()->create(['department_id' => $w['salesDept']->id, 'department_name' => 'Sales', 'budget' => 50000, 'fte' => 2, 'tools' => 'CRM seats']);
        if (! $draft) {
            $chat->forceFill(['status' => 'published', 'published_by' => $w['ceo']->id, 'published_at' => now(), 'organization_id' => $w['org']->id])->save();
        }

        return $chat;
    }

    public function test_a_member_sees_their_roles_goal_with_context(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $cards = app(MyGoals::class)->for($w['rep']);

        $this->assertCount(1, $cards);
        $card = $cards->first();
        $this->assertSame('Launch the upgrade motion', $card['goal']->recommended_action);
        $this->assertSame('Grow enterprise revenue 30%', $card['company_goal']);
        $this->assertSame('Sales', $card['role_name']);
        $this->assertSame('50000.00', $card['resources']->budget);
        $this->assertSame('Ship SOC2 controls', $card['waiting_on']->recommended_action);
        $this->assertCount(0, $card['waiting_on_you']);
        $this->assertSame('Launch the upgrade motion', app(MyGoals::class)->for($w['pm'])->first()['waiting_on_you']->first()->recommended_action);
    }

    public function test_drafts_are_never_shown(): void
    {
        $w = $this->world();
        $this->publishedGoal($w, draft: true);

        $this->assertCount(0, app(MyGoals::class)->for($w['rep']));
    }

    public function test_a_user_without_a_role_sees_nothing_even_with_unlinked_goals(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoal($w);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Ghost', 'recommended_action' => 'Unlinked', 'org_role_id' => null]);

        $this->assertCount(0, app(MyGoals::class)->for($w['nobody']));
        $this->assertNull(app(MyGoals::class)->visibleGoal($w['nobody'], ExpectedState::where('role', 'Ghost')->value('id')));
    }

    public function test_another_organizations_strategy_is_invisible(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);
        $globex = Organization::create(['domain' => 'globex.com', 'name' => 'Globex']);
        $theirSales = OrgRole::create(['organization_id' => $globex->id, 'name' => 'Sales']);
        $spy = User::factory()->create(['email' => 'spy@globex.com', 'user_type' => 'customer', 'organization_id' => $globex->id, 'org_role_id' => $theirSales->id]);

        $this->assertCount(0, app(MyGoals::class)->for($spy));
        $this->assertNull(app(MyGoals::class)->visibleGoal($spy, ExpectedState::where('role', 'Sales')->value('id')));
    }

    public function test_a_member_without_a_matching_department_falls_back_to_the_whole_organization_row(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoal($w);
        $chat->resources()->create(['department_id' => null, 'department_name' => 'Whole organization', 'budget' => 7]);

        $this->assertSame('Whole organization', app(MyGoals::class)->for($w['pm'])->first()['resources']->department_name);
    }
}
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=MyGoalCardTest`
Expected: FAIL with `Target class [App\Services\MyGoals] does not exist.`

- [ ] **Step 3: Migration**

Create `database/migrations/2026_09_24_000000_create_goal_responses_and_obstacles.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "My goal" card (Features spec, phase 3): each person's response to a
     * published goal, and the obstacles they report against it.
     */
    public function up(): void
    {
        Schema::create('goal_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('decision', 30)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['expected_state_id', 'user_id']);
        });

        Schema::create('goal_obstacles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('body');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_obstacles');
        Schema::dropIfExists('goal_responses');
    }
};
```

- [ ] **Step 4: Models**

Create `app/Models/GoalResponse.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's response to a published goal.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int $user_id
 * @property string|null $decision
 */
class GoalResponse extends Model
{
    protected $fillable = ['expected_state_id', 'user_id', 'decision', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    /** @return BelongsTo<ExpectedState, $this> */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Create `app/Models/GoalObstacle.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something in the way of a published goal, reported by someone who holds it.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int $user_id
 * @property string $body
 */
class GoalObstacle extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['expected_state_id', 'user_id', 'body'];

    /** @return BelongsTo<ExpectedState, $this> */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

In `app/Models/ExpectedState.php`, add before the closing brace:

```php
    /** @return HasMany<GoalResponse, $this> */
    public function responses(): HasMany
    {
        return $this->hasMany(GoalResponse::class);
    }

    /** @return HasMany<GoalObstacle, $this> */
    public function obstacles(): HasMany
    {
        return $this->hasMany(GoalObstacle::class);
    }
```

- [ ] **Step 5: The service**

Create `app/Services/MyGoals.php`:

```php
<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\StrategyResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * What a person sees of published strategies: the goals for their role, in
 * their organization. The one place that visibility rule lives.
 */
class MyGoals
{
    public const DECISIONS = ['act_on_it', 'review_in_detail', 'not_viable'];

    /** @return Builder<ExpectedState> */
    public function query(User $user): Builder
    {
        // A null role never matches: unlinked goals also have a null role.
        return ExpectedState::query()
            ->whereNotNull('org_role_id')
            ->where('org_role_id', (int) $user->org_role_id)
            ->whereHas('searchUserChat', fn ($q) => $q->where('status', 'published')
                ->where('organization_id', (int) $user->organization_id));
    }

    public function visibleGoal(User $user, int $goalId): ?ExpectedState
    {
        if (! $user->organization_id || ! $user->org_role_id) {
            return null;
        }

        return $this->query($user)->whereKey($goalId)->first();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function for(User $user): Collection
    {
        if (! $user->organization_id || ! $user->org_role_id) {
            return collect();
        }

        $goals = $this->query($user)
            ->with(['searchUserChat', 'orgRole:id,name', 'dependsOn.orgRole:id,name', 'dependents.orgRole:id,name'])
            ->get()
            ->sortByDesc(fn (ExpectedState $g) => $g->searchUserChat->published_at)
            ->values();
        $ids = $goals->pluck('id');
        $responses = GoalResponse::where('user_id', $user->id)->whereIn('expected_state_id', $ids)->get()->keyBy('expected_state_id');
        $obstacles = GoalObstacle::where('user_id', $user->id)->whereIn('expected_state_id', $ids)->orderByDesc('id')->get()->groupBy('expected_state_id');

        // ponytail: two queries per card (company goal, resources); fine for a
        // handful of published strategies, batch them if a dashboard shows dozens.
        return $goals->map(fn (ExpectedState $g) => [
            'goal' => $g,
            'strategy' => $g->searchUserChat,
            'company_goal' => $this->companyGoal($g->searchUserChat),
            'role_name' => $g->orgRole?->name,
            'resources' => $this->teamResources($g->searchUserChat, $user),
            'waiting_on' => $g->dependsOn,
            'waiting_on_you' => $g->dependents,
            'response' => $responses->get($g->id),
            'obstacles' => $obstacles->get($g->id, collect()),
        ]);
    }

    /** The strategy's first question; the chat row has no goal column. */
    private function companyGoal(SearchUserChat $chat): string
    {
        $first = SearchUserChatData::where('search_user_chat_id', $chat->id)->orderBy('id')->value('search');
        if (filled($first)) {
            return trim((string) $first);
        }

        return trim(ltrim((string) strtok((string) $chat->leadership_brief, "\n"), '# '));
    }

    /** The user's department's committed resources, else the whole-organization row. */
    private function teamResources(SearchUserChat $chat, User $user): ?StrategyResource
    {
        $rows = $chat->resources()->get();

        return ($user->department_id ? $rows->firstWhere('department_id', $user->department_id) : null)
            ?? $rows->firstWhere('department_id', null);
    }
}
```

- [ ] **Step 6: Run to verify they pass**

Run: `php artisan test --filter=MyGoalCardTest`
Expected: 5 passed.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint database/migrations/2026_09_24_000000_create_goal_responses_and_obstacles.php app/Models/GoalResponse.php app/Models/GoalObstacle.php app/Models/ExpectedState.php app/Services/MyGoals.php tests/Feature/MyGoalCardTest.php
git add database/migrations/2026_09_24_000000_create_goal_responses_and_obstacles.php app/Models/GoalResponse.php app/Models/GoalObstacle.php app/Models/ExpectedState.php app/Services/MyGoals.php tests/Feature/MyGoalCardTest.php
git commit -m "feat(my-goals): who sees which published goal"
```

---

### Task 2: Respond and report an obstacle

**Files:**
- Create: `app/Http/Controllers/Backend/MyGoalController.php`
- Modify: `routes/backend.php` (import near line 48; routes after the publish-gate routes)
- Test: `tests/Feature/MyGoalCardTest.php`

**Interfaces:**
- Consumes: `MyGoals::visibleGoal`, `MyGoals::DECISIONS`, `world()`, `publishedGoal()`.
- Produces: routes `my-goals.decide` (POST `goal_id`, `decision`) and `my-goals.obstacle` (POST `goal_id`, `body`). Both redirect back; 404 for an unseen goal.

- [ ] **Step 1: Write the failing tests**

Append to `MyGoalCardTest`:

```php
    private function salesGoalId(): int
    {
        return (int) ExpectedState::where('role', 'Sales')->value('id');
    }

    public function test_a_member_records_and_then_changes_their_decision(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['rep'])->from('/dashboard')->post(route('my-goals.decide'), ['goal_id' => $this->salesGoalId(), 'decision' => 'review_in_detail'])
            ->assertRedirect('/dashboard');
        $this->actingAs($w['rep'])->post(route('my-goals.decide'), ['goal_id' => $this->salesGoalId(), 'decision' => 'act_on_it']);

        $this->assertSame(1, GoalResponse::count());
        $this->assertSame('act_on_it', GoalResponse::first()->decision);
        $this->assertSame($w['rep']->id, (int) GoalResponse::first()->user_id);
    }

    public function test_deciding_on_a_goal_you_cannot_see_is_not_found(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['pm'])->post(route('my-goals.decide'), ['goal_id' => $this->salesGoalId(), 'decision' => 'act_on_it'])
            ->assertNotFound();
        $this->assertSame(0, GoalResponse::count());
    }

    public function test_an_unknown_decision_is_rejected(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['rep'])->post(route('my-goals.decide'), ['goal_id' => $this->salesGoalId(), 'decision' => 'maybe'])
            ->assertSessionHasErrors('decision');
    }

    public function test_a_member_reports_an_obstacle(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['rep'])->from('/dashboard')->post(route('my-goals.obstacle'), ['goal_id' => $this->salesGoalId(), 'body' => '  Missing budget for CRM seats  '])
            ->assertRedirect('/dashboard');

        $obstacle = GoalObstacle::first();
        $this->assertSame('Missing budget for CRM seats', $obstacle->body);
        $this->assertSame($w['rep']->id, (int) $obstacle->user_id);
    }

    public function test_a_blank_or_oversized_obstacle_is_rejected(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['rep'])->post(route('my-goals.obstacle'), ['goal_id' => $this->salesGoalId(), 'body' => '   '])
            ->assertSessionHasErrors('body');
        $this->actingAs($w['rep'])->post(route('my-goals.obstacle'), ['goal_id' => $this->salesGoalId(), 'body' => str_repeat('x', 2001)])
            ->assertSessionHasErrors('body');
        $this->assertSame(0, GoalObstacle::count());
    }

    public function test_reporting_on_a_draft_goal_is_not_found(): void
    {
        $w = $this->world();
        $this->publishedGoal($w, draft: true);

        $this->actingAs($w['rep'])->post(route('my-goals.obstacle'), ['goal_id' => $this->salesGoalId(), 'body' => 'x'])
            ->assertNotFound();
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=MyGoalCardTest`
Expected: FAIL with `Route [my-goals.decide] not defined.`

- [ ] **Step 3: Controller**

Create `app/Http/Controllers/Backend/MyGoalController.php`:

```php
<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Services\MyGoals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The "My goal" card's two actions: respond to a published goal, and report
 * what is in its way. Visibility is MyGoals' rule; anything else is a 404.
 */
class MyGoalController extends Controller
{
    public function __construct(protected MyGoals $goals) {}

    public function decide(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_id' => 'required|integer',
            'decision' => ['required', Rule::in(MyGoals::DECISIONS)],
        ]);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);

        GoalResponse::updateOrCreate(
            ['expected_state_id' => $goal->id, 'user_id' => $request->user()->id],
            ['decision' => $data['decision'], 'decided_at' => now()],
        );
        flash(localize('Your response has been saved'))->success();

        return back();
    }

    public function reportObstacle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_id' => 'required|integer',
            'body' => 'required|string|max:2000',
        ]);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);

        GoalObstacle::create(['expected_state_id' => $goal->id, 'user_id' => $request->user()->id, 'body' => trim($data['body'])]);
        flash(localize('Thanks, your obstacle has been recorded'))->success();

        return back();
    }
}
```

- [ ] **Step 4: Routes**

In `routes/backend.php`, add `use App\Http\Controllers\Backend\MyGoalController;` after `use App\Http\Controllers\Backend\DashboardController;`. After the line containing `->name('users-new-chat-goal-role.index');`, add:

```php
                // "My goal" card: respond to a published goal, report an obstacle
                Route::post('/my-goals/decide', [MyGoalController::class, 'decide'])->name('my-goals.decide');
                Route::post('/my-goals/obstacle', [MyGoalController::class, 'reportObstacle'])->name('my-goals.obstacle');
```

- [ ] **Step 5: Run to verify they pass**

Run: `php artisan test --filter=MyGoalCardTest`
Expected: 11 passed.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/MyGoalController.php routes/backend.php tests/Feature/MyGoalCardTest.php
git add app/Http/Controllers/Backend/MyGoalController.php routes/backend.php tests/Feature/MyGoalCardTest.php
git commit -m "feat(my-goals): respond to a goal and report obstacles"
```

---

### Task 3: The card on the dashboard

**Files:**
- Create: `resources/views/backend/pages/goals/my-goals.blade.php`
- Modify: `app/Http/Controllers/Backend/DashboardController.php` (`index` view data)
- Modify: `resources/views/backend/pages/dashboard.blade.php` (one `@include` before the "Active strategic context" card)
- Test: `tests/Feature/MyGoalCardTest.php`

**Interfaces:**
- Consumes: `MyGoals::for`, the routes from Task 2.
- Produces: `#my-goals` card on `/dashboard`.

- [ ] **Step 1: Write the failing tests**

Append:

```php
    public function test_the_dashboard_shows_the_members_goal_card(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['rep'])->get('/dashboard')
            ->assertOk()
            ->assertSee('Your goals')
            ->assertSee('Grow enterprise revenue 30%')
            ->assertSee('Launch the upgrade motion')
            ->assertSee('Ship SOC2 controls')
            ->assertSee('CRM seats')
            ->assertSee(route('my-goals.decide'), false);
    }

    public function test_the_card_renders_without_resources(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoal($w);
        $chat->resources()->delete();

        $this->actingAs($w['rep'])->get('/dashboard')
            ->assertOk()
            ->assertSee('Launch the upgrade motion')
            ->assertDontSee('Your team\'s resources');
    }

    public function test_a_member_without_a_role_sees_the_hint(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['nobody'])->get('/dashboard')
            ->assertOk()
            ->assertSee('once your organization gives you a role')
            ->assertDontSee('Launch the upgrade motion');
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=MyGoalCardTest`
Expected: the 3 new tests fail on `Your goals` / the hint text not being found.

- [ ] **Step 3: Pass the cards from the controller**

In `DashboardController::index`, add to the `view('backend.pages.dashboard', [ ... ])` array, after `'profileCompletion' => $this->profileCompletion($user),`:

```php
            // Features spec, phase 3: this user's goals from published strategies.
            'myGoals' => app(\App\Services\MyGoals::class)->for($user),
```

- [ ] **Step 4: The partial**

Create `resources/views/backend/pages/goals/my-goals.blade.php`:

```blade
{{-- "My goal" card (Features spec, phase 3): this user's goals from the
     organization's published strategies. --}}
@php
    $decisionLabels = [
        'act_on_it' => localize('Act on it'),
        'review_in_detail' => localize('Review in detail'),
        'not_viable' => localize('Not viable'),
    ];
    $currency = config('custom.default_currency_symbol') ?: '$';
@endphp
<style>
    #my-goals .mg-goal { border: 1px solid #36839b; border-radius: 10px; padding: 14px; margin-top: 12px; background: #e7f3f7; }
    #my-goals .mg-label { font-size: 12px; color: #2c6d82; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 2px; }
    #my-goals .mg-action { font-weight: 600; }
    #my-goals .mg-btn { border: 1px solid #36839b; color: #2c6d82; background: #fff; }
    #my-goals .mg-btn.active { background: #36839b; color: #fff; }
    #my-goals .mg-obstacle { font-size: 12px; background: #fbf2ea; border-left: 3px solid #ec883f; padding: 4px 8px; margin-top: 4px; border-radius: 4px; }
</style>
<div class="card mb-4" id="my-goals">
    <div class="card-body">
        <h5 class="mb-1">{{ localize('Your goals') }}</h5>
        @if (! $user->org_role_id)
            <p class="text-muted small mb-0">{{ localize('You will see goals here once your organization gives you a role.') }}</p>
        @elseif ($myGoals->isEmpty())
            <p class="text-muted small mb-0">{{ localize('No published strategy has a goal for your role yet.') }}</p>
        @else
            @foreach ($myGoals as $card)
                <div class="mg-goal">
                    <div class="mg-label">{{ localize('Company goal') }}</div>
                    <div class="mb-2">{{ $card['company_goal'] }}</div>
                    <div class="small text-muted mb-2">
                        {{ $card['strategy']->selected_strategy }}@if ($card['strategy']->selected_scenario) · {{ $card['strategy']->selected_scenario }}@endif
                    </div>

                    <div class="mg-label">{{ localize('Your goal') }} ({{ $card['role_name'] }})</div>
                    <div class="mg-action mb-2">{{ $card['goal']->recommended_action }}</div>
                    @if ($card['goal']->success_metric || $card['goal']->target_date)
                        <div class="small mb-2">
                            @if ($card['goal']->success_metric){{ localize('Measure') }}: {{ $card['goal']->success_metric }}@endif
                            @if ($card['goal']->target_value) ({{ $card['goal']->target_value }})@endif
                            @if ($card['goal']->target_date) · {{ localize('By') }} {{ \Illuminate\Support\Carbon::parse($card['goal']->target_date)->toFormattedDateString() }}@endif
                        </div>
                    @endif

                    @if ($card['resources'])
                        <div class="mg-label">{{ localize('Your team\'s resources') }} ({{ $card['resources']->department_name }})</div>
                        <div class="small mb-2">
                            {{ $card['resources']->budget !== null ? $currency.number_format((float) $card['resources']->budget) : '—' }}
                            · {{ $card['resources']->fte !== null ? (float) $card['resources']->fte.' FTE' : '—' }}
                            @if ($card['resources']->tools) · {{ $card['resources']->tools }}@endif
                        </div>
                    @endif

                    @if ($card['waiting_on'])
                        <div class="small mb-1"><strong>{{ localize('Waiting on') }}:</strong> {{ $card['waiting_on']->orgRole?->name ?? $card['waiting_on']->role }} — {{ $card['waiting_on']->recommended_action }}</div>
                    @endif
                    @foreach ($card['waiting_on_you'] as $dependent)
                        <div class="small mb-1"><strong>{{ localize('Waiting on you') }}:</strong> {{ $dependent->orgRole?->name ?? $dependent->role }} — {{ $dependent->recommended_action }}</div>
                    @endforeach

                    <form method="POST" action="{{ route('my-goals.decide') }}" class="d-flex flex-wrap gap-2 mt-2">
                        @csrf
                        <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                        @foreach ($decisionLabels as $value => $label)
                            <button type="submit" name="decision" value="{{ $value }}"
                                class="btn btn-sm mg-btn {{ $card['response']?->decision === $value ? 'active' : '' }}">{{ $label }}</button>
                        @endforeach
                    </form>

                    <form method="POST" action="{{ route('my-goals.obstacle') }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                        <div class="d-flex gap-2">
                            <input type="text" name="body" maxlength="2000" class="form-control form-control-sm"
                                placeholder="{{ localize('Anything in the way? e.g. the tool keeps timing out') }}">
                            <button type="submit" class="btn btn-sm mg-btn">{{ localize('Report') }}</button>
                        </div>
                    </form>
                    @foreach ($card['obstacles'] as $obstacle)
                        <div class="mg-obstacle">{{ $obstacle->body }} <span class="text-muted">· {{ optional($obstacle->created_at)->diffForHumans() }}</span></div>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>
</div>
```

- [ ] **Step 5: Include it**

In `resources/views/backend/pages/dashboard.blade.php`, directly before the `<div class="card mb-4">` that opens the card whose `<h5>` is `{{ localize('Active strategic context') }}`, insert:

```blade
                @include('backend.pages.goals.my-goals')
```

- [ ] **Step 6: Run to verify they pass**

Run: `php artisan test --filter=MyGoalCardTest`
Expected: 14 passed.

Run: `php artisan test`
Expected: the full suite passes (`OnboardingFlowTest` and `OrganizationPageTest` render the dashboard).

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/DashboardController.php tests/Feature/MyGoalCardTest.php
git add resources/views/backend/pages/goals/my-goals.blade.php app/Http/Controllers/Backend/DashboardController.php resources/views/backend/pages/dashboard.blade.php tests/Feature/MyGoalCardTest.php
git commit -m "feat(my-goals): Your goals card on the dashboard"
```

---

### Task 4: The author sees reported obstacles

**Files:**
- Modify: `app/Http/Controllers/Backend/AI/StrategyPublishController.php` (`payload`)
- Modify: `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php`
- Test: `tests/Feature/MyGoalCardTest.php`

**Interfaces:**
- Consumes: `GoalObstacle`, the Phase 1 payload.
- Produces: `payload.obstacles: [{goal_id, role, user, body, at}]`, newest first, only when published (otherwise `[]`).

- [ ] **Step 1: Write the failing tests**

Append:

```php
    public function test_the_author_sees_obstacles_once_published(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoal($w);
        GoalObstacle::create(['expected_state_id' => $this->salesGoalId(), 'user_id' => $w['rep']->id, 'body' => 'Missing budget']);

        $this->actingAs($w['ceo'])->getJson(route('users-new-chat-resources.show', ['chat' => $chat->id]))
            ->assertOk()
            ->assertJsonPath('obstacles.0.body', 'Missing budget')
            ->assertJsonPath('obstacles.0.user', $w['rep']->name)
            ->assertJsonPath('obstacles.0.role', 'Sales');
    }

    public function test_a_draft_reports_no_obstacles(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoal($w, draft: true);
        GoalObstacle::create(['expected_state_id' => $this->salesGoalId(), 'user_id' => $w['rep']->id, 'body' => 'x']);

        $this->actingAs($w['ceo'])->getJson(route('users-new-chat-resources.show', ['chat' => $chat->id]))
            ->assertOk()
            ->assertJsonCount(0, 'obstacles');
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=MyGoalCardTest`
Expected: the published test fails, because the payload has no `obstacles`. The draft test fails because `assertJsonCount` finds no key.

- [ ] **Step 3: Payload**

In `StrategyPublishController`, add `use App\Models\GoalObstacle;`. In `payload()`, before `return [`, add:

```php
        $obstacles = $chat->isPublished()
            ? GoalObstacle::whereIn('expected_state_id', $goals->pluck('id'))->with('user:id,name')->orderByDesc('id')->get()
            : collect();
        $goalRoles = $goals->mapWithKeys(fn (ExpectedState $g) => [$g->id => $g->orgRole?->name ?? $g->role]);
```

And add to the returned array after `'roles' => ...`:

```php
            'obstacles' => $obstacles->map(fn (GoalObstacle $o) => [
                'goal_id' => $o->expected_state_id,
                'role' => $goalRoles[$o->expected_state_id] ?? '',
                'user' => $o->user?->name,
                'body' => $o->body,
                'at' => $o->created_at?->toIso8601String(),
            ])->values(),
```

- [ ] **Step 4: Show them in the card**

In `publish-gate.blade.php`, add this function above `function goalsHtml(`:

```js
    function obstaclesHtml() {
        if (!state.obstacles || !state.obstacles.length) return '';
        return `<div class="pg-history"><strong>Reported obstacles</strong><ul class="mb-0">${state.obstacles.map(o =>
            `<li>${esc(new Date(o.at).toLocaleString())} — ${esc(o.user)} (${esc(o.role)}): ${esc(o.body)}</li>`).join('')}</ul></div>`;
    }
```

In `render()`'s template, directly after `${goalsHtml(published)}`, insert `${obstaclesHtml()}`.

- [ ] **Step 5: Run to verify they pass**

Run: `php artisan test --filter=MyGoalCardTest`
Expected: 16 passed.

Run: `php artisan test`
Expected: the full suite passes.

- [ ] **Step 6: CI gates**

Run: `vendor/bin/pint --test $(git diff --name-only feat/role-goal-links...HEAD -- '*.php')` and `vendor/bin/phpstan analyse --memory-limit=1G --no-progress`
Expected: Pint passed; PHPStan no errors.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Backend/AI/StrategyPublishController.php resources/views/backend/pages/aiChat/inc/publish-gate.blade.php tests/Feature/MyGoalCardTest.php
git commit -m "feat(my-goals): the author sees reported obstacles in the publish card"
```
