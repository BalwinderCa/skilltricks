# Goal Cascade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Managers cascade a goal to their direct reports as sub-goals, AI-suggested and then edited and sent. Recipients see the sub-goal, report progress on it, and can cascade it further (L3/L4). The executive view counts sub-goals under each goal.

**Architecture:** A self-referential `goal_cascades` table; a `Cascades` service (authorization, AI suggestions, drafts, sending, progress, queries); a `CascadeController` with four form posts; a `goals/cascade.blade.php` partial plus a "Cascaded to you" section in `my-goals.blade.php`; and a count on the executive deliverables.

**Spec:** `docs/superpowers/specs/2026-09-24-goal-cascade-design.md`

## Global Constraints

- Assignees must be the cascader's own direct reports: same organization, `manager_id` = the cascader.
- A cascade source is either a goal from `MyGoals::visibleGoal`, or a sub-goal sent *to* the user whose root strategy is still published in the user's organization. Anything else is a 404.
- Organization context only in the AI prompt. The suggest route is throttled at `throttle:10,1`.
- Re-suggesting deletes only the cascader's **unsent** drafts for that source.
- Alerts go through `StrategyAlerts`, type `goal_cascade`, URL `dashboard`.

## Review Focus

1. **A crafted `assignee_id` that is not a direct report** (a peer, or someone in another org) — refused.
2. **A crafted `texts[id]` for a draft that belongs to another cascader** — ignored.
3. **A sub-goal whose strategy has since been unpublished, or belongs to another org** — invisible, with no progress and no cascade.
4. **Sending with every draft removed or blanked** — nothing sent, no alert.
5. **A manager who is also the author** — works like any other cascader.

---

### Task 1: Model, service, and endpoints

**Files:** migration `2026_09_24_000500_create_goal_cascades_table.php`; `app/Models/GoalCascade.php`; `app/Services/Cascades.php`; `app/Http/Controllers/Backend/CascadeController.php`; `routes/backend.php`; test `tests/Feature/GoalCascadeTest.php`.

