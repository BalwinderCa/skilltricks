# Role Goal Links Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Every role goal is linked to one of the organization's own roles. The AI names goals only with those roles, the executive confirms the links in the Publish card, and publishing needs every goal linked.

**Architecture:** A nullable `expected_states.org_role_id`, plus a small `RoleGoalLinker` service for automatic name matching and validated manual assignment. `DocumentContextService::roleNamesFor()` feeds a shared role rule into the three role-goal prompts in `AiChatController`. `StrategyPublishController` (Phase 1) gains goals and roles in its payload, an `assignRole` action, and one publish rule. The Phase 1 card include gains a "Who gets which goal" section.

**Tech Stack:** Laravel 12, PHP 8, Blade + vanilla JS, PHPUnit feature tests on in-memory SQLite, MySQL on staging.

**Spec:** `docs/superpowers/specs/2026-09-23-role-goal-links-design.md` (builds on `docs/superpowers/specs/2026-09-23-publish-gate-design.md`)

## Global Constraints

- Branch `feat/role-goal-links`, cut from `feat/publish-gate` (Phase 1, unmerged).
- `expected_states.role` is never rewritten; the link lives only in `org_role_id`.
- `autoLink` never changes a goal that already has an `org_role_id`, and only reads roles from the author's organization.
- Role names in prompts go through `sanitiseForPrompt()` and are capped at `ORG_MAX_ROSTER_LINES` (60).
- With no org roles, all three prompts keep today's wording exactly.
- Extension beyond the spec's wording: when roles are listed, the prompt's role count becomes "up to N", where N = min(existing maximum, number of roles). Asking for "5 to 7 distinct roles" from an organization with 3 roles would force the model to invent roles.
- Ownership checks use `SearchUserChat::where('id', $id)->where('user_id', $user->id)->exists()`.
- UI colours: teal `#36839b`, orange `#ec883f`, red `#b42318` for errors. No purple.
- CI: PHPUnit, PHPStan (2 errors already exist on main; add none), Pint on changed files.

## Review Focus

1. **Role names with odd spacing or `---`** — "VP  of Sales" or "R&D --- Ops" must still auto-link to the goal the model wrote from the sanitised name. Test in Task 1.
2. **Existing Phase 1 tests' goals are unlinked** — every Phase 1 publish test must keep passing once the new publish rule lands, so their helper links its goals. Handled in Task 3.
3. **An organization with fewer roles than the prompt asks for** — the prompt must not demand 5 roles from 3. Test in Task 2.
4. **A goal id from another strategy posted to `assignRole`** — 422, and neither goal changes. Test in Task 3.
5. **An author with no organization opening the card** — `roles` is empty, goals stay unlinked, and nothing errors. Test in Task 3.

---

## File Map

| File | Responsibility |
| --- | --- |
| `database/migrations/2026_09_23_000100_add_org_role_id_to_expected_states.php` (create) | The link column |
| `app/Models/ExpectedState.php` (modify) | `org_role_id` fillable, `orgRole()` |
| `app/Services/RoleGoalLinker.php` (create) | `autoLink`, `assign`, `normalise` |
| `app/Services/AI/DocumentContextService.php` (modify) | `roleNamesFor()` |
| `app/Http/Controllers/Backend/AI/AiChatController.php` (modify) | Role rule in three prompts |
| `app/Http/Controllers/Backend/AI/StrategyPublishController.php` (modify) | Payload, `assignRole`, publish rule |
| `routes/backend.php` (modify) | `users-new-chat-goal-role.index` |
| `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php` (modify) | "Who gets which goal" section |
| `tests/Feature/RoleGoalLinksTest.php` (create) | Phase 2 tests |
| `tests/Feature/PublishGateTest.php` (modify) | `finishedChat` links its goals |

---

### Task 1: Link column and RoleGoalLinker

**Files:**
- Create: `database/migrations/2026_09_23_000100_add_org_role_id_to_expected_states.php`
- Create: `app/Services/RoleGoalLinker.php`
- Modify: `app/Models/ExpectedState.php`
- Test: `tests/Feature/RoleGoalLinksTest.php`

**Interfaces:**
- Produces: `ExpectedState::orgRole(): BelongsTo<OrgRole>`, with `org_role_id` fillable; `RoleGoalLinker::autoLink(SearchUserChat $chat): void`; `RoleGoalLinker::assign(ExpectedState $goal, int $orgRoleId, User $author): bool`; `RoleGoalLinker::normalise(string $name): string` (public static). Test helpers in `RoleGoalLinksTest`: `org(string $domain): Organization`, `role(Organization, string): OrgRole`, `member(Organization, string $email, ?OrgRole): User`, `chatWithGoals(User, array $roleTexts): SearchUserChat`.

