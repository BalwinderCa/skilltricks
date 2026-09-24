<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\ExpectedState;
use App\Models\GoalResponse;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\Alignment;
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

    public function test_dropping_the_goal_after_committing_no_longer_counts(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoals($w);
        GoalResponse::create(['expected_state_id' => $this->goal('Sales')->id, 'user_id' => $w['rep1']->id, 'decision' => 'not_viable', 'starting_point' => 'X']);

        $this->assertSame(0, app(Alignment::class)->forStrategy($chat)['overall']['committed']);
    }

    public function test_shared_options_are_built_without_the_members_private_documents(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        Document::forceCreate([
            'user_id' => $w['rep1']->id, 'name' => 'Board memo', 'file_path' => 'x.pdf', 'file_name' => 'x.pdf',
            'parsed_text' => 'SECRET-ACQUISITION-PLAN', 'parse_status' => 'completed',
        ]);
        $system = null;
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturnUsing(function ($sys) use (&$system) {
            $system = $sys;

            return new ClientResponse(new PsrResponse(200, [], json_encode(['candidates' => [['content' => ['parts' => [['text' => self::OPTIONS]]]]]])));
        });
        $ai->shouldReceive('extractText')->andReturn(self::OPTIONS);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);

        app(StartingPoints::class)->ensureOptions($this->goal('Sales'), $w['rep1']);

        $this->assertNotNull($system);
        $this->assertStringContainsString('execution guide', $system);
        $this->assertStringNotContainsString('SECRET-ACQUISITION-PLAN', $system);
    }

    public function test_changing_a_goals_wording_or_role_clears_its_options(): void
    {
        $w = $this->world();
        $this->publishedGoals($w);
        $this->goal('Sales')->update(['starting_options' => ['A', 'B']]);

        $this->goal('Sales')->update(['success_metric' => 'Upgrades signed']);
        $this->assertSame(['A', 'B'], $this->goal('Sales')->starting_options);

        $this->goal('Sales')->update(['recommended_action' => 'A different action']);
        $this->assertNull($this->goal('Sales')->starting_options);

        $this->goal('Sales')->update(['starting_options' => ['A', 'B']]);
        $this->goal('Sales')->update(['org_role_id' => $w['product']->id]);
        $this->assertNull($this->goal('Sales')->starting_options);
    }
}
