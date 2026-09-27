<?php

namespace Tests\Feature;

use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
use Tests\TestCase;

/**
 * The first wizard step asked for insights that "MUST reference a specific
 * document by name" even when nothing was uploaded, so the model invented
 * file names ("Q3_2023_Sales_Report.pdf") and showed them as company facts.
 */
class DocumentInsightPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_with_no_documents_the_first_prompt_forbids_citing_any(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $chat = SearchUserChat::create(['user_id' => $user->id, 'status1' => 0]);

        $prompt = '';
        $ai = Mockery::mock(AiProviderService::class);
        $ai->shouldReceive('providerLabel')->andReturn('Fake');
        $ai->shouldReceive('generate')->andReturnUsing(function ($system, $userPrompt) use (&$prompt) {
            $prompt = $userPrompt;

            return new ClientResponse(new PsrResponse(200, [], '{}'));
        });
        $ai->shouldIgnoreMissing();
        $this->instance(AiProviderService::class, $ai);

        $this->actingAs($user)->postJson(route('users-new-chat-ask.index'), ['question' => 'Grow revenue 20%', 'chat_id' => $chat->id]);

        $this->assertStringContainsString('No company documents were uploaded', $prompt);
        $this->assertStringNotContainsString('MUST reference a specific document', $prompt);
    }
}