- [ ] **Step 1: Write the test file with helpers and failing linker tests**

Create `tests/Feature/RoleGoalLinksTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\RoleGoalLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Role goals linked to real roles (Features spec, phase 2): every goal a
 * strategy produces is tied to one of the organization's own roles.
 */
class RoleGoalLinksTest extends TestCase
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

    private function org(string $domain = 'acme.com'): Organization
    {
        return Organization::create(['domain' => $domain, 'name' => ucfirst(strtok($domain, '.'))]);
    }

    private function role(Organization $org, string $name): OrgRole
    {
        return OrgRole::create(['organization_id' => $org->id, 'name' => $name]);
    }

    private function member(Organization $org, string $email, ?OrgRole $role = null): User
    {
        return User::factory()->create([
            'email' => $email, 'user_type' => 'customer',
            'organization_id' => $org->id, 'org_role_id' => $role?->id,
        ]);
    }

    /** @param  array<int, string>  $roleTexts */
    private function chatWithGoals(User $author, array $roleTexts): SearchUserChat
    {
        $chat = SearchUserChat::create([
            'user_id' => $author->id, 'status1' => 0,
            'selected_strategy' => 'Security upsell', 'selected_scenario' => 'Expected',
            'leadership_brief' => 'Brief.',
        ]);
        foreach ($roleTexts as $text) {
            ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => $text, 'recommended_action' => 'Act for '.$text]);
        }

        return $chat;
    }

    public function test_auto_link_matches_role_names_ignoring_case_and_spacing(): void
    {
        $org = $this->org();
        $sales = $this->role($org, 'VP of  Sales');
        $rnd = $this->role($org, 'R&D --- Ops');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['vp of sales ', 'R&D Ops', 'Chief Dreamer']);

        app(RoleGoalLinker::class)->autoLink($chat);

        $links = ExpectedState::orderBy('id')->pluck('org_role_id')->all();
        $this->assertSame([$sales->id, $rnd->id, null], array_map(fn ($v) => $v === null ? null : (int) $v, $links));
    }

    public function test_auto_link_ignores_same_named_roles_in_other_organizations(): void
    {
        $acme = $this->org();
        $globex = $this->org('globex.com');
        $this->role($globex, 'VP of Sales');
        $author = $this->member($acme, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['VP of Sales']);

        app(RoleGoalLinker::class)->autoLink($chat);

        $this->assertNull(ExpectedState::first()->org_role_id);
    }

    public function test_auto_link_never_overrides_a_manual_choice(): void
    {
        $org = $this->org();
        $this->role($org, 'VP of Sales');
        $director = $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['VP of Sales']);
        ExpectedState::first()->update(['org_role_id' => $director->id]);

        app(RoleGoalLinker::class)->autoLink($chat);

        $this->assertSame($director->id, (int) ExpectedState::first()->org_role_id);
    }

    public function test_assign_refuses_a_role_from_another_organization(): void
    {
        $acme = $this->org();
        $foreign = $this->role($this->org('globex.com'), 'Spy');
        $own = $this->role($acme, 'Director');
        $author = $this->member($acme, 'ceo@acme.com');
        $this->chatWithGoals($author, ['Director']);
        $goal = ExpectedState::first();
        $linker = app(RoleGoalLinker::class);

        $this->assertFalse($linker->assign($goal, $foreign->id, $author));
        $this->assertNull($goal->fresh()->org_role_id);
        $this->assertTrue($linker->assign($goal, $own->id, $author));
        $this->assertSame($own->id, (int) $goal->fresh()->org_role_id);
    }
}
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=RoleGoalLinksTest`
Expected: FAIL with `Class "App\Services\RoleGoalLinker" not found`.

- [ ] **Step 3: Write the migration**

Create `database/migrations/2026_09_23_000100_add_org_role_id_to_expected_states.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role goals linked to real roles (Features spec, phase 2). The AI's role
     * text stays in `role`; this is the link to the organization's own role.
     */
    public function up(): void
    {
        Schema::table('expected_states', function (Blueprint $table) {
            if (! Schema::hasColumn('expected_states', 'org_role_id')) {
                $table->unsignedBigInteger('org_role_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expected_states', function (Blueprint $table) {
            $table->dropIndex(['org_role_id']);
            $table->dropColumn('org_role_id');
        });
    }
};
```

- [ ] **Step 4: Extend `ExpectedState`**

In `app/Models/ExpectedState.php`, add `'org_role_id',` to `$fillable` after `'recommended_action',`, and add this relation next to the model's other relations:

