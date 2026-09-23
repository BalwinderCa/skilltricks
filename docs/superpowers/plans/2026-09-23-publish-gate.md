# Publish Gate Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A strategy stays private (Draft) until an org-chart leader commits per-department resources and publishes it; after publishing, resource amendments are logged.

**Architecture:** Four columns on `search_user_chat` plus two tables (`strategy_resources`, `strategy_resource_changes`). One new controller, `StrategyPublishController`, with four JSON actions, and one Blade include holding the card's markup and script, mounted under the Leadership Alignment Brief. The leader rule lives in `OrganizationService::canPublish()`.

**Tech Stack:** Laravel 12, PHP 8, Blade + vanilla JS, PHPUnit feature tests on in-memory SQLite, MySQL on staging.

**Spec:** `docs/superpowers/specs/2026-09-23-publish-gate-design.md`

## Global Constraints

- Ownership is checked with `SearchUserChat::where('id', $id)->where('user_id', $user->id)->exists()`, never relation + `!==` (spurious 403 on MySQL).
- Status values are exactly `'draft'` and `'published'`. There is no unpublish.
- The existing wizard, Action Table and OI flow are not modified.
- UI colours: teal `#36839b` (hover `#2c6d82`, tint `#e7f3f7`), orange `#ec883f` (tint `#fbf2ea`). No purple.
- Currency symbol comes from `config('custom.default_currency_symbol')`; no currency column.
- The prompt is built only from saved data (`selected_strategy`, `selected_scenario`, `expected_states`, `leadership_brief`, departments), never from request text.
- CI gates: PHPStan, PHPUnit, Pint. Run `vendor/bin/pint` on touched PHP files before each commit.
- The include lives in `resources/views/backend/pages/aiChat/inc/` (this repo's convention for chat partials; the spec's `partials/` path is replaced by it).

## Review Focus

1. **Cross-organization department id** — a crafted `department_id` from another organization must be refused (422), not saved. Test in Task 3.
2. **AI amounts as text** — `"$50k"`, `"1,200,000"` or `"2 FTE"` must become numbers, not null or a 500. Test in Task 4.
3. **Double-click Publish** — a second publish must return 409 and leave `published_at` unchanged. Test in Task 5.
4. **Amending with unchanged values** — must write zero change rows. Test in Task 6.
5. **Author with no organization** — suggest must still work (one "Whole organization" row), and publish must refuse. Tests in Tasks 2 and 4.

---

## File Map

| File | Responsibility |
| --- | --- |
| `database/migrations/2026_09_23_000000_add_publish_gate.php` (create) | Columns on `search_user_chat`, the two tables, organization backfill |
| `app/Models/StrategyResource.php` (create) | One department's committed resources |
| `app/Models/StrategyResourceChange.php` (create) | One logged field change after publish |
| `app/Models/SearchUserChat.php` (modify) | New fillable/casts, `resources()`, `publisher()`, `isPublished()` |
| `app/Services/OrganizationService.php` (modify) | `canPublish(User): bool` |
| `app/Http/Controllers/Backend/AI/StrategyPublishController.php` (create) | `show`, `suggest`, `save`, `publish` |
| `routes/backend.php` (modify) | Four routes next to the `users-new-chat-*` group |
| `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php` (create) | Card styles + script |
| `resources/views/backend/pages/aiChat/users-new-chat.blade.php` (modify) | One `@include` line |
| `tests/Feature/PublishGateTest.php` (create) | All feature tests |

---

### Task 1: Schema, models, backfill

**Files:**
- Create: `database/migrations/2026_09_23_000000_add_publish_gate.php`
- Create: `app/Models/StrategyResource.php`
- Create: `app/Models/StrategyResourceChange.php`
- Modify: `app/Models/SearchUserChat.php`
- Test: `tests/Feature/PublishGateTest.php`

**Interfaces:**
- Produces: `SearchUserChat::resources(): HasMany<StrategyResource>`, `SearchUserChat::publisher(): BelongsTo<User>`, `SearchUserChat::isPublished(): bool`; `StrategyResource` fillable `search_user_chat_id, department_id, department_name, budget, fte, tools, notes, ai_suggestion` (`ai_suggestion` cast to array); `StrategyResource::changes(): HasMany<StrategyResourceChange>`; `StrategyResourceChange` fillable `strategy_resource_id, user_id, field, old_value, new_value`, with `created_at` only; the migration object exposes a public `backfill(): void`.

- [ ] **Step 1: Write the test file with shared helpers and the first failing tests**

