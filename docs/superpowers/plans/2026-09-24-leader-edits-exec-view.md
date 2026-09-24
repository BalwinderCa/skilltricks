# Leader Edits and Executive View Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Leaders refine their goals' wording, with history and in-app alerts up the reporting chain, and get an executive view of every published strategy in their organization.

**Architecture:** `OrganizationService` gains `isLeader` and `managerChain`. A `goal_revisions` table plus a `GoalRevisions` service handle edits and alerts, using the existing `saveNotification()` bell. A `StrategyOverview` service feeds a new `StrategyOverviewController` with list and detail Blade pages. The dashboard card gains a revise form and an "Executive view" link for leaders.

**Tech Stack:** Laravel 12, Blade, PHPUnit on SQLite.

**Spec:** `docs/superpowers/specs/2026-09-24-leader-edits-exec-view-design.md`

## Global Constraints

- Leader = organization owner, a department head, or anyone with a direct report, all within the user's own organization.
- The executive view shows only `published` strategies in the viewer's organization: 404 otherwise, and 403 for non-leaders.
- Alerts use `saveNotification($title, $relativeUrl, 'customer', $userId, null, 'goal_revision', $description)`. The URL is relative with no leading slash, because `WrNotificationContoller` redirects to `'/'.$url`.
- Recipients: the editor's manager chain (same organization, loop-safe, at most 20) plus the strategy's author, de-duplicated, never the editor.
- UI: teal `#36839b`, orange `#ec883f`, no purple; `{{ }}` for all text.

## Review Focus

1. **A manager chain with a loop** (A reports to B, B reports to A) — the walk stops and nobody is alerted twice. Test in Task 1.
2. **A manager in another organization** (stale `manager_id`) — never alerted. Test in Task 1.
3. **Saving the same wording** — no revision row and no alerts. Test in Task 2.
4. **The author is also in the editor's chain** — one alert, not two. Test in Task 2.
5. **Drift events where the latest one is "None" after an earlier drift** — counts as on track. Test in Task 3.

---

### Task 1: isLeader and managerChain

**Files:**
- Modify: `app/Services/OrganizationService.php`
- Test: `tests/Feature/LeaderEditsTest.php`

**Interfaces:**
- Produces: `OrganizationService::isLeader(User): bool` (and `canPublish` delegates to it); `OrganizationService::managerChain(User): Collection<int, User>`. Test helpers `world()`, `published()`, `goal()`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/LeaderEditsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\GoalRevision;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Models\WrNotification;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Leader edits (Features spec, phase 5): leaders refine their goals, and the
 * reporting chain up to the strategy's author hears about it.
 */
