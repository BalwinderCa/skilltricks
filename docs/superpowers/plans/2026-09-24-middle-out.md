# Middle-Out Initiatives Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** A director's or VP's initiative is matched to a C-suite priority, goes through an approval gate, and rolls up into the executive scorecards.

**Architecture:**
- A `Correlation` service: candidates, whether approval is required, and the AI match.
- `SearchUserChat` gains the parent, correlation and approval columns, plus `isLocked()`.
- `StrategyPublishController`: match and link endpoints, a publish branch that sends for approval, and the lock checks switched to `isLocked()`.
- `StrategyOverviewController`: approve and reject.
- `StrategyOverview`: approvals, supports / supporting counts, and supporting initiatives.
- UI in the publish card and the executive views.

**Spec:** `docs/superpowers/specs/2026-09-24-middle-out-design.md`

## Global Constraints

- Candidates are published strategies in the viewer's organization, authored by the owner, with no parent, and never the initiative itself.
- Approval is required when the user is not the owner and at least one candidate exists.
- Approvers are the parent's author or the owner. Approve and reject are conditional updates on `status = 'pending_approval'`, so a double click can't decide twice.
- Organization context only in the AI prompt; the match route is throttled at `throttle:10,1`.
- Alert types: `approval_request` (to the parent's author) and `approval_decision` (to the requester).

## Review Focus

1. **A parent that stops being a candidate before publishing** (unpublished, or re-parented) — publish returns 422.
2. **Approving twice** — the second attempt returns 404, and only one alert goes out.
3. **An initiative linked to itself, or to another initiative** — refused (422).
4. **Rejecting after the requester has left the organization** — no error, and no alert to anyone outside it.
5. **Pending strategies** — invisible in "Your goals", and not counted in the executive list.

---

### Task 1: Linking and the approval gate (backend)

**Files:** migration `2026_09_24_000600_add_middle_out_to_search_user_chat.php`; `app/Models/SearchUserChat.php`; `app/Services/Correlation.php`; `app/Http/Controllers/Backend/AI/StrategyPublishController.php`; `app/Http/Controllers/Backend/StrategyOverviewController.php`; `app/Services/StrategyOverview.php` (`canApprove`); `routes/backend.php`; test `tests/Feature/MiddleOutTest.php`.

- [ ] **Step 1: Failing tests** — create `tests/Feature/MiddleOutTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Models\WrNotification;
use App\Services\AI\AiProviderService;
use App\Services\MyGoals;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
use Tests\TestCase;

/**
 * Middle-out initiatives (Features spec, phase 9 / Notion Illustration 2):
 * matched to a C-suite priority, approved upstream, rolled up.
 */
class MiddleOutTest extends TestCase
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

    private function strategy(User $author, string $question, Organization $org, OrgRole $role, bool $published): SearchUserChat
    {
        $chat = SearchUserChat::create(['user_id' => $author->id, 'status1' => 0, 'selected_strategy' => 'Path for '.$question, 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $author->id, 'search' => $question, 'response' => 'ok']);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => $role->name, 'recommended_action' => 'Act on '.$question, 'org_role_id' => $role->id]);
        $chat->resources()->create(['department_id' => null, 'department_name' => 'Whole organization', 'budget' => 1000]);
        $chat->forceFill(['organization_id' => $org->id] + ($published ? ['status' => 'published', 'published_by' => $author->id, 'published_at' => now()] : []))->save();

        return $chat;
    }

    /**
     * ceo (owner) with a published C-suite priority; director (Sales dept head)
     * with a draft initiative; manager (another leader); rep (Sales).
     *
     * @return array<string, mixed>
     */
    private function world(bool $withPriority = true): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $make = fn (string $email, array $extra = []) => User::factory()->create(['email' => $email, 'user_type' => 'customer', 'organization_id' => $org->id] + $extra);
        $ceo = $make('ceo@acme.com', ['name' => 'Casey CEO']);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $director = $make('director@acme.com', ['name' => 'Dana Director', 'manager_id' => $ceo->id]);
        Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E', 'head_user_id' => $director->id]);
        $manager = $make('manager@acme.com', ['manager_id' => $ceo->id]);
        $rep = $make('rep@acme.com', ['org_role_id' => $sales->id, 'manager_id' => $manager->id]);

        $priority = $withPriority ? $this->strategy($ceo, 'Grow revenue 30%', $org, $sales, true) : null;
        $initiative = $this->strategy($director, 'Automate contract review', $org, $sales, false);

        return compact('org', 'sales', 'ceo', 'director', 'manager', 'rep', 'priority', 'initiative');
    }

    private function fakeAi(string $text, int $status = 200): void
    {
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturn(new ClientResponse(new PsrResponse($status, [], '{}')));
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    private function link(array $w): void
    {
        $this->actingAs($w['director'])->postJson(route('users-new-chat-parent.index'), ['chat_id' => $w['initiative']->id, 'parent_chat_id' => $w['priority']->id])->assertOk();
    }

    private function sendForApproval(array $w)
    {
        return $this->actingAs($w['director'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $w['initiative']->id]);
    }

    public function test_a_director_must_link_an_initiative_before_sending_it(): void
    {
        $w = $this->world();

        $this->sendForApproval($w)->assertStatus(422)->assertJsonPath('error', 'Link this initiative to a corporate priority before sending it for approval.');
        $this->assertSame('draft', $w['initiative']->fresh()->status);
    }

    public function test_a_linked_initiative_goes_to_approval_and_is_locked(): void
    {
        $w = $this->world();
        $this->link($w);

        $this->sendForApproval($w)->assertOk()->assertJsonPath('status', 'pending_approval')->assertJsonPath('approval.approver', 'Casey CEO');

        $this->assertSame([$w['ceo']->id], WrNotification::where('type', 'approval_request')->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        $this->assertCount(1, app(MyGoals::class)->for($w['rep']));  // the priority's goal only
        $this->actingAs($w['director'])->postJson(route('users-new-chat-resources-save.index'), ['chat_id' => $w['initiative']->id, 'rows' => []])->assertStatus(409);
        $this->actingAs($w['director'])->postJson(route('users-new-chat-rank-goals.index'), ['chat_id' => $w['initiative']->id])->assertStatus(409);
        $this->actingAs($w['director'])->postJson(route('users-new-chat-parent.index'), ['chat_id' => $w['initiative']->id, 'parent_chat_id' => $w['priority']->id])->assertStatus(409);
    }

    public function test_an_org_without_priorities_publishes_directly(): void
    {
        $w = $this->world(withPriority: false);

        $this->sendForApproval($w)->assertOk()->assertJsonPath('status', 'published');
    }

    public function test_the_owner_publishes_directly(): void
    {
        $w = $this->world();
        $ownerDraft = $this->strategy($w['ceo'], 'Second priority', $w['org'], $w['sales'], false);

        $this->actingAs($w['ceo'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $ownerDraft->id])->assertOk()->assertJsonPath('status', 'published');
    }

    public function test_the_ai_links_the_best_matching_priority(): void
    {
        $w = $this->world();
        $second = $this->strategy($w['ceo'], 'Cut costs 10%', $w['org'], $w['sales'], true);
        $this->fakeAi(json_encode(['matches' => [
            ['id' => $w['priority']->id, 'score' => 45, 'reason' => 'Loosely related'],
            ['id' => $second->id, 'score' => '88', 'reason' => 'Automation cuts legal cost'],
            ['id' => $w['initiative']->id, 'score' => 100, 'reason' => 'itself'],
        ]]));

        $this->actingAs($w['director'])->postJson(route('users-new-chat-match.index'), ['chat_id' => $w['initiative']->id])
            ->assertOk()->assertJsonPath('parent.id', $second->id)->assertJsonPath('parent.score', 88);

        $this->assertSame('Automate contract review', app(MyGoals::class)->companyGoal($w['initiative']->fresh()));
        $this->assertSame('Automation cuts legal cost', $w['initiative']->fresh()->correlation_reason);

        $this->fakeAi('nothing useful', 500);
        $this->actingAs($w['director'])->postJson(route('users-new-chat-match.index'), ['chat_id' => $w['initiative']->id])->assertStatus(502);
    }

    public function test_only_corporate_priorities_can_be_parents(): void
    {
        $w = $this->world();
        $other = Organization::create(['domain' => 'globex.com', 'name' => 'Globex']);
        $foreign = $this->strategy(User::factory()->create(['email' => 'x@globex.com', 'user_type' => 'customer', 'organization_id' => $other->id]), 'Theirs', $other, $w['sales'], true);
        $directorPublished = $this->strategy($w['director'], 'Director plan', $w['org'], $w['sales'], true);
        $post = fn (int $parent) => $this->actingAs($w['director'])->postJson(route('users-new-chat-parent.index'), ['chat_id' => $w['initiative']->id, 'parent_chat_id' => $parent]);

        $post($foreign->id)->assertStatus(422);
        $post($directorPublished->id)->assertStatus(422);   // not authored by the owner
        $post($w['initiative']->id)->assertStatus(422);     // itself
    }

    public function test_a_parent_that_stops_being_a_candidate_blocks_sending(): void
    {
        $w = $this->world();
        $this->link($w);
        $w['priority']->forceFill(['status' => 'draft'])->save();

        $this->sendForApproval($w)->assertStatus(422);
    }

    public function test_the_approver_approves_once(): void
    {
        $w = $this->world();
        $this->link($w);
        $this->sendForApproval($w);

        $this->actingAs($w['ceo'])->post(route('strategies.approve', $w['initiative']->id))->assertRedirect();
        $this->actingAs($w['ceo'])->post(route('strategies.approve', $w['initiative']->id))->assertNotFound();

        $chat = $w['initiative']->fresh();
        $this->assertSame('published', $chat->status);
        $this->assertNotNull($chat->published_at);
        $this->assertSame([$w['director']->id, $w['ceo']->id], [(int) $chat->published_by, (int) $chat->approval_decided_by]);
        $this->assertSame(1, WrNotification::where('type', 'approval_decision')->where('user_id', $w['director']->id)->count());
        $this->assertCount(2, app(MyGoals::class)->for($w['rep']));
    }

    public function test_rejection_returns_the_initiative_to_draft_with_a_note(): void
    {
        $w = $this->world();
        $this->link($w);
        $this->sendForApproval($w);

        $this->actingAs($w['ceo'])->post(route('strategies.reject', $w['initiative']->id), ['note' => ''])->assertSessionHasErrors('note');
        $this->actingAs($w['ceo'])->post(route('strategies.reject', $w['initiative']->id), ['note' => 'Budget too high for Q3'])->assertRedirect();

        $this->assertSame(['draft', 'Budget too high for Q3'], [$w['initiative']->fresh()->status, $w['initiative']->fresh()->approval_note]);
        $this->assertSame(1, WrNotification::where('type', 'approval_decision')->count());
        $this->actingAs($w['director'])->getJson(route('users-new-chat-resources.show', ['chat' => $w['initiative']->id]))
            ->assertOk()->assertJsonPath('rejection.note', 'Budget too high for Q3');
    }

    public function test_only_approvers_decide_and_only_pending_initiatives(): void
    {
        $w = $this->world();
        $this->actingAs($w['ceo'])->post(route('strategies.approve', $w['initiative']->id))->assertNotFound(); // still a draft

        $this->link($w);
        $this->sendForApproval($w);
        $this->actingAs($w['manager'])->post(route('strategies.approve', $w['initiative']->id))->assertForbidden();
    }

    public function test_the_match_route_is_throttled(): void
    {
        $route = app('router')->getRoutes()->getByName('users-new-chat-match.index');

        $this->assertTrue(collect($route->gatherMiddleware())->contains(fn ($m) => str_starts_with($m, 'throttle:')));
    }
}
```

- [ ] **Step 2: Run** → FAIL (`Route [users-new-chat-parent.index] not defined.`).

- [ ] **Step 3: Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Middle-out initiatives (Features spec, phase 9): the parent priority, the match, the approval. */
    public function up(): void
    {
        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_chat_id')->nullable()->index();
            $table->unsignedTinyInteger('correlation_score')->nullable();
            $table->string('correlation_reason', 300)->nullable();
            $table->timestamp('approval_requested_at')->nullable();
            $table->unsignedBigInteger('approval_decided_by')->nullable();
            $table->timestamp('approval_decided_at')->nullable();
            $table->text('approval_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->dropIndex(['parent_chat_id']);
            $table->dropColumn(['parent_chat_id', 'correlation_score', 'correlation_reason', 'approval_requested_at', 'approval_decided_by', 'approval_decided_at', 'approval_note']);
        });
    }
};
```

- [ ] **Step 4: Model** — in `SearchUserChat`:
  - Append `'parent_chat_id', 'correlation_score', 'correlation_reason', 'approval_requested_at', 'approval_decided_by', 'approval_decided_at', 'approval_note'` to `$fillable`.
  - Add `'approval_requested_at' => 'datetime', 'approval_decided_at' => 'datetime', 'correlation_score' => 'integer'` to `$casts`.
  - Add, after `isPublished()`:

```php
    /** Published, or waiting for upstream approval: either way the author can no longer edit it. */
    public function isLocked(): bool
    {
        return in_array($this->status, ['published', 'pending_approval'], true);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<SearchUserChat, $this> */
    public function parentChat()
    {
        return $this->belongsTo(SearchUserChat::class, 'parent_chat_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<SearchUserChat, $this> */
    public function childChats()
    {
        return $this->hasMany(SearchUserChat::class, 'parent_chat_id');
    }
```

- [ ] **Step 5: Service** — `app/Services/Correlation.php`:

```php
<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use Illuminate\Support\Collection;

/**
 * Notion Illustration 2: an operational initiative is matched to the C-suite
 * priority it advances, and needs that priority's approval to go live.
 */
class Correlation
{
    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected MyGoals $goals,
    ) {}

    /** @return Collection<int, SearchUserChat> published, owner-authored, unlinked strategies in the user's organization */
    public function candidates(User $user, ?SearchUserChat $except = null): Collection
    {
        $org = $user->organization_id ? Organization::find($user->organization_id) : null;
        if (! $org || ! $org->owner_user_id) {
            return collect();
        }

        return SearchUserChat::where('status', 'published')->where('organization_id', $org->id)
            ->where('user_id', $org->owner_user_id)->whereNull('parent_chat_id')
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->orderByDesc('published_at')->get();
    }

    public function isCandidate(User $user, SearchUserChat $chat, int $parentId): bool
    {
        return $this->candidates($user, $chat)->contains(fn (SearchUserChat $c) => (int) $c->id === $parentId);
    }

    /** Leaders other than the owner go through approval once there is a priority to roll up into. */
    public function requiresApproval(User $user): bool
    {
        $ownerId = $user->organization_id ? Organization::whereKey($user->organization_id)->value('owner_user_id') : null;

        return $ownerId !== null && (int) $ownerId !== (int) $user->id && $this->candidates($user)->isNotEmpty();
    }

    /** @return array{id: int, score: int, reason: string|null}|null the best-scoring candidate */
    public function match(SearchUserChat $chat, User $user): ?array
    {
        $candidates = $this->candidates($user, $chat);
        if ($candidates->isEmpty()) {
            return null;
        }

        $line = fn ($v) => str_replace('---', '--', trim((string) preg_replace('/\s+/', ' ', (string) $v)));
        $list = $candidates->map(fn (SearchUserChat $c) => '- id '.$c->id.' | goal: "'.$line($this->goals->companyGoal($c)).'" | path: "'.$line($c->selected_strategy).'"')->implode("\n");
        $roleGoals = ExpectedState::where('search_user_chat_id', $chat->id)->pluck('recommended_action')->map(fn ($a) => '- '.$line($a))->implode("\n") ?: '(none)';
        $system = 'You are an executive strategy analyst. Return ONLY valid JSON. No markdown, no code fences, no commentary.'.$this->docs->orgContextBlock($user);
        $prompt = "Operational initiative:\n"
            .'Goal: "'.$line($this->goals->companyGoal($chat))."\"\n"
            .'Path: "'.$line($chat->selected_strategy)."\"\n"
            ."Role goals:\n{$roleGoals}\n\n"
            ."Corporate priorities:\n{$list}\n\n"
            ."Score how strongly the initiative advances each corporate priority, from 0 (unrelated) to 100 (directly advances it).\n"
            .'Output exactly: {"matches":[{"id":<id>,"score":<0-100>,"reason":"<one sentence>"}]}';

        try {
            $response = $this->ai->generate($system, $prompt, 800, 0.2, true);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
        if (! $response->successful()) {
            return null;
        }

        $parsed = $this->ai->parseJson($this->ai->extractText($response));
        $ids = $candidates->pluck('id')->map(fn ($id) => (int) $id);
        $best = null;
        foreach (is_array($parsed['matches'] ?? null) ? $parsed['matches'] : [] as $row) {
            if (! is_array($row) || ! $ids->contains((int) ($row['id'] ?? 0))) {
                continue;
            }
            $score = filter_var($row['score'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
            if ($score === false || ($best !== null && $score <= $best['score'])) {
                continue;
            }
            $reason = mb_substr(trim(is_scalar($row['reason'] ?? null) ? (string) $row['reason'] : ''), 0, 300);
            $best = ['id' => (int) $row['id'], 'score' => $score, 'reason' => $reason !== '' ? $reason : null];
        }

        return $best;
    }
}
```

- [ ] **Step 6: StrategyPublishController**
  1. Imports: `use App\Services\Correlation;`, `use App\Services\MyGoals;`, `use App\Services\StrategyAlerts;`. Add `protected Correlation $correlation,` as the last constructor parameter.
  2. Replace `->isPublished();` in `publishedUnderLock` with `->isLocked();`. Change `publishedMeanwhile`'s message to `'This strategy has been published or sent for approval. Reload to see it.'`.
  3. In `show`: `if (! $record->isPublished())` → `if (! $record->isLocked())`.
  4. In `suggest`: `if ($chat->isPublished())` → `if ($chat->isLocked())`, with message `'This strategy is published or awaiting approval.'`.
  5. In `rankGoals`: `if ($chat->isPublished())` → `if ($chat->isLocked())`.
  6. In `publish()`, inside the transaction:
     - Replace `if ($chat->isPublished()) {` → `if ($chat->isLocked()) {`, with message `'This strategy is already published or awaiting approval.'`.
     - Directly before `$chat->forceFill([` (after the `$unlinked` check), insert:

```php
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
```

     - After `if ($result instanceof JsonResponse) { return $result; }`, insert:

```php
        if ($result->status === 'pending_approval' && ($approver = User::find($result->parentChat?->user_id))) {
            app(StrategyAlerts::class)->send($approver, localize('Approval requested').': '.app(MyGoals::class)->companyGoal($result),
                'dashboard/strategies', $user->name.' asks you to approve an initiative supporting "'.app(MyGoals::class)->companyGoal($result->parentChat).'".',
                'approval_request');
        }
```

  7. Add the two actions after `setWeight`:

```php
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
```

  8. In `payload()`, add to the returned array:

```php
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
```

  9. Routes, after `users-new-chat-goal-weight.index`:

```php
                Route::post('/users-new-chat-match', [StrategyPublishController::class, 'matchParent'])->middleware('throttle:10,1')->name('users-new-chat-match.index');
                Route::post('/users-new-chat-parent', [StrategyPublishController::class, 'setParent'])->name('users-new-chat-parent.index');
```

- [ ] **Step 7: Approve and reject** — in `StrategyOverview`, add:

```php
    /** The parent priority's author, or the organization owner. */
    public function canApprove(User $viewer, SearchUserChat $child): bool
    {
        $ownerId = Organization::whereKey((int) $child->organization_id)->value('owner_user_id');

        return (int) $viewer->id === (int) $ownerId || (int) $viewer->id === (int) $child->parentChat?->user_id;
    }
```

In `StrategyOverviewController`, add `use App\Services\MyGoals;` and `use App\Services\StrategyAlerts;`, then:

```php
    public function approve(Request $request, $chat, StrategyAlerts $alerts, MyGoals $goals): RedirectResponse
    {
        $record = $this->pendingFor($request, (int) $chat);
        // Conditional on still pending: a double click decides once.
        $decided = SearchUserChat::whereKey($record->id)->where('status', 'pending_approval')->update([
            'status' => 'published', 'published_at' => now(), 'approval_decided_by' => $request->user()->id,
            'approval_decided_at' => now(), 'approval_note' => null,
        ]);
        abort_unless($decided, 404);
        $this->tellRequester($record, $alerts, localize('Initiative approved').': '.$goals->companyGoal($record),
            $request->user()->name.' approved your initiative. It is now live for your organization.');
        flash(localize('Initiative approved and published'))->success();

        return back();
    }

    public function reject(Request $request, $chat, StrategyAlerts $alerts, MyGoals $goals): RedirectResponse
    {
        $data = $request->validate(['note' => 'required|string|max:500']);
        $record = $this->pendingFor($request, (int) $chat);
        $decided = SearchUserChat::whereKey($record->id)->where('status', 'pending_approval')->update([
            'status' => 'draft', 'published_by' => null, 'approval_decided_by' => $request->user()->id,
            'approval_decided_at' => now(), 'approval_note' => trim($data['note']),
        ]);
        abort_unless($decided, 404);
        $this->tellRequester($record, $alerts, localize('Initiative sent back').': '.$goals->companyGoal($record),
            $request->user()->name.': "'.trim($data['note']).'"');
        flash(localize('Initiative sent back to its author'))->success();

        return back();
    }

    private function pendingFor(Request $request, int $chatId): SearchUserChat
    {
        $record = SearchUserChat::whereKey($chatId)->where('status', 'pending_approval')
            ->where('organization_id', (int) $request->user()->organization_id)->with('parentChat')->first();
        abort_unless($record, 404);
        abort_unless($this->overview->canApprove($request->user(), $record), 403);

        return $record;
    }

    private function tellRequester(SearchUserChat $record, StrategyAlerts $alerts, string $title, string $body): void
    {
        $requester = \App\Models\User::whereKey($record->published_by ?: $record->user_id)
            ->where('organization_id', $record->organization_id)->first();
        if ($requester) {
            $alerts->send($requester, $title, 'dashboard/users-new-chat/'.$record->id, $body, 'approval_decision');
        }
    }
```

Routes, after `strategies.recourse`:

```php
                Route::post('/strategies/{chat}/approve', [StrategyOverviewController::class, 'approve'])->name('strategies.approve');
                Route::post('/strategies/{chat}/reject', [StrategyOverviewController::class, 'reject'])->name('strategies.reject');
```

- [ ] **Step 8: Run** → all `MiddleOutTest` pass; `php artisan test` (full) passes. **Commit** — `feat(middle-out): link initiatives to C-suite priorities and gate them on approval`.

---

### Task 2: Roll-up, approvals list, and the card

**Files:** `app/Services/StrategyOverview.php`; `app/Http/Controllers/Backend/StrategyOverviewController.php` (`index` passes `approvals`); `resources/views/backend/pages/strategies/index.blade.php` and `show.blade.php`; `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php`; test.

- [ ] **Step 1: Failing tests** — append:

```php
    private function approved(array $w): void
    {
        $this->link($w);
        $this->sendForApproval($w);
        $this->actingAs($w['ceo'])->post(route('strategies.approve', $w['initiative']->id));
    }

    public function test_approvals_are_listed_for_the_approver(): void
    {
        $w = $this->world();
        $this->link($w);
        $this->sendForApproval($w);

        $this->actingAs($w['ceo'])->get(route('strategies.index'))
            ->assertOk()
            ->assertSee('Awaiting your approval')
            ->assertSee('Automate contract review')
            ->assertSee('Dana Director')
            ->assertSee(route('strategies.approve', $w['initiative']->id), false)
            ->assertSee(route('strategies.reject', $w['initiative']->id), false);
    }

    public function test_initiatives_roll_up_into_their_priority(): void
    {
        $w = $this->world();
        $this->approved($w);

        $this->actingAs($w['ceo'])->get(route('strategies.index'))
            ->assertOk()->assertSee('Supports: Grow revenue 30%')->assertSee('1 supporting initiative');
        $this->actingAs($w['ceo'])->get(route('strategies.show', $w['priority']->id))
            ->assertOk()->assertSee('Supporting initiatives')->assertSee('Automate contract review')->assertSee('Dana Director');
    }

    public function test_the_card_carries_the_priority_controls(): void
    {
        $w = $this->world();

        $this->actingAs($w['director'])->get('/dashboard/users-new-chat/'.$w['initiative']->id)
            ->assertOk()->assertSee(route('users-new-chat-match.index'), false)->assertSee(route('users-new-chat-parent.index'), false);
    }
```

- [ ] **Step 2: Run** → FAIL.

- [ ] **Step 3: StrategyOverview**
  1. In `list()`, add to each row:

```php
                    'supports' => $chat->parent_chat_id && ($parent = SearchUserChat::find($chat->parent_chat_id)) ? $this->goals->companyGoal($parent) : null,
                    'supporting_count' => SearchUserChat::where('parent_chat_id', $chat->id)->where('status', 'published')->count(),
```

  2. In `detail()`, add to the returned array:

```php
            'supporting' => SearchUserChat::where('parent_chat_id', $chat->id)->where('status', 'published')->with('user:id,name')->orderByDesc('published_at')->get()
                ->map(function (SearchUserChat $child) {
                    $childIds = ExpectedState::where('search_user_chat_id', $child->id)->pluck('id');
                    $alignment = $this->alignment->forStrategy($child)['overall'];

                    return [
                        'chat' => $child,
                        'company_goal' => $this->goals->companyGoal($child),
                        'owner' => $child->user?->name,
                        'alignment' => $alignment,
                        'badge' => self::badge($alignment['rate'], GoalObstacle::whereIn('expected_state_id', $childIds)->count(), $this->flaggedBlocked($childIds)->count()),
                        'drift_index' => $child->drift_index !== null ? (float) $child->drift_index : null,
                        'drift_level' => $child->drift_level,
                    ];
                }),
```

  3. Add:

```php
    /** @return Collection<int, array<string, mixed>> initiatives waiting for this viewer's approval */
    public function approvalsFor(User $viewer): Collection
    {
        if (! $viewer->organization_id) {
            return collect();
        }

        return SearchUserChat::where('status', 'pending_approval')->where('organization_id', (int) $viewer->organization_id)
            ->with(['parentChat', 'publisher:id,name'])->orderBy('approval_requested_at')->get()
            ->filter(fn (SearchUserChat $c) => $this->canApprove($viewer, $c))
            ->map(fn (SearchUserChat $c) => [
                'chat' => $c,
                'company_goal' => $this->goals->companyGoal($c),
                'requester' => $c->publisher?->name,
                'supports' => $c->parentChat ? $this->goals->companyGoal($c->parentChat) : null,
                'score' => $c->correlation_score,
                'reason' => $c->correlation_reason,
                'budget' => (float) $c->resources()->sum('budget'),
                'goals' => ExpectedState::where('search_user_chat_id', $c->id)->count(),
            ])->values();
    }
```

- [ ] **Step 4: Controller** — `index` passes `'approvals' => $this->overview->approvalsFor($request->user())`.

- [ ] **Step 5: Views**

`index.blade.php`: directly after `<p class="text-muted small">{{ localize('Every published strategy in your organization.') }}</p>`, insert:

```blade
            @if ($approvals->isNotEmpty())
                <div class="card mb-3" style="border:1px solid #ec883f"><div class="card-body">
                    <h6 style="color:#9a5a12">{{ localize('Awaiting your approval') }}</h6>
                    @foreach ($approvals as $item)
                        <div class="border-top pt-2 mt-2">
                            <strong>{{ $item['company_goal'] }}</strong>
                            <div class="small text-muted">
                                {{ localize('From') }} {{ $item['requester'] }} · {{ localize('Supports') }}: {{ $item['supports'] }}
                                @if ($item['score'] !== null) · {{ localize('AI match') }} {{ $item['score'] }}/100 @endif
                                · {{ $item['goals'] }} {{ \Illuminate\Support\Str::plural('goal', $item['goals']) }}
                                · {{ localize('Budget') }} {{ config('custom.default_currency_symbol') ?: '$' }}{{ number_format($item['budget']) }}
                            </div>
                            @if ($item['reason'])<div class="small">{{ $item['reason'] }}</div>@endif
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <form method="POST" action="{{ route('strategies.approve', $item['chat']->id) }}">@csrf
                                    <button type="submit" class="btn btn-sm" style="background:#36839b;color:#fff">{{ localize('Approve and publish') }}</button>
                                </form>
                                <form method="POST" action="{{ route('strategies.reject', $item['chat']->id) }}" class="d-flex gap-2">@csrf
                                    <input type="text" name="note" required maxlength="500" class="form-control form-control-sm" style="box-sizing:border-box;min-width:260px" placeholder="{{ localize('Why send it back?') }}">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ localize('Send back') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div></div>
            @endif
```

In the list's goal cell, after `<div class="small text-muted">{{ $row['chat']->selected_strategy }}</div>`, add:

```blade
                                                @if ($row['supports'])<div class="small" style="color:#9a5a12">{{ localize('Supports') }}: {{ $row['supports'] }}</div>@endif
                                                @if ($row['supporting_count'])<div class="small text-muted">{{ $row['supporting_count'] }} {{ \Illuminate\Support\Str::plural('supporting initiative', $row['supporting_count']) }}</div>@endif
```

`show.blade.php`: directly before the "Departmental progress & deliverables" card (`<div class="card mb-3"><div class="card-body">` followed by `<h6>{{ localize('Departmental progress & deliverables') }}</h6>`), insert:

```blade
            @if ($supporting->isNotEmpty())
                <div class="card mb-3"><div class="card-body">
                    <h6>{{ localize('Supporting initiatives') }}</h6>
                    @foreach ($supporting as $child)
                        <div class="small mb-2">
                            <a href="{{ route('strategies.show', $child['chat']->id) }}" style="color:#2c6d82">{{ $child['company_goal'] }}</a>
                            <span class="text-muted">— {{ $child['owner'] }}</span>
                            @include('backend.pages.strategies.badge', ['badge' => $child['badge']])
                            @include('backend.pages.strategies.drift-pill', ['index' => $child['drift_index'], 'level' => $child['drift_level']])
                            <span class="text-muted">{{ $child['alignment']['committed'] }} of {{ $child['alignment']['people'] }} {{ localize('committed') }}</span>
                        </div>
                    @endforeach
                </div></div>
            @endif
```

- [ ] **Step 6: The card** — in `publish-gate.blade.php`:
1. Add to `urls`:
```js
        match: '{{ route('users-new-chat-match.index') }}',
        parent: '{{ route('users-new-chat-parent.index') }}',
```
2. Add above `function goalsHtml(`:
```js
    function priorityHtml() {
        if (state.status !== 'draft' || !state.requires_approval) return '';
        const current = state.parent ? state.parent.id : null;
        const opts = (state.parent_candidates || []).map(c => `<option value="${c.id}" ${c.id === current ? 'selected' : ''}>${esc(c.goal)}</option>`).join('');
        const match = state.parent && state.parent.score !== null && state.parent.score !== undefined
            ? `<div class="pg-hint">AI match ${state.parent.score}/100 — ${esc(state.parent.reason ?? '')}</div>` : '';
        return `<div class="pg-goals"><strong>Corporate priority this initiative supports</strong>
            <div class="pg-hint">Initiatives from directors and VPs roll up into a C-suite priority, and need its owner's approval before they go live.</div>
            <div class="d-flex flex-wrap gap-2 mt-1">
                <select class="form-select form-select-sm" data-parent ${busy ? 'disabled' : ''} style="max-width:480px">
                    ${current === null ? '<option value="" selected disabled>Pick a priority…</option>' : ''}${opts}
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-act="match" ${busy ? 'disabled' : ''}>Find the matching priority</button>
            </div>${match}</div>`;
    }

    async function matchParent() {
        const unsaved = unsavedRows();
        if (unsaved) state.rows = unsaved;
        busy = true; error = ''; render();
        try {
            state = await call(urls.match, { chat_id: chatId });
            if (unsaved && state.status === 'draft') state.rows = unsaved;
        }
        catch (e) { error = e.message; }
        busy = false; render();
    }

    async function setParent(parentId) {
        const unsaved = unsavedRows();
        if (unsaved) state.rows = unsaved;
        busy = true; error = ''; render();
        try {
            state = await call(urls.parent, { chat_id: chatId, parent_chat_id: parentId });
            if (unsaved && state.status === 'draft') state.rows = unsaved;
        }
        catch (e) { error = e.message; }
        busy = false; render();
    }
```
3. In `render()`, replace
```js
        const published = state.status === 'published';
        const editable = !published || state.is_publisher;
```
with
```js
        // Pending approval is as final as published for the author: nothing to edit.
        const published = state.status !== 'draft';
        const pending = state.status === 'pending_approval';
        const editable = !published || state.is_publisher;
```
   and change the `header` so that pending and rejected states read correctly, replacing `const header = published` with:
```js
        const header = pending
            ? `<div class="pg-published">Sent for approval to <strong>${esc(state.approval && state.approval.approver)}</strong> on ${esc(new Date(state.approval && state.approval.requested_at).toLocaleString())}. It goes live when they approve.</div>`
            : state.rejection && !published
            ? `<div class="pg-error">Sent back: ${esc(state.rejection.note)}</div><div class="pg-sub">Review the resources each department needs. This strategy stays private until it is published.</div>`
            : published
```
4. The publish button label: when `state.requires_approval`, show "Send for approval". Replace `'🔒 Commit Resources &amp; Publish'` in the enabled button with `(state.requires_approval ? '🔒 Commit Resources &amp; Send for Approval' : '🔒 Commit Resources &amp; Publish')`, and `'Click again to publish to your organization'` with `(state.requires_approval ? 'Click again to send for approval' : 'Click again to publish to your organization')`.
5. In the card template, insert `${priorityHtml()}` directly before `${goalsHtml(published)}`.
6. Click map: add `match: matchParent`.
7. `change` listener: right after the weight-select line, add:
```js
        const p = e.target.closest && e.target.closest('select[data-parent]');
        if (el && p && el.contains(p) && !busy && p.value !== '') return setParent(Number(p.value));
```

- [ ] **Step 7: Run** — `php artisan test --filter='MiddleOutTest|ExecutiveViewTest|PublishGateTest'`, the full suite, Pint, PHPStan, and `node --check` on the card script. **Commit** — `feat(middle-out): approvals list, roll-up, and priority controls in the card`.