- [ ] **Step 1: Failing tests** — create `tests/Feature/GoalCascadeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Mail\EmailManager;
use App\Models\ExpectedState;
use App\Models\GoalCascade;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Models\WrNotification;
use App\Services\AI\AiProviderService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * Cascading goals to direct reports (Features spec, phase 8 / Notion Epic 3).
 */
class GoalCascadeTest extends TestCase
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
     * ceo (owner, author) ← vp (Sales role) ← lead1, lead2; lead1 ← frontline.
     * One published strategy with a Sales goal.
     *
     * @return array<string, mixed>
     */
    private function world(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $make = fn (string $email, string $name, ?OrgRole $role, ?User $manager) => User::factory()->create([
            'email' => $email, 'name' => $name, 'user_type' => 'customer', 'organization_id' => $org->id,
            'org_role_id' => $role?->id, 'manager_id' => $manager?->id,
        ]);
        $ceo = $make('ceo@acme.com', 'Casey CEO', null, null);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $vp = $make('vp@acme.com', 'Val VP', $sales, $ceo);
        $lead1 = $make('lead1@acme.com', 'Lee Lead', null, $vp);
        $lead2 = $make('lead2@acme.com', 'Lou Lead', null, $vp);
        $frontline = $make('front@acme.com', 'Fran Front', null, $lead1);

        $chat = SearchUserChat::create(['user_id' => $ceo->id, 'status1' => 0, 'selected_strategy' => 'Upsell', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $ceo->id, 'search' => 'Grow revenue 30%', 'response' => 'ok']);
        $goal = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion', 'org_role_id' => $sales->id]);
        $chat->forceFill(['status' => 'published', 'published_by' => $ceo->id, 'published_at' => now(), 'organization_id' => $org->id])->save();

        return compact('org', 'sales', 'ceo', 'vp', 'lead1', 'lead2', 'frontline', 'chat', 'goal');
    }

    private function fakeAi(string $text): void
    {
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturn(new ClientResponse(new PsrResponse(200, [], '{}')));
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    private function suggestFor(array $w): void
    {
        $this->fakeAi(json_encode(['items' => [
            ['user_id' => $w['lead1']->id, 'text' => 'Draft the upgrade contract template'],
            ['user_id' => $w['lead2']->id, 'text' => 'List the 20 accounts closest to upgrade'],
            ['user_id' => $w['ceo']->id, 'text' => 'Not a direct report'],
        ]]));
        $this->actingAs($w['vp'])->post(route('my-goals.cascade.suggest'), ['goal_id' => $w['goal']->id])->assertRedirect();
    }

    public function test_suggest_drafts_one_sub_goal_per_direct_report(): void
    {
        $w = $this->world();

        $this->suggestFor($w);

        $drafts = GoalCascade::orderBy('id')->get();
        $this->assertSame([$w['lead1']->id, $w['lead2']->id], $drafts->pluck('assignee_user_id')->map(fn ($id) => (int) $id)->all());
        $this->assertTrue($drafts->every(fn ($d) => $d->sent_at === null && (int) $d->created_by === $w['vp']->id && (int) $d->expected_state_id === $w['goal']->id));
    }

    public function test_re_suggesting_replaces_only_unsent_drafts(): void
    {
        $w = $this->world();
        $sent = GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Already sent', 'sent_at' => now()]);
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead2']->id, 'text' => 'Old draft']);

        $this->suggestFor($w);

        $this->assertNotNull(GoalCascade::find($sent->id));
        $this->assertSame(0, GoalCascade::where('text', 'Old draft')->count());
        $this->assertSame(2, GoalCascade::whereNull('sent_at')->count());
    }

    public function test_a_manual_draft_must_go_to_a_direct_report(): void
    {
        $w = $this->world();

        $this->actingAs($w['vp'])->post(route('my-goals.cascade.add'), ['goal_id' => $w['goal']->id, 'assignee_id' => $w['lead2']->id, 'text' => 'Book the pricing review'])->assertRedirect();
        $this->actingAs($w['vp'])->post(route('my-goals.cascade.add'), ['goal_id' => $w['goal']->id, 'assignee_id' => $w['frontline']->id, 'text' => 'Skip a level'])->assertSessionHasErrors('assignee_id');

        $this->assertSame(['Book the pricing review'], GoalCascade::pluck('text')->all());
    }

    public function test_sending_edits_removes_and_alerts_assignees(): void
    {
        Mail::fake();
        $w = $this->world();
        $this->suggestFor($w);
        [$first, $second] = GoalCascade::orderBy('id')->get()->all();
        $foreign = GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['ceo']->id, 'assignee_user_id' => $w['vp']->id, 'text' => 'CEO draft']);

        $this->actingAs($w['vp'])->post(route('my-goals.cascade.send'), [
            'goal_id' => $w['goal']->id,
            'texts' => [$first->id => 'Draft the enterprise upgrade contract', $foreign->id => 'Hijacked'],
            'remove' => [$second->id],
        ])->assertRedirect();

        $this->assertSame('Draft the enterprise upgrade contract', $first->fresh()->text);
        $this->assertNotNull($first->fresh()->sent_at);
        $this->assertNull(GoalCascade::find($second->id));
        $this->assertSame(['CEO draft', null], [$foreign->fresh()->text, $foreign->fresh()->sent_at]);
        $this->assertSame([$w['lead1']->id], WrNotification::where('type', 'goal_cascade')->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        Mail::assertQueued(EmailManager::class, fn ($m) => $m->hasTo('lead1@acme.com'));
    }

    public function test_sending_nothing_alerts_nobody(): void
    {
        $w = $this->world();
        $this->suggestFor($w);
        $ids = GoalCascade::pluck('id')->all();

        $this->actingAs($w['vp'])->post(route('my-goals.cascade.send'), ['goal_id' => $w['goal']->id, 'remove' => $ids])->assertRedirect();

        $this->assertSame(0, GoalCascade::count());
        $this->assertSame(0, WrNotification::count());
    }

    public function test_the_assignee_reports_progress_and_can_cascade_further(): void
    {
        $w = $this->world();
        $item = GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Draft the contract', 'sent_at' => now()]);

        $this->actingAs($w['lead1'])->post(route('my-goals.cascade.progress'), ['cascade_id' => $item->id, 'status' => 'completed', 'pct' => 10, 'note' => 'Signed off'])->assertRedirect();
        $this->assertSame(['completed', 100, 'Signed off'], [$item->fresh()->status, $item->fresh()->pct, $item->fresh()->note]);

        $this->actingAs($w['lead1'])->post(route('my-goals.cascade.add'), ['cascade_id' => $item->id, 'assignee_id' => $w['frontline']->id, 'text' => 'Collect the redlines'])->assertRedirect();
        $child = GoalCascade::where('text', 'Collect the redlines')->first();
        $this->assertSame([$item->id, $w['goal']->id], [(int) $child->parent_id, (int) $child->expected_state_id]);
    }

    public function test_access_rules(): void
    {
        $w = $this->world();
        $item = GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Draft the contract', 'sent_at' => now()]);

        // Not the assignee.
        $this->actingAs($w['lead2'])->post(route('my-goals.cascade.progress'), ['cascade_id' => $item->id, 'status' => 'in_progress', 'pct' => 5])->assertNotFound();
        // No direct reports.
        $this->actingAs($w['lead2'])->post(route('my-goals.cascade.suggest'), ['cascade_id' => GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead2']->id, 'text' => 'x', 'sent_at' => now()])->id])->assertForbidden();
        // Goal the user cannot see (lead1 holds no role).
        $this->actingAs($w['lead1'])->post(route('my-goals.cascade.suggest'), ['goal_id' => $w['goal']->id])->assertNotFound();
        // Unpublished strategy.
        $w['chat']->forceFill(['status' => 'draft'])->save();
        $this->actingAs($w['lead1'])->post(route('my-goals.cascade.progress'), ['cascade_id' => $item->id, 'status' => 'in_progress', 'pct' => 5])->assertNotFound();
    }

    public function test_the_suggest_route_is_throttled(): void
    {
        $route = app('router')->getRoutes()->getByName('my-goals.cascade.suggest');

        $this->assertTrue(collect($route->gatherMiddleware())->contains(fn ($m) => str_starts_with($m, 'throttle:')));
    }
}
```

