<?php

namespace Tests\Feature;

use App\Services\AI\AiProviderService;
use App\Services\AI\OnboardingAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Tests\TestCase;

class OnboardingAgentTest extends TestCase
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

    /** Build the agent with a stubbed provider that returns fixed text. */
    private function agentReturning(string $text): OnboardingAgentService
    {
        $provider = $this->createMock(AiProviderService::class);
        $provider->method('generate')->willReturn(new Response(new \GuzzleHttp\Psr7\Response(200, [], '{}')));
        $provider->method('extractText')->willReturn($text);
        $provider->method('parseJson')->willReturnCallback(
            fn ($t) => is_array($d = json_decode((string) $t, true)) ? $d : null
        );

        return new OnboardingAgentService($provider);
    }

    public function test_the_seed_question_is_the_agreed_copy(): void
    {
        $this->assertSame(
            'To help SkillTricks anchor its intelligence in your daily reality: What is your current role, and what specific team or area of the organization do you directly drive or influence?',
            OnboardingAgentService::SEED_QUESTION
        );
    }

    public function test_a_follow_up_question_is_returned_as_a_single_line(): void
    {
        $agent = $this->agentReturning("  How many people sit under that remit today?  \n");

        $question = $agent->nextQuestion([
            ['question' => OnboardingAgentService::SEED_QUESTION, 'answer' => 'Head of Learning & OD'],
        ], null);

        $this->assertSame('How many people sit under that remit today?', $question);
    }

    public function test_a_multi_line_answer_is_collapsed_to_the_first_question(): void
    {
        // Rule 1 of the brief: one question per turn, never a list. If the model
        // sends more than one line anyway, only the first survives.
        $agent = $this->agentReturning("How many people report to you?\n- And what is your budget?\n- And your tenure?");

        $question = $agent->nextQuestion([
            ['question' => OnboardingAgentService::SEED_QUESTION, 'answer' => 'COO'],
        ], null);

        $this->assertSame('How many people report to you?', $question);
        $this->assertStringNotContainsString('budget', $question);
    }

    public function test_summarize_returns_a_validated_profile(): void
    {
        $agent = $this->agentReturning(json_encode([
            'role' => 'Chief Operating Officer',
            'rank' => 50,
            'scale' => '4,000 employees',
            'governance' => 'Quarterly OKRs',
            'frictions' => ['Slow handoffs', 'Unclear ownership'],
            'summary_bullets' => ['Drives operations globally', 'Owns quarterly OKR cadence'],
        ]));

        $profile = $agent->summarize([
            ['question' => OnboardingAgentService::SEED_QUESTION, 'answer' => 'COO'],
        ], null);

        $this->assertSame('Chief Operating Officer', $profile['role']);
        $this->assertSame(50, $profile['rank']);
        $this->assertSame(['Slow handoffs', 'Unclear ownership'], $profile['frictions']);
        $this->assertCount(2, $profile['summary_bullets']);
    }

    public function test_an_out_of_ladder_rank_is_clamped_to_the_floor(): void
    {
        // The model is untrusted at this boundary. An unrecognised rank must not
        // reach the cascade rule and must never grant unearned seniority.
        $agent = $this->agentReturning(json_encode([
            'role' => 'Supreme Leader',
            'rank' => 999,
            'scale' => 'unknown',
            'governance' => 'unknown',
            'frictions' => [],
            'summary_bullets' => ['Claims an unrecognised rank'],
        ]));

        $profile = $agent->summarize([['question' => 'q', 'answer' => 'a']], null);

        $this->assertSame(10, $profile['rank']);
    }

    public function test_unparseable_output_returns_null(): void
    {
        $agent = $this->agentReturning('I am afraid I cannot do that.');

        $this->assertNull($agent->summarize([['question' => 'q', 'answer' => 'a']], null));
    }
}