Create `tests/Feature/PublishGateTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\SearchUserChat;
use App\Models\StrategyResource;
use App\Models\StrategyResourceChange;
use App\Models\User;
use App\Services\AI\AiProviderService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Publish gate (Features spec, phase 1): a strategy stays Draft until an
 * org-chart leader commits resources and publishes it.
 */
class PublishGateTest extends TestCase
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
     * An organization with an owner, a department head, a manager with one
     * report, and a plain member; departments Sales and Engineering.
     *
     * @return array{org: Organization, owner: User, head: User, manager: User, report: User, member: User, sales: Department, eng: Department}
     */
    private function world(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $make = fn (string $email, array $extra = []) => User::factory()->create(array_merge([
            'email' => $email, 'user_type' => 'customer', 'organization_id' => $org->id,
        ], $extra));

        $owner = $make('owner@acme.com');
        $org->forceFill(['owner_user_id' => $owner->id])->save();

        $head = $make('head@acme.com');
        $manager = $make('manager@acme.com');
        $report = $make('report@acme.com', ['manager_id' => $manager->id]);
        $member = $make('member@acme.com');

        $sales = Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E', 'head_user_id' => $head->id]);
        $eng = Department::create(['organization_id' => $org->id, 'name' => 'Engineering', 'color' => '#3B82F6']);

        return compact('org', 'owner', 'head', 'manager', 'report', 'member', 'sales', 'eng');
    }

    /** A finished strategy: pathway, scenario, brief and two role goals. */
    private function finishedChat(User $author): SearchUserChat
    {
        $chat = SearchUserChat::create([
            'user_id' => $author->id, 'status1' => 0,
            'selected_strategy' => 'Product-led security upsell',
            'selected_scenario' => 'Realistic Positive',
            'leadership_brief' => 'Convert mid-market accounts to enterprise tiers.',
        ]);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'VP of Sales', 'recommended_action' => 'Create an enterprise upgrade motion']);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'VP of Product', 'recommended_action' => 'Ship SOC2 controls']);

        return $chat;
    }

    /** Bind an AI provider that answers every generate() with $text. */
    private function fakeAi(string $text, int $status = 200): void
    {
        $ai = Mockery::mock(AiProviderService::class);
        $ai->shouldReceive('providerLabel')->andReturn('Fake');
        $ai->shouldReceive('generate')->andReturn(new ClientResponse(new PsrResponse($status, [], json_encode([
            'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
        ]))));
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('extractUsage')->andReturn([]);
        $ai->shouldReceive('recordChatTokens')->andReturn(0);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    public function test_a_new_strategy_starts_as_draft(): void
    {
        $w = $this->world();
        $chat = SearchUserChat::create(['user_id' => $w['owner']->id, 'status1' => 0]);

        $this->assertSame('draft', $chat->fresh()->status);
        $this->assertFalse($chat->fresh()->isPublished());
    }

    public function test_backfill_copies_the_authors_organization_onto_existing_strategies(): void
    {
        $w = $this->world();
        $chat = SearchUserChat::create(['user_id' => $w['member']->id, 'status1' => 0]);
        DB::table('search_user_chat')->where('id', $chat->id)->update(['organization_id' => null]);

        $migration = require database_path('migrations/2026_09_23_000000_add_publish_gate.php');
        $migration->backfill();

        $this->assertSame($w['org']->id, (int) $chat->fresh()->organization_id);
        $this->assertSame('draft', $chat->fresh()->status);
    }

    public function test_resources_and_their_changes_hang_off_the_strategy(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['owner']);
        $row = $chat->resources()->create([
            'department_id' => $w['sales']->id, 'department_name' => 'Sales',
            'budget' => 50000, 'fte' => 1.5, 'ai_suggestion' => ['budget' => 40000],
        ]);
        $row->changes()->create(['user_id' => $w['owner']->id, 'field' => 'budget', 'old_value' => '40000.00', 'new_value' => '50000.00']);

        $this->assertSame(['budget' => 40000], $chat->resources()->first()->ai_suggestion);
        $this->assertSame(1, StrategyResourceChange::count());

        $chat->delete();
        $this->assertSame(0, StrategyResource::count());
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PublishGateTest`
Expected: FAIL. `Class "App\Models\StrategyResource" not found`, or `status` is null.

- [ ] **Step 3: Write the migration**

Create `database/migrations/2026_09_23_000000_add_publish_gate.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Publish gate (Features spec, phase 1): a strategy is Draft until a leader
     * commits resources and publishes it to their organization.
     */
    public function up(): void
    {
        Schema::table('search_user_chat', function (Blueprint $table) {
            if (! Schema::hasColumn('search_user_chat', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->index();
            }
            if (! Schema::hasColumn('search_user_chat', 'status')) {
                $table->string('status', 20)->default('draft');
            }
            if (! Schema::hasColumn('search_user_chat', 'published_by')) {
                $table->unsignedBigInteger('published_by')->nullable();
            }
            if (! Schema::hasColumn('search_user_chat', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }
        });

        $this->backfill();

        // search_user_chat.id differs in type between the legacy MySQL table and
        // the SQLite test table; the foreign key must match it exactly.
        [$isUnsigned, $isBigInt] = [true, true];
        if (DB::connection()->getDriverName() === 'mysql') {
            $info = DB::select("SHOW COLUMNS FROM `search_user_chat` LIKE 'id'");
            if (! empty($info)) {
                $type = strtolower($info[0]->Type);
                $isUnsigned = str_contains($type, 'unsigned');
                $isBigInt = str_contains($type, 'bigint');
            }
        }

        Schema::create('strategy_resources', function (Blueprint $table) use ($isUnsigned, $isBigInt) {
            $table->id();
            $column = match (true) {
                $isUnsigned && $isBigInt => 'unsignedBigInteger',
                $isUnsigned => 'unsignedInteger',
                $isBigInt => 'bigInteger',
                default => 'integer',
            };
            $table->{$column}('search_user_chat_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('department_name');
            $table->decimal('budget', 12, 2)->nullable();
            $table->decimal('fte', 6, 2)->nullable();
            $table->text('tools')->nullable();
            $table->text('notes')->nullable();
            $table->json('ai_suggestion')->nullable();
            $table->timestamps();

            $table->foreign('search_user_chat_id')->references('id')->on('search_user_chat')->onDelete('cascade');
            $table->index('department_id');
        });

        Schema::create('strategy_resource_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_resource_id')->constrained('strategy_resources')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('field', 20);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /** Existing strategies belong to their author's organization. */
    public function backfill(): void
    {
        DB::statement('UPDATE search_user_chat SET organization_id = '
            .'(SELECT organization_id FROM users WHERE users.id = search_user_chat.user_id) '
            .'WHERE organization_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_resource_changes');
        Schema::dropIfExists('strategy_resources');
        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->dropIndex(['organization_id']);
            $table->dropColumn(['organization_id', 'status', 'published_by', 'published_at']);
        });
    }
};
```

The `match` covers the same four cases as `2026_06_22_000000_create_expected_states_table.php`.

- [ ] **Step 4: Write the two models**

Create `app/Models/StrategyResource.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One department's committed resources for a strategy.
 *
 * @property int $id
 * @property int $search_user_chat_id
 * @property int|null $department_id
 * @property string $department_name
 * @property string|null $budget
 * @property string|null $fte
 * @property string|null $tools
 * @property string|null $notes
 * @property array<string, mixed>|null $ai_suggestion
 */
class StrategyResource extends Model
{
    protected $fillable = [
        'search_user_chat_id',
        'department_id',
        'department_name',
        'budget',
        'fte',
        'tools',
        'notes',
        'ai_suggestion',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'fte' => 'decimal:2',
        'ai_suggestion' => 'array',
    ];

    /** @return BelongsTo<SearchUserChat, $this> */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(SearchUserChat::class, 'search_user_chat_id');
    }

    /** @return HasMany<StrategyResourceChange, $this> */
    public function changes(): HasMany
    {
        return $this->hasMany(StrategyResourceChange::class);
    }
}
```

