<?php

namespace Tests\Feature;

use App\Models\ExpectedState;
use App\Models\GoalResponse;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\DriftIndex;
use App\Services\ImpactRanking;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
use Tests\TestCase;

/**
 * Impact ranking and action weights (Features spec, phase 7 / Notion Epic 1).
 */
class ImpactRankingTest extends TestCase
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
        $this->freezeTime();
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $product = OrgRole::create(['organization_id' => $org->id, 'name' => 'Product']);
        $ceo = User::factory()->create(['email' => 'ceo@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id]);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $rep = User::factory()->create(['email' => 'rep@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id, 'org_role_id' => $sales->id, 'manager_id' => $ceo->id]);
        $pm = User::factory()->create(['email' => 'pm@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id, 'org_role_id' => $product->id, 'manager_id' => $ceo->id]);
        $chat = SearchUserChat::create(['user_id' => $ceo->id, 'status1' => 0, 'selected_strategy' => 'Upsell', 'selected_scenario' => 'Expected', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $ceo->id, 'search' => 'Grow revenue 30%', 'response' => 'ok']);
        $due = now()->addDays(10)->toDateString();
        $salesGoal = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion', 'org_role_id' => $sales->id, 'target_date' => $due]);
        $productGoal = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Product', 'recommended_action' => 'Ship SOC2', 'org_role_id' => $product->id, 'target_date' => $due]);

        return compact('org', 'ceo', 'rep', 'pm', 'chat', 'salesGoal', 'productGoal');
    }

    private function publish(array $w): void
    {
        $end = now()->addDays(10)->endOfDay();
        $w['chat']->forceFill(['status' => 'published', 'published_by' => $w['ceo']->id, 'organization_id' => $w['org']->id,
            'published_at' => now()->subSeconds($end->getTimestamp() - now()->getTimestamp())])->save();
    }

    private function fakeAi(string $text, int $status = 200): void
    {
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturn(new ClientResponse(new PsrResponse($status, [], '{}')));
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    public function test_ranking_stores_scores_reasons_and_default_weights(): void
    {
        $w = $this->world();
        $this->fakeAi(json_encode(['scores' => [
            ['id' => $w['salesGoal']->id, 'score' => '8', 'reason' => 'Directly converts accounts'],
            ['id' => $w['productGoal']->id, 'score' => 11, 'reason' => 'Out of range'],
            ['id' => 99999, 'score' => 5, 'reason' => 'Unknown goal'],
        ]]));

        $this->assertTrue(app(ImpactRanking::class)->rank($w['chat'], $w['ceo']));

        $sales = $w['salesGoal']->fresh();
        $this->assertSame([8, 'Directly converts accounts', 4], [$sales->impact_score, $sales->impact_reason, $sales->weight]);
        $this->assertNull($w['productGoal']->fresh()->impact_score);
    }

    public function test_re_ranking_keeps_a_manual_weight(): void
    {
        $w = $this->world();
        $w['salesGoal']->update(['weight' => 1]);
        $this->fakeAi(json_encode(['scores' => [['id' => $w['salesGoal']->id, 'score' => 10, 'reason' => 'x']]]));

        app(ImpactRanking::class)->rank($w['chat'], $w['ceo']);

        $this->assertSame([10, 1], [$w['salesGoal']->fresh()->impact_score, $w['salesGoal']->fresh()->weight]);
    }

    public function test_an_unusable_reply_ranks_nothing(): void
    {
        $w = $this->world();
        $this->fakeAi('{"scores":[{"id":1,"score":"8.5"}]}');

        $this->assertFalse(app(ImpactRanking::class)->rank($w['chat'], $w['ceo']));
    }

    public function test_the_drift_index_is_weighted(): void
    {
        $w = $this->world();
        $this->publish($w);
        GoalResponse::create(['expected_state_id' => $w['salesGoal']->id, 'user_id' => $w['rep']->id, 'decision' => 'act_on_it', 'progress_status' => 'in_progress', 'progress_pct' => 30]); // drift 40
        GoalResponse::create(['expected_state_id' => $w['productGoal']->id, 'user_id' => $w['pm']->id, 'decision' => 'act_on_it', 'progress_status' => 'in_progress', 'progress_pct' => 50]); // drift 0
        $drift = app(DriftIndex::class);

        $this->assertEqualsWithDelta(20.0, $drift->evaluate($w['chat']->fresh(), alert: false)['index'], 0.1); // no weights → plain average

        $w['salesGoal']->update(['weight' => 5]);
        $w['productGoal']->update(['weight' => 1]);
        $this->assertEqualsWithDelta(33.3, $drift->evaluate($w['chat']->fresh(), alert: false)['index'], 0.1);
    }

    public function test_the_author_ranks_goals_from_the_publish_card(): void
    {
        $w = $this->world();
        $this->fakeAi(json_encode(['scores' => [['id' => $w['salesGoal']->id, 'score' => 9, 'reason' => 'Decisive'], ['id' => $w['productGoal']->id, 'score' => 3, 'reason' => 'Enabler']]]));

        $this->actingAs($w['ceo'])->postJson(route('users-new-chat-rank-goals.index'), ['chat_id' => $w['chat']->id])
            ->assertOk()
            ->assertJsonPath('goals.0.impact_score', 9)
            ->assertJsonPath('goals.0.weight', 5)
            ->assertJsonPath('goals.1.impact_reason', 'Enabler');
    }

    public function test_ranking_is_refused_after_publishing_and_reports_ai_failure(): void
    {
        $w = $this->world();
        $this->fakeAi('nope', 500);
        $this->actingAs($w['ceo'])->postJson(route('users-new-chat-rank-goals.index'), ['chat_id' => $w['chat']->id])->assertStatus(502);

        $this->publish($w);
        $this->actingAs($w['ceo'])->postJson(route('users-new-chat-rank-goals.index'), ['chat_id' => $w['chat']->id])->assertStatus(409);
        $this->actingAs($w['rep'])->postJson(route('users-new-chat-rank-goals.index'), ['chat_id' => $w['chat']->id])->assertForbidden();
    }

    public function test_the_author_sets_a_weight_until_publishing(): void
    {
        $w = $this->world();
        $other = SearchUserChat::create(['user_id' => $w['ceo']->id, 'status1' => 0]);
        $foreignGoal = ExpectedState::create(['search_user_chat_id' => $other->id, 'role' => 'X', 'recommended_action' => 'Y']);
        $post = fn (array $body) => $this->actingAs($w['ceo'])->postJson(route('users-new-chat-goal-weight.index'), $body + ['chat_id' => $w['chat']->id]);

        $post(['goal_id' => $w['salesGoal']->id, 'weight' => 3])->assertOk();
        $this->assertSame(3, $w['salesGoal']->fresh()->weight);
        $post(['goal_id' => $w['salesGoal']->id, 'weight' => 6])->assertStatus(422);
        $post(['goal_id' => $foreignGoal->id, 'weight' => 2])->assertStatus(422);

        $this->publish($w);
        $post(['goal_id' => $w['salesGoal']->id, 'weight' => 1])->assertStatus(409);
        $this->assertSame(3, $w['salesGoal']->fresh()->weight);
    }

    public function test_the_pages_show_rank_controls_and_scores(): void
    {
        $w = $this->world();
        $w['salesGoal']->update(['impact_score' => 8, 'weight' => 4]);

        $this->actingAs($w['ceo'])->get('/dashboard/users-new-chat/'.$w['chat']->id)
            ->assertOk()->assertSee(route('users-new-chat-rank-goals.index'), false)->assertSee(route('users-new-chat-goal-weight.index'), false);

        $this->publish($w);
        $this->actingAs($w['ceo'])->get(route('strategies.show', $w['chat']->id))
            ->assertOk()->assertSee('8/10')->assertSee('weight 4');
    }
}