```php
    /** The organization role this goal is for (Features spec, phase 2). */
    public function orgRole(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OrgRole::class);
    }
```

- [ ] **Step 5: Write the linker**

Create `app/Services/RoleGoalLinker.php`:

```php
<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\User;

/**
 * Ties a strategy's role goals to the organization's own roles: by name when
 * the model wrote a role that exists, by hand when the executive picks one.
 */
class RoleGoalLinker
{
    /** Link unlinked goals whose role text names one of the author's organization's roles. */
    public function autoLink(SearchUserChat $chat): void
    {
        $orgId = User::whereKey($chat->user_id)->value('organization_id');
        if (! $orgId) {
            return;
        }

        $roles = OrgRole::where('organization_id', $orgId)->get(['id', 'name'])
            ->keyBy(fn (OrgRole $r) => self::normalise($r->name));

        ExpectedState::where('search_user_chat_id', $chat->id)->whereNull('org_role_id')->get()
            ->each(function (ExpectedState $goal) use ($roles) {
                $role = $roles->get(self::normalise((string) $goal->role));
                if ($role) {
                    $goal->update(['org_role_id' => $role->id]);
                }
            });
    }

    /** Link a goal to a role, only when that role belongs to the author's organization. */
    public function assign(ExpectedState $goal, int $orgRoleId, User $author): bool
    {
        if (! $author->organization_id
            || ! OrgRole::whereKey($orgRoleId)->where('organization_id', $author->organization_id)->exists()) {
            return false;
        }

        $goal->update(['org_role_id' => $orgRoleId]);

        return true;
    }

    /**
     * Compare names the way the prompt shows them: DocumentContextService's
     * sanitiser collapses whitespace and "---" runs, so the model can only
     * echo that form back.
     */
    public static function normalise(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s*-{3,}\s*/', ' ', preg_replace('/\s+/', ' ', $name))));
    }
}
```

- [ ] **Step 6: Run to verify they pass**

Run: `php artisan test --filter=RoleGoalLinksTest`
Expected: 4 passed.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint database/migrations/2026_09_23_000100_add_org_role_id_to_expected_states.php app/Services/RoleGoalLinker.php app/Models/ExpectedState.php tests/Feature/RoleGoalLinksTest.php
git add database/migrations/2026_09_23_000100_add_org_role_id_to_expected_states.php app/Services/RoleGoalLinker.php app/Models/ExpectedState.php tests/Feature/RoleGoalLinksTest.php
git commit -m "feat(role-goals): link role goals to org roles by name or by hand"
```

---

### Task 2: The AI names goals with the organization's roles

**Files:**
- Modify: `app/Services/AI/DocumentContextService.php` (new public method next to `orgContextBlock`)
- Modify: `app/Http/Controllers/Backend/AI/AiChatController.php` (the prompts in `generate_strategy_variant` ~L597-633, `users_new_chat_update_strategy` ~L909-935, `users_new_chat_update_scenario` ~L1007-1030; one private helper)
- Test: `tests/Feature/RoleGoalLinksTest.php`

**Interfaces:**
- Consumes: the `org`, `role`, `member` and `chatWithGoals` helpers from Task 1.
- Produces: `DocumentContextService::roleNamesFor($user): array<int, string>`; private `AiChatController::roleRule($user, int $max): ?array` returning `['max' => int, 'rule' => string]`, or null when the org has no roles.

- [ ] **Step 1: Write the failing tests**

Add these imports to `RoleGoalLinksTest`:

```php
use App\Services\AI\AiProviderService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
```

Append to the class:

```php
    /** Bind an AI provider that records the user prompt it is sent. */
    private function capturePrompt(?string &$captured): void
    {
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturnUsing(function ($system, $prompt) use (&$captured) {
            $captured = $prompt;

            return new ClientResponse(new PsrResponse(200, [], json_encode(['candidates' => [['content' => ['parts' => [['text' => '{}']]]]]])));
        });
        $ai->shouldReceive('extractText')->andReturn('{}');
        $ai->shouldReceive('recordChatTokens')->andReturn(0);
        $ai->shouldReceive('parseJson')->andReturn([]);
        $this->instance(AiProviderService::class, $ai);
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function rolePrompts(): array
    {
        return [
            'wizard variant' => ['users-new-chat-generate-strategy-variant.index', ['original_question' => 'Grow', 'strategy_name' => 'Upsell']],
            'update strategy' => ['users-new-chat-update-strategy.index', ['original_question' => 'Grow', 'selected_strategy' => 'Upsell']],
            'update scenario' => ['users-new-chat-update-scenario.index', ['original_question' => 'Grow', 'selected_scenario' => 'Expected']],
        ];
    }

    /** @dataProvider rolePrompts */
    public function test_role_goal_prompts_use_only_the_organizations_roles(string $route, array $input): void
    {
        $org = $this->org();
        $this->role($org, 'Head of Sales');
        $this->role($org, 'VP of Product');
        $this->role($org, 'Data Lead');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, []);
        $captured = null;
        $this->capturePrompt($captured);

        $this->actingAs($author)->postJson(route($route), $input + ['chat_id' => $chat->id]);

        $this->assertNotNull($captured, 'the prompt was never sent');
        $this->assertStringContainsString('Use ONLY these role titles, exactly as written', $captured);
        $this->assertStringContainsString('Data Lead, Head of Sales, VP of Product', $captured);
        $this->assertStringContainsString('up to 3', $captured);
        $this->assertStringNotContainsString('from documents', $captured);
        $this->assertStringNotContainsString('in the documents', $captured);
        $this->assertStringNotContainsString('in the company documents', $captured);
    }

    /** @dataProvider rolePrompts */
    public function test_role_goal_prompts_keep_todays_wording_without_org_roles(string $route, array $input): void
    {
        $org = $this->org();
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, []);
        $captured = null;
        $this->capturePrompt($captured);

        $this->actingAs($author)->postJson(route($route), $input + ['chat_id' => $chat->id]);

        $this->assertNotNull($captured, 'the prompt was never sent');
        $this->assertStringNotContainsString('Use ONLY these role titles', $captured);
        $this->assertMatchesRegularExpression('/role titles (from the documents|that actually appear in the company documents|that exist in the documents)/', $captured);
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=RoleGoalLinksTest`
Expected: the 3 `..._use_only_the_organizations_roles` cases fail on "Use ONLY these role titles". The 3 `..._keep_todays_wording...` cases pass already, because they pin the existing text.

- [ ] **Step 3: Add `roleNamesFor`**

In `app/Services/AI/DocumentContextService.php`, directly above `orgContextBlock`:

```php
    /**
     * The author's organization's role names, as the prompt should show them:
     * sanitised and capped like the roster block. Empty when there are none.
     *
     * @return array<int, string>
     */
    public function roleNamesFor($user): array
    {
        $org = optional($user)->organization;
        if (! $org) {
            return [];
        }

        return $org->roles()->orderBy('name')->limit(self::ORG_MAX_ROSTER_LINES)->pluck('name')
            ->map(fn ($name) => $this->sanitiseForPrompt((string) $name))
            ->filter()->values()->all();
    }
