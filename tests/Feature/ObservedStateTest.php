<?php

namespace Tests\Feature;

use App\Models\ExpectedState;
use App\Models\ObservedState;
use App\Models\SearchUserChat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservedStateTest extends TestCase
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

    public function test_get_progress_data_fetches_successfully(): void
    {
        $user = User::factory()->create();

        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        $expectedState = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'Marketing Dept',
            'recommended_action' => 'Deploy A/B tests',
            'decision' => 'act_on_it',
            'success_metric' => '10% lift',
        ]);

        $observedState = ObservedState::create([
            'expected_state_id' => $expectedState->id,
            'actual_value' => '2% lift',
            'status' => 'In Progress',
            'observation_date' => '2026-06-22',
            'status_notes' => 'Some initial progress made.',
        ]);

        $response = $this->actingAs($user)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'states' => [
                '*' => [
                    'id',
                    'role',
                    'recommended_action',
                    'decision',
                    'latest_observation' => [
                        'id',
                        'expected_state_id',
                        'actual_value',
                        'status',
                    ]
                ]
            ]
        ]);
    }

    public function test_save_observed_state_saves_successfully(): void
    {
        $user = User::factory()->create();

        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        $expectedState = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'COO',
            'recommended_action' => 'Hold status meetings',
            'decision' => 'act_on_it',
            'success_metric' => 'Weekly meetings',
        ]);

        $postData = [
            'expected_state_id' => $expectedState->id,
            'status' => 'Complete',
            'actual_value' => '1 meeting held',
            'observation_date' => '2026-06-22',
            'status_notes' => 'Met on Tuesday.',
        ];

        $response = $this->actingAs($user)->postJson(route('users-new-chat-save-observed-state.index'), $postData);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('observed_states', [
            'expected_state_id' => $expectedState->id,
            'status' => 'Complete',
            'actual_value' => '1 meeting held',
            'observation_date' => '2026-06-22 00:00:00',
            'status_notes' => 'Met on Tuesday.',
        ]);
    }

    public function test_observed_state_actions_blocked_if_unauthorized(): void
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

        $expectedState = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'CEO',
            'recommended_action' => 'Authorize funds',
            'decision' => 'act_on_it',
        ]);

        // Trying to fetch progress data of User 1 as User 2
        $responseFetch = $this->actingAs($user2)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));
        $responseFetch->assertStatus(403);

        // Trying to log observed progress against User 1's state as User 2
        $postData = [
            'expected_state_id' => $expectedState->id,
            'status' => 'Complete',
            'actual_value' => 'Approved',
            'observation_date' => '2026-06-22',
        ];
        $responseSave = $this->actingAs($user2)->postJson(route('users-new-chat-save-observed-state.index'), $postData);
        $responseSave->assertStatus(403);
    }
}
