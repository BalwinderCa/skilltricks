<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\MyGoals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "My goal" dashboard card (Features spec, phase 3): each user sees their
 * role's goal from every published strategy in their organization.
 */
class MyGoalCardTest extends TestCase
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
     * Acme with a CEO (author), a Sales rep, a Product lead and a role-less
     * member; roles Sales and Product; department Sales.
     *
     * @return array{org: Organization, ceo: User, rep: User, pm: User, nobody: User, sales: OrgRole, product: OrgRole, salesDept: Department}
     */
    private function world(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $product = OrgRole::create(['organization_id' => $org->id, 'name' => 'Product']);
        $salesDept = Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E']);
        $make = fn (string $email, ?OrgRole $role, ?Department $dept = null) => User::factory()->create([
            'email' => $email, 'user_type' => 'customer', 'organization_id' => $org->id,
            'org_role_id' => $role?->id, 'department_id' => $dept?->id,
        ]);

        return [
            'org' => $org, 'sales' => $sales, 'product' => $product, 'salesDept' => $salesDept,
            'ceo' => $make('ceo@acme.com', null),
            'rep' => $make('rep@acme.com', $sales, $salesDept),
            'pm' => $make('pm@acme.com', $product),
            'nobody' => $make('nobody@acme.com', null),
        ];
    }

    /** A strategy by $author with one goal per role; published unless $draft. */
    private function publishedGoal(array $w, bool $draft = false): SearchUserChat
    {
        $chat = SearchUserChat::create([
            'user_id' => $w['ceo']->id, 'status1' => 0,
            'selected_strategy' => 'Security upsell', 'selected_scenario' => 'Expected',
            'leadership_brief' => "# Brief\nConvert mid-market accounts.",
        ]);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $w['ceo']->id, 'search' => 'Grow enterprise revenue 30%', 'response' => 'ok']);
        $product = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Product', 'recommended_action' => 'Ship SOC2 controls', 'org_role_id' => $w['product']->id]);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion', 'org_role_id' => $w['sales']->id, 'depends_on_id' => $product->id]);
        $chat->resources()->create(['department_id' => $w['salesDept']->id, 'department_name' => 'Sales', 'budget' => 50000, 'fte' => 2, 'tools' => 'CRM seats']);
        if (! $draft) {
            $chat->forceFill(['status' => 'published', 'published_by' => $w['ceo']->id, 'published_at' => now(), 'organization_id' => $w['org']->id])->save();
        }

        return $chat;
    }

    public function test_a_member_sees_their_roles_goal_with_context(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $cards = app(MyGoals::class)->for($w['rep']);

        $this->assertCount(1, $cards);
        $card = $cards->first();
        $this->assertSame('Launch the upgrade motion', $card['goal']->recommended_action);
        $this->assertSame('Grow enterprise revenue 30%', $card['company_goal']);
        $this->assertSame('Sales', $card['role_name']);
        $this->assertSame('50000.00', $card['resources']->budget);
        $this->assertSame('Ship SOC2 controls', $card['waiting_on']->recommended_action);
        $this->assertCount(0, $card['waiting_on_you']);
        $this->assertSame('Launch the upgrade motion', app(MyGoals::class)->for($w['pm'])->first()['waiting_on_you']->first()->recommended_action);
    }

    public function test_drafts_are_never_shown(): void
    {
        $w = $this->world();
        $this->publishedGoal($w, draft: true);

        $this->assertCount(0, app(MyGoals::class)->for($w['rep']));
    }

    public function test_a_user_without_a_role_sees_nothing_even_with_unlinked_goals(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoal($w);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Ghost', 'recommended_action' => 'Unlinked', 'org_role_id' => null]);

        $this->assertCount(0, app(MyGoals::class)->for($w['nobody']));
        $this->assertNull(app(MyGoals::class)->visibleGoal($w['nobody'], ExpectedState::where('role', 'Ghost')->value('id')));
    }

    public function test_another_organizations_strategy_is_invisible(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);
        $globex = Organization::create(['domain' => 'globex.com', 'name' => 'Globex']);
        $theirSales = OrgRole::create(['organization_id' => $globex->id, 'name' => 'Sales']);
        $spy = User::factory()->create(['email' => 'spy@globex.com', 'user_type' => 'customer', 'organization_id' => $globex->id, 'org_role_id' => $theirSales->id]);

        $this->assertCount(0, app(MyGoals::class)->for($spy));
        $this->assertNull(app(MyGoals::class)->visibleGoal($spy, ExpectedState::where('role', 'Sales')->value('id')));
    }

    public function test_a_member_without_a_matching_department_falls_back_to_the_whole_organization_row(): void
    {
        $w = $this->world();
        $chat = $this->publishedGoal($w);
        $chat->resources()->create(['department_id' => null, 'department_name' => 'Whole organization', 'budget' => 7]);

        $this->assertSame('Whole organization', app(MyGoals::class)->for($w['pm'])->first()['resources']->department_name);
    }

    private function salesGoalId(): int
    {
        return (int) ExpectedState::where('role', 'Sales')->value('id');
    }

    public function test_a_member_records_and_then_changes_their_decision(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['rep'])->from('/dashboard')->post(route('my-goals.decide'), ['goal_id' => $this->salesGoalId(), 'decision' => 'review_in_detail'])
            ->assertRedirect('/dashboard');
        $this->actingAs($w['rep'])->post(route('my-goals.decide'), ['goal_id' => $this->salesGoalId(), 'decision' => 'act_on_it']);

        $this->assertSame(1, GoalResponse::count());
        $this->assertSame('act_on_it', GoalResponse::first()->decision);
        $this->assertSame($w['rep']->id, (int) GoalResponse::first()->user_id);
    }

    public function test_deciding_on_a_goal_you_cannot_see_is_not_found(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['pm'])->post(route('my-goals.decide'), ['goal_id' => $this->salesGoalId(), 'decision' => 'act_on_it'])
            ->assertNotFound();
        $this->assertSame(0, GoalResponse::count());
    }

    public function test_an_unknown_decision_is_rejected(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['rep'])->post(route('my-goals.decide'), ['goal_id' => $this->salesGoalId(), 'decision' => 'maybe'])
            ->assertSessionHasErrors('decision');
    }

    public function test_a_member_reports_an_obstacle(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['rep'])->from('/dashboard')->post(route('my-goals.obstacle'), ['goal_id' => $this->salesGoalId(), 'body' => '  Missing budget for CRM seats  '])
            ->assertRedirect('/dashboard');

        $obstacle = GoalObstacle::first();
        $this->assertSame('Missing budget for CRM seats', $obstacle->body);
        $this->assertSame($w['rep']->id, (int) $obstacle->user_id);
    }

    public function test_a_blank_or_oversized_obstacle_is_rejected(): void
    {
        $w = $this->world();
        $this->publishedGoal($w);

        $this->actingAs($w['rep'])->post(route('my-goals.obstacle'), ['goal_id' => $this->salesGoalId(), 'body' => '   '])
            ->assertSessionHasErrors('body');
        $this->actingAs($w['rep'])->post(route('my-goals.obstacle'), ['goal_id' => $this->salesGoalId(), 'body' => str_repeat('x', 2001)])
            ->assertSessionHasErrors('body');
        $this->assertSame(0, GoalObstacle::count());
    }

    public function test_reporting_on_a_draft_goal_is_not_found(): void
    {
        $w = $this->world();
        $this->publishedGoal($w, draft: true);

        $this->actingAs($w['rep'])->post(route('my-goals.obstacle'), ['goal_id' => $this->salesGoalId(), 'body' => 'x'])
            ->assertNotFound();
    }
}
