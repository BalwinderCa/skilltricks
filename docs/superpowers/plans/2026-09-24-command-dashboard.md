# Command Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Progress updates and bottleneck flags on goals, Notion's drift index with nudges, red alerts and AI recourse, pace-based projections, and the command tiles (teams involved, savings, drift) with deliverables and the upstream-blocker alert. Alerts go out in-app and by email.

**Architecture:**
- **Services:**
  - `ProgressUpdates` records progress.
  - `DriftIndex` does the per-goal and per-strategy math, persists it, and fires alerts.
  - `StrategyAlerts` sends each alert in-app and by email.
  - `Recourse` asks the AI for recourse options.
  - `StrategyOverview` gains the command metrics.
- **Controllers:** `MyGoalController::progress`, plus `StrategyOverviewController::recourse` and `::settings`.
- **Scheduling:** a daily Artisan command, `strategies:evaluate-drift`.

**Tech Stack:** Laravel 12, Blade, PHPUnit on SQLite, Carbon time travel, `Mail::fake`.

**Spec:** `docs/superpowers/specs/2026-09-24-command-dashboard-design.md`

## Global Constraints

- Drift constants: grace 10% of the window, yellow ≥ 5, red > 15. Levels are `green`, `yellow`, `red`, or null when nothing is measured.
- An alert fires once per level change. It is claimed with a conditional update, so concurrent evaluations cannot double-send.
- The recourse prompt uses `orgContextBlock` only, never `buildSystemMessage($user)`, so no private documents reach it.
- Every figure the page shows as an estimate says "estimate" or "at current pace".
- Notification URLs are relative.

## Review Focus

1. **Evaluating a draft strategy** — nothing is persisted and no alert is sent. Test in Task 2.
2. **A goal whose target date is before `published_at`** — not measured, and no division by zero. Test in Task 2.
3. **The same level on two evaluations in a row** — exactly one alert. Test in Task 3.
4. **A person without an email address** — the in-app alert is still sent, with no exception. Test in Task 3.
5. **Owner settings with negative or absurd values** — refused. Test in Task 5.

---

### Task 1: Progress updates

**Files:** migration `database/migrations/2026_09_24_000300_add_command_dashboard.php` (create); `app/Models/GoalProgressUpdate.php` (create); `app/Models/GoalResponse.php`, `app/Models/SearchUserChat.php`, `app/Models/Organization.php` (modify); `app/Services/ProgressUpdates.php`, `app/Services/DriftIndex.php` (create; `evaluate` is a stub returning `[]` until Task 2); `app/Services/StrategyAlerts.php` (create); `app/Http/Controllers/Backend/MyGoalController.php`, `routes/backend.php`, `resources/views/backend/pages/goals/my-goals.blade.php` (modify); test `tests/Feature/CommandDashboardTest.php`.

**Interfaces — Produces:**
- `ProgressUpdates::STATUSES` and `ProgressUpdates::record(ExpectedState, User, string $status, int $pct, ?string $note): void`;
- route `my-goals.progress`;
- test helpers `world()`, `goal(string)`, `progress(User, string, string $status, int $pct, ?string $note = null)`.

- [ ] **Step 1: Failing tests** — create `tests/Feature/CommandDashboardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Mail\EmailManager;
use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\GoalProgressUpdate;
use App\Models\GoalResponse;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Models\WrNotification;
use App\Services\AI\AiProviderService;
use App\Services\DriftIndex;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * Executive command dashboard (Features spec, phase 6 / Notion Epic 4):
 * progress telemetry, the drift index and its alerts, projections, savings.
 */
class CommandDashboardTest extends TestCase
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
     * Acme: ceo (owner, author), rep (Sales, Sales dept), pm (Product).
     * One strategy published 10 days ago. Sales: target in 10 days (so the
     * baseline is 50% today), target value 25, depends on Product. Product:
     * no target date.
     *
     * @return array<string, mixed>
     */
    private function world(bool $publish = true): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $product = OrgRole::create(['organization_id' => $org->id, 'name' => 'Product']);
        $dept = Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E']);
        $ceo = User::factory()->create(['email' => 'ceo@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id]);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $rep = User::factory()->create(['email' => 'rep@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id, 'org_role_id' => $sales->id, 'department_id' => $dept->id, 'manager_id' => $ceo->id]);
        $pm = User::factory()->create(['email' => 'pm@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id, 'org_role_id' => $product->id, 'manager_id' => $ceo->id]);

        $chat = SearchUserChat::create(['user_id' => $ceo->id, 'status1' => 0, 'selected_strategy' => 'Upsell', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $ceo->id, 'search' => 'Grow revenue 30%', 'response' => 'ok']);
        $productGoal = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Product', 'recommended_action' => 'Ship SOC2', 'org_role_id' => $product->id]);
        ExpectedState::create([
            'search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion',
            'org_role_id' => $sales->id, 'target_value' => '25', 'target_date' => now()->addDays(10)->toDateString(),
            'depends_on_id' => $productGoal->id,
        ]);
        if ($publish) {
            $chat->forceFill(['status' => 'published', 'published_by' => $ceo->id, 'published_at' => now()->subDays(10)->startOfDay(), 'organization_id' => $org->id])->save();
        }

        return compact('org', 'sales', 'product', 'dept', 'ceo', 'rep', 'pm', 'chat');
    }

    private function goal(string $role): ExpectedState
    {
        return ExpectedState::where('role', $role)->firstOrFail();
    }

    private function progress(User $user, string $role, string $status, int $pct, ?string $note = null)
    {
        return $this->actingAs($user)->post(route('my-goals.progress'), ['goal_id' => $this->goal($role)->id, 'status' => $status, 'pct' => $pct, 'note' => $note]);
    }

    public function test_a_holder_posts_progress_and_it_is_kept_with_history(): void
    {
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'in_progress', 30, 'Drafting the contract')->assertRedirect();
        $this->progress($w['rep'], 'Sales', 'in_progress', 40);

        $response = GoalResponse::first();
        $this->assertSame(['in_progress', 40, null], [$response->progress_status, $response->progress_pct, $response->progress_note]);
        $this->assertSame('act_on_it', $response->decision);
        $this->assertSame(2, GoalProgressUpdate::count());
        $this->assertSame('Drafting the contract', GoalProgressUpdate::orderBy('id')->first()->note);
    }

    public function test_completed_counts_as_one_hundred_percent(): void
    {
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'completed', 20);

        $this->assertSame(100, GoalResponse::first()->progress_pct);
    }

    public function test_bad_progress_input_is_refused(): void
    {
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'done-ish', 10)->assertSessionHasErrors('status');
        $this->progress($w['rep'], 'Sales', 'in_progress', 101)->assertSessionHasErrors('pct');
        $this->progress($w['pm'], 'Sales', 'in_progress', 10)->assertNotFound();
        $this->assertSame(0, GoalProgressUpdate::count());
    }

    public function test_the_card_offers_a_status_update_after_act_on_it(): void
    {
        $w = $this->world();
        GoalResponse::create(['expected_state_id' => $this->goal('Sales')->id, 'user_id' => $w['rep']->id, 'decision' => 'act_on_it']);

        $this->actingAs($w['rep'])->get('/dashboard')
            ->assertOk()
            ->assertSee(route('my-goals.progress'), false)
            ->assertSee('Blocked — flag a bottleneck');
    }
}
```