- [ ] **Step 2: Run** → FAIL (`Class "App\Models\GoalCascade" not found`).

- [ ] **Step 3: Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Cascading goals to direct reports (Features spec, phase 8 / Notion Epic 3). */
    public function up(): void
    {
        Schema::create('goal_cascades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expected_state_id')->constrained('expected_states')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('goal_cascades')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('assignee_user_id')->index();
            $table->text('text');
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 20)->nullable();
            $table->unsignedTinyInteger('pct')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('progress_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_cascades');
    }
};
```

- [ ] **Step 4: Model** — `app/Models/GoalCascade.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sub-goal a manager cascaded to a direct report (Notion Epic 3). The root is
 * always the published role goal; parent is set when a sub-goal is cascaded on.
 *
 * @property int $id
 * @property int $expected_state_id
 * @property int|null $parent_id
 * @property int $created_by
 * @property int $assignee_user_id
 * @property string $text
 * @property string|null $status
 * @property int|null $pct
 * @property string|null $note
 */
class GoalCascade extends Model
{
    protected $fillable = ['expected_state_id', 'parent_id', 'created_by', 'assignee_user_id', 'text', 'sent_at', 'status', 'pct', 'note', 'progress_at'];

    protected $casts = ['sent_at' => 'datetime', 'progress_at' => 'datetime', 'pct' => 'integer'];

    /** @return BelongsTo<ExpectedState, $this> */
    public function root(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'expected_state_id');
    }

    /** @return BelongsTo<GoalCascade, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(GoalCascade::class, 'parent_id');
    }

    /** @return HasMany<GoalCascade, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(GoalCascade::class, 'parent_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }
}
```

- [ ] **Step 5: Service** — `app/Services/Cascades.php`:

```php
<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalCascade;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Notion Epic 3's sub-cascade: a manager turns a goal they hold (or a sub-goal
 * cascaded to them) into sub-goals for their direct reports.
 */