```

- [ ] **Step 4: Add the shared rule helper to `AiChatController`**

Add next to `ownedChat()`:

```php
    /**
     * The role-title rule for role-goal prompts. With org roles the model may use
     * only those names, and never more goals than there are roles; null means
     * the organization has none and the prompt keeps its document wording.
     *
     * @return array{max: int, rule: string}|null
     */
    private function roleRule($user, int $max): ?array
    {
        $names = $this->docs->roleNamesFor($user);
        if ($names === []) {
            return null;
        }

        return [
            'max' => min($max, count($names)),
            'rule' => 'Use ONLY these role titles, exactly as written, one goal per role you choose: '.implode(', ', $names).'.',
        ];
    }
```

- [ ] **Step 5: Wire it into the wizard variant prompt**

In `generate_strategy_variant`, directly before `$prompt = <<<EOT`, add:

```php
        $roles = $this->roleRule($user, 7);
        $rolePlaceholder = $roles ? 'Role title from the list above' : 'Role title from documents';
        $roleCountRule = $roles
            ? "- Each \"rolesGoals\": up to {$roles['max']} DISTINCT roles. {$roles['rule']} \"action\" is EXACTLY one sentence."
            : '- Each "rolesGoals": 5 to 7 DISTINCT roles using ONLY exact role titles from the documents. "action" is EXACTLY one sentence.';
```

In the heredoc, replace each of the three occurrences of
`"rolesGoals": [{"role": "Role title from documents", "goal": "1-2 sentences", "action": "EXACTLY one sentence"}],`
with
`"rolesGoals": [{"role": "{$rolePlaceholder}", "goal": "1-2 sentences", "action": "EXACTLY one sentence"}],`

and replace the rule line
`- Each "rolesGoals": 5 to 7 DISTINCT roles using ONLY exact role titles from the documents. "action" is EXACTLY one sentence.`
with
`{$roleCountRule}`

- [ ] **Step 6: Wire it into `users_new_chat_update_strategy`**

Directly before its `$prompt = <<<EOT`, add:

```php
        $roles = $this->roleRule($user, 10);
        $roleCount = $roles ? "Output up to {$roles['max']} roles only, numbered in order." : 'Output 5 to 10 roles only, numbered in order.';
        $roleSource = $roles ? $roles['rule'] : 'Only use role titles that actually appear in the company documents.';