- [ ] **Step 2: Run** — `php artisan test --filter=CommandDashboardTest` → FAIL (`Class "App\Models\GoalProgressUpdate" not found`).

- [ ] **Step 3: Migration** — create `database/migrations/2026_09_24_000300_add_command_dashboard.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executive command dashboard (Features spec, phase 6): progress telemetry,
     * the drift index, cached recourse, and the owner's savings assumptions.
     */
    public function up(): void
    {
        Schema::table('goal_responses', function (Blueprint $table) {
            $table->string('progress_status', 20)->nullable();
            $table->unsignedTinyInteger('progress_pct')->nullable();
            $table->text('progress_note')->nullable();
            $table->timestamp('progress_at')->nullable();
        });

        Schema::create('goal_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('status', 20);
            $table->unsignedTinyInteger('pct');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->decimal('drift_index', 6, 2)->nullable();
            $table->string('drift_level', 10)->nullable();
            $table->string('drift_alerted_level', 10)->nullable();
            $table->timestamp('drift_checked_at')->nullable();
            $table->json('recourse')->nullable();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->json('command_settings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', fn (Blueprint $table) => $table->dropColumn('command_settings'));
        Schema::table('search_user_chat', fn (Blueprint $table) => $table->dropColumn(['drift_index', 'drift_level', 'drift_alerted_level', 'drift_checked_at', 'recourse']));
        Schema::dropIfExists('goal_progress_updates');
        Schema::table('goal_responses', fn (Blueprint $table) => $table->dropColumn(['progress_status', 'progress_pct', 'progress_note', 'progress_at']));
    }
};
```

- [ ] **Step 4: Models**

`app/Models/GoalProgressUpdate.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One progress report on a goal: status, percent, note.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int $user_id
 * @property string $status
 * @property int $pct
 * @property string|null $note
 */
class GoalProgressUpdate extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['expected_state_id', 'user_id', 'status', 'pct', 'note'];

    protected $casts = ['pct' => 'integer'];

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

- `GoalResponse`: append `'progress_status', 'progress_pct', 'progress_note', 'progress_at'` to `$fillable`, and add `'progress_pct' => 'integer', 'progress_at' => 'datetime'` to `$casts`.
- `SearchUserChat`: append `'drift_index', 'drift_level', 'drift_alerted_level', 'drift_checked_at', 'recourse'` to `$fillable`, and add `'drift_checked_at' => 'datetime', 'recourse' => 'array'` to `$casts`.
- `Organization`: append `'command_settings'` to `$fillable`, and add `protected $casts = ['command_settings' => 'array'];` after `$fillable`.

- [ ] **Step 5: Services**

`app/Services/StrategyAlerts.php`:

```php
<?php

namespace App\Services;

use App\Mail\EmailManager;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Strategy alerts go out in-app (the navbar bell) and by email. The email is
 * best-effort: a failure is reported and never blocks the action behind it.
 */
class StrategyAlerts
{
    /** $url is relative: the notification controller redirects to '/'.$url. */
    public function send(User $to, string $title, string $url, string $body, string $type): void
    {
        saveNotification($title, $url, 'customer', (int) $to->id, null, $type, $body);

        if (! $to->email) {
            return;
        }
        try {
            Mail::to($to->email)->queue(new EmailManager([
                'view' => 'emails.strategy-alert',
                'from' => config('custom.mail_from_address'),
                'subject' => $title,
                'title' => $title,
                'body' => $body,
                'link' => url($url),
            ]));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
```

`app/Services/ProgressUpdates.php`:

```php
<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalProgressUpdate;
use App\Models\GoalResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Execution telemetry (Features spec, phase 6): what the people holding a goal
 * report about it. Every report is kept; the latest sits on their response.
 */
class ProgressUpdates
{
    public const STATUSES = ['not_started', 'in_progress', 'completed', 'blocked'];

    public function __construct(protected DriftIndex $drift) {}

    public function record(ExpectedState $goal, User $member, string $status, int $pct, ?string $note): void
    {
        $pct = $status === 'completed' ? 100 : max(0, min(100, $pct));
        $note = $note !== null && trim($note) !== '' ? trim($note) : null;

        DB::transaction(function () use ($goal, $member, $status, $pct, $note) {
            GoalProgressUpdate::create(['expected_state_id' => $goal->id, 'user_id' => $member->id, 'status' => $status, 'pct' => $pct, 'note' => $note]);
            $response = GoalResponse::firstOrNew(['expected_state_id' => $goal->id, 'user_id' => $member->id]);
            $response->fill(['progress_status' => $status, 'progress_pct' => $pct, 'progress_note' => $note, 'progress_at' => now()]);
            if ($response->decision === null) {
                $response->decision = 'act_on_it';
                $response->decided_at = now();
            }
            $response->save();
        });

        $this->drift->evaluate($goal->searchUserChat);
    }
}
```

`app/Services/DriftIndex.php` (a stub for now; Task 2 replaces the body):

```php
<?php

namespace App\Services;

use App\Models\SearchUserChat;

class DriftIndex
{
    /** @return array<string, mixed> */
    public function evaluate(SearchUserChat $chat, bool $alert = true): array
    {
        return [];
    }
}
```

- [ ] **Step 6: Controller, route, card**

In `MyGoalController`, add `use App\Services\ProgressUpdates;`, and add `protected ProgressUpdates $progressUpdates,` as the last constructor parameter. Then add:

```php
    public function progress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_id' => 'required|integer',
            'status' => ['required', Rule::in(ProgressUpdates::STATUSES)],
            'pct' => 'required|integer|min:0|max:100',
            'note' => 'nullable|string|max:500',
        ]);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);

        $this->progressUpdates->record($goal, $request->user(), $data['status'], (int) $data['pct'], $data['note'] ?? null);
        flash(localize('Your update has been posted'))->success();

        return back();
    }
```

Route, after `->name('my-goals.revise');`:

```php
                Route::post('/my-goals/progress', [MyGoalController::class, 'progress'])->name('my-goals.progress');
```

In `my-goals.blade.php`, add `$statusLabels` to the top `@php` block:

```php
    $statusLabels = [
        'not_started' => localize('Not started'),
        'in_progress' => localize('In progress'),
        'completed' => localize('Completed'),
        'blocked' => localize('Blocked — flag a bottleneck'),
    ];
