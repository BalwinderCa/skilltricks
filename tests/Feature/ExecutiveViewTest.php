<?php

namespace Tests\Feature;

use App\Models\DriftEvent;
use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Models\GoalRevision;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Executive view (Features spec, phase 5): leaders see every published
 * strategy in their organization, with alignment, drift and obstacles.
 */
class ExecutiveViewTest extends TestCase
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

    /** @return array<string, mixed> */
    private function world(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $ceo = User::factory()->create(['email' => 'ceo@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id]);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $rep = User::factory()->create(['email' => 'rep@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id, 'org_role_id' => $sales->id, 'manager_id' => $ceo->id]);

        return compact('org', 'sales', 'ceo', 'rep');
    }

    private function strategy(array $w, string $question, string $status = 'published', ?Organization $org = null): SearchUserChat
    {
        $org ??= $w['org'];
        $chat = SearchUserChat::create(['user_id' => $w['ceo']->id, 'status1' => 0, 'selected_strategy' => 'Upsell', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $w['ceo']->id, 'search' => $question, 'response' => 'ok']);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion', 'org_role_id' => $w['sales']->id]);
        $chat->forceFill(['status' => $status, 'published_by' => $w['ceo']->id, 'published_at' => now(), 'organization_id' => $org->id])->save();

        return $chat;
    }

    public function test_a_leader_sees_published_strategies_in_their_organization_only(): void
    {
        $w = $this->world();
        $this->strategy($w, 'Grow revenue 30%');
        $this->strategy($w, 'Secret draft', 'draft');
        $this->strategy($w, 'Their plan', 'published', Organization::create(['domain' => 'globex.com', 'name' => 'Globex']));

        $this->actingAs($w['ceo'])->get(route('strategies.index'))
            ->assertOk()
            ->assertSee('Grow revenue 30%')
            ->assertDontSee('Secret draft')
            ->assertDontSee('Their plan')
            ->assertSee('Not measured yet')
            ->assertSee('0 of 1');
    }

    public function test_non_leaders_are_refused(): void
    {
        $w = $this->world();
        $chat = $this->strategy($w, 'Grow revenue 30%');

        $this->actingAs($w['rep'])->get(route('strategies.index'))->assertForbidden();
        $this->actingAs($w['rep'])->get(route('strategies.show', $chat->id))->assertForbidden();
    }

    public function test_drafts_and_other_organizations_are_not_found(): void
    {
        $w = $this->world();
        $draft = $this->strategy($w, 'Secret draft', 'draft');
        $foreign = $this->strategy($w, 'Their plan', 'published', Organization::create(['domain' => 'globex.com', 'name' => 'Globex']));

        $this->actingAs($w['ceo'])->get(route('strategies.show', $draft->id))->assertNotFound();
        $this->actingAs($w['ceo'])->get(route('strategies.show', $foreign->id))->assertNotFound();
    }

    public function test_the_detail_page_shows_decisions_obstacles_and_revisions(): void
    {
        $w = $this->world();
        $chat = $this->strategy($w, 'Grow revenue 30%');
        $goal = ExpectedState::first();
        GoalResponse::create(['expected_state_id' => $goal->id, 'user_id' => $w['rep']->id, 'decision' => 'act_on_it', 'starting_point' => 'Book a sync']);
        GoalObstacle::create(['expected_state_id' => $goal->id, 'user_id' => $w['rep']->id, 'body' => 'CRM keeps timing out']);
        GoalRevision::create(['expected_state_id' => $goal->id, 'user_id' => $w['ceo']->id, 'old_text' => 'Old', 'new_text' => 'Launch the upgrade motion', 'reason' => 'Clarity']);

        $this->actingAs($w['ceo'])->get(route('strategies.show', $chat->id))
            ->assertOk()
            ->assertSee('Grow revenue 30%')
            ->assertSee('Launch the upgrade motion')
            ->assertSee('1 of 1')
            ->assertSee('CRM keeps timing out')
            ->assertSee('Clarity');
    }

    public function test_drift_status_uses_each_goals_latest_event(): void
    {
        $w = $this->world();
        $this->strategy($w, 'Grow revenue 30%');
        $goal = ExpectedState::first();

        DriftEvent::create(['expected_state_id' => $goal->id, 'drift_type' => 'Timeline Drift', 'detected_at' => now()->subDay()]);
        $this->actingAs($w['ceo'])->get(route('strategies.index'))->assertSee('Drift on 1 goal');

        DriftEvent::create(['expected_state_id' => $goal->id, 'drift_type' => 'None', 'detected_at' => now()]);
        $this->actingAs($w['ceo'])->get(route('strategies.index'))->assertSee('On track');
    }
}
