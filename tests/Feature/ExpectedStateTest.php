<?php

namespace Tests\Feature;

use App\Models\ExpectedState;
use App\Models\SearchUserChat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpectedStateTest extends TestCase
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
        $this->withoutMiddleware();
    }

    public function test_save_expected_state_decision_act_on_it_saves_successfully(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'user_type' => 'customer',
        ]);

        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        $postData = [
            'chat_id' => $chat->id,
            'role' => 'Marketing Head',
            'recommended_action' => 'Deploy A/B tested Localized Value Propositions.',
            'decision' => 'act_on_it',
            'success_metric' => '15% increase in click-through rate.',
            'target_value' => '15%',
            'target_date' => '2026-12-31',
            'resources_committed' => true,
        ];

        $response = $this->actingAs($user)->postJson(route('users-new-chat-save-expected-state.index'), $postData);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('expected_states', [
            'search_user_chat_id' => $chat->id,
            'role' => 'Marketing Head',
            'recommended_action' => 'Deploy A/B tested Localized Value Propositions.',
            'decision' => 'act_on_it',
            'success_metric' => '15% increase in click-through rate.',
            'target_value' => '15%',
            'target_date' => '2026-12-31 00:00:00',
            'resources_committed' => 1,
        ]);
    }

    public function test_save_expected_state_decision_review_in_detail_saves_successfully(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'user_type' => 'customer',
        ]);

        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        $postData = [
            'chat_id' => $chat->id,
            'role' => 'COO',
            'recommended_action' => 'Establish weekly silo-busting meeting.',
            'decision' => 'review_in_detail',
        ];

        $response = $this->actingAs($user)->postJson(route('users-new-chat-save-expected-state.index'), $postData);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('expected_states', [
            'search_user_chat_id' => $chat->id,
            'role' => 'COO',
            'decision' => 'review_in_detail',
            'success_metric' => null,
            'target_value' => null,
            'target_date' => null,
            'resources_committed' => 0,
        ]);
    }

    public function test_save_expected_state_fails_if_unauthorized(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $chat = SearchUserChat::create([
            'user_id' => $user1->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        $postData = [
            'chat_id' => $chat->id,
            'role' => 'COO',
            'recommended_action' => 'Establish weekly silo-busting meeting.',
            'decision' => 'review_in_detail',
        ];

        $response = $this->actingAs($user2)->postJson(route('users-new-chat-save-expected-state.index'), $postData);

        $response->assertStatus(403);
    }
}
