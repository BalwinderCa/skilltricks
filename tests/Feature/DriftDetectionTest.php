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
}