```

Then insert this directly before the `@endif` that closes the `@if ($card['response']?->decision === 'act_on_it')` block (right after the Committed line's `@endif`):

```blade
                        <form method="POST" action="{{ route('my-goals.progress') }}" class="row g-2 align-items-center mt-2">
                            @csrf
                            <input type="hidden" name="goal_id" value="{{ $card['goal']->id }}">
                            <div class="col-md-3">
                                <select name="status" class="form-select form-select-sm">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(($card['response']->progress_status ?? 'in_progress') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="pct" min="0" max="100" required class="form-control form-control-sm"
                                    value="{{ $card['response']->progress_pct ?? 0 }}" aria-label="{{ localize('Progress %') }}">
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="note" maxlength="500" class="form-control form-control-sm" placeholder="{{ localize('What changed?') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm mg-btn w-100">{{ localize('Update status') }}</button>
                            </div>
                        </form>
                        @if ($card['response']->progress_at)
                            <div class="small text-muted mt-1">
                                {{ $statusLabels[$card['response']->progress_status] ?? '' }} · {{ $card['response']->progress_pct }}%
                                @if ($card['response']->progress_note) · {{ $card['response']->progress_note }}@endif
                                · {{ $card['response']->progress_at->diffForHumans() }}
                            </div>
                        @endif
```

- [ ] **Step 7: Run** — `php artisan test --filter=CommandDashboardTest` → 4 passed.

- [ ] **Step 8: Commit** — run Pint on the touched PHP files, then commit with `feat(command): progress updates and bottleneck flags on goals`.

---

### Task 2: The drift index

**Files:** `app/Services/DriftIndex.php` (replace); test.

**Interfaces — Produces:**
- `DriftIndex::goalMetrics(ExpectedState, SearchUserChat, Collection $responses): array{expected:?float, observed:float, drift:?float, measured:bool, projected_completion:?Carbon, projected_value:?float, days_behind:?int}`;
- `DriftIndex::evaluate(SearchUserChat, bool $alert = true): array{index:?float, level:?string, worst:?ExpectedState, projected:?Carbon, goals: Collection}`;
- `DriftIndex::levelFor(?float): ?string`.

- [ ] **Step 1: Failing tests** — append:

```php
    public function test_drift_follows_the_notion_formula_and_thresholds(): void
    {
        $w = $this->world();
        $drift = app(DriftIndex::class);

        // Baseline 50%. Observed 30 → (50−30)/50 = 40% → red.
        $this->progress($w['rep'], 'Sales', 'in_progress', 30);
        $state = $drift->evaluate($w['chat']->fresh(), alert: false);
        $this->assertEqualsWithDelta(40.0, $state['index'], 0.5);
        $this->assertSame('red', $state['level']);

        $this->progress($w['rep'], 'Sales', 'in_progress', 45); // 10% → yellow
        $this->assertSame('yellow', $drift->evaluate($w['chat']->fresh(), alert: false)['level']);

        $this->progress($w['rep'], 'Sales', 'in_progress', 48); // 4% → green
        $this->assertSame('green', $drift->evaluate($w['chat']->fresh(), alert: false)['level']);

        $this->progress($w['rep'], 'Sales', 'completed', 0); // 100 → no drift
        $this->assertSame(0.0, (float) $drift->evaluate($w['chat']->fresh(), alert: false)['index']);
    }

    public function test_the_index_is_persisted_on_the_strategy(): void
    {
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'in_progress', 30);

        $chat = $w['chat']->fresh();
        $this->assertSame('red', $chat->drift_level);
        $this->assertEqualsWithDelta(40.0, (float) $chat->drift_index, 0.5);
        $this->assertNotNull($chat->drift_checked_at);
    }

    public function test_goals_are_not_measured_in_the_grace_period_or_without_a_target_date(): void
    {
        $w = $this->world();
        $w['chat']->forceFill(['published_at' => now()->subDay()])->save(); // 1/11 of the window < 10%

        $state = app(DriftIndex::class)->evaluate($w['chat']->fresh(), alert: false);

        $this->assertNull($state['index']);
        $this->assertNull($state['level']);
        $product = $state['goals']->firstWhere('goal.role', 'Product');
        $this->assertFalse($product['measured']);
        $this->assertNull($product['expected']);
    }

    public function test_a_target_date_before_publishing_is_not_measured(): void
    {
        $w = $this->world();
        $this->goal('Sales')->update(['target_date' => now()->subDays(20)->toDateString()]);

        $state = app(DriftIndex::class)->evaluate($w['chat']->fresh(), alert: false);

        $this->assertNull($state['index']);
    }

    public function test_a_draft_is_not_persisted(): void
    {
        $w = $this->world(publish: false);

        app(DriftIndex::class)->evaluate($w['chat']->fresh());

        $this->assertNull($w['chat']->fresh()->drift_level);
        $this->assertSame(0, WrNotification::count());
    }

    public function test_projections_follow_the_current_pace(): void
    {
        $w = $this->world();
        $this->progress($w['rep'], 'Sales', 'in_progress', 50); // on baseline after 10 of 20 days

        $row = app(DriftIndex::class)->evaluate($w['chat']->fresh(), alert: false)['goals']->firstWhere('goal.role', 'Sales');

        $this->assertSame(now()->addDays(10)->toDateString(), $row['projected_completion']->toDateString());
        $this->assertEqualsWithDelta(25.0, $row['projected_value'], 0.01);
        $this->assertSame(0, $row['days_behind']);

        $this->progress($w['rep'], 'Sales', 'in_progress', 30);
        $row = app(DriftIndex::class)->evaluate($w['chat']->fresh(), alert: false)['goals']->firstWhere('goal.role', 'Sales');
        $this->assertEqualsWithDelta(15.0, $row['projected_value'], 0.01);
        $this->assertSame(4, $row['days_behind']);
    }
```

- [ ] **Step 2: Run** → FAIL (the index is not computed).

- [ ] **Step 3: Implement** — replace `app/Services/DriftIndex.php`:

```php
<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalResponse;
use App\Models\SearchUserChat;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Notion's "Live Strategic Drift Engine" (Features spec, phase 6):
 * drift % = (expected baseline − observed progress) ÷ expected baseline × 100,
 * per goal, averaged per strategy; green < 5, yellow 5–15, red > 15.
 */
class DriftIndex
{
    /** A goal is measured once this share (%) of its window has passed. */
    public const GRACE = 10.0;

    public const YELLOW = 5.0;

    public const RED = 15.0;

    public function __construct(protected StrategyAlerts $alerts) {}

    public static function levelFor(?float $index): ?string
    {
        return match (true) {
            $index === null => null,
            $index > self::RED => 'red',
            $index >= self::YELLOW => 'yellow',
            default => 'green',
        };
    }

    /**
     * @param  Collection<int, GoalResponse>  $responses
     * @return array{expected: ?float, observed: float, drift: ?float, measured: bool, projected_completion: ?Carbon, projected_value: ?float, days_behind: ?int}
     */
    public function goalMetrics(ExpectedState $goal, SearchUserChat $chat, Collection $responses): array
    {
        $reported = $responses->filter(fn (GoalResponse $r) => $r->progress_pct !== null);
        $observed = $reported->isEmpty() ? 0.0 : (float) $reported->avg(fn (GoalResponse $r) => $r->progress_status === 'completed' ? 100 : (int) $r->progress_pct);
        $metrics = ['expected' => null, 'observed' => $observed, 'drift' => null, 'measured' => false, 'projected_completion' => null, 'projected_value' => null, 'days_behind' => null];

        $start = $chat->published_at;
        $end = $goal->target_date ? Carbon::parse($goal->target_date)->endOfDay() : null;
        if (! $start || ! $end || $end->lte($start)) {
            return $metrics;
        }

        $window = $end->getTimestamp() - $start->getTimestamp();
        $elapsed = max(0, min($window, now()->getTimestamp() - $start->getTimestamp()));
        $expected = 100 * $elapsed / $window;
        $metrics['expected'] = round($expected, 1);
        if ($observed > 0 && $elapsed > 0) {
            $metrics['projected_completion'] = $start->copy()->addSeconds((int) round($elapsed * 100 / $observed));
        }
        if ($expected < self::GRACE) {
            return $metrics;
        }

        $metrics['measured'] = true;
        $metrics['drift'] = round(max(0, ($expected - $observed) / $expected * 100), 1);
        $metrics['days_behind'] = $expected > $observed ? (int) round(($expected - $observed) / 100 * $window / 86400) : 0;
        if (preg_match('/-?\d+(?:\.\d+)?/', (string) $goal->target_value, $m)) {
            $metrics['projected_value'] = round((float) $m[0] * min(1, $observed / $expected), 1);
        }

        return $metrics;
    }