class LeaderEditsTest extends TestCase
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
     * ceo (owner, author) ← vp (Sales dept head) ← lead (has a report) ← rep.
     * vp, lead and rep hold the Sales role.
     *
     * @return array<string, mixed>
     */
    private function world(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $product = OrgRole::create(['organization_id' => $org->id, 'name' => 'Product']);
        $make = fn (string $email, ?OrgRole $role, ?User $manager) => User::factory()->create([
            'email' => $email, 'user_type' => 'customer', 'organization_id' => $org->id,
            'org_role_id' => $role?->id, 'manager_id' => $manager?->id,
        ]);
        $ceo = $make('ceo@acme.com', null, null);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $vp = $make('vp@acme.com', $sales, $ceo);
        $lead = $make('lead@acme.com', $sales, $vp);
        $rep = $make('rep@acme.com', $sales, $lead);
        Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E', 'head_user_id' => $vp->id]);

        return compact('org', 'sales', 'product', 'ceo', 'vp', 'lead', 'rep');
    }

    private function published(array $w): SearchUserChat
    {
        $chat = SearchUserChat::create(['user_id' => $w['ceo']->id, 'status1' => 0, 'selected_strategy' => 'Upsell', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $w['ceo']->id, 'search' => 'Grow revenue', 'response' => 'ok']);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Old wording', 'org_role_id' => $w['sales']->id, 'starting_options' => ['A', 'B']]);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Product', 'recommended_action' => 'Ship it', 'org_role_id' => $w['product']->id]);
        $chat->forceFill(['status' => 'published', 'published_by' => $w['ceo']->id, 'published_at' => now(), 'organization_id' => $w['org']->id])->save();

        return $chat;
    }

    private function goal(string $role): ExpectedState
    {
        return ExpectedState::where('role', $role)->firstOrFail();
    }

    public function test_leaders_come_from_the_org_chart(): void
    {
        $w = $this->world();
        $orgs = app(OrganizationService::class);

        $this->assertTrue($orgs->isLeader($w['ceo']), 'owner');
        $this->assertTrue($orgs->isLeader($w['vp']), 'department head');
        $this->assertTrue($orgs->isLeader($w['lead']), 'has a report');
        $this->assertFalse($orgs->isLeader($w['rep']), 'no reports');
        $this->assertSame($orgs->isLeader($w['rep']), $orgs->canPublish($w['rep']));
    }

    public function test_the_manager_chain_walks_up_to_the_top(): void
    {
        $w = $this->world();

        $this->assertSame(
            [$w['lead']->id, $w['vp']->id, $w['ceo']->id],
            app(OrganizationService::class)->managerChain($w['rep'])->pluck('id')->map(fn ($id) => (int) $id)->all(),
        );
    }

    public function test_the_manager_chain_stops_at_a_loop_and_at_the_organization_edge(): void
    {
        $w = $this->world();
        $w['ceo']->forceFill(['manager_id' => $w['rep']->id])->save();
        $orgs = app(OrganizationService::class);

        $this->assertSame([$w['lead']->id, $w['vp']->id, $w['ceo']->id], $orgs->managerChain($w['rep']->fresh())->pluck('id')->map(fn ($id) => (int) $id)->all());

        $outsider = User::factory()->create(['email' => 'x@globex.com', 'user_type' => 'customer', 'organization_id' => Organization::create(['domain' => 'globex.com'])->id]);
        $w['ceo']->forceFill(['manager_id' => $outsider->id])->save();
        $this->assertNotContains($outsider->id, $orgs->managerChain($w['rep']->fresh())->pluck('id')->map(fn ($id) => (int) $id)->all());
    }
}
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=LeaderEditsTest`
Expected: FAIL with `Call to undefined method App\Services\OrganizationService::isLeader()`.

- [ ] **Step 3: Implement**

In `app/Services/OrganizationService.php`, add `use Illuminate\Support\Collection;` to the imports, and replace the whole `canPublish` method (docblock included) with:

```php
    /**
     * A leader, by the org chart rather than seniority levels: the owner, a
     * department head, or anyone with a direct report.
     */
    public function isLeader(User $user): bool
    {
        $orgId = $user->organization_id;
        if (! $orgId) {
            return false;
        }

        return Organization::where('id', $orgId)->where('owner_user_id', $user->id)->exists()
            || Department::where('organization_id', $orgId)->where('head_user_id', $user->id)->exists()
            || User::where('organization_id', $orgId)->where('manager_id', $user->id)->exists();
    }

    /** May this user publish a strategy to their organization? Leaders may. */
    public function canPublish(User $user): bool
    {
        return $this->isLeader($user);
    }

    /**
     * The user's manager, that manager's manager, and so on, inside the user's
     * organization. Stops at a loop, at the organization's edge, or after 20.
     *
     * @return Collection<int, User>
     */
    public function managerChain(User $user): Collection
    {
        $chain = collect();
        $seen = [(int) $user->id => true];
        $current = $user;

        for ($step = 0; $step < 20 && $current->manager_id; $step++) {
            $manager = User::where('id', $current->manager_id)->where('organization_id', $user->organization_id)->first();
            if (! $manager || isset($seen[(int) $manager->id])) {
                break;
            }
            $seen[(int) $manager->id] = true;
            $chain->push($manager);
            $current = $manager;
        }

        return $chain;
    }
```

- [ ] **Step 4: Run to verify they pass**

Run: `php artisan test --filter='LeaderEditsTest|PublishGateTest'`
Expected: 38 passed (3 + 35).

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint app/Services/OrganizationService.php tests/Feature/LeaderEditsTest.php
git add app/Services/OrganizationService.php tests/Feature/LeaderEditsTest.php
git commit -m "feat(leaders): isLeader and the manager chain"
```

---

### Task 2: Revising a goal, with history and alerts

**Files:**
- Create: `database/migrations/2026_09_24_000200_create_goal_revisions_table.php`
- Create: `app/Models/GoalRevision.php`
- Create: `app/Services/GoalRevisions.php`
- Modify: `app/Models/ExpectedState.php` (`revisions()`)
- Modify: `app/Http/Controllers/Backend/MyGoalController.php` (`revise`)
- Modify: `routes/backend.php` (after `my-goals.commit`)
- Test: `tests/Feature/LeaderEditsTest.php`

**Interfaces:**
- Consumes: `isLeader`, `managerChain` (Task 1); `MyGoals::visibleGoal`.
- Produces: `GoalRevision` model (fillable `expected_state_id, user_id, old_text, new_text, reason`; `user()`, `goal()`); `ExpectedState::revisions()`; `GoalRevisions::revise(ExpectedState, User, string, ?string): bool` (false when the wording is unchanged); route `my-goals.revise` (POST `goal_id`, `text`, `reason`); the exec detail route name `strategies.show` is used in the alert URL. It is registered in Task 3, so this task builds the path as a literal `dashboard/strategies/{id}`.

- [ ] **Step 1: Write the failing tests**

Append to `LeaderEditsTest`:

```php
    public function test_a_leader_revises_their_goal(): void
    {
        $w = $this->world();
        $chat = $this->published($w);

        $this->actingAs($w['lead'])->from('/dashboard')->post(route('my-goals.revise'), [
            'goal_id' => $this->goal('Sales')->id, 'text' => '  New wording  ', 'reason' => 'Priorities shifted',
        ])->assertRedirect('/dashboard');

        $goal = $this->goal('Sales');
        $this->assertSame('New wording', $goal->recommended_action);
        $this->assertSame($w['lead']->name, $goal->revised_by_name);
        $this->assertSame('Sales', $goal->revised_by_role);
        $this->assertSame('Priorities shifted', $goal->revision_notes);
        $this->assertNull($goal->starting_options);

        $revision = GoalRevision::first();
        $this->assertSame(['Old wording', 'New wording', 'Priorities shifted'], [$revision->old_text, $revision->new_text, $revision->reason]);

        // lead's chain is vp then ceo; ceo is also the author: one alert each, none to lead.
        $this->assertEqualsCanonicalizing([$w['vp']->id, $w['ceo']->id], WrNotification::pluck('user_id')->map(fn ($id) => (int) $id)->all());
        $alert = WrNotification::first();
        $this->assertSame('customer', $alert->user_role);
        $this->assertSame('dashboard/strategies/'.$chat->id, $alert->url);
        $this->assertStringContainsString('New wording', $alert->description);
    }

    public function test_saving_the_same_wording_changes_nothing(): void
    {
        $w = $this->world();
        $this->published($w);

        $this->actingAs($w['lead'])->post(route('my-goals.revise'), ['goal_id' => $this->goal('Sales')->id, 'text' => 'Old wording']);

        $this->assertSame(0, GoalRevision::count());
        $this->assertSame(0, WrNotification::count());
        $this->assertSame(['A', 'B'], $this->goal('Sales')->starting_options);
    }

    public function test_a_non_leader_cannot_revise(): void
    {
        $w = $this->world();
        $this->published($w);

        $this->actingAs($w['rep'])->post(route('my-goals.revise'), ['goal_id' => $this->goal('Sales')->id, 'text' => 'Mine now'])
            ->assertForbidden();
        $this->assertSame('Old wording', $this->goal('Sales')->recommended_action);
    }

    public function test_a_leader_cannot_revise_a_goal_they_cannot_see(): void
    {
        $w = $this->world();
        $this->published($w);

        $this->actingAs($w['lead'])->post(route('my-goals.revise'), ['goal_id' => $this->goal('Product')->id, 'text' => 'Hijacked'])
            ->assertNotFound();
        $this->assertSame('Ship it', $this->goal('Product')->recommended_action);
    }

    public function test_empty_or_long_wording_is_rejected(): void
    {
        $w = $this->world();
        $this->published($w);

        $this->actingAs($w['lead'])->post(route('my-goals.revise'), ['goal_id' => $this->goal('Sales')->id, 'text' => '  '])->assertSessionHasErrors('text');
        $this->actingAs($w['lead'])->post(route('my-goals.revise'), ['goal_id' => $this->goal('Sales')->id, 'text' => str_repeat('x', 501)])->assertSessionHasErrors('text');
        $this->assertSame(0, GoalRevision::count());
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=LeaderEditsTest`
Expected: FAIL. `Class "App\Models\GoalRevision" not found` / `Route [my-goals.revise] not defined.`

- [ ] **Step 3: Migration and model**

Create `database/migrations/2026_09_24_000200_create_goal_revisions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Leader edits (Features spec, phase 5): every change to a goal's wording. */
    public function up(): void
    {
        Schema::create('goal_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('old_text');
            $table->text('new_text');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_revisions');
    }
};
```

Create `app/Models/GoalRevision.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One change a leader made to a goal's wording.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int $user_id
 * @property string $old_text
 * @property string $new_text
 * @property string|null $reason
 */
class GoalRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['expected_state_id', 'user_id', 'old_text', 'new_text', 'reason'];

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
    /** @return HasMany<GoalRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(GoalRevision::class);
    }
```

- [ ] **Step 4: The service**

Create `app/Services/GoalRevisions.php`:

```php
<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Leader edits (Features spec, phase 5): change a goal's wording, keep the
 * history, and tell the reporting chain up to the strategy's author.
 */
class GoalRevisions
{
    public function __construct(protected OrganizationService $orgs) {}

    /** False when the wording did not change: nothing is written or sent. */
    public function revise(ExpectedState $goal, User $leader, string $text, ?string $reason): bool
    {
        $old = (string) $goal->recommended_action;
        $text = trim($text);
        $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
        if ($text === $old) {
            return false;
        }

        DB::transaction(function () use ($goal, $leader, $old, $text, $reason) {
            GoalRevision::create(['expected_state_id' => $goal->id, 'user_id' => $leader->id, 'old_text' => $old, 'new_text' => $text, 'reason' => $reason]);
            // Keep the author's OI Action Table in step, and let the next
            // "Act on it" suggest starting points for the new wording.
            $goal->forceFill([
                'recommended_action' => $text,
                'revised_by_name' => $leader->name,
                'revised_by_role' => $leader->orgRole?->name,
                'revised_at' => now(),
                'revision_notes' => $reason,
                'starting_options' => null,
            ])->save();
        });

        $chat = $goal->searchUserChat;
        $role = $goal->orgRole->name ?? $goal->role;
        $recipients = $this->orgs->managerChain($leader)->pluck('id')
            ->push($chat->user_id)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => $id === (int) $leader->id);

        foreach ($recipients as $userId) {
            // Relative on purpose: the notification controller redirects to '/'.$url.
            saveNotification(
                localize('Goal changed').': '.$role,
                'dashboard/strategies/'.$chat->id,
                'customer',
                $userId,
                null,
                'goal_revision',
                $leader->name.': "'.Str::limit($old, 120).'" → "'.Str::limit($text, 120).'"',
            );
        }

        return true;
    }
}
```