class Cascades
{
    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected MyGoals $goals,
        protected StrategyAlerts $alerts,
    ) {}

    /** @return Collection<int, User> the user's direct reports, in their organization */
    public function reportsOf(User $leader): Collection
    {
        if (! $leader->organization_id) {
            return collect();
        }

        return User::where('organization_id', $leader->organization_id)->where('manager_id', $leader->id)
            ->with(['orgRole:id,name', 'department:id,name'])->orderBy('name')->get();
    }

    /**
     * What $user may cascade from: a goal they can see, or a sub-goal sent to them
     * under a strategy still published in their organization.
     *
     * @return array{0: ExpectedState, 1: GoalCascade|null}|null
     */
    public function source(User $user, ?int $goalId, ?int $cascadeId): ?array
    {
        if ($cascadeId) {
            $item = GoalCascade::whereKey($cascadeId)->where('assignee_user_id', $user->id)->whereNotNull('sent_at')->with('root.searchUserChat')->first();

            return $item && $this->isLive($item->root?->searchUserChat, $user) ? [$item->root, $item] : null;
        }
        $goal = $goalId ? $this->goals->visibleGoal($user, $goalId) : null;

        return $goal ? [$goal, null] : null;
    }

    public function companyGoal(SearchUserChat $chat): string
    {
        return $this->goals->companyGoal($chat);
    }

    /** @return Collection<int, GoalCascade> this cascader's drafts and sent sub-goals for one source */
    public function itemsFor(User $leader, ExpectedState $root, ?GoalCascade $parent): Collection
    {
        return GoalCascade::where('created_by', $leader->id)->where('expected_state_id', $root->id)
            ->where('parent_id', $parent?->id)->with('assignee:id,name')->orderBy('id')->get();
    }

    /** AI drafts, one per direct report; returns how many were drafted. */
    public function suggest(User $leader, ExpectedState $root, ?GoalCascade $parent): int
    {
        $reports = $this->reportsOf($leader);
        if ($reports->isEmpty()) {
            return 0;
        }

        $line = fn ($v) => str_replace('---', '--', trim((string) preg_replace('/\s+/', ' ', (string) $v)));
        $people = $reports->map(fn (User $u) => '- user_id '.$u->id.' | '.$line($u->name).' | role: '.$line($u->orgRole->name ?? 'n/a').' | department: '.$line($u->department->name ?? 'n/a'))->implode("\n");
        $system = "You are StrategiStudio's execution guide. Return ONLY valid JSON. No markdown, no code fences, no commentary.".$this->docs->orgContextBlock($leader);
        $prompt = 'Company goal: "'.$line($this->goals->companyGoal($root->searchUserChat))."\"\n"
            .'The manager\'s mandate: "'.$line($parent ? $parent->text : $root->recommended_action)."\"\n\n"
            ."Direct reports:\n{$people}\n\n"
            ."Write one concrete sub-goal for each direct report that advances the manager's mandate within that person's role. Each under 25 words.\n"
            .'Output exactly: {"items":[{"user_id":<id>,"text":"<sub-goal>"}]}';

        try {
            $response = $this->ai->generate($system, $prompt, 1200, 0.4, true);
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
        if (! $response->successful()) {
            return 0;
        }

        $parsed = $this->ai->parseJson($this->ai->extractText($response));
        $allowed = $reports->pluck('id')->map(fn ($id) => (int) $id);
        $items = collect(is_array($parsed['items'] ?? null) ? $parsed['items'] : [])
            ->filter(fn ($i) => is_array($i) && $allowed->contains((int) ($i['user_id'] ?? 0)) && is_string($i['text'] ?? null) && trim($i['text']) !== '')
            ->unique(fn (array $i) => (int) $i['user_id'])
            ->values();
        if ($items->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($leader, $root, $parent, $items) {
            GoalCascade::where('created_by', $leader->id)->where('expected_state_id', $root->id)->where('parent_id', $parent?->id)->whereNull('sent_at')->delete();
            foreach ($items as $i) {
                GoalCascade::create(['expected_state_id' => $root->id, 'parent_id' => $parent?->id, 'created_by' => $leader->id,
                    'assignee_user_id' => (int) $i['user_id'], 'text' => mb_substr(trim($i['text']), 0, 300)]);
            }
        });

        return $items->count();
    }

    /** A hand-written draft; false when the assignee is not a direct report. */
    public function add(User $leader, ExpectedState $root, ?GoalCascade $parent, int $assigneeId, string $text): bool
    {
        if (! $this->reportsOf($leader)->contains(fn (User $u) => (int) $u->id === $assigneeId)) {
            return false;
        }
        GoalCascade::create(['expected_state_id' => $root->id, 'parent_id' => $parent?->id, 'created_by' => $leader->id,
            'assignee_user_id' => $assigneeId, 'text' => mb_substr(trim($text), 0, 300)]);

        return true;
    }

    /**
     * Send the cascader's drafts for this source, with edits and removals applied.
     *
     * @param  array<int|string, mixed>  $texts
     * @param  array<int, mixed>  $remove
     */
    public function send(User $leader, ExpectedState $root, ?GoalCascade $parent, array $texts, array $remove): int
    {
        $drafts = $this->itemsFor($leader, $root, $parent)->whereNull('sent_at');
        $removeIds = collect($remove)->map(fn ($id) => (int) $id);
        $sent = collect();

        DB::transaction(function () use ($drafts, $texts, $removeIds, $sent) {
            foreach ($drafts as $draft) {
                $text = trim((string) ($texts[$draft->id] ?? $draft->text));
                if ($removeIds->contains((int) $draft->id) || $text === '') {
                    $draft->delete();

                    continue;
                }
                $draft->forceFill(['text' => mb_substr($text, 0, 300), 'sent_at' => now()])->save();
                $sent->push($draft);
            }
        });

        foreach ($sent as $item) {
            if ($assignee = User::find($item->assignee_user_id)) {
                $this->alerts->send($assignee, localize('New goal from').' '.$leader->name, 'dashboard',
                    $leader->name.' cascaded a goal to you: "'.Str::limit($item->text, 160).'"', 'goal_cascade');
            }
        }

        return $sent->count();
    }

    public function progress(GoalCascade $item, string $status, int $pct, ?string $note): void
    {
        $note = $note !== null && trim($note) !== '' ? trim($note) : null;
        $item->forceFill(['status' => $status, 'pct' => $status === 'completed' ? 100 : max(0, min(100, $pct)), 'note' => $note, 'progress_at' => now()])->save();
    }

    /** @return Collection<int, GoalCascade> sub-goals sent to $user under live strategies */
    public function forAssignee(User $user): Collection
    {
        return GoalCascade::where('assignee_user_id', $user->id)->whereNotNull('sent_at')
            ->with(['root.searchUserChat', 'root.orgRole:id,name', 'parent', 'creator:id,name'])->orderByDesc('id')->get()
            ->filter(fn (GoalCascade $c) => $this->isLive($c->root?->searchUserChat, $user))->values();
    }

    /**
     * Sent sub-goals under each root goal, all levels.
     *
     * @param  Collection<int, mixed>  $rootIds
     * @return array<int, array{total: int, completed: int}>
     */
    public static function countsFor(Collection $rootIds): array
    {
        return GoalCascade::whereIn('expected_state_id', $rootIds)->whereNotNull('sent_at')->get(['expected_state_id', 'status'])
            ->groupBy(fn (GoalCascade $c) => (int) $c->expected_state_id)
            ->map(fn (Collection $g) => ['total' => $g->count(), 'completed' => $g->where('status', 'completed')->count()])
            ->all();
    }

    private function isLive(?SearchUserChat $chat, User $user): bool
    {
        return $chat !== null && $chat->isPublished() && (int) $chat->organization_id === (int) $user->organization_id;
    }
}
```

- [ ] **Step 6: Controller** — `app/Http/Controllers/Backend/CascadeController.php`:

```php
<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Cascades;
use App\Services\ProgressUpdates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Notion Epic 3: cascade a goal to direct reports, and report on a cascaded sub-goal. */
class CascadeController extends Controller
{
    public function __construct(protected Cascades $cascades) {}