Create `app/Models/StrategyResourceChange.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One field changed on a published strategy's resources: who, when, old, new.
 *
 * @property int $id
 * @property int $strategy_resource_id
 * @property int $user_id
 * @property string $field
 * @property string|null $old_value
 * @property string|null $new_value
 */
class StrategyResourceChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'strategy_resource_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<StrategyResource, $this> */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(StrategyResource::class, 'strategy_resource_id');
    }
}
```

- [ ] **Step 5: Extend `SearchUserChat`**

In `app/Models/SearchUserChat.php`:
- append to `$fillable`: `'organization_id', 'status', 'published_by', 'published_at',`
- add `'published_at' => 'datetime',` to `$casts`
- add the in-memory default and three methods below `messages()`:

```php
    /** Matches the column default, so a freshly created model reads 'draft' too. */
    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<StrategyResource, $this> */
    public function resources()
    {
        return $this->hasMany(StrategyResource::class, 'search_user_chat_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this> */
    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
```

Put `protected $attributes` next to the other `protected` properties at the top of the class.

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter=PublishGateTest`
Expected: 3 passed.

- [ ] **Step 7: Run the full suite to catch collateral damage**

Run: `php artisan test`
Expected: same pass count as before this task, plus 3.

- [ ] **Step 8: Commit**

```bash
vendor/bin/pint app/Models/StrategyResource.php app/Models/StrategyResourceChange.php app/Models/SearchUserChat.php database/migrations/2026_09_23_000000_add_publish_gate.php tests/Feature/PublishGateTest.php
git add app/Models/StrategyResource.php app/Models/StrategyResourceChange.php app/Models/SearchUserChat.php database/migrations/2026_09_23_000000_add_publish_gate.php tests/Feature/PublishGateTest.php
git commit -m "feat(publish-gate): draft status and resource tables on strategies"
```

---

### Task 2: Who can publish

**Files:**
- Modify: `app/Services/OrganizationService.php`
- Test: `tests/Feature/PublishGateTest.php`

**Interfaces:**
- Consumes: `world()` from Task 1.
- Produces: `OrganizationService::canPublish(User $user): bool`.

- [ ] **Step 1: Write the failing test**

Append to `PublishGateTest`:

```php
    public function test_owner_department_head_and_managers_can_publish_but_plain_members_cannot(): void
    {
        $w = $this->world();
        $orgs = app(\App\Services\OrganizationService::class);

        $this->assertTrue($orgs->canPublish($w['owner']), 'owner');
        $this->assertTrue($orgs->canPublish($w['head']), 'department head');
        $this->assertTrue($orgs->canPublish($w['manager']), 'has a direct report');
        $this->assertFalse($orgs->canPublish($w['report']), 'has a manager, no reports');
        $this->assertFalse($orgs->canPublish($w['member']), 'plain member');
    }

    public function test_a_user_without_an_organization_cannot_publish(): void
    {
        $loner = User::factory()->create(['email' => 'solo@example.com', 'user_type' => 'customer', 'organization_id' => null]);

        $this->assertFalse(app(\App\Services\OrganizationService::class)->canPublish($loner));
    }

    public function test_heading_a_department_in_another_organization_does_not_count(): void
    {
        $w = $this->world();
        $other = Organization::create(['domain' => 'globex.com', 'name' => 'Globex']);
        Department::create(['organization_id' => $other->id, 'name' => 'Ops', 'color' => '#EF4444', 'head_user_id' => $w['member']->id]);

        $this->assertFalse(app(\App\Services\OrganizationService::class)->canPublish($w['member']));
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=PublishGateTest`
Expected: FAIL with `Call to undefined method App\Services\OrganizationService::canPublish()`.

- [ ] **Step 3: Implement**

Add to `app/Services/OrganizationService.php` (add `use App\Models\Department;` to the imports):

```php
    /**
     * May this user publish a strategy to their organization? The org chart
     * decides, not seniority levels: the owner, a department head, or anyone
     * with a direct report.
     */
    public function canPublish(User $user): bool
    {
        $orgId = $user->organization_id;
        if (! $orgId) {
            return false;
        }

        return Organization::where('id', $orgId)->where('owner_user_id', $user->id)->exists()
            || Department::where('organization_id', $orgId)->where('head_user_id', $user->id)->exists()
            || User::where('organization_id', $orgId)->where('manager_id', $user->id)->exists();
    }
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test --filter=PublishGateTest`
Expected: 6 passed.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint app/Services/OrganizationService.php tests/Feature/PublishGateTest.php
git add app/Services/OrganizationService.php tests/Feature/PublishGateTest.php
git commit -m "feat(publish-gate): decide who can publish from the org chart"
```

---

### Task 3: Controller skeleton — show and Draft save

**Files:**
- Create: `app/Http/Controllers/Backend/AI/StrategyPublishController.php`
- Modify: `routes/backend.php` (import at line 9; routes after the `users-new-chat-activate-intervention` route)
- Test: `tests/Feature/PublishGateTest.php`

**Interfaces:**
- Consumes: `canPublish()` (Task 2), `SearchUserChat::resources()`, `isPublished()`, `publisher()` (Task 1).
- Produces: routes `users-new-chat-resources.show` (GET, `{chat}`), `users-new-chat-resources-save.index` (POST: `chat_id`, `rows[]` with `id?`, `department_id?`, `department_name`, `budget?`, `fte?`, `tools?`, `notes?`). Both return `payload()`:
  `{status, is_publisher, can_publish, ready, published_by, published_at, currency, departments:[{id,name}], rows:[{id,department_id,department_name,budget,fte,tools,notes,ai_suggestion}], changes:[{field,old_value,new_value,user,at,department_name}]}`.
  Private helpers used by later tasks: `ownChat(Request, $chatId): ?SearchUserChat`, `departmentsFor(User): Collection`, `payload(SearchUserChat, User): array`, `denied(): JsonResponse`.

- [ ] **Step 1: Write the failing tests**

Append to `PublishGateTest`:

```php
    public function test_show_returns_the_card_state_for_the_author(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['head']);

        $this->actingAs($w['head'])->getJson(route('users-new-chat-resources.show', ['chat' => $chat->id]))
            ->assertOk()
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('can_publish', true)
            ->assertJsonPath('ready', true)
            ->assertJsonPath('departments.0.name', 'Engineering')
            ->assertJsonPath('departments.1.name', 'Sales')
            ->assertJsonCount(0, 'rows');
    }

    public function test_show_refuses_someone_elses_strategy(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['owner']);

        $this->actingAs($w['member'])->getJson(route('users-new-chat-resources.show', ['chat' => $chat->id]))
            ->assertForbidden();
    }

    public function test_draft_save_replaces_the_rows_and_logs_nothing(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['member']);
        $old = $chat->resources()->create(['department_id' => $w['eng']->id, 'department_name' => 'Engineering', 'budget' => 10]);

        $this->actingAs($w['member'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id,
            'rows' => [
                ['department_id' => $w['sales']->id, 'department_name' => 'ignored', 'budget' => 50000, 'fte' => 1.5, 'tools' => 'CRM seats', 'notes' => 'From Q3 brand budget'],
            ],
        ])->assertOk()->assertJsonCount(1, 'rows')->assertJsonPath('rows.0.department_name', 'Sales');

        $this->assertNull(StrategyResource::find($old->id));
        $this->assertSame(0, StrategyResourceChange::count());
    }

    public function test_draft_save_keeps_the_ai_suggestion_on_an_edited_row(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['member']);
        $row = $chat->resources()->create(['department_id' => $w['sales']->id, 'department_name' => 'Sales', 'budget' => 40000, 'ai_suggestion' => ['budget' => 40000]]);

        $this->actingAs($w['member'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id,
            'rows' => [['id' => $row->id, 'department_id' => $w['sales']->id, 'department_name' => 'Sales', 'budget' => 45000]],
        ])->assertOk();

        $this->assertSame('45000.00', $row->fresh()->budget);
        $this->assertSame(['budget' => 40000], $row->fresh()->ai_suggestion);
    }

    public function test_save_refuses_a_department_from_another_organization(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['member']);
        $other = Organization::create(['domain' => 'globex.com', 'name' => 'Globex']);
        $foreign = Department::create(['organization_id' => $other->id, 'name' => 'Ops', 'color' => '#EF4444']);

        $this->actingAs($w['member'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id,
            'rows' => [['department_id' => $foreign->id, 'department_name' => 'Ops', 'budget' => 1]],
        ])->assertStatus(422);

        $this->assertSame(0, StrategyResource::count());
    }

    public function test_save_refuses_negative_amounts(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['member']);

        $this->actingAs($w['member'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id,
            'rows' => [['department_id' => null, 'department_name' => 'Whole organization', 'budget' => -5]],
        ])->assertStatus(422);
    }

    public function test_save_refuses_someone_elses_strategy(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['owner']);

        $this->actingAs($w['member'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id, 'rows' => [],
        ])->assertForbidden();
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=PublishGateTest`
Expected: FAIL with `Route [users-new-chat-resources.show] not defined.`

- [ ] **Step 3: Write the controller (show + save, Draft branch only)**

Create `app/Http/Controllers/Backend/AI/StrategyPublishController.php`:

```php
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

    // -------------------------------------------------------------------------

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
```

The `$ai` and `$docs` constructor arguments are unused until Task 4. They're injected now so the constructor doesn't change later.

- [ ] **Step 4: Register the routes**

In `routes/backend.php`, add below line 9 (`use App\Http\Controllers\Backend\AI\AiChatController;`):

```php
use App\Http\Controllers\Backend\AI\StrategyPublishController;
```

Directly after the `users-new-chat-activate-intervention` route, add:

```php
                // publish gate: commit resources, then publish the strategy to the organization
                Route::get('/users-new-chat-resources/{chat}', [StrategyPublishController::class, 'show'])->name('users-new-chat-resources.show');
                Route::post('/users-new-chat-resources-suggest', [StrategyPublishController::class, 'suggest'])->name('users-new-chat-resources-suggest.index');
                Route::post('/users-new-chat-resources-save', [StrategyPublishController::class, 'save'])->name('users-new-chat-resources-save.index');
                Route::post('/users-new-chat-publish', [StrategyPublishController::class, 'publish'])->name('users-new-chat-publish.index');
```

`suggest` and `publish` don't exist until Tasks 4 and 5. Route registration is lazy, so only calling them fails in the meantime, and no test calls them before then.

- [ ] **Step 5: Run to verify they pass**

Run: `php artisan test --filter=PublishGateTest`
Expected: 13 passed.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/AI/StrategyPublishController.php routes/backend.php tests/Feature/PublishGateTest.php
git add app/Http/Controllers/Backend/AI/StrategyPublishController.php routes/backend.php tests/Feature/PublishGateTest.php
git commit -m "feat(publish-gate): show and save a strategy's draft resources"
```

---

### Task 4: AI resource suggestions

**Files:**
- Modify: `app/Http/Controllers/Backend/AI/StrategyPublishController.php`
- Test: `tests/Feature/PublishGateTest.php`

**Interfaces:**
- Consumes: `ownChat`, `denied`, `departmentsFor`, `payload`, `text`, `WHOLE_ORG` (Task 3); `fakeAi()` (Task 1).
- Produces: `POST users-new-chat-resources-suggest.index` (`chat_id`) returning `payload()`, or `{error}` with status 409 (published), 422 (not finished), 502 (AI failure or nothing usable).

- [ ] **Step 1: Write the failing tests**

Append to `PublishGateTest`:

```php
    public function test_suggest_saves_one_row_per_matching_department_and_drops_unknown_ones(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['head']);
        $this->fakeAi(json_encode(['rows' => [
            ['department' => 'sales', 'budget' => '$50k', 'fte' => '2 FTE', 'tools' => ['CRM seats', 'CPQ'], 'rationale' => 'Upgrade motion'],
            ['department' => 'Engineering', 'budget' => '1,200,000', 'fte' => 4, 'tools' => 'SOC2 tooling', 'rationale' => 'Compliance'],
            ['department' => 'Legal', 'budget' => 10, 'fte' => 1, 'tools' => '', 'rationale' => 'Not a department here'],
        ]]));

        $this->actingAs($w['head'])->postJson(route('users-new-chat-resources-suggest.index'), ['chat_id' => $chat->id])
            ->assertOk()->assertJsonCount(2, 'rows');

        $sales = StrategyResource::where('department_id', $w['sales']->id)->first();
        $this->assertSame('50000.00', $sales->budget);
        $this->assertSame('2.00', $sales->fte);
        $this->assertSame('CRM seats, CPQ', $sales->tools);
        $this->assertSame('Upgrade motion', $sales->ai_suggestion['rationale']);
        $this->assertSame('1200000.00', StrategyResource::where('department_id', $w['eng']->id)->value('budget'));
    }

    public function test_suggest_replaces_earlier_draft_rows(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['head']);
        $chat->resources()->create(['department_id' => $w['eng']->id, 'department_name' => 'Engineering', 'budget' => 1]);
        $this->fakeAi(json_encode(['rows' => [['department' => 'Sales', 'budget' => 5, 'fte' => 1, 'tools' => 'x', 'rationale' => 'y']]]));

        $this->actingAs($w['head'])->postJson(route('users-new-chat-resources-suggest.index'), ['chat_id' => $chat->id])->assertOk();

        $this->assertSame(['Sales'], StrategyResource::pluck('department_name')->all());
    }

    public function test_an_organization_without_departments_gets_one_whole_organization_row(): void
    {
        $loner = User::factory()->create(['email' => 'solo@example.com', 'user_type' => 'customer', 'organization_id' => null]);
        $chat = $this->finishedChat($loner);
        $this->fakeAi(json_encode(['rows' => [['department' => 'Whole organization', 'budget' => 9000, 'fte' => 1, 'tools' => 'x', 'rationale' => 'y']]]));

        $this->actingAs($loner)->postJson(route('users-new-chat-resources-suggest.index'), ['chat_id' => $chat->id])
            ->assertOk()
            ->assertJsonPath('rows.0.department_name', 'Whole organization')
            ->assertJsonPath('rows.0.department_id', null)
            ->assertJsonPath('rows.0.budget', 9000.0);
    }

    public function test_suggest_needs_a_finished_wizard(): void
    {
        $w = $this->world();
        $chat = SearchUserChat::create(['user_id' => $w['head']->id, 'status1' => 0]);
        $this->fakeAi('{}');

        $this->actingAs($w['head'])->postJson(route('users-new-chat-resources-suggest.index'), ['chat_id' => $chat->id])
            ->assertStatus(422);
    }

    public function test_suggest_reports_an_ai_failure_without_saving(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['head']);
        $this->fakeAi('not json at all', 500);

        $this->actingAs($w['head'])->postJson(route('users-new-chat-resources-suggest.index'), ['chat_id' => $chat->id])
            ->assertStatus(502);
        $this->assertSame(0, StrategyResource::count());
    }

    public function test_suggest_reports_unparseable_json_as_a_failure(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['head']);
        $this->fakeAi('Sure! Here are some ideas.');

        $this->actingAs($w['head'])->postJson(route('users-new-chat-resources-suggest.index'), ['chat_id' => $chat->id])
            ->assertStatus(502);
    }

    public function test_suggest_refuses_a_published_strategy(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['head']);
        $chat->forceFill(['status' => 'published', 'published_by' => $w['head']->id, 'published_at' => now()])->save();
        $this->fakeAi('{}');

        $this->actingAs($w['head'])->postJson(route('users-new-chat-resources-suggest.index'), ['chat_id' => $chat->id])
            ->assertStatus(409);
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=PublishGateTest`
Expected: FAIL. The new tests error with `Method ... suggest does not exist`.

- [ ] **Step 3: Implement `suggest` and its helpers**

Add to `StrategyPublishController` (the public method goes after `save`, the helpers in the private section):

```php
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
```

Private helpers:

```php
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
```

- [ ] **Step 4: Run to verify they pass**

Run: `php artisan test --filter=PublishGateTest`
Expected: 20 passed.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/AI/StrategyPublishController.php tests/Feature/PublishGateTest.php
git add app/Http/Controllers/Backend/AI/StrategyPublishController.php tests/Feature/PublishGateTest.php
git commit -m "feat(publish-gate): AI-suggested resources per department"
```

