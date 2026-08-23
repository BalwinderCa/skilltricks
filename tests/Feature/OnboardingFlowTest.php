<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\AI\OnboardingAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
    }

    private function customer(): User
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);

        return User::factory()->create([
            'email' => 'ceo@acme.com',
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
        ]);
    }

    public function test_the_interview_opens_with_the_seed_question(): void
    {
        $response = $this->actingAs($this->customer())->get(route('onboarding.index'));

        $response->assertOk();
        $response->assertSee('anchor its intelligence in your daily reality', false);
    }

    public function test_confirm_uses_the_session_profile_not_the_request_body(): void
    {
        $user = $this->customer();

        // A profile the interview actually produced, at rank 10.
        $this->withSession(['onboarding.profile' => [
            'role' => 'Business Analyst',
            'rank' => 10,
            'scale' => '12 people',
            'governance' => 'Weekly standups',
            'frictions' => ['Unclear priorities'],
            'summary_bullets' => ['Analyst on the ops team'],
        ], 'onboarding.turns' => [
            ['question' => OnboardingAgentService::SEED_QUESTION, 'answer' => 'Business Analyst'],
        ]])->actingAs($user)
            ->post(route('onboarding.confirm'), ['rank' => 60, 'role' => 'Board Member'])
            ->assertRedirect();

        // The posted rank 60 must be ignored entirely.
        $this->assertSame(10, (int) $user->fresh()->hierarchy_rank);
        $this->assertDatabaseHas('org_context_versions', ['user_id' => $user->id, 'rank' => 10]);
        $this->assertDatabaseMissing('org_context_versions', ['user_id' => $user->id, 'rank' => 60]);
    }

    public function test_confirm_without_a_session_profile_is_rejected(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->post(route('onboarding.confirm'), ['rank' => 60])
            ->assertRedirect(route('onboarding.index'));

        $this->assertNull($user->fresh()->hierarchy_rank);
        $this->assertDatabaseCount('org_context_versions', 0);
    }

    public function test_confirm_records_the_context_and_marks_the_user_calibrated(): void
    {
        $user = $this->customer();

        $this->withSession(['onboarding.profile' => [
            'role' => 'Chief Executive Officer',
            'rank' => 50,
            'scale' => '4,000 employees',
            'governance' => 'Quarterly OKRs',
            'frictions' => ['Slow handoffs'],
            'summary_bullets' => ['Runs the company'],
        ], 'onboarding.turns' => [
            ['question' => OnboardingAgentService::SEED_QUESTION, 'answer' => 'CEO'],
        ]])->actingAs($user)->post(route('onboarding.confirm'));

        $user = $user->fresh();

        $this->assertSame(50, (int) $user->hierarchy_rank);
        $this->assertSame(
            'Chief Executive Officer',
            $user->organization->fresh()->activeContext->profile['role']
        );
        // The transcript is preserved alongside the profile.
        $this->assertNotNull($user->organization->fresh()->activeContext->transcript);
    }
}