```

In its heredoc replace
`    - Output 5 to 10 roles only, numbered in order.` with `    - {$roleCount}`
and
`    - Only use role titles that actually appear in the company documents.` with `    - {$roleSource}`.

- [ ] **Step 7: Wire it into `users_new_chat_update_scenario`**

Directly before its `$prompt = <<<EOT`, add:

```php
        $roles = $this->roleRule($user, 10);
        $roleCount = $roles ? "Output up to {$roles['max']} roles only, numbered in order (1., 2., 3., ...)." : 'Output 5 to 10 roles only, numbered in order (1., 2., 3., ...).';
        $roleSource = $roles ? $roles['rule'] : 'Only use role titles that exist in the documents.';
```

In its heredoc replace
`- Output 5 to 10 roles only, numbered in order (1., 2., 3., ...).` with `- {$roleCount}`
and
`- Only use role titles that exist in the documents.` with `- {$roleSource}`.

- [ ] **Step 8: Run to verify they pass**

Run: `php artisan test --filter=RoleGoalLinksTest`
Expected: 10 passed (4 + 6 data-provider cases).

Run: `php artisan test`
Expected: the full suite passes. `ChatTenancyTest` and `ChatSelectionContextTest` exercise these endpoints.

- [ ] **Step 9: Commit**

```bash
vendor/bin/pint app/Services/AI/DocumentContextService.php app/Http/Controllers/Backend/AI/AiChatController.php tests/Feature/RoleGoalLinksTest.php
git add app/Services/AI/DocumentContextService.php app/Http/Controllers/Backend/AI/AiChatController.php tests/Feature/RoleGoalLinksTest.php
git commit -m "feat(role-goals): role-goal prompts use only the organization's roles"
```

---

### Task 3: Goals and roles in the card payload, and assigning a role

**Files:**
- Modify: `app/Http/Controllers/Backend/AI/StrategyPublishController.php`
- Modify: `routes/backend.php` (after the `users-new-chat-publish.index` route)
- Modify: `tests/Feature/PublishGateTest.php` (`finishedChat` helper)
- Test: `tests/Feature/RoleGoalLinksTest.php`

**Interfaces:**
- Consumes: `RoleGoalLinker::autoLink/assign` (Task 1); Phase 1's `ownChat`, `denied`, `payload`, `publishedUnderLock`, `publishedMeanwhile`.
- Produces: `payload()` gains `goals: [{id, role_text, action, org_role_id, org_role_name}]` and `roles: [{id, name, member_count}]`; route `users-new-chat-goal-role.index` (POST `chat_id`, `goal_id`, `org_role_id`) returning `payload()`, or 403 / 409 / 422.

- [ ] **Step 1: Write the failing tests**

Append to `RoleGoalLinksTest`:

```php
    public function test_the_card_auto_links_goals_and_lists_roles_with_member_counts(): void
    {
        $org = $this->org();
        $sales = $this->role($org, 'Head of Sales');
        $empty = $this->role($org, 'Data Lead');
        $author = $this->member($org, 'ceo@acme.com');
        $this->member($org, 'rep@acme.com', $sales);
        $chat = $this->chatWithGoals($author, ['Head of Sales', 'Chief Dreamer']);

        $this->actingAs($author)->getJson(route('users-new-chat-resources.show', ['chat' => $chat->id]))
            ->assertOk()
            ->assertJsonPath('goals.0.org_role_id', $sales->id)
            ->assertJsonPath('goals.0.org_role_name', 'Head of Sales')
            ->assertJsonPath('goals.1.org_role_id', null)
            ->assertJsonPath('goals.1.role_text', 'Chief Dreamer')
            ->assertJsonPath('roles.0.id', $empty->id)
            ->assertJsonPath('roles.0.member_count', 0)
            ->assertJsonPath('roles.1.member_count', 1);
    }

    public function test_an_author_without_an_organization_gets_no_roles_and_no_error(): void
    {
        $loner = User::factory()->create(['email' => 'solo@example.com', 'user_type' => 'customer', 'organization_id' => null]);
        $chat = $this->chatWithGoals($loner, ['Head of Sales']);

        $this->actingAs($loner)->getJson(route('users-new-chat-resources.show', ['chat' => $chat->id]))
            ->assertOk()
            ->assertJsonCount(0, 'roles')
            ->assertJsonPath('goals.0.org_role_id', null);
    }

    public function test_the_author_assigns_a_goal_to_a_role(): void
    {
        $org = $this->org();
        $director = $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['Chief Dreamer']);
        $goal = ExpectedState::first();

        $this->actingAs($author)->postJson(route('users-new-chat-goal-role.index'), [
            'chat_id' => $chat->id, 'goal_id' => $goal->id, 'org_role_id' => $director->id,
        ])->assertOk()->assertJsonPath('goals.0.org_role_name', 'Director');

        $this->assertSame($director->id, (int) $goal->fresh()->org_role_id);
    }

    public function test_assigning_another_strategys_goal_is_refused(): void
    {
        $org = $this->org();
        $director = $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $mine = $this->chatWithGoals($author, ['A']);
        $other = $this->chatWithGoals($author, ['B']);
        $otherGoal = ExpectedState::where('search_user_chat_id', $other->id)->first();

        $this->actingAs($author)->postJson(route('users-new-chat-goal-role.index'), [
            'chat_id' => $mine->id, 'goal_id' => $otherGoal->id, 'org_role_id' => $director->id,
        ])->assertStatus(422);

        $this->assertNull($otherGoal->fresh()->org_role_id);
    }

    public function test_assigning_a_role_from_another_organization_is_refused(): void
    {
        $org = $this->org();
        $foreign = $this->role($this->org('globex.com'), 'Spy');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['A']);

        $this->actingAs($author)->postJson(route('users-new-chat-goal-role.index'), [
            'chat_id' => $chat->id, 'goal_id' => ExpectedState::first()->id, 'org_role_id' => $foreign->id,
        ])->assertStatus(422);
    }

    public function test_assigning_on_someone_elses_strategy_is_refused(): void
    {
        $org = $this->org();
        $director = $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $intruder = $this->member($org, 'ic@acme.com');
        $chat = $this->chatWithGoals($author, ['A']);

        $this->actingAs($intruder)->postJson(route('users-new-chat-goal-role.index'), [
            'chat_id' => $chat->id, 'goal_id' => ExpectedState::first()->id, 'org_role_id' => $director->id,
        ])->assertForbidden();
    }

    public function test_links_are_final_once_published(): void
    {
        $org = $this->org();
        $director = $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['A']);
        $chat->forceFill(['status' => 'published', 'published_by' => $author->id, 'published_at' => now()])->save();

        $this->actingAs($author)->postJson(route('users-new-chat-goal-role.index'), [
            'chat_id' => $chat->id, 'goal_id' => ExpectedState::first()->id, 'org_role_id' => $director->id,
        ])->assertStatus(409);

        $this->assertNull(ExpectedState::first()->org_role_id);
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=RoleGoalLinksTest`
Expected: FAIL. The payload has no `goals` key, and `Route [users-new-chat-goal-role.index] not defined.`

- [ ] **Step 3: Extend the payload and run `autoLink` in `show`**

In `StrategyPublishController`:

1. Add the imports `use App\Models\OrgRole;` and `use App\Services\RoleGoalLinker;`.
2. Add `protected RoleGoalLinker $linker,` as the last constructor parameter.
3. In `show`, before `return response()->json(...)`, add `$this->linker->autoLink($record);`.
4. In `payload()`, before `return [`, add:

```php
        $goals = ExpectedState::where('search_user_chat_id', $chat->id)->with('orgRole:id,name')->orderBy('id')->get();
        $roles = $user->organization_id
            ? OrgRole::where('organization_id', $user->organization_id)->orderBy('name')->get(['id', 'name'])
            : collect();
        $holders = $user->organization_id
            ? User::where('organization_id', $user->organization_id)->whereNotNull('org_role_id')
                ->selectRaw('org_role_id, count(*) as n')->groupBy('org_role_id')->pluck('n', 'org_role_id')
            : collect();
```

and add these two keys to the returned array, after `'departments'`:

```php
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
```

- [ ] **Step 4: Add `assignRole`**

Add after `publish()`:

```php
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
```

- [ ] **Step 5: Register the route**

In `routes/backend.php`, after the `users-new-chat-publish.index` line:

```php
                Route::post('/users-new-chat-goal-role', [StrategyPublishController::class, 'assignRole'])->name('users-new-chat-goal-role.index');
```

- [ ] **Step 6: Keep the Phase 1 tests' goals linked**

Task 4's publish rule would otherwise fail every Phase 1 publish test, because their goals are unlinked. In `tests/Feature/PublishGateTest.php`, add `use App\Models\OrgRole;` and replace the two `ExpectedState::create` lines in `finishedChat()` with:

```php
        foreach (['VP of Sales' => 'Create an enterprise upgrade motion', 'VP of Product' => 'Ship SOC2 controls'] as $role => $action) {
            // Phase 2: publishing needs every goal linked to a role in the author's organization.
            $orgRoleId = $author->organization_id
                ? OrgRole::firstOrCreate(['organization_id' => $author->organization_id, 'name' => $role])->id
                : null;
            ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => $role, 'recommended_action' => $action, 'org_role_id' => $orgRoleId]);
        }
