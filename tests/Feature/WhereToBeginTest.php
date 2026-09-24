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
}