---

### Task 5: Publish

**Files:**
- Modify: `app/Http/Controllers/Backend/AI/StrategyPublishController.php`
- Test: `tests/Feature/PublishGateTest.php`

**Interfaces:**
- Consumes: `ownChat`, `denied`, `payload` (Task 3); `canPublish` (Task 2).
- Produces: `POST users-new-chat-publish.index` (`chat_id`) returning `payload()`, or 403 / 409 / 422.

- [ ] **Step 1: Write the failing tests**

Append to `PublishGateTest`:

```php
    private function withRow(SearchUserChat $chat, Department $dept): SearchUserChat
    {
        $chat->resources()->create(['department_id' => $dept->id, 'department_name' => $dept->name, 'budget' => 50000, 'fte' => 2]);

        return $chat;
    }

    public function test_a_leader_publishes_their_strategy(): void
    {
        $w = $this->world();
        $chat = $this->withRow($this->finishedChat($w['manager']), $w['sales']);
        DB::table('search_user_chat')->where('id', $chat->id)->update(['organization_id' => null]);

        $this->actingAs($w['manager'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])
            ->assertOk()
            ->assertJsonPath('status', 'published')
            ->assertJsonPath('is_publisher', true)
            ->assertJsonPath('published_by', $w['manager']->name);

        $chat = $chat->fresh();
        $this->assertTrue($chat->isPublished());
        $this->assertSame($w['manager']->id, (int) $chat->published_by);
        $this->assertNotNull($chat->published_at);
        $this->assertSame($w['org']->id, (int) $chat->organization_id);
    }

    public function test_a_plain_member_cannot_publish(): void
    {
        $w = $this->world();
        $chat = $this->withRow($this->finishedChat($w['member']), $w['sales']);

        $this->actingAs($w['member'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])
            ->assertForbidden();
        $this->assertFalse($chat->fresh()->isPublished());
    }

    public function test_a_leader_cannot_publish_someone_elses_strategy(): void
    {
        $w = $this->world();
        $chat = $this->withRow($this->finishedChat($w['member']), $w['sales']);

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])
            ->assertForbidden();
        $this->assertFalse($chat->fresh()->isPublished());
    }

    public function test_publishing_twice_is_refused_and_keeps_the_first_timestamp(): void
    {
        $w = $this->world();
        $chat = $this->withRow($this->finishedChat($w['owner']), $w['sales']);

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])->assertOk();
        $first = $chat->fresh()->published_at->toIso8601String();

        $this->travel(5)->minutes();
        $this->actingAs($w['owner'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])
            ->assertStatus(409);
        $this->assertSame($first, $chat->fresh()->published_at->toIso8601String());
    }

    public function test_publishing_with_no_resources_is_refused(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['owner']);

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])
            ->assertStatus(422);
        $this->assertFalse($chat->fresh()->isPublished());
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=PublishGateTest`
Expected: FAIL with `Method ... publish does not exist`.