    public function suggest(Request $request): RedirectResponse
    {
        [$root, $parent] = $this->source($request);
        $count = $this->cascades->suggest($request->user(), $root, $parent);
        $count > 0
            ? flash(localize('Draft sub-goals are ready to review'))->success()
            : flash(localize('We could not suggest sub-goals just now, please try again.'))->warning();

        return back();
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate(['assignee_id' => 'required|integer', 'text' => 'required|string|max:300']);
        [$root, $parent] = $this->source($request);
        if (! $this->cascades->add($request->user(), $root, $parent, (int) $data['assignee_id'], $data['text'])) {
            return back()->withErrors(['assignee_id' => localize('Pick one of your direct reports.')]);
        }
        flash(localize('Draft added'))->success();

        return back();
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['texts' => 'nullable|array', 'texts.*' => 'nullable|string|max:300', 'remove' => 'nullable|array', 'remove.*' => 'integer']);
        [$root, $parent] = $this->source($request);
        $count = $this->cascades->send($request->user(), $root, $parent, $data['texts'] ?? [], $data['remove'] ?? []);
        flash($count > 0 ? localize('Sent to your team') : localize('Nothing was sent'))->success();

        return back();
    }

    public function progress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cascade_id' => 'required|integer',
            'status' => ['required', Rule::in(ProgressUpdates::STATUSES)],
            'pct' => 'required|integer|min:0|max:100',
            'note' => 'nullable|string|max:500',
        ]);
        $source = $this->cascades->source($request->user(), null, (int) $data['cascade_id']);
        abort_unless($source && $source[1], 404);

        $this->cascades->progress($source[1], $data['status'], (int) $data['pct'], $data['note'] ?? null);
        flash(localize('Your update has been posted'))->success();

        return back();
    }

    /** @return array{0: \App\Models\ExpectedState, 1: \App\Models\GoalCascade|null} */
    private function source(Request $request): array
    {
        $request->validate(['goal_id' => 'nullable|integer|required_without:cascade_id', 'cascade_id' => 'nullable|integer']);
        $source = $this->cascades->source($request->user(), $request->integer('goal_id') ?: null, $request->integer('cascade_id') ?: null);
        abort_unless($source, 404);
        abort_if($this->cascades->reportsOf($request->user())->isEmpty(), 403);

        return $source;
    }
}
```

Routes — add `use App\Http\Controllers\Backend\CascadeController;` after the `MyGoalController` import, and after `->name('my-goals.progress');`:

```php
                // Notion Epic 3: cascade a goal to direct reports
                Route::post('/my-goals/cascade/suggest', [CascadeController::class, 'suggest'])->middleware('throttle:10,1')->name('my-goals.cascade.suggest');
                Route::post('/my-goals/cascade/add', [CascadeController::class, 'add'])->name('my-goals.cascade.add');
                Route::post('/my-goals/cascade/send', [CascadeController::class, 'send'])->name('my-goals.cascade.send');
                Route::post('/my-goals/cascade/progress', [CascadeController::class, 'progress'])->name('my-goals.cascade.progress');
