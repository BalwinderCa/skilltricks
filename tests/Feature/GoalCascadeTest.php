<?php

namespace Tests\Feature;

use App\Mail\EmailManager;
use App\Models\ExpectedState;
use App\Models\GoalCascade;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Models\WrNotification;
use App\Services\AI\AiProviderService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * Cascading goals to direct reports (Features spec, phase 8 / Notion Epic 3).
 */
class GoalCascadeTest extends TestCase
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

    /**
     * ceo (owner, author) ← vp (Sales role) ← lead1, lead2; lead1 ← frontline.
     * One published strategy with a Sales goal.
     *
     * @return array<string, mixed>
     */
    private function world(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $make = fn (string $email, string $name, ?OrgRole $role, ?User $manager) => User::factory()->create([
            'email' => $email, 'name' => $name, 'user_type' => 'customer', 'organization_id' => $org->id,
            'org_role_id' => $role?->id, 'manager_id' => $manager?->id,
        ]);
        $ceo = $make('ceo@acme.com', 'Casey CEO', null, null);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $vp = $make('vp@acme.com', 'Val VP', $sales, $ceo);
        $lead1 = $make('lead1@acme.com', 'Lee Lead', null, $vp);
        $lead2 = $make('lead2@acme.com', 'Lou Lead', null, $vp);
        $frontline = $make('front@acme.com', 'Fran Front', null, $lead1);

        $chat = SearchUserChat::create(['user_id' => $ceo->id, 'status1' => 0, 'selected_strategy' => 'Upsell', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $ceo->id, 'search' => 'Grow revenue 30%', 'response' => 'ok']);
        $goal = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion', 'org_role_id' => $sales->id]);
        $chat->forceFill(['status' => 'published', 'published_by' => $ceo->id, 'published_at' => now(), 'organization_id' => $org->id])->save();

        return compact('org', 'sales', 'ceo', 'vp', 'lead1', 'lead2', 'frontline', 'chat', 'goal');
    }

    private function fakeAi(string $text): void
    {
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturn(new ClientResponse(new PsrResponse(200, [], '{}')));
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    private function suggestFor(array $w): void
    {
        $this->fakeAi(json_encode(['items' => [
            ['user_id' => $w['lead1']->id, 'text' => 'Draft the upgrade contract template'],
            ['user_id' => $w['lead2']->id, 'text' => 'List the 20 accounts closest to upgrade'],
            ['user_id' => $w['ceo']->id, 'text' => 'Not a direct report'],
        ]]));
        $this->actingAs($w['vp'])->post(route('my-goals.cascade.suggest'), ['goal_id' => $w['goal']->id])->assertRedirect();
    }

    public function test_suggest_drafts_one_sub_goal_per_direct_report(): void
    {
        $w = $this->world();

        $this->suggestFor($w);

        $drafts = GoalCascade::orderBy('id')->get();
        $this->assertSame([$w['lead1']->id, $w['lead2']->id], $drafts->pluck('assignee_user_id')->map(fn ($id) => (int) $id)->all());
        $this->assertTrue($drafts->every(fn ($d) => $d->sent_at === null && (int) $d->created_by === $w['vp']->id && (int) $d->expected_state_id === $w['goal']->id));
    }

    public function test_re_suggesting_replaces_only_unsent_drafts(): void
    {
        $w = $this->world();
        $sent = GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Already sent', 'sent_at' => now()]);
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead2']->id, 'text' => 'Old draft']);

        $this->suggestFor($w);

        $this->assertNotNull(GoalCascade::find($sent->id));
        $this->assertSame(0, GoalCascade::where('text', 'Old draft')->count());
        $this->assertSame(2, GoalCascade::whereNull('sent_at')->count());
    }

    public function test_a_manual_draft_must_go_to_a_direct_report(): void
    {
        $w = $this->world();

        $this->actingAs($w['vp'])->post(route('my-goals.cascade.add'), ['goal_id' => $w['goal']->id, 'assignee_id' => $w['lead2']->id, 'text' => 'Book the pricing review'])->assertRedirect();
        $this->actingAs($w['vp'])->post(route('my-goals.cascade.add'), ['goal_id' => $w['goal']->id, 'assignee_id' => $w['frontline']->id, 'text' => 'Skip a level'])->assertSessionHasErrors('assignee_id');

        $this->assertSame(['Book the pricing review'], GoalCascade::pluck('text')->all());
    }

    public function test_sending_edits_removes_and_alerts_assignees(): void
    {
        Mail::fake();
        $w = $this->world();
        $this->suggestFor($w);
        [$first, $second] = GoalCascade::orderBy('id')->get()->all();
        $foreign = GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['ceo']->id, 'assignee_user_id' => $w['vp']->id, 'text' => 'CEO draft']);

        $this->actingAs($w['vp'])->post(route('my-goals.cascade.send'), [
            'goal_id' => $w['goal']->id,
            'texts' => [$first->id => 'Draft the enterprise upgrade contract', $foreign->id => 'Hijacked'],
            'remove' => [$second->id],
        ])->assertRedirect();

        $this->assertSame('Draft the enterprise upgrade contract', $first->fresh()->text);
        $this->assertNotNull($first->fresh()->sent_at);
        $this->assertNull(GoalCascade::find($second->id));
        $this->assertSame(['CEO draft', null], [$foreign->fresh()->text, $foreign->fresh()->sent_at]);
        $this->assertSame([$w['lead1']->id], WrNotification::where('type', 'goal_cascade')->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        Mail::assertQueued(EmailManager::class, fn ($m) => $m->hasTo('lead1@acme.com'));
    }

    public function test_sending_nothing_alerts_nobody(): void
    {
        $w = $this->world();
        $this->suggestFor($w);
        $ids = GoalCascade::pluck('id')->all();

        $this->actingAs($w['vp'])->post(route('my-goals.cascade.send'), ['goal_id' => $w['goal']->id, 'remove' => $ids])->assertRedirect();

        $this->assertSame(0, GoalCascade::count());
        $this->assertSame(0, WrNotification::count());
    }

    public function test_the_assignee_reports_progress_and_can_cascade_further(): void
    {
        $w = $this->world();
        $item = GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Draft the contract', 'sent_at' => now()]);

        $this->actingAs($w['lead1'])->post(route('my-goals.cascade.progress'), ['cascade_id' => $item->id, 'status' => 'completed', 'pct' => 10, 'note' => 'Signed off'])->assertRedirect();
        $this->assertSame(['completed', 100, 'Signed off'], [$item->fresh()->status, $item->fresh()->pct, $item->fresh()->note]);

        $this->actingAs($w['lead1'])->post(route('my-goals.cascade.add'), ['cascade_id' => $item->id, 'assignee_id' => $w['frontline']->id, 'text' => 'Collect the redlines'])->assertRedirect();
        $child = GoalCascade::where('text', 'Collect the redlines')->first();
        $this->assertSame([$item->id, $w['goal']->id], [(int) $child->parent_id, (int) $child->expected_state_id]);
    }

    public function test_access_rules(): void
    {
        $w = $this->world();
        $item = GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Draft the contract', 'sent_at' => now()]);

        // Not the assignee.
        $this->actingAs($w['lead2'])->post(route('my-goals.cascade.progress'), ['cascade_id' => $item->id, 'status' => 'in_progress', 'pct' => 5])->assertNotFound();
        // No direct reports.
        $this->actingAs($w['lead2'])->post(route('my-goals.cascade.suggest'), ['cascade_id' => GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead2']->id, 'text' => 'x', 'sent_at' => now()])->id])->assertForbidden();
        // Goal the user cannot see (lead1 holds no role).
        $this->actingAs($w['lead1'])->post(route('my-goals.cascade.suggest'), ['goal_id' => $w['goal']->id])->assertNotFound();
        // Unpublished strategy.
        $w['chat']->forceFill(['status' => 'draft'])->save();
        $this->actingAs($w['lead1'])->post(route('my-goals.cascade.progress'), ['cascade_id' => $item->id, 'status' => 'in_progress', 'pct' => 5])->assertNotFound();
    }

    public function test_the_suggest_route_is_throttled(): void
    {
        $route = app('router')->getRoutes()->getByName('my-goals.cascade.suggest');

        $this->assertTrue(collect($route->gatherMiddleware())->contains(fn ($m) => str_starts_with($m, 'throttle:')));
    }

    public function test_a_manager_sees_the_cascade_controls_and_drafts(): void
    {
        $w = $this->world();
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Draft the contract']);

        $this->actingAs($w['vp'])->get('/dashboard')
            ->assertOk()
            ->assertSee('Cascade to your team')
            ->assertSee('Suggest line-item actions for my team')
            ->assertSee('Draft the contract')
            ->assertSee(route('my-goals.cascade.send'), false);
    }

    public function test_the_assignee_sees_sent_sub_goals_only(): void
    {
        $w = $this->world();
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Unsent draft']);
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'Draft the contract', 'sent_at' => now()]);

        $this->actingAs($w['lead1'])->get('/dashboard')
            ->assertOk()
            ->assertSee('Cascaded to you')
            ->assertSee('Draft the contract')
            ->assertSee('Val VP')
            ->assertSee('Grow revenue 30%')
            ->assertDontSee('Unsent draft')
            ->assertSee(route('my-goals.cascade.progress'), false)
            ->assertSee('Cascade to your team'); // lead1 has a report
    }

    public function test_the_executive_view_counts_sub_goals(): void
    {
        $w = $this->world();
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead1']->id, 'text' => 'A', 'sent_at' => now(), 'status' => 'completed']);
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead2']->id, 'text' => 'B', 'sent_at' => now()]);
        GoalCascade::create(['expected_state_id' => $w['goal']->id, 'created_by' => $w['vp']->id, 'assignee_user_id' => $w['lead2']->id, 'text' => 'Unsent']);

        $this->actingAs($w['ceo'])->get(route('strategies.show', $w['chat']->id))
            ->assertOk()->assertSee('2 sub-goals cascaded (1 completed)');
    }
}