    /** @return array{index: ?float, level: ?string, worst: ?ExpectedState, projected: ?Carbon, goals: Collection<int, array<string, mixed>>} */
    public function evaluate(SearchUserChat $chat, bool $alert = true): array
    {
        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->with('orgRole:id,name')->orderBy('id')->get();
        $responses = GoalResponse::whereIn('expected_state_id', $goals->pluck('id'))->get()->groupBy('expected_state_id');
        $rows = $goals->map(fn (ExpectedState $g) => ['goal' => $g] + $this->goalMetrics($g, $chat, $responses->get($g->id, collect())));

        $measured = $rows->where('measured', true);
        $index = $measured->isEmpty() ? null : round((float) $measured->avg('drift'), 1);
        $level = self::levelFor($index);
        $worst = $measured->sortByDesc('drift')->first()['goal'] ?? null;
        $projected = $rows->pluck('projected_completion')->filter()->max();

        if ($chat->isPublished()) {
            $chat->forceFill(['drift_index' => $index, 'drift_level' => $level, 'drift_checked_at' => now()])->save();
            if ($alert) {
                $this->alertOnChange($chat, $index, $level, $worst);
            }
        }

        return ['index' => $index, 'level' => $level, 'worst' => $worst, 'projected' => $projected, 'goals' => $rows];
    }

    /** Task 3 fills this in. */
    private function alertOnChange(SearchUserChat $chat, ?float $index, ?string $level, ?ExpectedState $worst): void {}
}
```

- [ ] **Step 4: Run** → 10 passed. **Step 5: Commit** — `feat(command): Notion drift index with pace-based projections`.

---

### Task 3: Threshold actions, email, and the daily command

**Files:** `app/Services/DriftIndex.php` (`alertOnChange`), `app/Services/GoalRevisions.php` (use `StrategyAlerts`), `resources/views/emails/strategy-alert.blade.php` (create), `app/Console/Commands/EvaluateStrategyDrift.php` (create), `routes/console.php`; test.

- [ ] **Step 1: Failing tests** — append:

```php
    public function test_yellow_nudges_the_bottleneck_roles_holders_once(): void
    {
        Mail::fake();
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'in_progress', 45); // yellow
        app(DriftIndex::class)->evaluate($w['chat']->fresh()); // same level again

        $nudges = WrNotification::where('type', 'drift_nudge')->get();
        $this->assertSame([$w['rep']->id], $nudges->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        Mail::assertQueued(EmailManager::class, fn ($m) => $m->hasTo('rep@acme.com'));
    }

    public function test_red_alerts_the_author_and_rearms_after_green(): void
    {
        Mail::fake();
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'in_progress', 30); // red
        $this->progress($w['rep'], 'Sales', 'in_progress', 50); // green
        $this->progress($w['rep'], 'Sales', 'in_progress', 20); // red again

        $alerts = WrNotification::where('type', 'drift_red')->get();
        $this->assertCount(2, $alerts);
        $this->assertSame($w['ceo']->id, (int) $alerts->first()->user_id);
        $this->assertSame('dashboard/strategies/'.$w['chat']->id, $alerts->first()->url);
        Mail::assertQueued(EmailManager::class, fn ($m) => $m->hasTo('ceo@acme.com'));
    }

    public function test_an_alert_to_someone_without_email_still_lands_in_app(): void
    {
        Mail::fake();
        $w = $this->world();
        $w['ceo']->forceFill(['email' => ''])->save();

        $this->progress($w['rep'], 'Sales', 'in_progress', 30);

        $this->assertSame(1, WrNotification::where('type', 'drift_red')->count());
        Mail::assertNotQueued(EmailManager::class, fn ($m) => $m->hasTo(''));
    }

    public function test_goal_revisions_are_emailed_too(): void
    {
        Mail::fake();
        $w = $this->world();
        $w['rep']->forceFill(['manager_id' => null])->save();
        User::where('id', $w['pm']->id)->update(['manager_id' => $w['rep']->id]); // rep leads someone → leader

        $this->actingAs($w['rep'])->post(route('my-goals.revise'), ['goal_id' => $this->goal('Sales')->id, 'text' => 'New wording']);

        Mail::assertQueued(EmailManager::class, fn ($m) => $m->hasTo('ceo@acme.com'));
    }

    public function test_the_daily_command_evaluates_published_strategies(): void
    {
        Mail::fake();
        $w = $this->world();
        GoalResponse::create(['expected_state_id' => $this->goal('Sales')->id, 'user_id' => $w['rep']->id, 'decision' => 'act_on_it', 'progress_status' => 'in_progress', 'progress_pct' => 30]);

        $this->artisan('strategies:evaluate-drift')->assertExitCode(0);

        $this->assertSame('red', $w['chat']->fresh()->drift_level);
        $this->assertSame(1, WrNotification::where('type', 'drift_red')->count());
    }
```

- [ ] **Step 2: Run** → FAIL (no nudges; `strategies:evaluate-drift` not found).

- [ ] **Step 3: Alerts** — replace the stub `alertOnChange` in `DriftIndex`:

```php
    /**
     * Notion's threshold actions, once per level change: yellow nudges the
     * people holding the worst-drifting goal; red alerts the strategy's author.
     * Back to green re-arms them.
     */
    private function alertOnChange(SearchUserChat $chat, ?float $index, ?string $level, ?ExpectedState $worst): void
    {
        if ($level === $chat->drift_alerted_level) {
            return;
        }
        if ($level === null || $level === 'green') {
            $chat->forceFill(['drift_alerted_level' => $level])->save();

            return;
        }
        // Claim the alert first, so two concurrent evaluations cannot both send it.
        $claimed = SearchUserChat::whereKey($chat->id)
            ->where(fn ($q) => $q->whereNull('drift_alerted_level')->orWhere('drift_alerted_level', '!=', $level))
            ->update(['drift_alerted_level' => $level]);
        if (! $claimed) {
            return;
        }

        $role = $worst ? ($worst->orgRole->name ?? $worst->role) : '';
        if ($level === 'yellow' && $worst && $worst->org_role_id) {
            $holders = User::where('organization_id', $chat->organization_id)->where('org_role_id', $worst->org_role_id)->get();
            foreach ($holders as $holder) {
                $this->alerts->send($holder, localize('Your goal is falling behind').': '.$role, 'dashboard',
                    'A published strategy is drifting ('.$index.'%), and your goal "'.Str::limit((string) $worst->recommended_action, 120).'" is furthest behind its baseline. Post an update, or flag what is blocking you.',
                    'drift_nudge');
            }
        }
        if ($level === 'red' && ($author = User::find($chat->user_id))) {
            $this->alerts->send($author, localize('Severe strategy drift').': '.$index.'%', 'dashboard/strategies/'.$chat->id,
                'Execution is more than '.self::RED.'% behind its baseline'.($worst ? ', furthest on the '.$role.' goal' : '').'. Open the executive view for the breakdown and AI-suggested recourse.',
                'drift_red');
        }
    }