```

- [ ] **Step 7: Run** → 8 passed. **Commit** — `feat(cascade): cascade goals to direct reports as sub-goals`.

---

### Task 2: Dashboard and executive view

**Files:** `app/Http/Controllers/Backend/DashboardController.php`; `resources/views/backend/pages/goals/my-goals.blade.php`; `resources/views/backend/pages/goals/cascade.blade.php` (create); `app/Services/StrategyOverview.php`; `resources/views/backend/pages/strategies/show.blade.php`; test.

- [ ] **Step 1: Failing tests** — append to `GoalCascadeTest`:

```php
    public function test_a_manager_sees_the_cascade_controls_and_drafts(): void
    {
        $w = $this->world();
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Draft the contract']);

        $this->actingAs($w['vp'])->get('/dashboard')
            ->assertOk()
            ->assertSee('Cascade to your team')
            ->assertSee('Suggest line-item actions for my team')
            ->assertSee('Draft the contract')
            ->assertSee(route('my-goals.cascade.send'), false);
    }

    public function test_the_assignee_sees_sent_sub_goals_only(): void
    {
        $w = $this->world();
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Unsent draft']);
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Draft the contract', 'sent_at' => now()]);

        $this->actingAs($w['lead1'])->get('/dashboard')
            ->assertOk()
            ->assertSee('Cascaded to you')
            ->assertSee('Draft the contract')
            ->assertSee('Val VP')
            ->assertSee('Grow revenue 30%')
            ->assertDontSee('Unsent draft')
            ->assertSee(route('my-goals.cascade.progress'), false)
            ->assertSee('Cascade to your team'); // lead1 has a report
    }

    public function test_the_executive_view_counts_sub_goals(): void
    {
        $w = $this->world();
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'A', 'sent_at' => now(), 'status' => 'completed']);
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead2']->id, 'text' => 'B', 'sent_at' => now()]);
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead2']->id, 'text' => 'Unsent']);

        $this->actingAs($w['ceo'])->get(route('strategies.show', $w['chat']->id))
            ->assertOk()->assertSee('2 sub-goals cascaded (1 completed)');
    }