```

- [ ] **Step 7: Run to verify they pass**

Run: `php artisan test --filter=RoleGoalLinksTest`
Expected: 17 passed.

Run: `php artisan test --filter=PublishGateTest`
Expected: 35 passed.

- [ ] **Step 8: Commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/AI/StrategyPublishController.php routes/backend.php tests/Feature/RoleGoalLinksTest.php tests/Feature/PublishGateTest.php
git add app/Http/Controllers/Backend/AI/StrategyPublishController.php routes/backend.php tests/Feature/RoleGoalLinksTest.php tests/Feature/PublishGateTest.php
git commit -m "feat(role-goals): show goal links in the publish card and let the author change them"
```

---

### Task 4: Publishing needs every goal linked

**Files:**
- Modify: `app/Http/Controllers/Backend/AI/StrategyPublishController.php` (`publish` transaction)
- Test: `tests/Feature/RoleGoalLinksTest.php`

**Interfaces:**
- Consumes: `assignRole` route (Task 3), Phase 1 publish.
- Produces: `publish` returns 422 `Link every goal to a role before publishing.` while any goal of the chat has `org_role_id` null.

- [ ] **Step 1: Write the failing tests**

Add `use App\Models\Department;` to the imports and append:

```php
    /** A leader (department head) with a finished strategy that has one resource row. */
    private function publishable(array $roleTexts): array
    {
        $org = $this->org();
        $author = $this->member($org, 'ceo@acme.com');
        Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E', 'head_user_id' => $author->id]);
        $chat = $this->chatWithGoals($author, $roleTexts);
        $chat->resources()->create(['department_id' => null, 'department_name' => 'Whole organization', 'budget' => 1]);

        return [$org, $author, $chat];
    }

    public function test_publishing_is_refused_while_a_goal_has_no_role(): void
    {
        [, $author, $chat] = $this->publishable(['Chief Dreamer']);

        $this->actingAs($author)->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Link every goal to a role before publishing.');
        $this->assertFalse($chat->fresh()->isPublished());
    }

    public function test_publishing_succeeds_once_every_goal_is_linked(): void
    {
        [$org, $author, $chat] = $this->publishable(['Chief Dreamer']);
        $role = $this->role($org, 'Director');
        ExpectedState::first()->update(['org_role_id' => $role->id]);

        $this->actingAs($author)->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])->assertOk();
        $this->assertTrue($chat->fresh()->isPublished());
    }

    public function test_a_strategy_with_no_goals_can_still_be_published(): void
    {
        [, $author, $chat] = $this->publishable([]);

        $this->actingAs($author)->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])->assertOk();
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=RoleGoalLinksTest`
Expected: `test_publishing_is_refused_while_a_goal_has_no_role` fails with 200 instead of 422. The other two pass already; they guard against the rule being over-broad.