- [ ] **Step 5: Controller and route**

In `MyGoalController`, add `use App\Services\GoalRevisions;` and `use App\Services\OrganizationService;`. Extend the constructor to:

```php
    public function __construct(
        protected MyGoals $goals,
        protected StartingPoints $starts,
        protected GoalRevisions $revisions,
        protected OrganizationService $orgs,
    ) {}
```

Add the action:

```php
    public function revise(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_id' => 'required|integer',
            'text' => 'required|string|max:500',
            'reason' => 'nullable|string|max:500',
        ]);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);
        abort_unless($this->orgs->isLeader($request->user()), 403);

        if ($this->revisions->revise($goal, $request->user(), $data['text'], $data['reason'] ?? null)) {
            flash(localize('The goal has been updated'))->success();
        }

        return back();
    }
```

In `routes/backend.php`, after the line containing `->name('my-goals.commit');`:

```php
                Route::post('/my-goals/revise', [MyGoalController::class, 'revise'])->name('my-goals.revise');
```

- [ ] **Step 6: Run to verify they pass**

Run: `php artisan test --filter=LeaderEditsTest`
Expected: 8 passed.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint database/migrations/2026_09_24_000200_create_goal_revisions_table.php app/Models/GoalRevision.php app/Services/GoalRevisions.php app/Models/ExpectedState.php app/Http/Controllers/Backend/MyGoalController.php routes/backend.php tests/Feature/LeaderEditsTest.php
git add database/migrations/2026_09_24_000200_create_goal_revisions_table.php app/Models/GoalRevision.php app/Services/GoalRevisions.php app/Models/ExpectedState.php app/Http/Controllers/Backend/MyGoalController.php routes/backend.php tests/Feature/LeaderEditsTest.php
git commit -m "feat(leaders): revise a goal with history and alerts up the chain"
```

---

### Task 3: The executive view

**Files:**
- Create: `app/Services/StrategyOverview.php`
- Create: `app/Http/Controllers/Backend/StrategyOverviewController.php`
- Create: `resources/views/backend/pages/strategies/index.blade.php`, `resources/views/backend/pages/strategies/show.blade.php`
- Modify: `routes/backend.php` (import + two routes)
- Test: `tests/Feature/ExecutiveViewTest.php`

**Interfaces:**
- Consumes: `Alignment::forStrategy`, `MyGoals::companyGoal`, `MyGoals::DECISIONS`, `OrganizationService::isLeader`, `GoalRevision`.
- Produces:
  - `StrategyOverview::list(User): Collection` of `{chat, company_goal, alignment (overall), drift (string), obstacles (int), not_viable (int)}`;
  - `StrategyOverview::detail(User, int): ?array` with `{chat, company_goal, alignment, drift, goals: [{goal, role, holders, committed, decisions, drift}], obstacles, revisions}`;
  - routes `strategies.index` and `strategies.show`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ExecutiveViewTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\DriftEvent;
use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Models\GoalRevision;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Executive view (Features spec, phase 5): leaders see every published
 * strategy in their organization, with alignment, drift and obstacles.
 */
class ExecutiveViewTest extends TestCase
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
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $ceo = User::factory()->create(['email' => 'ceo@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id]);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $rep = User::factory()->create(['email' => 'rep@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id, 'org_role_id' => $sales->id, 'manager_id' => $ceo->id]);

        return compact('org', 'sales', 'ceo', 'rep');
    }

    private function strategy(array $w, string $question, string $status = 'published', ?Organization $org = null): SearchUserChat
    {
        $org ??= $w['org'];
        $chat = SearchUserChat::create(['user_id' => $w['ceo']->id, 'status1' => 0, 'selected_strategy' => 'Upsell', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $w['ceo']->id, 'search' => $question, 'response' => 'ok']);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion', 'org_role_id' => $w['sales']->id]);
        $chat->forceFill(['status' => $status, 'published_by' => $w['ceo']->id, 'published_at' => now(), 'organization_id' => $org->id])->save();

        return $chat;
    }

    public function test_a_leader_sees_published_strategies_in_their_organization_only(): void
    {
        $w = $this->world();
        $this->strategy($w, 'Grow revenue 30%');
        $this->strategy($w, 'Secret draft', 'draft');
        $this->strategy($w, 'Their plan', 'published', Organization::create(['domain' => 'globex.com', 'name' => 'Globex']));

        $this->actingAs($w['ceo'])->get(route('strategies.index'))
            ->assertOk()
            ->assertSee('Grow revenue 30%')
            ->assertDontSee('Secret draft')
            ->assertDontSee('Their plan')
            ->assertSee('Not measured yet')
            ->assertSee('0 of 1');
    }

    public function test_non_leaders_are_refused(): void
    {
        $w = $this->world();
        $chat = $this->strategy($w, 'Grow revenue 30%');

        $this->actingAs($w['rep'])->get(route('strategies.index'))->assertForbidden();
        $this->actingAs($w['rep'])->get(route('strategies.show', $chat->id))->assertForbidden();
    }

    public function test_drafts_and_other_organizations_are_not_found(): void
    {
        $w = $this->world();
        $draft = $this->strategy($w, 'Secret draft', 'draft');
        $foreign = $this->strategy($w, 'Their plan', 'published', Organization::create(['domain' => 'globex.com', 'name' => 'Globex']));

        $this->actingAs($w['ceo'])->get(route('strategies.show', $draft->id))->assertNotFound();
        $this->actingAs($w['ceo'])->get(route('strategies.show', $foreign->id))->assertNotFound();
    }

    public function test_the_detail_page_shows_decisions_obstacles_and_revisions(): void
    {
        $w = $this->world();
        $chat = $this->strategy($w, 'Grow revenue 30%');
        $goal = ExpectedState::first();
        GoalResponse::create(['expected_state_id' => $goal->id, 'user_id' => $w['rep']->id, 'decision' => 'act_on_it', 'starting_point' => 'Book a sync']);
        GoalObstacle::create(['expected_state_id' => $goal->id, 'user_id' => $w['rep']->id, 'body' => 'CRM keeps timing out']);
        GoalRevision::create(['expected_state_id' => $goal->id, 'user_id' => $w['ceo']->id, 'old_text' => 'Old', 'new_text' => 'Launch the upgrade motion', 'reason' => 'Clarity']);

        $this->actingAs($w['ceo'])->get(route('strategies.show', $chat->id))
            ->assertOk()
            ->assertSee('Grow revenue 30%')
            ->assertSee('Launch the upgrade motion')
            ->assertSee('1 of 1')
            ->assertSee('CRM keeps timing out')
            ->assertSee('Clarity');
    }

    public function test_drift_status_uses_each_goals_latest_event(): void
    {
        $w = $this->world();
        $this->strategy($w, 'Grow revenue 30%');
        $goal = ExpectedState::first();

        DriftEvent::create(['expected_state_id' => $goal->id, 'drift_type' => 'Timeline Drift', 'detected_at' => now()->subDay()]);
        $this->actingAs($w['ceo'])->get(route('strategies.index'))->assertSee('Drift on 1 goal');

        DriftEvent::create(['expected_state_id' => $goal->id, 'drift_type' => 'None', 'detected_at' => now()]);
        $this->actingAs($w['ceo'])->get(route('strategies.index'))->assertSee('On track');
    }
}
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=ExecutiveViewTest`
Expected: FAIL with `Route [strategies.index] not defined.`

