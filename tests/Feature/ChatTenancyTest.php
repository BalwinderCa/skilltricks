<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\AI\AiProviderService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
use Tests\TestCase;

/**
 * Can one member of an organization read another's chat?
 *
 * Colleagues share an organization and its context, so "we are in the same org"
 * must not be enough to read each other's conversations.
 */
class ChatTenancyTest extends TestCase
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

    /** @return array{0: User, 1: User, 2: SearchUserChat} */
    private function twoColleaguesAndAChat(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);

        $ceo = User::factory()->create([
            'email' => 'ceo@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 50,
        ]);
        $ic = User::factory()->create([
            'email' => 'ic@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 10,
        ]);

        $chat = SearchUserChat::create([
            'user_id' => $ceo->id, 'status1' => 0,
            'response' => 'CONFIDENTIAL BOARD PLAN',
        ]);

        SearchUserChatData::create([
            'search_user_chat_id' => $chat->id,
            'user_id' => $ceo->id,
            'search' => 'should we acquire our competitor?',
            'response' => 'CONFIDENTIAL BOARD PLAN',
        ]);

        return [$ceo, $ic, $chat];
    }

    /** Bind a provider that records the system message instead of calling out. */
    private function captureSystemMessage(&$captured): void
    {
        $ai = Mockery::mock(AiProviderService::class);
        $ai->shouldReceive('providerLabel')->andReturn('Fake');
        $ai->shouldReceive('generate')->andReturnUsing(function ($systemMessage) use (&$captured) {
            $captured = $systemMessage;

            return new ClientResponse(new PsrResponse(200, [], json_encode([
                'candidates' => [['content' => ['parts' => [['text' => 'ok']]]]],
            ])));
        });
        $ai->shouldReceive('extractText')->andReturn('ok');
        $ai->shouldReceive('extractUsage')->andReturn([]);
        $ai->shouldReceive('recordChatTokens')->andReturn(0);
        $ai->shouldReceive('parseJson')->andReturn([]);
        $this->instance(AiProviderService::class, $ai);
    }

    public function test_a_colleagues_private_context_never_reaches_your_prompt(): void
    {
        [$ceo, $ic] = $this->twoColleaguesAndAChat();
        $chat = SearchUserChat::create(['user_id' => $ceo->id, 'status1' => 0]);
        $chat->appendAdditionalContext('SECRET: we are acquiring Globex in Q3');

        $captured = '';
        $this->captureSystemMessage($captured);

        // A chat id belonging to someone else, on an endpoint that used to load it
        // with find() and no ownership check at all.
        $this->actingAs($ic)->postJson(route('users-new-chat-update-strategy.index'), [
            'chat_id' => $chat->id, 'selected_strategy' => 'X', 'original_question' => 'Q',
        ]);

        $this->assertStringNotContainsString('SECRET: we are acquiring Globex', $captured);
    }

    public function test_a_colleague_cannot_change_your_scenario_through_update_scenario(): void
    {
        [$ceo, $ic] = $this->twoColleaguesAndAChat();
        $chat = SearchUserChat::create([
            'user_id' => $ceo->id, 'status1' => 0, 'selected_scenario' => 'Risk',
        ]);

        $captured = '';
        $this->captureSystemMessage($captured);

        $this->actingAs($ic)->postJson(route('users-new-chat-update-scenario.index'), [
            'chat_id' => $chat->id, 'selected_scenario' => 'Best Case',
            'original_question' => 'Q', 'is_user_selection' => true,
        ])->assertNotFound();

        $this->assertSame('Risk', $chat->fresh()->selected_scenario);
    }

    public function test_the_ceos_own_chat_shows_their_messages(): void
    {
        [$ceo, , $chat] = $this->twoColleaguesAndAChat();

        $this->actingAs($ceo)->get('/dashboard/users-new-chat/'.$chat->id)
            ->assertOk()
            ->assertSee('CONFIDENTIAL BOARD PLAN');
    }

    public function test_a_colleague_opening_the_same_chat_sees_none_of_it(): void
    {
        [, $ic, $chat] = $this->twoColleaguesAndAChat();

        // Same organization, same page, someone else's chat id.
        $this->actingAs($ic)->get('/dashboard/users-new-chat/'.$chat->id)
            ->assertOk()
            ->assertDontSee('CONFIDENTIAL BOARD PLAN')
            ->assertDontSee('should we acquire our competitor?');
    }

    public function test_a_colleague_cannot_delete_the_chat(): void
    {
        [$ceo, $ic, $chat] = $this->twoColleaguesAndAChat();

        $this->actingAs($ic)->get('/dashboard/users-chat-search-delete/'.$chat->id);

        $this->assertNotNull(SearchUserChat::find($chat->id), "another member's chat must survive");
        $this->assertSame($ceo->id, (int) SearchUserChat::find($chat->id)->user_id);
    }

    public function test_a_colleague_cannot_overwrite_the_chosen_scenario(): void
    {
        [, $ic, $chat] = $this->twoColleaguesAndAChat();
        $chat->update(['selected_scenario' => 'Risk Case']);

        $this->actingAs($ic)->postJson(route('users-new-chat-select-scenario.index'), [
            'chat_id' => $chat->id, 'strategy_id' => 's1', 'scenario_id' => 'sc1',
        ])->assertNotFound();

        $this->assertSame('Risk Case', $chat->fresh()->selected_scenario);
    }
}