```

- [ ] **Step 4: Email view** — create `resources/views/emails/strategy-alert.blade.php`:

```blade
<div style="font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; color: #1f2937;">
    <h2 style="color: #2c6d82; font-size: 18px;">{{ $title }}</h2>
    <p style="font-size: 14px; line-height: 1.5;">{{ $body }}</p>
    <p><a href="{{ $link }}" style="display: inline-block; background: #36839b; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none;">{{ localize('Open') }}</a></p>
</div>
```

- [ ] **Step 5: Revisions use StrategyAlerts** — in `app/Services/GoalRevisions.php`, change the constructor to `public function __construct(protected OrganizationService $orgs, protected StrategyAlerts $alerts) {}`, and replace the `foreach ($recipients as $userId) { saveNotification(...); }` loop with:

```php
        foreach (User::whereIn('id', $recipients->all())->get() as $recipient) {
            // Relative on purpose: the notification controller redirects to '/'.$url.
            $this->alerts->send(
                $recipient,
                localize('Goal changed').': '.$role,
                'dashboard/strategies/'.$chat->id,
                $leader->name.': "'.Str::limit($old, 120).'" → "'.Str::limit($text, 120).'"',
                'goal_revision',
            );
        }
```

- [ ] **Step 6: Command** — create `app/Console/Commands/EvaluateStrategyDrift.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\SearchUserChat;
use App\Services\DriftIndex;
use Illuminate\Console\Command;

/** Drift grows with time even when nobody reports: re-evaluate daily. */
class EvaluateStrategyDrift extends Command
{
    protected $signature = 'strategies:evaluate-drift';

    protected $description = 'Recalculate the drift index of every published strategy and send threshold alerts';

