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
use App\Services\OrganizationService;
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
    private function fakeAi(string $text, int $status = 200, ?callable $during = null): void
    {
        $response = new ClientResponse(new PsrResponse($status, [], json_encode([
            'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
        ])));
        $ai = Mockery::mock(AiProviderService::class);
        $ai->shouldReceive('providerLabel')->andReturn('Fake');
        // $during runs while the "model is thinking", to stage a concurrent change.
        $ai->shouldReceive('generate')->andReturnUsing(function () use ($response, $during) {
            if ($during) {
                $during();
            }

            return $response;
        });
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

    public function test_owner_department_head_and_managers_can_publish_but_plain_members_cannot(): void
    {
        $w = $this->world();
        $orgs = app(OrganizationService::class);

        $this->assertTrue($orgs->canPublish($w['owner']), 'owner');
        $this->assertTrue($orgs->canPublish($w['head']), 'department head');
        $this->assertTrue($orgs->canPublish($w['manager']), 'has a direct report');
        $this->assertFalse($orgs->canPublish($w['report']), 'has a manager, no reports');
        $this->assertFalse($orgs->canPublish($w['member']), 'plain member');
    }

    public function test_a_user_without_an_organization_cannot_publish(): void
    {
        $loner = User::factory()->create(['email' => 'solo@example.com', 'user_type' => 'customer', 'organization_id' => null]);

        $this->assertFalse(app(OrganizationService::class)->canPublish($loner));
    }

    public function test_heading_a_department_in_another_organization_does_not_count(): void
    {
        $w = $this->world();
        $other = Organization::create(['domain' => 'globex.com', 'name' => 'Globex']);
        Department::create(['organization_id' => $other->id, 'name' => 'Ops', 'color' => '#EF4444', 'head_user_id' => $w['member']->id]);

        $this->assertFalse(app(OrganizationService::class)->canPublish($w['member']));
    }

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
            ->assertJsonPath('rows.0.budget', 9000);
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
            'rows' => [['id' => $row->id, 'department_id' => $w['sales']->id, 'department_name' => 'Sales', 'budget' => '50000.00', 'fte' => '2.00', 'tools' => '', 'notes' => '  ']],
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

    public function test_the_chat_page_carries_the_publish_card_script(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['owner']);

        $this->actingAs($w['owner'])->get('/dashboard/users-new-chat/'.$chat->id)
            ->assertOk()
            ->assertSee('publish-gate-card', false)
            ->assertSee(route('users-new-chat-resources.show', ['chat' => $chat->id]), false);
    }

    public function test_ai_amounts_ignore_words_that_start_with_k_or_m(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['head']);
        $this->fakeAi(json_encode(['rows' => [['department' => 'Sales', 'budget' => '$50k', 'fte' => '10 mentors', 'tools' => 'x', 'rationale' => 'y']]]));

        $this->actingAs($w['head'])->postJson(route('users-new-chat-resources-suggest.index'), ['chat_id' => $chat->id])->assertOk();

        $this->assertSame('10.00', StrategyResource::value('fte'));
        $this->assertSame('50000.00', StrategyResource::value('budget'));
    }

    public function test_ai_amounts_beyond_the_column_limits_are_dropped(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['head']);
        $this->fakeAi(json_encode(['rows' => [['department' => 'Sales', 'budget' => 25000000000, 'fte' => '20000', 'tools' => 'x', 'rationale' => 'y']]]));

        $this->actingAs($w['head'])->postJson(route('users-new-chat-resources-suggest.index'), ['chat_id' => $chat->id])->assertOk();

        $row = StrategyResource::first();
        $this->assertNull($row->budget);
        $this->assertNull($row->fte);
    }

    public function test_suggest_does_not_overwrite_a_strategy_published_while_the_ai_was_thinking(): void
    {
        $w = $this->world();
        $chat = $this->withRow($this->finishedChat($w['head']), $w['sales']);
        $this->fakeAi(
            json_encode(['rows' => [['department' => 'Engineering', 'budget' => 1, 'fte' => 1, 'tools' => 'x', 'rationale' => 'y']]]),
            200,
            fn () => DB::table('search_user_chat')->where('id', $chat->id)
                ->update(['status' => 'published', 'published_by' => $w['head']->id, 'published_at' => now()]),
        );

        $this->actingAs($w['head'])->postJson(route('users-new-chat-resources-suggest.index'), ['chat_id' => $chat->id])
            ->assertStatus(409);
        $this->assertSame(['Sales'], StrategyResource::pluck('department_name')->all());
    }

    public function test_the_publisher_can_still_amend_after_a_department_is_deleted(): void
    {
        $w = $this->world();
        $chat = $this->published($w['owner'], $w['sales']);
        $row = $chat->resources()->first();
        $w['sales']->delete();

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id,
            'rows' => [['id' => $row->id, 'department_id' => $w['sales']->id, 'department_name' => 'Sales', 'budget' => 60000, 'fte' => 2]],
        ])->assertOk();

        $this->assertSame('60000.00', $row->fresh()->budget);
    }

    public function test_a_draft_row_survives_its_department_being_deleted(): void
    {
        $w = $this->world();
        $chat = $this->withRow($this->finishedChat($w['member']), $w['eng']);
        $row = $chat->resources()->first();
        $w['eng']->delete();

        $this->actingAs($w['member'])->postJson(route('users-new-chat-resources-save.index'), [
            'chat_id' => $chat->id,
            'rows' => [['id' => $row->id, 'department_id' => $w['eng']->id, 'department_name' => 'Engineering', 'budget' => 7]],
        ])->assertOk();

        $this->assertSame('Engineering', $row->fresh()->department_name);
        $this->assertSame('7.00', $row->fresh()->budget);
    }
}