- [ ] **Step 3: The service**

Create `app/Services/StrategyOverview.php`:

```php
<?php

namespace App\Services;

use App\Models\DriftEvent;
use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Models\GoalRevision;
use App\Models\SearchUserChat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Executive view (Features spec, phase 5): how every published strategy in the
 * viewer's organization is going.
 */
class StrategyOverview
{
    public function __construct(protected MyGoals $goals, protected Alignment $alignment) {}

    /** @return Collection<int, array<string, mixed>> */
    public function list(User $viewer): Collection
    {
        if (! $viewer->organization_id) {
            return collect();
        }

        // ponytail: a few queries per strategy; batch them if an organization
        // publishes dozens.
        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $this->published($viewer)->with('publisher:id,name')->orderByDesc('published_at')->get()
            ->map(function (SearchUserChat $chat) {
                $ids = ExpectedState::where('search_user_chat_id', $chat->id)->pluck('id');

                return [
                    'chat' => $chat,
                    'company_goal' => $this->goals->companyGoal($chat),
                    'alignment' => $this->alignment->forStrategy($chat)['overall'],
                    'drift' => $this->drift($ids),
                    'obstacles' => GoalObstacle::whereIn('expected_state_id', $ids)->count(),
                    'not_viable' => GoalResponse::whereIn('expected_state_id', $ids)->where('decision', 'not_viable')->count(),
                ];
            });

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function detail(User $viewer, int $chatId): ?array
    {
        if (! $viewer->organization_id) {
            return null;
        }
        $chat = $this->published($viewer)->with('publisher:id,name')->whereKey($chatId)->first();
        if (! $chat) {
            return null;
        }

        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->with('orgRole:id,name')->orderBy('id')->get();
        $ids = $goals->pluck('id');
        $responses = GoalResponse::whereIn('expected_state_id', $ids)->get()->groupBy('expected_state_id');
        $holders = User::where('organization_id', $chat->organization_id)->whereNotNull('org_role_id')
            ->selectRaw('org_role_id, count(*) as n')->groupBy('org_role_id')->pluck('n', 'org_role_id');
        $latest = $this->latestDrift($ids);

        return [
            'chat' => $chat,
            'company_goal' => $this->goals->companyGoal($chat),
            'alignment' => $this->alignment->forStrategy($chat),
            'drift' => $this->drift($ids),
            'goals' => $goals->map(function (ExpectedState $goal) use ($responses, $holders, $latest) {
                $mine = $responses->get($goal->id, collect());

                return [
                    'goal' => $goal,
                    'role' => $goal->orgRole->name ?? $goal->role,
                    'holders' => (int) ($holders[(int) $goal->org_role_id] ?? 0),
                    'committed' => $mine->whereNotNull('starting_point')->count(),
                    'decisions' => collect(MyGoals::DECISIONS)->mapWithKeys(fn (string $d) => [$d => $mine->where('decision', $d)->count()])->all(),
                    'drift' => $latest->get($goal->id),
                ];
            }),
            'obstacles' => GoalObstacle::whereIn('expected_state_id', $ids)->with(['user:id,name', 'goal.orgRole:id,name'])->orderByDesc('id')->get(),
            'revisions' => GoalRevision::whereIn('expected_state_id', $ids)->with(['user:id,name', 'goal.orgRole:id,name'])->orderByDesc('id')->get(),
        ];
    }

    /** @return Builder<SearchUserChat> */
    private function published(User $viewer): Builder
    {
        return SearchUserChat::where('status', 'published')->where('organization_id', (int) $viewer->organization_id);
    }

    /**
     * Each goal's most recent drift type, as the OI engine last recorded it.
     *
     * @param  Collection<int, mixed>  $goalIds
     * @return Collection<int, string>
     */
    private function latestDrift(Collection $goalIds): Collection
    {
        return DriftEvent::whereIn('expected_state_id', $goalIds)->orderBy('id')->get(['expected_state_id', 'drift_type'])
            ->keyBy(fn (DriftEvent $e) => (int) $e->expected_state_id)
            ->map(fn (DriftEvent $e) => (string) $e->drift_type);
    }

    /** @param  Collection<int, mixed>  $goalIds */
    private function drift(Collection $goalIds): string
    {
        $latest = $this->latestDrift($goalIds);
        if ($latest->isEmpty()) {
            return 'Not measured yet';
        }
        $drifting = $latest->reject(fn (string $type) => $type === '' || $type === 'None')->count();

        return $drifting === 0 ? 'On track' : 'Drift on '.$drifting.' '.Str::plural('goal', $drifting);
    }
}
```

