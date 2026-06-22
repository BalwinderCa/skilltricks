<?php

namespace Tests\Feature;

use App\Models\ExpectedState;
use App\Models\Intervention;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Tests\TestCase;

class AlignmentInterventionTest extends TestCase
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

    public function test_generate_intervention_calls_ai_engine_and_saves_successfully(): void
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
            'role' => 'Marketing',
            'recommended_action' => 'Launch ad campaign',
            'decision' => 'act_on_it',
            'success_metric' => 'CTR > 5%',
        ]);

        // Mock the application's AI Provider Service
        $aiMock = $this->mock(AiProviderService::class);

        // We mock generate to return a successful ClientResponse
        $mockGuzzleResponse = new Response(200, [], json_encode(['choices' => []]));
        $mockClientResponse = new ClientResponse($mockGuzzleResponse);

        $aiMock->shouldReceive('generate')
            ->once()
            ->andReturn($mockClientResponse);

        $aiMock->shouldReceive('extractText')
            ->once()
            ->with($mockClientResponse)
            ->andReturn('Mock AI Intervention: Reallocate resources to unblock the campaign.');

        $aiMock->shouldReceive('recordChatTokens')
            ->once()
            ->with($chat->id, $mockClientResponse)
            ->andReturn(120);

        $postData = [
            'expected_state_id' => $expectedState->id,
        ];

        $response = $this->actingAs($user)->postJson(route('users-new-chat-generate-intervention.index'), $postData);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'intervention' => [
                'expected_state_id' => $expectedState->id,
                'ai_recommendation' => 'Mock AI Intervention: Reallocate resources to unblock the campaign.',
                'status' => 'proposed',
            ],
        ]);

        $this->assertDatabaseHas('interventions', [
            'expected_state_id' => $expectedState->id,
            'ai_recommendation' => 'Mock AI Intervention: Reallocate resources to unblock the campaign.',
            'status' => 'proposed',
        ]);
    }

    public function test_activate_intervention_activates_successfully(): void
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
            'role' => 'Marketing',
            'recommended_action' => 'Launch ad campaign',
            'decision' => 'act_on_it',
            'success_metric' => 'CTR > 5%',
        ]);

        $intervention = Intervention::create([
            'expected_state_id' => $expectedState->id,
            'ai_recommendation' => 'Mock Recommendation',
            'status' => 'proposed',
        ]);

        $postData = [
            'intervention_id' => $intervention->id,
        ];

        $response = $this->actingAs($user)->postJson(route('users-new-chat-activate-intervention.index'), $postData);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'intervention' => [
                'id' => $intervention->id,
                'status' => 'active',
            ],
        ]);

        $this->assertDatabaseHas('interventions', [
            'id' => $intervention->id,
            'status' => 'active',
        ]);

        $this->assertNotNull(Intervention::find($intervention->id)->activated_at);
    }
}
