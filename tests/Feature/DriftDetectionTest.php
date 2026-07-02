<?php

namespace Tests\Feature;

use App\Models\ExpectedState;
use App\Models\ObservedState;
use App\Models\SearchUserChat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriftDetectionTest extends TestCase
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

    public function test_timeline_drift_identified_when_target_date_passed_and_incomplete(): void
    {
        $user = User::factory()->create();

        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        // Commitment with past date (Overdue)
        $state = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'Marketing',
            'recommended_action' => 'Launch ad campaign',
            'decision' => 'act_on_it',
            'success_metric' => 'CTR > 5%',
            'target_date' => Carbon::now()->subDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals('Timeline Drift', $data['states'][0]['drift_status']);
    }

    public function test_dependency_blocked_identified_when_dependency_is_blocked(): void
    {
        $user = User::factory()->create();

        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        // Dependency task (Commitment A)
        $depState = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'Data Analytics',
            'recommended_action' => 'Create Heatmap Audit',
            'decision' => 'act_on_it',
            'success_metric' => 'Audit delivered',
        ]);

        // Blocked observation on Commitment A
        ObservedState::create([
            'expected_state_id' => $depState->id,
            'status' => 'Blocked',
            'actual_value' => 'Data stream broken',
            'observation_date' => Carbon::now()->toDateString(),
        ]);

        // Dependent task (Commitment B depends on Commitment A)
        $state = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'depends_on_id' => $depState->id,
            'role' => 'Marketing',
            'recommended_action' => 'Adjust campaign targets',
            'decision' => 'act_on_it',
            'success_metric' => 'Targets updated',
        ]);

        $response = $this->actingAs($user)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));

        $response->assertOk();
        $data = $response->json();

        // Get the state record for Marketing (dependent)
        $marketingState = collect($data['states'])->firstWhere('role', 'Marketing');

        $this->assertEquals('Dependency Blocked', $marketingState['drift_status']);

        // Assert leadership alerts contains warning message
        $this->assertCount(1, $data['leadership_alerts']);
        $this->assertStringContainsString('Marketing', $data['leadership_alerts'][0]);
        $this->assertStringContainsString('Data Analytics', $data['leadership_alerts'][0]);
    }

    public function test_dependency_blocked_identified_when_dependency_is_overdue(): void
    {
        $user = User::factory()->create();

        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        // Overdue dependency task (Commitment A)
        $depState = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'Data Analytics',
            'recommended_action' => 'Create Heatmap Audit',
            'decision' => 'act_on_it',
            'success_metric' => 'Audit delivered',
            'target_date' => Carbon::now()->subDays(1)->toDateString(),
        ]);

        // Dependent task (Commitment B depends on Commitment A)
        $state = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'depends_on_id' => $depState->id,
            'role' => 'Marketing',
            'recommended_action' => 'Adjust campaign targets',
            'decision' => 'act_on_it',
            'success_metric' => 'Targets updated',
        ]);

        $response = $this->actingAs($user)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));

        $response->assertOk();
        $data = $response->json();

        $marketingState = collect($data['states'])->firstWhere('role', 'Marketing');

        $this->assertEquals('Dependency Blocked', $marketingState['drift_status']);
        $this->assertCount(1, $data['leadership_alerts']);
    }

    public function test_capacity_drift_when_completed_below_achievement_threshold(): void
    {
        $user = User::factory()->create();
        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        $state = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'VP Sales',
            'recommended_action' => 'Engage distributors',
            'decision' => 'act_on_it',
            'success_metric' => 'Qualified partners identified',
            'target_value' => '10 partnerships',
            'resources_committed' => true,
            'target_date' => Carbon::now()->addDays(30)->toDateString(),
        ]);

        ObservedState::create([
            'expected_state_id' => $state->id,
            'status' => 'Complete',
            'actual_value' => '4 partnerships',
            'observation_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));

        $response->assertOk();
        $result = $response->json('states.0');

        $this->assertEquals('Capacity Drift', $result['drift_status']);
        $this->assertEquals(0.4, $result['achievement_rate']);
        $this->assertEquals(0.6, $result['drift_magnitude']);

        // Drift transition is persisted for audit history
        $this->assertDatabaseHas('drift_events', [
            'expected_state_id' => $state->id,
            'drift_type' => 'Capacity Drift',
            'severity' => 'High',
        ]);
    }

    public function test_no_drift_when_completed_at_target(): void
    {
        $user = User::factory()->create();
        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        $state = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'VP Sales',
            'recommended_action' => 'Engage distributors',
            'decision' => 'act_on_it',
            'success_metric' => 'Qualified partners identified',
            'target_value' => '10',
            'resources_committed' => true,
        ]);

        ObservedState::create([
            'expected_state_id' => $state->id,
            'status' => 'Complete',
            'actual_value' => '10',
            'observation_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));

        $response->assertOk();
        $this->assertEquals('None', $response->json('states.0.drift_status'));
        $this->assertEquals(1.0, $response->json('states.0.achievement_rate'));
        $this->assertDatabaseMissing('drift_events', ['expected_state_id' => $state->id]);
    }

    public function test_capacity_drift_when_resources_not_committed(): void
    {
        $user = User::factory()->create();
        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'Marketing',
            'recommended_action' => 'Launch localized campaigns',
            'decision' => 'act_on_it',
            'success_metric' => 'CTR > 5%',
            'resources_committed' => false,
        ]);

        $response = $this->actingAs($user)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));

        $response->assertOk();
        $this->assertEquals('Capacity Drift', $response->json('states.0.drift_status'));
    }

    public function test_priority_drift_when_no_progress_logged_past_midpoint(): void
    {
        $user = User::factory()->create();
        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => 'Goal Sync output',
            'status1' => 1,
            'status2' => 1,
        ]);

        $state = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'Head of L&D',
            'recommended_action' => 'Run competitive workshop',
            'decision' => 'act_on_it',
            'success_metric' => 'Workshop completed',
            'resources_committed' => true,
            'target_date' => Carbon::now()->addDays(2)->toDateString(),
        ]);

        // Backdate the commitment so we are past the midpoint with no observation
        $state->created_at = Carbon::now()->subDays(10);
        $state->save();

        $response = $this->actingAs($user)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));

        $response->assertOk();
        $this->assertEquals('Priority Drift', $response->json('states.0.drift_status'));
    }
}