- [ ] **Step 4: Controller**

Create `app/Http/Controllers/Backend/StrategyOverviewController.php`:

```php
<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\OrganizationService;
use App\Services\StrategyOverview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Executive view (Features spec, phase 5), for leaders only. */
class StrategyOverviewController extends Controller
{
    public function __construct(protected StrategyOverview $overview, protected OrganizationService $orgs) {}

    public function index(Request $request): View
    {
        abort_unless($this->orgs->isLeader($request->user()), 403);

        return view('backend.pages.strategies.index', ['strategies' => $this->overview->list($request->user())]);
    }

    public function show(Request $request, $chat): View
    {
        abort_unless($this->orgs->isLeader($request->user()), 403);
        $detail = $this->overview->detail($request->user(), (int) $chat);
        abort_unless($detail, 404);

        return view('backend.pages.strategies.show', $detail);
    }
}
```

- [ ] **Step 5: Views**

Create `resources/views/backend/pages/strategies/index.blade.php`:

```blade
@extends('backend.layouts.master')

@section('title')
    {{ localize('Executive view') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <h4 class="mb-1">{{ localize('Executive view') }}</h4>
            <p class="text-muted small">{{ localize('Every published strategy in your organization.') }}</p>
            <div class="card">
                <div class="card-body">
                    @if ($strategies->isEmpty())
                        <p class="text-muted mb-0">{{ localize('No strategy has been published yet.') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ localize('Company goal') }}</th>
                                        <th>{{ localize('Published') }}</th>
                                        <th>{{ localize('Committed') }}</th>
                                        <th>{{ localize('Drift') }}</th>
                                        <th>{{ localize('Obstacles') }}</th>
                                        <th>{{ localize('Not viable') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($strategies as $row)
                                        <tr>
                                            <td>
                                                <a href="{{ route('strategies.show', $row['chat']->id) }}" style="color:#2c6d82">{{ $row['company_goal'] }}</a>
                                                <div class="small text-muted">{{ $row['chat']->selected_strategy }}</div>
                                            </td>
                                            <td class="small">{{ $row['chat']->publisher?->name }}<br>{{ optional($row['chat']->published_at)->toFormattedDateString() }}</td>
                                            <td>{{ $row['alignment']['committed'] }} of {{ $row['alignment']['people'] }}
                                                @if ($row['alignment']['rate'] !== null)<span class="text-muted small">({{ $row['alignment']['rate'] }}%)</span>@endif
                                            </td>
                                            <td>{{ $row['drift'] }}</td>
                                            <td>{{ $row['obstacles'] }}</td>
                                            <td>{{ $row['not_viable'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
```

Create `resources/views/backend/pages/strategies/show.blade.php`:

```blade
@extends('backend.layouts.master')

@section('title')
    {{ localize('Executive view') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    @php
        $decisionLabels = ['act_on_it' => localize('Act on it'), 'review_in_detail' => localize('Review'), 'not_viable' => localize('Not viable')];
    @endphp
    <section class="tt-section pt-4">
        <div class="container">
            <a href="{{ route('strategies.index') }}" class="small" style="color:#2c6d82">&larr; {{ localize('Executive view') }}</a>
            <h4 class="mt-2 mb-1">{{ $company_goal }}</h4>
            <p class="text-muted small">
                {{ $chat->selected_strategy }} · {{ localize('Published by') }} {{ $chat->publisher?->name }}
                {{ optional($chat->published_at)->toFormattedDateString() }} · {{ localize('Drift') }}: {{ $drift }}
            </p>

            <div class="card mb-3"><div class="card-body">
                <h6>{{ localize('Alignment') }}: {{ $alignment['overall']['committed'] }} of {{ $alignment['overall']['people'] }}
                    @if ($alignment['overall']['rate'] !== null)({{ $alignment['overall']['rate'] }}%)@endif</h6>
                @if (! empty($alignment['departments']))
                    <table class="table table-sm mb-0">
                        <thead><tr><th>{{ localize('Department') }}</th><th>{{ localize('Committed') }}</th><th>{{ localize('Rate') }}</th></tr></thead>
                        <tbody>
                            @foreach ($alignment['departments'] as $dept)
                                <tr><td>{{ $dept['name'] }}</td><td>{{ $dept['committed'] }} / {{ $dept['people'] }}</td><td>{{ $dept['rate'] === null ? '—' : $dept['rate'].'%' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h6>{{ localize('Goals') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>{{ localize('Role') }}</th><th>{{ localize('Goal') }}</th><th>{{ localize('People') }}</th><th>{{ localize('Committed') }}</th><th>{{ localize('Responses') }}</th><th>{{ localize('Drift') }}</th></tr></thead>
                        <tbody>
                            @foreach ($goals as $row)
                                <tr>
                                    <td>{{ $row['role'] }}</td>
                                    <td>{{ $row['goal']->recommended_action }}</td>
                                    <td>{{ $row['holders'] }}</td>
                                    <td>{{ $row['committed'] }}</td>
                                    <td class="small">
                                        @foreach ($row['decisions'] as $decision => $count)
                                            {{ $decisionLabels[$decision] }}: {{ $count }}@if (! $loop->last), @endif
                                        @endforeach
                                    </td>
                                    <td>{{ $row['drift'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h6>{{ localize('Reported obstacles') }}</h6>
                @forelse ($obstacles as $obstacle)
                    <div class="small mb-1" style="border-left:3px solid #ec883f;padding-left:8px">
                        {{ $obstacle->body }}
                        <span class="text-muted">— {{ $obstacle->user?->name }}, {{ $obstacle->goal->orgRole->name ?? $obstacle->goal->role }}, {{ optional($obstacle->created_at)->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">{{ localize('Nothing reported.') }}</p>
                @endforelse
            </div></div>

            <div class="card mb-4"><div class="card-body">
                <h6>{{ localize('Goal changes') }}</h6>
                @forelse ($revisions as $revision)
                    <div class="small mb-2">
                        <strong>{{ $revision->goal->orgRole->name ?? $revision->goal->role }}</strong>:
                        "{{ $revision->old_text }}" &rarr; "{{ $revision->new_text }}"
                        <span class="text-muted">— {{ $revision->user?->name }}, {{ optional($revision->created_at)->diffForHumans() }}</span>
                        @if ($revision->reason)<div class="text-muted">{{ $revision->reason }}</div>@endif
                    </div>
                @empty
                    <p class="text-muted small mb-0">{{ localize('No changes yet.') }}</p>
                @endforelse
            </div></div>
        </div>
    </section>
@endsection
```

- [ ] **Step 6: Routes**

In `routes/backend.php`, add `use App\Http\Controllers\Backend\StrategyOverviewController;` after `use App\Http\Controllers\Backend\MyGoalController;`. After the line containing `->name('my-goals.revise');`, add:

```php
                // Executive view: every published strategy in the organization, leaders only
                Route::get('/strategies', [StrategyOverviewController::class, 'index'])->name('strategies.index');
                Route::get('/strategies/{chat}', [StrategyOverviewController::class, 'show'])->name('strategies.show');
```

- [ ] **Step 7: Run to verify they pass**

Run: `php artisan test --filter=ExecutiveViewTest`
Expected: 5 passed.

- [ ] **Step 8: Commit**

```bash
vendor/bin/pint app/Services/StrategyOverview.php app/Http/Controllers/Backend/StrategyOverviewController.php routes/backend.php tests/Feature/ExecutiveViewTest.php
git add app/Services/StrategyOverview.php app/Http/Controllers/Backend/StrategyOverviewController.php resources/views/backend/pages/strategies routes/backend.php tests/Feature/ExecutiveViewTest.php
git commit -m "feat(leaders): executive view of published strategies"
```

---

### Task 4: Revise form, links, and the "before this goal changed" marker

**Files:**
- Modify: `app/Http/Controllers/Backend/DashboardController.php` (pass `isLeader`)
- Modify: `resources/views/backend/pages/goals/my-goals.blade.php`
- Modify: `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php` (link to the exec page once published)
- Modify: `app/Services/MyGoals.php` (card key `last_revised_at`)
- Test: `tests/Feature/LeaderEditsTest.php`

**Interfaces:**
- Consumes: routes `my-goals.revise`, `strategies.index`, `strategies.show`; `ExpectedState::revisions()`.
- Produces: dashboard variable `isLeader`; card key `last_revised_at` (Carbon|null).