- [ ] **Step 3: Add the rule**

In `publish()`'s transaction, directly after the `if (! $chat->resources()->exists()) { ... }` block, add:

```php
            if (ExpectedState::where('search_user_chat_id', $chat->id)->whereNull('org_role_id')->exists()) {
                return response()->json(['error' => 'Link every goal to a role before publishing.'], 422);
            }
```

- [ ] **Step 4: Run to verify they pass**

Run: `php artisan test --filter=RoleGoalLinksTest`
Expected: 20 passed.

Run: `php artisan test`
Expected: the full suite passes.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/AI/StrategyPublishController.php tests/Feature/RoleGoalLinksTest.php
git add app/Http/Controllers/Backend/AI/StrategyPublishController.php tests/Feature/RoleGoalLinksTest.php
git commit -m "feat(role-goals): publishing needs every goal linked to a role"
```

---

### Task 5: "Who gets which goal" in the Publish card

**Files:**
- Modify: `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php`
- Test: `tests/Feature/RoleGoalLinksTest.php`, plus a check by hand in the browser

**Interfaces:**
- Consumes: `payload.goals`, `payload.roles`, and route `users-new-chat-goal-role.index` (Task 3).
- Produces: a `.pg-goals` section inside `#publish-gate-card`; `<select data-goal="<id>">` elements that post on change.

- [ ] **Step 1: Write the failing test**

Append:

```php
    public function test_the_chat_page_carries_the_goal_role_route(): void
    {
        $org = $this->org();
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['A']);

        $this->actingAs($author)->get('/dashboard/users-new-chat/'.$chat->id)
            ->assertOk()
            ->assertSee(route('users-new-chat-goal-role.index'), false)
            ->assertSee('Who gets which goal', false);
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=test_the_chat_page_carries_the_goal_role_route`
Expected: FAIL, because the route URL isn't in the page.

- [ ] **Step 3: Add the route, the styles and the section**

In `publish-gate.blade.php`:

1. In `<style>`, add:

```css
    .pg-goals { margin-bottom: 14px; }
    .pg-goals .pg-flag-red { color: #b42318; font-size: 12px; }
    .pg-goals .pg-flag-orange { color: #ec883f; font-size: 12px; }
    .pg-goals .pg-role-text { color: #6c757d; font-size: 12px; }
```

2. In the `urls` object, add:

```js
        goalRole: '{{ route('users-new-chat-goal-role.index') }}',
```

3. Add this function above `function render()`:

```js
    function goalsHtml(published) {
        if (!state.goals || !state.goals.length) return '';
        const holders = Object.fromEntries((state.roles || []).map(r => [r.id, r.member_count]));
        const rows = state.goals.map(g => {
            const flag = g.org_role_id === null
                ? '<div class="pg-flag-red">No matching role — pick one</div>'
                : (holders[g.org_role_id] === 0 ? '<div class="pg-flag-orange">Nobody holds this role yet</div>' : '');
            const picker = published
                ? esc(g.org_role_name ?? '—')
                : `<select class="form-select form-select-sm" data-goal="${g.id}" ${busy ? 'disabled' : ''}>
                    ${g.org_role_id === null ? '<option value="" selected disabled>Pick a role…</option>' : ''}
                    ${(state.roles || []).map(r => `<option value="${r.id}" ${r.id === g.org_role_id ? 'selected' : ''}>${esc(r.name)}</option>`).join('')}
                   </select>`;
            return `<tr><td>${esc(g.action)}<div class="pg-role-text">AI role: ${esc(g.role_text)}</div></td><td style="min-width:180px">${picker}${flag}</td></tr>`;
        }).join('');
        const noRoles = !published && !(state.roles || []).length
            ? '<div class="pg-hint">Your organization has no roles yet. Add them on the Roles page, then reload.</div>' : '';
        return `<div class="pg-goals"><strong>Who gets which goal</strong>${noRoles}
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Goal</th><th>Role</th></tr></thead><tbody>${rows}</tbody></table></div></div>`;
    }

    async function assignRole(goalId, roleId) {
        busy = true; error = ''; render();
        try { state = await call(urls.goalRole, { chat_id: chatId, goal_id: goalId, org_role_id: roleId }); }
        catch (e) { error = e.message; }
        busy = false; render();
    }
```

4. In `render()`'s `el.innerHTML` template, insert `${goalsHtml(published)}` directly after `${busy ? '<div class="pg-sub">Working…</div>' : ''}`, so the section sits above the resources table.

5. Directly after the existing `document.addEventListener('click', ...)` block, add:

```js
    document.addEventListener('change', e => {
        const el = card();
        const sel = e.target.closest && e.target.closest('select[data-goal]');
        if (!el || !sel || !el.contains(sel) || busy || sel.value === '') return;
        assignRole(Number(sel.dataset.goal), Number(sel.value));
    });
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --filter=RoleGoalLinksTest`
Expected: 21 passed.

Run: `php artisan test`
Expected: the full suite passes.

- [ ] **Step 5: Run the CI gates**

Run: `vendor/bin/pint --test $(git diff --name-only feat/publish-gate...HEAD -- '*.php')`
Expected: passed.

Run: `vendor/bin/phpstan analyse --memory-limit=1G --no-progress`
Expected: only the 2 errors that also exist on main (CustomersController:110, SubscriptionHistoryTrait:110).

- [ ] **Step 6: Commit**

```bash
git add resources/views/backend/pages/aiChat/inc/publish-gate.blade.php tests/Feature/RoleGoalLinksTest.php
git commit -m "feat(role-goals): Who gets which goal section in the publish card"
```

- [ ] **Step 7: Check by hand (after merge and deploy, together with Phase 1's check)**

On a finished strategy, as its author (a leader):
1. The card shows "Who gets which goal" above the resources, and goals whose AI role matches an org role are pre-selected.
2. Pick a role for an unlinked goal. The red flag disappears and survives a reload.
3. Publish while a goal is unlinked shows "Link every goal to a role before publishing."
4. After publishing, the section shows role names without dropdowns.
