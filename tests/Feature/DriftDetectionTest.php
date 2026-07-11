<?php

namespace Tests\Feature;

use App\Models\DriftEvent;
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

    public function test_multi_dimensional_checks_assumptions_and_assessment_recorded(): void
    {
        $user = User::factory()->create();

        // Contract JSON carrying the selected pathway's execution assumptions
        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => json_encode([
                'strategyMap' => [['id' => 's1', 'name' => 'Market-Specific Campaigns']],
                'selectedStrategyId' => 's1',
                'pathwayAssumptions' => ['s1' => ['Suitable partners can be recruited within 60 days.']],
            ]),
            'status1' => 1,
            'status2' => 1,
        ]);

        // Spec example: expected 10, observed 4, target date passed
        $state = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'VP Business Development',
            'recommended_action' => 'Onboard partner merchants',
            'decision' => 'act_on_it',
            'success_metric' => 'Partner merchants onboarded',
            'target_value' => '10',
            'target_date' => Carbon::now()->subDay()->toDateString(),
            'resources_committed' => true,
        ]);

        ObservedState::create([
            'expected_state_id' => $state->id,
            'actual_value' => '4',
            'status' => 'In Progress',
            'observation_date' => Carbon::now()->toDateString(),
            'source' => 'Manual',
        ]);

        // Downstream commitment depending on the drifting one → affected role
        ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'Marketing',
            'recommended_action' => 'Launch enrollment campaign',
            'decision' => 'act_on_it',
            'success_metric' => 'Customer enrollment',
            'target_date' => Carbon::now()->addDays(30)->toDateString(),
            'resources_committed' => true,
            'depends_on_id' => $state->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));

        $response->assertOk();
        $data = $response->json();
        $first = collect($data['states'])->firstWhere('role', 'VP Business Development');

        $this->assertTrue($first['drift_checks']['schedule']);
        $this->assertTrue($first['drift_checks']['performance']);
        $this->assertTrue($first['drift_checks']['assumption']);
        $this->assertFalse($first['drift_checks']['dependency']);
        $this->assertEquals(6, $first['gap']);
        $this->assertEquals('Overdue', $first['oi_status']);
        $this->assertContains('Marketing', $first['affected_roles']);
        $this->assertEquals('At Risk', $data['assumptions'][0]['status']);

        // Studio stores the assessment on the drift event
        $event = DriftEvent::where('expected_state_id', $state->id)->orderByDesc('id')->first();
        $this->assertEquals('Overdue', $event->status);
        $this->assertEquals('At Risk', $event->assumption_status);
        $this->assertEquals(6.0, $event->gap);
        $this->assertEquals(0.4, $event->progress);
        $this->assertContains('Marketing', $event->roles_impacted);
    }

    public function test_assumption_drift_is_per_kpi_when_assumptions_are_linked(): void
    {
        $user = User::factory()->create();

        $assumptionA = 'Suitable partners can be recruited within 60 days.';
        $assumptionB = 'The mobile app integration completes on schedule.';

        $chat = SearchUserChat::create([
            'user_id' => $user->id,
            'answers' => '{}',
            'response' => json_encode([
                'strategyMap' => [['id' => 's1', 'name' => 'Rewards Program']],
                'selectedStrategyId' => 's1',
                'pathwayAssumptions' => ['s1' => [$assumptionA, $assumptionB]],
            ]),
            'status1' => 1,
            'status2' => 1,
        ]);

        // KPI 1 drifts (overdue + below target) and tests assumption A.
        $drifting = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'VP Business Development',
            'recommended_action' => 'Onboard partner merchants',
            'decision' => 'act_on_it',
            'success_metric' => 'Partner merchants onboarded',
            'target_value' => '10',
            'target_date' => Carbon::now()->subDay()->toDateString(),
            'resources_committed' => true,
            'assumption_ref' => $assumptionA,
        ]);
        ObservedState::create([
            'expected_state_id' => $drifting->id,
            'actual_value' => '4',
            'status' => 'In Progress',
            'observation_date' => Carbon::now()->toDateString(),
            'source' => 'Manual',
        ]);

        // KPI 2 is healthy (complete at target) and tests assumption B.
        $healthy = ExpectedState::create([
            'search_user_chat_id' => $chat->id,
            'role' => 'IT Director',
            'recommended_action' => 'Integrate mobile app',
            'decision' => 'act_on_it',
            'success_metric' => 'Mobile app integration',
            'target_value' => '1',
            'target_date' => Carbon::now()->addDays(10)->toDateString(),
            'resources_committed' => true,
            'assumption_ref' => $assumptionB,
        ]);
        ObservedState::create([
            'expected_state_id' => $healthy->id,
            'actual_value' => '1',
            'status' => 'Complete',
            'observation_date' => Carbon::now()->toDateString(),
            'source' => 'Manual',
        ]);

        $response = $this->actingAs($user)->getJson(route('users-new-chat-progress-data.index', ['chat_id' => $chat->id]));

        $response->assertOk();
        $assumptions = collect($response->json('assumptions'))->keyBy('text');

        // The linked, drifting KPI flags ONLY its own assumption — not the other.
        $this->assertEquals('At Risk', $assumptions[$assumptionA]['status']);
        $this->assertEquals('VP Business Development', $assumptions[$assumptionA]['linked_role']);
        $this->assertEquals('Holding', $assumptions[$assumptionB]['status']);
        $this->assertEquals('IT Director', $assumptions[$assumptionB]['linked_role']);
    }
}