    public function handle(DriftIndex $drift): int
    {
        SearchUserChat::where('status', 'published')->orderBy('id')->each(fn (SearchUserChat $chat) => $drift->evaluate($chat));

        return self::SUCCESS;
    }
}
```

In `routes/console.php`, add `use Illuminate\Support\Facades\Schedule;` and, at the end:

```php
// Features spec, phase 6: drift grows with time even when nobody reports.
Schedule::command('strategies:evaluate-drift')->dailyAt('07:00');
```

- [ ] **Step 7: Run** — `php artisan test --filter='CommandDashboardTest|LeaderEditsTest'` → all pass. **Step 8: Commit** — `feat(command): drift nudges, red alerts, email, and a daily evaluation`.

---

### Task 4: AI-suggested recourse

**Files:** `app/Services/Recourse.php` (create); `StrategyOverviewController::recourse`; route; test.

- [ ] **Step 1: Failing tests** — append:

```php
    private function fakeRecourse(string $text, int $status = 200): void
    {
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturn(new ClientResponse(new PsrResponse($status, [], '{}')));
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    public function test_a_leader_gets_cached_recourse_options(): void
    {
        $w = $this->world();
        $this->fakeRecourse('{"options":[{"action":"Reallocate $20k from Product to Sales enablement","why":"Sales is furthest behind"},{"action":"Extend the Sales target by 10 days","why":"Product dependency slipped"}]}');

        $this->actingAs($w['ceo'])->post(route('strategies.recourse', $w['chat']->id))->assertRedirect();

        $recourse = $w['chat']->fresh()->recourse;
        $this->assertCount(2, $recourse['options']);
        $this->assertSame('Reallocate $20k from Product to Sales enablement', $recourse['options'][0]['action']);
        $this->actingAs($w['ceo'])->get(route('strategies.show', $w['chat']->id))->assertSee('Extend the Sales target by 10 days');
    }

    public function test_recourse_is_for_leaders_and_published_strategies_only(): void
    {
        $w = $this->world();
        $this->fakeRecourse('{"options":[{"action":"x","why":"y"}]}');

        $this->actingAs($w['rep'])->post(route('strategies.recourse', $w['chat']->id))->assertForbidden();
        $w['chat']->forceFill(['status' => 'draft'])->save();
        $this->actingAs($w['ceo'])->post(route('strategies.recourse', $w['chat']->id))->assertNotFound();
    }

    public function test_an_unusable_ai_reply_stores_nothing(): void
    {
        $w = $this->world();
        $this->fakeRecourse('Sorry, I cannot help.');

        $this->actingAs($w['ceo'])->post(route('strategies.recourse', $w['chat']->id))->assertRedirect();

        $this->assertNull($w['chat']->fresh()->recourse);
    }
```

- [ ] **Step 2: Run** → FAIL (`Route [strategies.recourse] not defined.`).

- [ ] **Step 3: Service** — create `app/Services/Recourse.php`:

```php
<?php

namespace App\Services;

use App\Models\GoalObstacle;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;

/**
 * Notion's red-alert card: AI-suggested recourse for a drifting strategy.
 * Organization context only — never anyone's private documents.
 */
class Recourse
{
    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected DriftIndex $drift,
        protected MyGoals $goals,
    ) {}

    /** @return list<array{action: string, why: string}>|null */
    public function suggest(SearchUserChat $chat, User $viewer): ?array
    {
        $state = $this->drift->evaluate($chat, alert: false);
        $line = fn ($v) => str_replace('---', '--', trim((string) preg_replace('/\s+/', ' ', (string) $v)));

        $goalLines = $state['goals']->map(fn (array $row) => '- '.$line($row['goal']->orgRole->name ?? $row['goal']->role).': "'.$line($row['goal']->recommended_action).'"'
            .' — expected '.($row['expected'] ?? 'n/a').'%, observed '.round($row['observed']).'%, drift '.($row['drift'] ?? 'not measured').'%')->implode("\n");
        $resourceLines = $chat->resources()->get()->map(fn ($r) => '- '.$line($r->department_name).': budget '.($r->budget ?? 'n/a').', '.($r->fte ?? 'n/a').' FTE')->implode("\n") ?: '(none)';
        $obstacleLines = GoalObstacle::whereIn('expected_state_id', $state['goals']->pluck('goal.id'))->orderByDesc('id')->limit(10)->pluck('body')
            ->map(fn ($b) => '- '.$line($b))->implode("\n") ?: '(none)';

        $system = 'You are an executive strategy advisor. Return ONLY valid JSON. No markdown, no code fences, no commentary.'.$this->docs->orgContextBlock($viewer);
        $prompt = 'Company goal: "'.$line($this->goals->companyGoal($chat))."\"\n"
            .'Strategy path: "'.$line($chat->selected_strategy)."\"\n"
            .'Drift index: '.($state['index'] ?? 'n/a')."% (above 15% is severe)\n\n"
            ."Goals against their baseline:\n{$goalLines}\n\nCommitted resources:\n{$resourceLines}\n\nReported obstacles:\n{$obstacleLines}\n\n"
            ."Suggest 2 or 3 concrete recourse options an executive could take now, such as reallocating a specific budget amount between departments, extending a specific target date by a number of days, or adding FTE.\n"
            .'Output exactly: {"options":[{"action":"<one sentence>","why":"<one sentence>"}]}';

        try {
            $response = $this->ai->generate($system, $prompt, 1000, 0.4, true);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
        if (! $response->successful()) {
            return null;
        }

        $parsed = $this->ai->parseJson($this->ai->extractText($response));
        $options = collect(is_array($parsed['options'] ?? null) ? $parsed['options'] : [])
            ->filter(fn ($o) => is_array($o) && is_string($o['action'] ?? null) && trim($o['action']) !== '')
            ->map(fn (array $o) => ['action' => mb_substr(trim($o['action']), 0, 300), 'why' => mb_substr(trim((string) ($o['why'] ?? '')), 0, 300)])
            ->take(3)->values()->all();
        if ($options === []) {
            return null;
        }

        $chat->forceFill(['recourse' => ['level' => $state['level'], 'index' => $state['index'], 'generated_at' => now()->toIso8601String(), 'options' => $options]])->save();

        return $options;
    }
}
```

- [ ] **Step 4: Controller and route** — in `StrategyOverviewController`, add `use App\Services\Recourse;`, `use Illuminate\Http\RedirectResponse;`, `use App\Models\SearchUserChat;`, and:

```php
    public function recourse(Request $request, $chat, Recourse $recourse): RedirectResponse
    {
        abort_unless($this->orgs->isLeader($request->user()), 403);
        $record = SearchUserChat::whereKey((int) $chat)->where('status', 'published')
            ->where('organization_id', (int) $request->user()->organization_id)->first();
        abort_unless($record, 404);

        if ($recourse->suggest($record, $request->user())) {
            flash(localize('Recourse options are ready'))->success();
        } else {
            flash(localize('We could not suggest recourse just now, please try again.'))->warning();
        }

        return back();
    }
```

After the `strategies.show` route:

```php
                Route::post('/strategies/{chat}/recourse', [StrategyOverviewController::class, 'recourse'])->name('strategies.recourse');
```

(The show page renders the cached options in Task 5. This task's page assertion passes once Task 5 lands. Until then, run the first two tests only: `--filter='test_a_leader_gets|test_recourse_is|test_an_unusable'` with the `assertSee` line expected to fail. Instead of that half-state, **do Task 4 and Task 5 back-to-back and commit them together** — see Task 5 Step 7.)

---

### Task 5: The command view

**Files:** `app/Services/StrategyOverview.php`; `StrategyOverviewController` (`settings`, and pass `isOwner`/`settings` to the views); `resources/views/backend/pages/strategies/index.blade.php` and `show.blade.php`; route; test.

**Interfaces — Produces:**
- `StrategyOverview::DEFAULT_SETTINGS`;
- `StrategyOverview::settingsFor(?Organization): array`;
- `StrategyOverview::savings(int $participants, int $tokens, array $settings): array{manual_cost, oi_cost, saved, hours_saved}`;
- list rows gain `drift_index`, `drift_level`, `savings`;
- detail gains `command` (`teams_involved`, `teams_total`, `contributors`, `savings`, `drift_index`, `drift_level`, `projected`), `deliverables`, `blockers`, `recourse`, and per-goal `metrics`.

- [ ] **Step 1: Failing tests** — append:

```php
    public function test_savings_follow_the_notion_formula_with_the_owners_assumptions(): void
    {
        $this->assertSame(
            ['manual_cost' => 2160.0, 'oi_cost' => 180.0, 'saved' => 1980.0, 'hours_saved' => 16.5],
            \App\Services\StrategyOverview::savings(3, 0, \App\Services\StrategyOverview::DEFAULT_SETTINGS),
        );
    }

    public function test_the_owner_edits_the_assumptions_and_others_cannot(): void
    {
        $w = $this->world();

        $this->actingAs($w['ceo'])->post(route('strategies.settings'), ['hourly_rate' => 200, 'manual_hours' => 4, 'oi_minutes' => 15, 'token_cost' => 0.01])->assertRedirect();
        $this->assertSame(200.0, (float) $w['org']->fresh()->command_settings['hourly_rate']);

        $this->actingAs($w['ceo'])->post(route('strategies.settings'), ['hourly_rate' => -5, 'manual_hours' => 4, 'oi_minutes' => 15, 'token_cost' => 0.01])->assertSessionHasErrors('hourly_rate');
        $this->actingAs($w['pm'])->post(route('strategies.settings'), ['hourly_rate' => 1, 'manual_hours' => 1, 'oi_minutes' => 1, 'token_cost' => 0])->assertForbidden();
    }

    public function test_the_command_view_shows_tiles_projection_deliverables_and_drift(): void
    {
        $w = $this->world();
        $this->progress($w['rep'], 'Sales', 'in_progress', 30, 'Contract template in review');

        $this->actingAs($w['ceo'])->get(route('strategies.index'))
            ->assertOk()->assertSee('40%')->assertSee('$1,980');

        $this->actingAs($w['ceo'])->get(route('strategies.show', $w['chat']->id))
            ->assertOk()
            ->assertSee('1 / 1 departments')
            ->assertSee('1 active contributor')
            ->assertSee('$1,980')
            ->assertSee('16.5 meeting hours')
            ->assertSee('Target 25 vs projected 15')
            ->assertSee('Contract template in review')
            ->assertSee('4 days behind baseline')
            ->assertSee('Zero upstream blockers detected across teams')
            ->assertSee('Suggest recourse options');
    }

    public function test_a_blocked_upstream_goal_raises_the_blocker_alert_and_severe_badge(): void
    {
        $w = $this->world();
        $this->progress($w['pm'], 'Product', 'blocked', 10, 'Waiting on the auditor');

        $this->actingAs($w['ceo'])->get(route('strategies.show', $w['chat']->id))
            ->assertOk()
            ->assertSee('1 upstream blocker')
            ->assertSee('Product')
            ->assertSee('Severe Drift')
            ->assertSee('1 goal blocked');
    }
```

- [ ] **Step 2: Run** → FAIL.

- [ ] **Step 3: StrategyOverview** — add these imports:

```php
use App\Models\Department;
use App\Models\GoalProgressUpdate;
use App\Models\Organization;
```

Add constants, the constructor dependency (`protected DriftIndex $drift` appended to the constructor), and the static helpers:

```php
    public const DEFAULT_SETTINGS = ['hourly_rate' => 120.0, 'manual_hours' => 6.0, 'oi_minutes' => 30.0, 'token_cost' => 0.02];

    /** @return array{hourly_rate: float, manual_hours: float, oi_minutes: float, token_cost: float} */
    public static function settingsFor(?Organization $org): array
    {
        $saved = is_array($org?->command_settings) ? $org->command_settings : [];

        return array_map('floatval', array_merge(self::DEFAULT_SETTINGS, array_intersect_key($saved, self::DEFAULT_SETTINGS)));
    }

    /**
     * Notion's alignment-savings formula, with the owner's assumptions.
     *
     * @param  array{hourly_rate: float, manual_hours: float, oi_minutes: float, token_cost: float}  $s
     * @return array{manual_cost: float, oi_cost: float, saved: float, hours_saved: float}
     */
    public static function savings(int $participants, int $tokens, array $s): array
    {
        $manual = $participants * $s['manual_hours'] * $s['hourly_rate'];
        $oi = $participants * $s['oi_minutes'] / 60 * $s['hourly_rate'] + $tokens / 1000 * $s['token_cost'];

        return [
            'manual_cost' => round($manual, 2),
            'oi_cost' => round($oi, 2),
            'saved' => round($manual - $oi, 2),
            'hours_saved' => round($participants * ($s['manual_hours'] - $s['oi_minutes'] / 60), 1),
        ];
    }

    /** @return Collection<int, int> ids of the people holding one of these goals' roles */
    private function holders(SearchUserChat $chat, Collection $goals): Collection
    {
        $roleIds = $goals->pluck('org_role_id')->filter()->map(fn ($id) => (int) $id)->unique();

        return $roleIds->isEmpty() ? collect() : User::where('organization_id', $chat->organization_id)->whereIn('org_role_id', $roleIds)->pluck('id');
    }

    /** Goals someone holding them has flagged Blocked. @return Collection<int, int> */
    private function flaggedBlocked(Collection $goalIds): Collection
    {
        return GoalResponse::whereIn('expected_state_id', $goalIds)->where('progress_status', 'blocked')->pluck('expected_state_id')->map(fn ($id) => (int) $id)->unique();
    }
```

In `list()`, inside the `map`:
1. Replace `$latest = $this->latestDrift($ids);` with:
```php
                $latest = $this->latestDrift($ids);
                $state = $this->drift->evaluate($chat);
                $goals = ExpectedState::where('search_user_chat_id', $chat->id)->get(['id', 'org_role_id']);
                $blocked = $this->blockedGoals($latest) + $this->flaggedBlocked($ids)->count();
```
2. Change the `badge` entry to pass `$blocked` instead of `$this->blockedGoals($latest)`.
3. Add these entries:
```php
                    'drift_index' => $state['index'],
                    'drift_level' => $state['level'],
                    'savings' => self::savings($this->holders($chat, $goals)->push((int) $chat->user_id)->unique()->count(), (int) $chat->total_tokens, $settings),
```
4. Before the `map`, add `$settings = self::settingsFor(Organization::find($viewer->organization_id));`, and add `$settings` to the closure's `use`: change `->map(function (SearchUserChat $chat) {` to `->map(function (SearchUserChat $chat) use ($settings) {`.

In `detail()`, after `$latest = $this->latestDrift($ids);` add:

```php
        $state = $this->drift->evaluate($chat);
        $metrics = $state['goals']->keyBy(fn (array $row) => (int) $row['goal']->id);
        $holderIds = $this->holders($chat, $goals);
        $flagged = $this->flaggedBlocked($ids);
        $settings = self::settingsFor(Organization::find($chat->organization_id));
        $holderDepts = User::whereIn('id', $holderIds)->whereNotNull('department_id')->distinct()->count('department_id');
        $contributors = GoalResponse::whereIn('expected_state_id', $ids)->distinct()->count('user_id');
        $lastUpdates = GoalProgressUpdate::whereIn('expected_state_id', $ids)->orderBy('id')->get()->keyBy('expected_state_id');
```

Change the detail `badge` entry's third argument to `$this->blockedGoals($latest) + $flagged->count()`. Add to the returned array:

```php
            'command' => [
                'teams_involved' => $holderDepts,
                'teams_total' => Department::where('organization_id', $chat->organization_id)->count(),
                'contributors' => $contributors,
                'savings' => self::savings($holderIds->push((int) $chat->user_id)->unique()->count(), (int) $chat->total_tokens, $settings),
                'drift_index' => $state['index'],
                'drift_level' => $state['level'],
                'projected' => $state['projected'],
            ],
            'deliverables' => $goals->map(function (ExpectedState $goal) use ($responses, $metrics, $lastUpdates) {
                $mine = $responses->get($goal->id, collect())->whereNotNull('progress_status');
                $status = match (true) {
                    $mine->contains('progress_status', 'blocked') => 'blocked',
                    $mine->isNotEmpty() && $mine->every(fn ($r) => $r->progress_status === 'completed') => 'completed',
                    $mine->whereIn('progress_status', ['in_progress', 'completed'])->isNotEmpty() => 'in_progress',
                    default => 'not_started',
                };

                return ['goal' => $goal, 'role' => $goal->orgRole->name ?? $goal->role, 'status' => $status,
                    'note' => $lastUpdates->get($goal->id)?->note, 'days_behind' => $metrics->get((int) $goal->id)['days_behind'] ?? null];
            }),
            'blockers' => $goals->filter(fn (ExpectedState $g) => $flagged->contains((int) $g->id) && $goals->contains(fn ($o) => (int) $o->depends_on_id === (int) $g->id))
                ->map(fn (ExpectedState $g) => $g->orgRole->name ?? $g->role)->values(),
            'recourse' => $chat->recourse,
```

In the detail's `goals` map, add `'metrics' => $metrics->get((int) $goal->id),`; its closure's `use` gains `$metrics`.

- [ ] **Step 4: Controller**
  - `index`: pass `'settings' => StrategyOverview::settingsFor($request->user()->organization)` and `'isOwner' => (int) optional($request->user()->organization)->owner_user_id === (int) $request->user()->id`.
  - Add `settings()`:

```php
    public function settings(Request $request): RedirectResponse
    {
        $org = $request->user()->organization;
        abort_unless($org && (int) $org->owner_user_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'hourly_rate' => 'required|numeric|min:0|max:100000',
            'manual_hours' => 'required|numeric|min:0|max:1000',
            'oi_minutes' => 'required|numeric|min:0|max:10000',
            'token_cost' => 'required|numeric|min:0|max:100',
        ]);
        $org->forceFill(['command_settings' => array_map('floatval', $data)])->save();
        flash(localize('Savings assumptions updated'))->success();

        return back();
    }
```

Route: `Route::post('/strategies/settings', [StrategyOverviewController::class, 'settings'])->name('strategies.settings');`. **Register it before** `/strategies/{chat}` routes, although POST vs GET cannot clash, keep it next to them.

- [ ] **Step 5: Views**

`index.blade.php`:
1. Add two header cells after `Status`: `<th>{{ localize('Drift index') }}</th>` and, after `Not viable`, `<th>{{ localize('Saved (est.)') }}</th>`.
2. Add the matching row cells after the Status cell:

```blade
                                            <td>@include('backend.pages.strategies.drift-pill', ['index' => $row['drift_index'], 'level' => $row['drift_level']])</td>
```

   and after the Not viable cell:

```blade
                                            <td>${{ number_format($row['savings']['saved']) }}</td>
```

   (The currency symbol is `$` in the savings UI. The assumptions are unit-agnostic, so the rate's currency is whatever the owner enters. **Ruling:** use `config('custom.default_currency_symbol') ?: '$'`. Implement that as `{{ config('custom.default_currency_symbol') ?: '$' }}{{ number_format(...) }}` everywhere money is shown, instead of a literal `$`.)
3. After the table card, add the owner's assumptions form:

```blade
            @if ($isOwner)
                <details class="mt-3">
                    <summary class="small" style="color:#2c6d82;cursor:pointer">{{ localize('Savings assumptions (estimate)') }}</summary>
                    <form method="POST" action="{{ route('strategies.settings') }}" class="row g-2 mt-2" style="max-width:720px">
                        @csrf
                        @foreach (['hourly_rate' => 'Blended hourly rate', 'manual_hours' => 'Manual alignment hours per participant', 'oi_minutes' => 'OI minutes per participant', 'token_cost' => 'Cost per 1,000 AI tokens'] as $key => $label)
                            <div class="col-md-6">
                                <label class="small">{{ localize($label) }}</label>
                                <input type="number" step="any" min="0" name="{{ $key }}" value="{{ $settings[$key] }}" required class="form-control form-control-sm" style="box-sizing:border-box">
                            </div>
                        @endforeach
                        <div class="col-12"><button type="submit" class="btn btn-sm" style="background:#36839b;color:#fff">{{ localize('Save assumptions') }}</button></div>
                    </form>
                </details>
            @endif
```

Create `resources/views/backend/pages/strategies/drift-pill.blade.php`:

```blade
{{-- Notion drift index: green < 5%, yellow 5–15%, red > 15%. --}}
@php
    [$fg, $bg] = ['green' => ['#1e7e34', '#e6f4ea'], 'yellow' => ['#9a5a12', '#fbf2ea'], 'red' => ['#b42318', '#fdecea']][$level] ?? ['#5f6b7a', '#eef1f4'];
@endphp
<span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;color:{{ $fg }};background:{{ $bg }}">
    {{ $index === null ? localize('Not measured') : rtrim(rtrim(number_format((float) $index, 1), '0'), '.').'%' }}
</span>
```

`show.blade.php`: directly after the badge `<div class="mb-2">…</div>`, insert the command tiles, the red-alert card, the deliverables and the blocker alert:

```blade
            @php $money = config('custom.default_currency_symbol') ?: '$'; @endphp
            <div class="row g-3 mb-3">
                <div class="col-md-4"><div class="card h-100"><div class="card-body">
                    <div class="small text-muted">{{ localize('Teams involved') }}</div>
                    <div class="fs-5 fw-bold">{{ $command['teams_involved'] }} / {{ $command['teams_total'] }} {{ localize('departments') }}</div>
                    <div class="small text-muted">{{ $command['contributors'] }} {{ \Illuminate\Support\Str::plural('active contributor', $command['contributors']) }}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card h-100"><div class="card-body">
                    <div class="small text-muted">{{ localize('Alignment effort saved (estimate)') }}</div>
                    <div class="fs-5 fw-bold">{{ $money }}{{ number_format($command['savings']['saved']) }}</div>
                    <div class="small text-muted">{{ rtrim(rtrim(number_format($command['savings']['hours_saved'], 1), '0'), '.') }} {{ localize('meeting hours saved') }}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card h-100"><div class="card-body">
                    <div class="small text-muted">{{ localize('Drift index') }}</div>
                    <div class="fs-5 fw-bold">@include('backend.pages.strategies.drift-pill', ['index' => $command['drift_index'], 'level' => $command['drift_level']])</div>
                    <div class="small text-muted">
                        @if ($command['projected']){{ localize('Projected completion') }}: {{ $command['projected']->toFormattedDateString() }} ({{ localize('at current pace') }})@else{{ localize('No progress reported yet') }}@endif
                    </div>
                </div></div></div>
            </div>

            @if ($command['drift_level'] === 'red')
                <div class="card mb-3" style="border:1px solid #b42318;background:#fdecea"><div class="card-body">
                    <h6 style="color:#b42318">⚠ {{ localize('Severe drift') }}: {{ $command['drift_index'] }}% {{ localize('behind baseline') }}</h6>
                    @if (! empty($recourse['options']))
                        <ul class="mb-2">
                            @foreach ($recourse['options'] as $option)
                                <li><strong>{{ $option['action'] }}</strong> <span class="text-muted">— {{ $option['why'] }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                    <form method="POST" action="{{ route('strategies.recourse', $chat->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background:#b42318;color:#fff">{{ empty($recourse['options']) ? localize('Suggest recourse options') : localize('Refresh recourse options') }}</button>
                    </form>
                </div></div>
            @endif

            <div class="card mb-3"><div class="card-body">
                <h6>{{ localize('Departmental progress & deliverables') }}</h6>
                @php $statusText = ['completed' => '✅ '.localize('Completed'), 'in_progress' => '⏳ '.localize('In progress'), 'blocked' => '⛔ '.localize('Blocked'), 'not_started' => localize('Not started')]; @endphp
                @foreach ($deliverables as $item)
                    <div class="small mb-1">
                        <strong>{{ $item['role'] }}</strong>: {{ $item['note'] ?? $item['goal']->recommended_action }}
                        <span class="ms-1">[{{ $statusText[$item['status']] }}]</span>
                        @if ($item['days_behind']) <span style="color:#b42318">— {{ $item['days_behind'] }} {{ \Illuminate\Support\Str::plural('day', $item['days_behind']) }} behind baseline</span>@endif
                    </div>
                @endforeach
                <div class="small mt-2 p-2" style="background:{{ $blockers->isEmpty() ? '#e6f4ea' : '#fdecea' }};border-radius:6px">
                    @if ($blockers->isEmpty())
                        {{ localize('Zero upstream blockers detected across teams.') }}
                    @else
                        ⚠ {{ $blockers->count() }} {{ \Illuminate\Support\Str::plural('upstream blocker', $blockers->count()) }}: {{ $blockers->implode(', ') }}
                    @endif
                </div>
            </div></div>
```

In the goals table, add a header cell `{{ localize('Target vs projected') }}` before `OI drift`, with this row cell:

```blade
                                    <td class="small">
                                        @if (($row['metrics']['projected_value'] ?? null) !== null)
                                            {{ localize('Target') }} {{ $row['goal']->target_value }} vs {{ localize('projected') }} {{ rtrim(rtrim(number_format($row['metrics']['projected_value'], 1), '0'), '.') }} <span class="text-muted">({{ localize('at current pace') }})</span>
                                        @else — @endif
                                    </td>
```

- [ ] **Step 6: Run** — `php artisan test --filter='CommandDashboardTest|ExecutiveViewTest'` → all pass. Then `php artisan test` (full), Pint on changed PHP, PHPStan, and `node --check` is not needed (no JS changes).

- [ ] **Step 7: Commit** (Tasks 4 and 5 together) — `feat(command): executive command view — tiles, projections, deliverables, blockers, recourse`.

- [ ] **Step 8: Browser check** — as `ceo@demo.test` (dev-login), open `/dashboard/strategies/{id}` and look at the tiles, the drift pill, and the deliverables. As `rep@demo.test`, post a progress update on "Your goals".