- [ ] **Step 3: Implement `publish`**

Add to `StrategyPublishController` after `suggest`:

```php
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
            $chat = SearchUserChat::whereKey($request->input('chat_id'))->lockForUpdate()->first();
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
```

SQLite ignores `lockForUpdate()`, and MySQL honours it. The 409 test passes on both because the second request reads the saved status.

- [ ] **Step 4: Run to verify they pass**

Run: `php artisan test --filter=PublishGateTest`
Expected: 25 passed.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/AI/StrategyPublishController.php tests/Feature/PublishGateTest.php
git add app/Http/Controllers/Backend/AI/StrategyPublishController.php tests/Feature/PublishGateTest.php
git commit -m "feat(publish-gate): commit resources and publish a strategy"
```

---

### Task 6: Amend published resources with a change log

**Files:**
- Modify: `app/Http/Controllers/Backend/AI/StrategyPublishController.php` (`save`)
- Test: `tests/Feature/PublishGateTest.php`

**Interfaces:**
- Consumes: `save`, `withRow` (Task 5 test helper), `payload`.
- Produces: on a published strategy, `save` edits existing rows only. It returns 403 for anyone but the publisher and 422 if rows are added or removed, and writes one `StrategyResourceChange` per changed field (`budget`, `fte`, `tools`, `notes`).

- [ ] **Step 1: Write the failing tests**

Append to `PublishGateTest`:

```php
    private function published(User $author, Department $dept): SearchUserChat
    {
        $chat = $this->withRow($this->finishedChat($author), $dept);
        $chat->forceFill(['status' => 'published', 'published_by' => $author->id, 'published_at' => now()])->save();

        return $chat;
    }

    public function test_amending_a_published_strategy_logs_each_changed_field(): void
    {
        $w = $this->world();
        $chat = $this->published($w['owner'], $w['sales']);
        $row = $chat->resources()->first();

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id,
            'rows' => [['id' => $row->id, 'department_id' => $w['sales']->id, 'department_name' => 'Sales', 'budget' => 60000, 'fte' => 2, 'tools' => 'CPQ', 'notes' => null]],
        ])->assertOk()->assertJsonCount(2, 'changes');

        $this->assertSame('60000.00', $row->fresh()->budget);
        $budget = StrategyResourceChange::where('field', 'budget')->first();
        $this->assertSame('50000.00', $budget->old_value);
        $this->assertSame('60000.00', $budget->new_value);
        $this->assertSame($w['owner']->id, (int) $budget->user_id);
        $this->assertSame(['budget', 'tools'], StrategyResourceChange::orderBy('field')->pluck('field')->all());
    }

    public function test_amending_with_unchanged_values_logs_nothing(): void
    {
        $w = $this->world();
        $chat = $this->published($w['owner'], $w['sales']);
        $row = $chat->resources()->first();

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id,
            'rows' => [['id' => $row->id, 'department_id' => $w['sales']->id, 'department_name' => 'Sales', 'budget' => '50000', 'fte' => 2.0, 'tools' => '', 'notes' => '  ']],
        ])->assertOk();

        $this->assertSame(0, StrategyResourceChange::count());
    }

    public function test_rows_cannot_be_added_or_removed_after_publishing(): void
    {
        $w = $this->world();
        $chat = $this->published($w['owner'], $w['sales']);
        $row = $chat->resources()->first();

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id,
            'rows' => [
                ['id' => $row->id, 'department_id' => $w['sales']->id, 'department_name' => 'Sales', 'budget' => 50000],
                ['department_id' => $w['eng']->id, 'department_name' => 'Engineering', 'budget' => 1],
            ],
        ])->assertStatus(422);

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id, 'rows' => [],
        ])->assertStatus(422);

        $this->assertSame(1, $chat->resources()->count());
    }

    public function test_only_the_publisher_may_amend(): void
    {
        $w = $this->world();
        $chat = $this->published($w['owner'], $w['sales']);
        // Same author, but the record says someone else published it.
        $chat->forceFill(['published_by' => $w['head']->id])->save();
        $row = $chat->resources()->first();

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id,
            'rows' => [['id' => $row->id, 'department_id' => $w['sales']->id, 'department_name' => 'Sales', 'budget' => 1]],
        ])->assertForbidden();
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=PublishGateTest`
Expected: FAIL. The add/remove test gets 200 instead of 422, and the logging test finds 0 changes.

- [ ] **Step 3: Implement the Published branch in `save`**

In `save`, directly before the existing `DB::transaction(function () use ($chat, $rows) {`, insert:

```php
        if ($chat->isPublished()) {
            return $this->amend($chat, $user, $rows);
        }
```

Add the private method:

```php
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
```

`$rows` already went through `text()` for tools/notes in `save`, and the department is taken from the stored row. After publishing, a submitted department change is ignored, because department isn't one of the amended fields.

- [ ] **Step 4: Run to verify they pass**

Run: `php artisan test --filter=PublishGateTest`
Expected: 29 passed.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/AI/StrategyPublishController.php tests/Feature/PublishGateTest.php
git add app/Http/Controllers/Backend/AI/StrategyPublishController.php tests/Feature/PublishGateTest.php
git commit -m "feat(publish-gate): log every resource change after publishing"
```

---

### Task 7: The Commit Resources & Publish card

**Files:**
- Create: `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php`
- Modify: `resources/views/backend/pages/aiChat/users-new-chat.blade.php` (one line before the final `@endsection`)
- Test: `tests/Feature/PublishGateTest.php` (page renders the mount script), plus a manual check on staging

**Interfaces:**
- Consumes: the four routes and the `payload()` shape from Tasks 3–6; `$id` (the chat id), which is already in the view's scope.
- Produces: `#publish-gate-card`, inserted after the last `.leadership-alignment-brief` on the page.

- [ ] **Step 1: Write the failing test**

Append to `PublishGateTest`:

```php
    public function test_the_chat_page_carries_the_publish_card_script(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['owner']);

        $this->actingAs($w['owner'])->get('/dashboard/users-new-chat/'.$chat->id)
            ->assertOk()
            ->assertSee('publish-gate-card', false)
            ->assertSee(route('users-new-chat-resources.show', ['chat' => $chat->id]), false);
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=test_the_chat_page_carries_the_publish_card_script`
Expected: FAIL. `publish-gate-card` isn't found in the response.

- [ ] **Step 3: Write the include**

Create `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php`:

```blade
{{-- Publish gate (Features spec, phase 1): Commit Resources & Publish card,
     mounted under the Leadership Alignment Brief once it exists. --}}
<style>
    .pg-card { border: 1px solid #36839b; border-radius: 10px; padding: 16px; background: #e7f3f7; }
    .pg-card h5 { color: #2c6d82; margin-bottom: 4px; }
    .pg-card .pg-sub { font-size: 13px; color: #555; margin-bottom: 12px; }
    .pg-card table input, .pg-card table textarea, .pg-card table select { min-width: 90px; font-size: 13px; }
    .pg-card .pg-hint { font-size: 12px; color: #6c757d; }
    .pg-btn-primary { background: #36839b; border-color: #36839b; color: #fff; }
    .pg-btn-primary:hover { background: #2c6d82; border-color: #2c6d82; color: #fff; }
    .pg-btn-confirm { background: #ec883f; border-color: #ec883f; color: #fff; }
    .pg-published { background: #fbf2ea; border-left: 4px solid #ec883f; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; }
    .pg-error { color: #b42318; font-size: 13px; margin: 8px 0; }
    .pg-history { font-size: 12px; margin-top: 12px; }
</style>
<script>
(function () {
    const urls = {
        show: '{{ route('users-new-chat-resources.show', ['chat' => $id]) }}',
        suggest: '{{ route('users-new-chat-resources-suggest.index') }}',
        save: '{{ route('users-new-chat-resources-save.index') }}',
        publish: '{{ route('users-new-chat-publish.index') }}',
    };
    const chatId = {{ (int) $id }};
    const csrf = '{{ csrf_token() }}';
    let state = null;
    let busy = false;
    let error = '';
    let confirmPublish = false;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const money = v => (v === null || v === undefined || v === '') ? '—' : state.currency + Number(v).toLocaleString();

    async function call(url, body) {
        const res = await fetch(url, {
            method: body ? 'POST' : 'GET',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: body ? JSON.stringify(body) : undefined,
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
            const msg = json.error || (json.errors ? Object.values(json.errors)[0][0] : '') || json.message || 'Something went wrong.';
            throw Object.assign(new Error(msg), { status: res.status });
        }
        return json;
    }

    function card() { return document.getElementById('publish-gate-card'); }

    // The brief is rendered from the DB on load, or injected after Finish, and the
    // wizard sometimes rebuilds its DOM; re-mount whenever the card goes missing.
    function mount() {
        const briefs = document.querySelectorAll('.leadership-alignment-brief');
        if (!briefs.length || card()) return;
        const el = document.createElement('div');
        el.id = 'publish-gate-card';
        el.className = 'pg-card mt-3';
        briefs[briefs.length - 1].insertAdjacentElement('afterend', el);
        if (state) { render(); } else { load(); }
    }

    async function load() {
        try {
            state = await call(urls.show);
            error = '';
            if (state.status === 'draft' && state.rows.length === 0 && state.ready) {
                return suggest();
            }
        } catch (e) { error = e.message; }
        render();
    }

    async function suggest() {
        busy = true; error = ''; render();
        try {
            state = await call(urls.suggest, { chat_id: chatId });
        } catch (e) {
            error = e.message;
            if (state && state.rows.length === 0) {
                // Let the author type figures in by hand while the AI is unavailable.
                state.rows = (state.departments.length ? state.departments : [{ id: null, name: 'Whole organization' }])
                    .map(d => ({ id: null, department_id: d.id, department_name: d.name, budget: null, fte: null, tools: '', notes: '' }));
            }
        }
        busy = false; render();
    }

    function readRows() {
        return Array.from(card().querySelectorAll('tr[data-row]')).map(tr => {
            const v = name => { const f = tr.querySelector(`[name="${name}"]`); return f ? f.value : null; };
            const num = name => { const x = v(name); return x === '' || x === null ? null : Number(x); };
            const dept = v('department_id');
            return {
                id: tr.dataset.id ? Number(tr.dataset.id) : null,
                department_id: dept === '' || dept === null ? null : Number(dept),
                department_name: tr.dataset.name,
                budget: num('budget'), fte: num('fte'), tools: v('tools'), notes: v('notes'),
            };
        });
    }

    async function save() {
        busy = true; error = ''; render();
        try { state = await call(urls.save, { chat_id: chatId, rows: readRows() }); }
        catch (e) { error = e.message; }
        busy = false; render();
        return !error;
    }

    async function publish() {
        if (!confirmPublish) { confirmPublish = true; render(); return; }
        confirmPublish = false;
        if (!(await save())) return;
        busy = true; render();
        try { state = await call(urls.publish, { chat_id: chatId }); }
        catch (e) { error = e.message; }
        busy = false; render();
    }

    function addRow() {
        const used = new Set(readRows().map(r => r.department_id));
        const pick = card().querySelector('#pg-add-dept').value;
        const d = pick === '' ? { id: null, name: 'Whole organization' } : state.departments.find(x => String(x.id) === pick);
        if (!d || used.has(d.id)) return;
        state.rows = readRows().concat([{ id: null, department_id: d.id, department_name: d.name, budget: null, fte: null, tools: '', notes: '' }]);
        render();
    }

    function removeRow(i) { state.rows = readRows().filter((_, j) => j !== i); render(); }

    function rowHtml(r, i, editable, fixedSet) {
        const ai = r.ai_suggestion || null;
        const aiHint = ai ? `<div class="pg-hint">AI: ${esc(money(ai.budget))} · ${esc(ai.fte ?? '—')} FTE${ai.rationale ? ' — ' + esc(ai.rationale) : ''}</div>` : '';
        const cell = (name, value, type) => editable
            ? (type === 'textarea'
                ? `<textarea class="form-control form-control-sm" name="${name}" rows="1">${esc(value)}</textarea>`
                : `<input class="form-control form-control-sm" type="number" min="0" step="any" name="${name}" value="${esc(value)}">`)
            : esc(name === 'budget' ? money(value) : (value ?? '—'));
        return `<tr data-row data-id="${r.id ?? ''}" data-name="${esc(r.department_name)}">
            <td><strong>${esc(r.department_name)}</strong><input type="hidden" name="department_id" value="${r.department_id ?? ''}">${aiHint}</td>
            <td>${cell('budget', r.budget)}</td>
            <td>${cell('fte', r.fte)}</td>
            <td>${cell('tools', r.tools, 'textarea')}</td>
            <td>${cell('notes', r.notes, 'textarea')}</td>
            <td>${editable && !fixedSet ? `<button type="button" class="btn btn-sm btn-link text-danger" data-remove="${i}" title="Remove">✕</button>` : ''}</td>
        </tr>`;
    }

    function render() {
        const el = card();
        if (!el) return;
        if (!state) {
            el.innerHTML = `<h5>Commit Resources &amp; Publish</h5>${error ? `<div class="pg-error">${esc(error)}</div>` : '<div class="pg-sub">Loading…</div>'}`;
            return;
        }

        const published = state.status === 'published';
        const editable = !published || state.is_publisher;
        const header = published
            ? `<div class="pg-published">Published by <strong>${esc(state.published_by)}</strong> on ${esc(new Date(state.published_at).toLocaleString())}. ${state.is_publisher ? 'You can still change amounts; every change is logged.' : ''}</div>`
            : `<div class="pg-sub">Review the resources each department needs. This strategy stays private until it is published.</div>`;

        const used = new Set(state.rows.map(r => r.department_id));
        const options = [{ id: '', name: 'Whole organization', key: null }]
            .concat(state.departments.map(d => ({ id: d.id, name: d.name, key: d.id })))
            .filter(o => !used.has(o.key))
            .map(o => `<option value="${o.id}">${esc(o.name)}</option>`).join('');

        const history = state.changes.length
            ? `<div class="pg-history"><strong>Change history</strong><ul class="mb-0">${state.changes.map(c =>
                `<li>${esc(new Date(c.at).toLocaleString())} — ${esc(c.user)} changed ${esc(c.department_name)} ${esc(c.field)} from ${esc(c.old_value ?? '—')} to ${esc(c.new_value ?? '—')}</li>`).join('')}</ul></div>`
            : '';

        const publishBtn = published ? '' : (state.can_publish
            ? `<button type="button" class="btn btn-sm ${confirmPublish ? 'pg-btn-confirm' : 'pg-btn-primary'}" data-act="publish" ${busy ? 'disabled' : ''}>${confirmPublish ? 'Click again to publish to your organization' : '🔒 Commit Resources &amp; Publish'}</button>`
            : `<button type="button" class="btn btn-sm pg-btn-primary" disabled>🔒 Commit Resources &amp; Publish</button>
               <div class="pg-hint mt-1">Only department heads, managers and the organization owner can publish. This strategy stays private to you.</div>`);

        el.innerHTML = `
            <h5>Commit Resources &amp; Publish</h5>
            ${header}
            ${error ? `<div class="pg-error">${esc(error)}</div>` : ''}
            ${busy ? '<div class="pg-sub">Working…</div>' : ''}
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-2">
                    <thead><tr><th>Department</th><th>Budget (${esc(state.currency)})</th><th>People (FTE)</th><th>Tools</th><th>Notes</th><th></th></tr></thead>
                    <tbody>${state.rows.map((r, i) => rowHtml(r, i, editable, published)).join('') || '<tr><td colspan="6" class="pg-hint">No resources yet.</td></tr>'}</tbody>
                </table>
            </div>
            ${!published ? `<div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                ${options ? `<select id="pg-add-dept" class="form-select form-select-sm" style="width:auto">${options}</select>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-act="add">Add row</button>` : ''}
                <button type="button" class="btn btn-sm btn-outline-secondary" data-act="suggest" ${busy || !state.ready ? 'disabled' : ''}>Regenerate suggestions</button>
            </div>` : ''}
            <div class="d-flex flex-wrap gap-2 align-items-start">
                ${editable ? `<button type="button" class="btn btn-sm btn-outline-secondary" data-act="save" ${busy ? 'disabled' : ''}>${published ? 'Save changes' : 'Save draft'}</button>` : ''}
                <div>${publishBtn}</div>
            </div>
            ${history}`;
    }

    document.addEventListener('click', e => {
        const el = card();
        if (!el || !el.contains(e.target)) return;
        const act = e.target.closest('[data-act]');
        const rm = e.target.closest('[data-remove]');
        if (rm) return removeRow(Number(rm.dataset.remove));
        if (!act || busy) return;
        if (act.dataset.act !== 'publish') confirmPublish = false;
        ({ suggest, save, publish, add: addRow })[act.dataset.act]();
    });

    new MutationObserver(mount).observe(document.body, { childList: true, subtree: true });
    mount();
})();
</script>
```

- [ ] **Step 4: Include it**

In `resources/views/backend/pages/aiChat/users-new-chat.blade.php`, insert directly before the file's final `@endsection`, which closes `@section('scripts')`:

```blade
@include('backend.pages.aiChat.inc.publish-gate')
```

- [ ] **Step 5: Run the tests**

Run: `php artisan test --filter=PublishGateTest`
Expected: 30 passed.

Run: `php artisan test`
Expected: the full suite is green.

- [ ] **Step 6: Run the CI gates locally**

Run: `vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G`
Expected: no Pint changes, and no new PHPStan errors. If PHPStan flags `$chat` as nullable inside the `publish` transaction, change the closure's first line to `$chat = SearchUserChat::whereKey(...)->lockForUpdate()->firstOrFail();`.

- [ ] **Step 7: Commit**

```bash
git add resources/views/backend/pages/aiChat/inc/publish-gate.blade.php resources/views/backend/pages/aiChat/users-new-chat.blade.php tests/Feature/PublishGateTest.php
git commit -m "feat(publish-gate): Commit Resources & Publish card under the brief"
```

- [ ] **Step 8: Manual check on staging (after the branch is merged and deployed)**

The spec requires this because SQLite tests miss MySQL-only mismatches. On `https://staging.skilltricksinc.com`:

1. As a leader (organization owner), open a finished strategy at `/dashboard/users-new-chat/<chatId>`. Check the card appears under the brief and suggestions load, with one row per department.
2. Edit a budget, click Save draft, and reload: the value persists.
3. Click Commit Resources & Publish twice. The card shows "Published by …", and the row fields stay editable.
4. Change a budget and click Save changes. The change history lists old → new.
5. As a plain member, open their own finished strategy. Publish is disabled and the explanatory note shows.
6. Hard-reload with a cache-buster (see the staging CDN note) if the card is missing after deploy.