- [ ] **Step 1: Write the failing tests**

Append to `LeaderEditsTest`:

```php
    public function test_leaders_see_the_revise_form_and_executive_view_link(): void
    {
        $w = $this->world();
        $this->published($w);

        $this->actingAs($w['lead'])->get('/dashboard')
            ->assertOk()
            ->assertSee(route('my-goals.revise'), false)
            ->assertSee(route('strategies.index'), false);

        $this->actingAs($w['rep'])->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('my-goals.revise'), false)
            ->assertDontSee(route('strategies.index'), false);
    }

    public function test_a_commitment_made_before_a_revision_is_marked(): void
    {
        $w = $this->world();
        $this->published($w);
        \App\Models\GoalResponse::create(['expected_state_id' => $this->goal('Sales')->id, 'user_id' => $w['rep']->id, 'decision' => 'act_on_it', 'starting_point' => 'A', 'committed_at' => now()->subDay()]);

        $this->actingAs($w['lead'])->post(route('my-goals.revise'), ['goal_id' => $this->goal('Sales')->id, 'text' => 'New wording']);

        $this->actingAs($w['rep'])->get('/dashboard')
            ->assertOk()
            ->assertSee('New wording')
            ->assertSee('made before this goal changed');
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=LeaderEditsTest`
Expected: both new tests fail on the missing revise URL and the missing marker text.

- [ ] **Step 3: Controller data and card key**

In `DashboardController::index`, after the `'myGoals' => ...` entry, add:

```php
            'isLeader' => app(\App\Services\OrganizationService::class)->isLeader($user),
```

In `MyGoals::for()`, add `'revisions'` to the `with([...])` list, and add this key to each card array after `'obstacles' => ...`:

```php
            'last_revised_at' => $g->revisions->max('created_at'),
```

- [ ] **Step 4: The card**

In `my-goals.blade.php`:

1. Replace `<h5 class="mb-1">{{ localize('Your goals') }}</h5>` with:

```blade
        <div class="d-flex justify-content-between align-items-baseline">
            <h5 class="mb-1">{{ localize('Your goals') }}</h5>
            @if ($isLeader ?? false)
                <a href="{{ route('strategies.index') }}" class="small" style="color:#2c6d82">{{ localize('Executive view') }} &rarr;</a>
            @endif
        </div>
```

2. Replace
```blade
                                <div class="small mt-1">{{ localize('Committed') }}: <strong>{{ $card['response']->starting_point }}</strong></div>
```
with
```blade
                                <div class="small mt-1">{{ localize('Committed') }}: <strong>{{ $card['response']->starting_point }}</strong>
                                    @if ($card['last_revised_at'] && $card['response']->committed_at && $card['response']->committed_at->lt($card['last_revised_at']))
                                        <span class="text-muted">({{ localize('made before this goal changed') }})</span>
                                    @endif
                                </div>
```

3. Directly before the obstacle form (`<form method="POST" action="{{ route('my-goals.obstacle') }}"`), insert:

```blade
                    @if ($isLeader ?? false)
                        <details class="mt-2">
                            <summary class="small" style="color:#2c6d82;cursor:pointer">{{ localize('Refine this goal') }}</summary>
                            <form method="POST" action="{{ route('my-goals.revise') }}" class="mt-2">
                                @csrf
                                <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                                <textarea name="text" rows="2" maxlength="500" required class="form-control form-control-sm mb-1">{{ $card['goal']->recommended_action }}</textarea>
                                <input type="text" name="reason" maxlength="500" class="form-control form-control-sm mb-1" placeholder="{{ localize('Why? (optional)') }}">
                                <button type="submit" class="btn btn-sm mg-btn">{{ localize('Save and notify') }}</button>
                            </form>
                        </details>
                    @endif
```

This task also shows the commitment note for the case where the goal still has options. Commitments made when the goal had none are not shown today; that is out of scope.

- [ ] **Step 5: Link from the Publish card**

In `publish-gate.blade.php`, in `render()`, change the published header string so that, after the "Published by …" sentence, it appends:

```js
 <a href="{{ url('dashboard/strategies') }}/${chatId}" style="color:#2c6d82">Open the executive view &rarr;</a>
```

That means: inside the `published ? \`<div class="pg-published">…</div>\`` template literal, insert the anchor directly before the closing `</div>`.

- [ ] **Step 6: Run to verify they pass**

Run: `php artisan test --filter=LeaderEditsTest`
Expected: 10 passed.

Run: `php artisan test`
Expected: the full suite passes.

- [ ] **Step 7: CI gates**

Run Pint on changed PHP files against `feat/where-to-begin`, PHPStan, and `node --check` on the publish card's extracted script.
Expected: all clean.

- [ ] **Step 8: Commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/DashboardController.php app/Services/MyGoals.php tests/Feature/LeaderEditsTest.php
git add app/Http/Controllers/Backend/DashboardController.php app/Services/MyGoals.php resources/views/backend/pages/goals/my-goals.blade.php resources/views/backend/pages/aiChat/inc/publish-gate.blade.php tests/Feature/LeaderEditsTest.php
git commit -m "feat(leaders): refine form, executive view links, and stale-commitment marker"
```