```

- [ ] **Step 2: Run** → FAIL.

- [ ] **Step 3: Controller data** — in `DashboardController::index`, after the `'isLeader' => ...` entry:

```php
            // Notion Epic 3: sub-goals cascaded to this user, and their own direct reports.
            'cascades' => app(\App\Services\Cascades::class),
            'cascadedToMe' => app(\App\Services\Cascades::class)->forAssignee($user),
            'myReports' => app(\App\Services\Cascades::class)->reportsOf($user),
```

- [ ] **Step 4: The cascade partial** — create `resources/views/backend/pages/goals/cascade.blade.php`:

```blade
{{-- Notion Epic 3: cascade $root (or the sub-goal $parent) to the user's direct reports. --}}
@php
    $items = $cascades->itemsFor($user, $root, $parent);
    $drafts = $items->whereNull('sent_at');
    $sentItems = $items->whereNotNull('sent_at');
    [$sourceName, $sourceId] = $parent ? ['cascade_id', $parent->id] : ['goal_id', $root->id];
@endphp
<details class="mt-2" @if ($drafts->isNotEmpty()) open @endif>
    <summary class="small" style="color:#2c6d82;cursor:pointer">{{ localize('Cascade to your team') }} ({{ $myReports->count() }})</summary>
    @foreach ($sentItems as $item)
        <div class="small mt-1">&rarr; <strong>{{ $item->assignee?->name }}</strong>: {{ $item->text }}
            <span class="text-muted">[{{ $statusLabels[$item->status] ?? localize('Not started') }}@if ($item->pct !== null) · {{ $item->pct }}%@endif]</span>
        </div>
    @endforeach
    <form method="POST" action="{{ route('my-goals.cascade.suggest') }}" class="mt-2">
        @csrf
        <input type="hidden" name="{{ $sourceName }}" value="{{ $sourceId }}">
        <button type="submit" class="btn btn-sm mg-btn">{{ localize('Suggest line-item actions for my team') }}</button>
    </form>
    @if ($drafts->isNotEmpty())
        <form method="POST" action="{{ route('my-goals.cascade.send') }}" class="mt-2">
            @csrf
            <input type="hidden" name="{{ $sourceName }}" value="{{ $sourceId }}">
            @foreach ($drafts as $draft)
                <div class="mb-2">
                    <div class="small"><strong>{{ $draft->assignee?->name }}</strong>
                        <label class="ms-2 text-muted"><input type="checkbox" name="remove[]" value="{{ $draft->id }}"> {{ localize('remove') }}</label>
                    </div>
                    <textarea name="texts[{{ $draft->id }}]" rows="2" maxlength="300" class="form-control form-control-sm">{{ $draft->text }}</textarea>
                </div>
            @endforeach
            <button type="submit" class="btn btn-sm" style="background:#36839b;color:#fff">{{ localize('Send to my team') }}</button>
        </form>
    @endif
    <form method="POST" action="{{ route('my-goals.cascade.add') }}" class="row g-2 mt-2">
        @csrf
        <input type="hidden" name="{{ $sourceName }}" value="{{ $sourceId }}">
        <div class="col-md-3">
            <select name="assignee_id" class="form-select form-select-sm">
                @foreach ($myReports as $report)
                    <option value="{{ $report->id }}">{{ $report->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-7"><input type="text" name="text" maxlength="300" required class="form-control form-control-sm" placeholder="{{ localize('Or write a sub-goal yourself') }}"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-sm mg-btn w-100">{{ localize('Add draft') }}</button></div>
    </form>
    @error('assignee_id')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
</details>
```

- [ ] **Step 5: my-goals.blade.php**
1. Directly before the obstacle form (`<form method="POST" action="{{ route('my-goals.obstacle') }}"`), insert:
```blade
                    @if (($myReports ?? collect())->isNotEmpty())
                        @include('backend.pages.goals.cascade', ['root' => $card['goal'], 'parent' => null])
                    @endif
```
2. Directly before the closing `    </div>\n</div>` of the `#my-goals` card (the last two closing div lines of the file), insert the "Cascaded to you" section:
```blade
        @if (($cascadedToMe ?? collect())->isNotEmpty())
            <h6 class="mt-4 mb-1">{{ localize('Cascaded to you') }}</h6>
            @foreach ($cascadedToMe as $item)
                <div class="mg-goal">
                    <div class="mg-label">{{ localize('Company goal') }}</div>
                    <div class="mb-1">{{ $cascades->companyGoal($item->root->searchUserChat) }}</div>
                    <div class="small text-muted mb-2">{{ localize('From') }} {{ $item->creator?->name }} · {{ localize('Part of') }}: {{ $item->parent?->text ?? $item->root->recommended_action }}</div>
                    <div class="mg-action mb-2">{{ $item->text }}</div>
                    <form method="POST" action="{{ route('my-goals.cascade.progress') }}" class="row g-2 align-items-center">
                        @csrf
                        <input type="hidden" name="cascade_id" value="{{ $item->id }}">
                        <div class="col-md-3">
                            <select name="status" class="form-select form-select-sm">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(($item->status ?? 'in_progress') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><input type="number" name="pct" min="0" max="100" required value="{{ $item->pct ?? 0 }}" class="form-control form-control-sm"></div>
                        <div class="col-md-5"><input type="text" name="note" maxlength="500" class="form-control form-control-sm" placeholder="{{ localize('What changed?') }}"></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-sm mg-btn w-100">{{ localize('Update status') }}</button></div>
                    </form>
                    @if ($item->progress_at)
                        <div class="small text-muted mt-1">{{ $statusLabels[$item->status] ?? '' }} · {{ $item->pct }}%@if ($item->note) · {{ $item->note }}@endif · {{ $item->progress_at->diffForHumans() }}</div>
                    @endif
                    @if ($myReports->isNotEmpty())
                        @include('backend.pages.goals.cascade', ['root' => $item->root, 'parent' => $item])
                    @endif
                </div>
            @endforeach
        @endif
```

- [ ] **Step 6: Executive count** — in `StrategyOverview::detail`, add `$cascadeCounts = Cascades::countsFor($ids);` next to `$lastUpdates`, add `$cascadeCounts` to the `deliverables` closure's `use`, and add `'cascaded' => $cascadeCounts[(int) $goal->id] ?? null,` to each deliverable. In `show.blade.php`'s deliverables loop, after the status span, add:
```blade
                        @if ($item['cascaded']) <span class="text-muted">· {{ $item['cascaded']['total'] }} {{ \Illuminate\Support\Str::plural('sub-goal', $item['cascaded']['total']) }} cascaded ({{ $item['cascaded']['completed'] }} completed)</span>@endif
```

- [ ] **Step 7: Run** — `php artisan test --filter='GoalCascadeTest|MyGoalCardTest|CommandDashboardTest'`, then the full suite, Pint and PHPStan. **Commit** — `feat(cascade): cascade controls on the dashboard and sub-goal counts for executives`.
