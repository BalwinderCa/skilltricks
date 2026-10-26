<?php

namespace Tests\Feature;

use App\Http\Controllers\Backend\AI\AiChatController;
use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use App\Services\MyGoals;
use App\Services\RoleGoalLinker;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Role goals linked to real roles (Features spec, phase 2): every goal a
 * strategy produces is tied to one of the organization's own roles.
 */
class RoleGoalLinksTest extends TestCase
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

    private function org(string $domain = 'acme.com'): Organization
    {
        return Organization::create(['domain' => $domain, 'name' => ucfirst(strtok($domain, '.'))]);
    }

    private function role(Organization $org, string $name): OrgRole
    {
        return OrgRole::create(['organization_id' => $org->id, 'name' => $name]);
    }

    private function member(Organization $org, string $email, ?OrgRole $role = null): User
    {
        return User::factory()->create([
            'email' => $email, 'user_type' => 'customer',
            'organization_id' => $org->id, 'org_role_id' => $role?->id,
        ]);
    }

    /** @param  array<int, string>  $roleTexts */
    private function chatWithGoals(User $author, array $roleTexts): SearchUserChat
    {
        $chat = SearchUserChat::create([
            'user_id' => $author->id, 'status1' => 0,
            'selected_strategy' => 'Security upsell', 'selected_scenario' => 'Expected',
            'leadership_brief' => 'Brief.',
        ]);
        foreach ($roleTexts as $text) {
            ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => $text, 'recommended_action' => 'Act for '.$text, 'target_date' => now()->addMonths(3)]);
        }

        return $chat;
    }

    public function test_auto_link_matches_role_names_ignoring_case_and_spacing(): void
    {
        $org = $this->org();
        $sales = $this->role($org, 'VP of  Sales');
        $rnd = $this->role($org, 'R&D --- Ops');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['vp of sales ', 'R&D -- Ops', 'Chief Dreamer']);

        app(RoleGoalLinker::class)->autoLink($chat);

        $links = ExpectedState::orderBy('id')->pluck('org_role_id')->all();
        $this->assertSame([$sales->id, $rnd->id, null], array_map(fn ($v) => $v === null ? null : (int) $v, $links));
    }

    public function test_auto_link_ignores_same_named_roles_in_other_organizations(): void
    {
        $acme = $this->org();
        $globex = $this->org('globex.com');
        $this->role($globex, 'VP of Sales');
        $author = $this->member($acme, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['VP of Sales']);

        app(RoleGoalLinker::class)->autoLink($chat);

        $this->assertNull(ExpectedState::first()->org_role_id);
    }

    public function test_auto_link_never_overrides_a_manual_choice(): void
    {
        $org = $this->org();
        $this->role($org, 'VP of Sales');
        $director = $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['VP of Sales']);
        ExpectedState::first()->update(['org_role_id' => $director->id]);

        app(RoleGoalLinker::class)->autoLink($chat);

        $this->assertSame($director->id, (int) ExpectedState::first()->org_role_id);
    }

    public function test_assign_refuses_a_role_from_another_organization(): void
    {
        $acme = $this->org();
        $foreign = $this->role($this->org('globex.com'), 'Spy');
        $own = $this->role($acme, 'Director');
        $author = $this->member($acme, 'ceo@acme.com');
        $this->chatWithGoals($author, ['Director']);
        $goal = ExpectedState::first();
        $linker = app(RoleGoalLinker::class);

        $this->assertFalse($linker->assign($goal, $foreign->id, $author));
        $this->assertNull($goal->fresh()->org_role_id);
        $this->assertTrue($linker->assign($goal, $own->id, $author));
        $this->assertSame($own->id, (int) $goal->fresh()->org_role_id);
    }

    /** Bind an AI provider that records the user prompt it is sent. */
    private function capturePrompt(?string &$captured): void
    {
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturnUsing(function ($system, $prompt) use (&$captured) {
            $captured = $prompt;

            return new ClientResponse(new PsrResponse(200, [], json_encode(['candidates' => [['content' => ['parts' => [['text' => '{}']]]]]])));
        });
        $ai->shouldReceive('extractText')->andReturn('{}');
        $ai->shouldReceive('recordChatTokens')->andReturn(0);
        $ai->shouldReceive('parseJson')->andReturn([]);
        $this->instance(AiProviderService::class, $ai);
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function rolePrompts(): array
    {
        return [
            'wizard variant' => ['users-new-chat-generate-strategy-variant.index', ['original_question' => 'Grow', 'strategy_name' => 'Upsell']],
            'update strategy' => ['users-new-chat-update-strategy.index', ['original_question' => 'Grow', 'selected_strategy' => 'Upsell']],
            'update scenario' => ['users-new-chat-update-scenario.index', ['original_question' => 'Grow', 'selected_scenario' => 'Expected']],
        ];
    }

    public function test_a_shared_role_is_listed_once_per_department_and_still_links(): void
    {
        $org = $this->org();
        $vp = $this->role($org, 'Vice President');
        $this->role($org, 'Analyst');
        $author = $this->member($org, 'ceo@acme.com');
        foreach (['Sales', 'Marketing'] as $name) {
            $this->member($org, strtolower($name).'@acme.com', $vp)
                ->forceFill(['department_id' => Department::create(['organization_id' => $org->id, 'name' => $name, 'color' => '#000000'])->id])->save();
        }

        // Analyst is left out: nobody holds it, so a goal for it would reach nobody.
        $this->assertSame(
            ['Vice President (Marketing)', 'Vice President (Sales)'],
            app(DocumentContextService::class)->roleNamesFor($author)
        );

        $chat = $this->chatWithGoals($author, ['Vice President (Sales)', 'Vice President (Marketing)']);
        app(RoleGoalLinker::class)->autoLink($chat);

        $this->assertSame([$vp->id, $vp->id], ExpectedState::where('search_user_chat_id', $chat->id)->pluck('org_role_id')->map(fn ($id) => (int) $id)->all());

        // Same role, but each VP is sent only their own department's goal.
        $chat->update(['status' => 'published', 'organization_id' => $org->id]);
        foreach (['sales' => 'Vice President (Sales)', 'marketing' => 'Vice President (Marketing)'] as $who => $title) {
            $mine = app(MyGoals::class)->for(User::where('email', $who.'@acme.com')->first());
            $this->assertSame([$title], $mine->map(fn ($card) => $card['goal']->role)->all());
        }
    }

    public function test_an_organization_where_nobody_holds_a_role_is_still_offered_every_role(): void
    {
        $org = $this->org();
        $this->role($org, 'Analyst');
        $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');

        $this->assertSame(['Analyst', 'Director'], app(DocumentContextService::class)->roleNamesFor($author));
    }

    public function test_action_table_rows_with_invented_roles_are_dropped(): void
    {
        $controller = app(AiChatController::class);
        $drop = new \ReflectionMethod($controller, 'dropInventedRoles');
        $rows = fn (array $roles) => array_map(fn ($role) => ['role' => $role, 'action' => 'Act.'], $roles);
        $roster = ['CIO (IT Leadership)', 'Solutions Director (Business Solutions)'];

        $this->assertSame(
            ['CIO (IT Leadership)', 'Solutions Director (Business Solutions)'],
            array_column($drop->invoke($controller, $rows(['CIO (IT Leadership)', 'Sales Director (Sales & Marketing)', 'Solutions Director (Business Solutions)', 'C-Suite']), $roster), 'role')
        );
        // Nothing recognisable: keep every row so the author can pick roles by hand.
        $this->assertSame(['Wizard'], array_column($drop->invoke($controller, $rows(['Wizard']), $roster), 'role'));
        // No roster at all: nothing to check against.
        $this->assertSame(['Wizard'], array_column($drop->invoke($controller, $rows(['Wizard']), []), 'role'));
    }

    public function test_action_table_rows_get_their_department_titles_back(): void
    {
        $controller = app(AiChatController::class);
        $restore = new \ReflectionMethod($controller, 'restoreDepartmentTitles');
        $titles = ['Board', 'Vice President (Product)', 'Vice President (Sales)'];
        $roles = fn (array $given, string $text) => array_column($restore->invoke(
            $controller, array_map(fn ($role) => ['role' => $role, 'action' => 'Act.'], $given), $titles, $text
        ), 'role');

        $goals = '1. Board 2. Vice President (Product) 3. Vice President (Sales)';

        // Two bare rows would be saved as one goal: each takes a title, in order.
        $this->assertSame(['Board', 'Vice President (Product)', 'Vice President (Sales)'], $roles(['Board', 'Vice President', 'Vice President'], $goals));
        // A row that kept its title keeps it, and the other gets what is left.
        $this->assertSame(['Vice President (Sales)', 'Vice President (Product)'], $roles(['Vice President (Sales)', 'Vice President'], $goals));
        // Older chats whose goals never named a department are left alone.
        $this->assertSame(['Vice President'], $roles(['Vice President'], '1. Vice President'));
    }

    #[DataProvider('rolePrompts')]
    public function test_role_goal_prompts_use_only_the_organizations_roles(string $route, array $input): void
    {
        $org = $this->org();
        $this->role($org, 'Head of Sales');
        $this->role($org, 'VP of Product');
        $this->role($org, 'Data Lead');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, []);
        $captured = null;
        $this->capturePrompt($captured);

        $this->actingAs($author)->postJson(route($route), $input + ['chat_id' => $chat->id]);

        $this->assertNotNull($captured, 'the prompt was never sent');
        $this->assertStringContainsString('Use ONLY these role titles, exactly as written', $captured);
        $this->assertStringContainsString('Data Lead, Head of Sales, VP of Product', $captured);
        // All three, not a pick of them: every role needs a goal to be linked to.
        $this->assertStringContainsString('exactly 3', $captured);
        $this->assertStringContainsString('one goal for EVERY one of them', $captured);
        $this->assertStringNotContainsString('from documents', $captured);
        $this->assertStringNotContainsString('in the documents', $captured);
        $this->assertStringNotContainsString('in the company documents', $captured);
    }

    #[DataProvider('rolePrompts')]
    public function test_a_large_roster_asks_for_goals_spread_across_departments(string $route, array $input): void
    {
        $org = $this->org();
        $author = $this->member($org, 'ceo@acme.com');
        // Twelve roles held across three departments: more than the ten a strategy picks.
        foreach (['Sales' => 6, 'Product' => 4, 'Finance' => 2] as $dept => $n) {
            $department = Department::create(['organization_id' => $org->id, 'name' => $dept, 'color' => '#22C55E']);
            for ($i = 1; $i <= $n; $i++) {
                $holder = $this->member($org, strtolower($dept).$i.'@acme.com', $this->role($org, $dept.' Role '.$i));
                $holder->forceFill(['department_id' => $department->id])->save();
            }
        }
        $chat = $this->chatWithGoals($author, []);
        $captured = null;
        $this->capturePrompt($captured);

        $this->actingAs($author)->postJson(route($route), $input + ['chat_id' => $chat->id]);

        $this->assertNotNull($captured, 'the prompt was never sent');
        $this->assertStringContainsString('up to 10', $captured);
        $this->assertStringContainsString('cover at least 3 different departments', $captured);
        $this->assertStringContainsString('no more than 3 roles from any one department', $captured);
    }

    #[DataProvider('rolePrompts')]
    public function test_role_goal_prompts_keep_todays_wording_without_org_roles(string $route, array $input): void
    {
        $org = $this->org();
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, []);
        $captured = null;
        $this->capturePrompt($captured);

        $this->actingAs($author)->postJson(route($route), $input + ['chat_id' => $chat->id]);

        $this->assertNotNull($captured, 'the prompt was never sent');
        $this->assertStringNotContainsString('Use ONLY these role titles', $captured);
        $this->assertMatchesRegularExpression('/role titles (from the documents|that actually appear in the company documents|that exist in the documents)/', $captured);
    }

    public function test_the_card_auto_links_goals_and_lists_roles_with_member_counts(): void
    {
        $org = $this->org();
        $sales = $this->role($org, 'Head of Sales');
        $empty = $this->role($org, 'Data Lead');
        $author = $this->member($org, 'ceo@acme.com');
        $this->member($org, 'rep@acme.com', $sales);
        $chat = $this->chatWithGoals($author, ['Head of Sales', 'Chief Dreamer']);

        $this->actingAs($author)->getJson(route('users-new-chat-resources.show', ['chat' => $chat->id]))
            ->assertOk()
            ->assertJsonPath('goals.0.org_role_id', $sales->id)
            ->assertJsonPath('goals.0.org_role_name', 'Head of Sales')
            ->assertJsonPath('goals.1.org_role_id', null)
            ->assertJsonPath('goals.1.role_text', 'Chief Dreamer')
            ->assertJsonPath('roles.0.id', $empty->id)
            ->assertJsonPath('roles.0.member_count', 0)
            ->assertJsonPath('roles.1.member_count', 1);
    }

    public function test_an_author_without_an_organization_gets_no_roles_and_no_error(): void
    {
        $loner = User::factory()->create(['email' => 'solo@example.com', 'user_type' => 'customer', 'organization_id' => null]);
        $chat = $this->chatWithGoals($loner, ['Head of Sales']);

        $this->actingAs($loner)->getJson(route('users-new-chat-resources.show', ['chat' => $chat->id]))
            ->assertOk()
            ->assertJsonCount(0, 'roles')
            ->assertJsonPath('goals.0.org_role_id', null);
    }

    public function test_the_author_assigns_a_goal_to_a_role(): void
    {
        $org = $this->org();
        $director = $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['Chief Dreamer']);
        $goal = ExpectedState::first();

        $this->actingAs($author)->postJson(route('users-new-chat-goal-role.index'), [
            'chat_id' => $chat->id, 'goal_id' => $goal->id, 'org_role_id' => $director->id,
        ])->assertOk()->assertJsonPath('goals.0.org_role_name', 'Director');

        $this->assertSame($director->id, (int) $goal->fresh()->org_role_id);
    }

    public function test_assigning_another_strategys_goal_is_refused(): void
    {
        $org = $this->org();
        $director = $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $mine = $this->chatWithGoals($author, ['A']);
        $other = $this->chatWithGoals($author, ['B']);
        $otherGoal = ExpectedState::where('search_user_chat_id', $other->id)->first();

        $this->actingAs($author)->postJson(route('users-new-chat-goal-role.index'), [
            'chat_id' => $mine->id, 'goal_id' => $otherGoal->id, 'org_role_id' => $director->id,
        ])->assertStatus(422);

        $this->assertNull($otherGoal->fresh()->org_role_id);
    }

    public function test_assigning_a_role_from_another_organization_is_refused(): void
    {
        $org = $this->org();
        $foreign = $this->role($this->org('globex.com'), 'Spy');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['A']);

        $this->actingAs($author)->postJson(route('users-new-chat-goal-role.index'), [
            'chat_id' => $chat->id, 'goal_id' => ExpectedState::first()->id, 'org_role_id' => $foreign->id,
        ])->assertStatus(422);
    }

    public function test_assigning_on_someone_elses_strategy_is_refused(): void
    {
        $org = $this->org();
        $director = $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $intruder = $this->member($org, 'ic@acme.com');
        $chat = $this->chatWithGoals($author, ['A']);

        $this->actingAs($intruder)->postJson(route('users-new-chat-goal-role.index'), [
            'chat_id' => $chat->id, 'goal_id' => ExpectedState::first()->id, 'org_role_id' => $director->id,
        ])->assertForbidden();
    }

    public function test_links_are_final_once_published(): void
    {
        $org = $this->org();
        $director = $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['A']);
        $chat->forceFill(['status' => 'published', 'published_by' => $author->id, 'published_at' => now()])->save();

        $this->actingAs($author)->postJson(route('users-new-chat-goal-role.index'), [
            'chat_id' => $chat->id, 'goal_id' => ExpectedState::first()->id, 'org_role_id' => $director->id,
        ])->assertStatus(409);

        $this->assertNull(ExpectedState::first()->org_role_id);
    }

    /** A leader (department head) with a finished strategy that has one resource row. */
    private function publishable(array $roleTexts): array
    {
        $org = $this->org();
        $author = $this->member($org, 'ceo@acme.com');
        Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E', 'head_user_id' => $author->id]);
        $chat = $this->chatWithGoals($author, $roleTexts);
        $chat->resources()->create(['expected_state_id' => ExpectedState::where('search_user_chat_id', $chat->id)->value('id'), 'department_id' => null, 'department_name' => 'Whole organization', 'budget' => 1]);

        return [$org, $author, $chat];
    }

    public function test_publishing_is_refused_while_a_goal_has_no_role(): void
    {
        [, $author, $chat] = $this->publishable(['Chief Dreamer']);

        $this->actingAs($author)->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Link every goal to a role before publishing.');
        $this->assertFalse($chat->fresh()->isPublished());
    }

    public function test_publishing_succeeds_once_every_goal_is_linked(): void
    {
        [$org, $author, $chat] = $this->publishable(['Chief Dreamer']);
        $role = $this->role($org, 'Director');
        ExpectedState::first()->update(['org_role_id' => $role->id]);

        $this->actingAs($author)->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])->assertOk();
        $this->assertTrue($chat->fresh()->isPublished());
    }

    public function test_a_strategy_with_no_goals_cannot_be_published(): void
    {
        [, $author, $chat] = $this->publishable([]);

        $this->actingAs($author)->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])
            ->assertStatus(422)->assertJsonFragment(['error' => 'Finish the strategy (Complete Intelligence Cycle) so its role goals exist before publishing.']);
        $this->assertFalse($chat->fresh()->isPublished());
    }

    public function test_every_goal_needs_a_target_date_before_publishing(): void
    {
        [$org, $author, $chat] = $this->publishable(['Sales']);
        $goal = ExpectedState::where('search_user_chat_id', $chat->id)->first();
        $goal->update(['org_role_id' => $this->role($org, 'Director')->id, 'target_date' => null]);

        $publish = fn () => $this->actingAs($author)->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id]);
        $publish()->assertStatus(422)->assertJsonFragment(['error' => 'Set a target date for every goal before publishing.']);

        $this->actingAs($author)->postJson(route('users-new-chat-goal-date.index'), ['chat_id' => $chat->id, 'goal_id' => $goal->id, 'target_date' => now()->addMonth()->toDateString()])
            ->assertOk()->assertJsonPath('goals.0.target_date', now()->addMonth()->toDateString());
        $publish()->assertOk();

        // Locked once published, like role links and weights.
        $this->actingAs($author)->postJson(route('users-new-chat-goal-date.index'), ['chat_id' => $chat->id, 'goal_id' => $goal->id, 'target_date' => now()->addMonths(2)->toDateString()])
            ->assertStatus(409);
    }

    public function test_the_chat_page_carries_the_goal_role_route(): void
    {
        $org = $this->org();
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['A']);

        $this->actingAs($author)->get('/dashboard/users-new-chat/'.$chat->id)
            ->assertOk()
            ->assertSee(route('users-new-chat-goal-role.index'), false)
            ->assertSee('Goals and resources', false);
    }

    public function test_a_goal_linked_to_a_deleted_role_counts_as_unlinked(): void
    {
        [$org, $author, $chat] = $this->publishable(['Director']);
        $role = $this->role($org, 'Director');
        ExpectedState::first()->update(['org_role_id' => $role->id]);
        $role->delete();

        $this->actingAs($author)->getJson(route('users-new-chat-resources.show', ['chat' => $chat->id]))
            ->assertOk()
            ->assertJsonPath('goals.0.org_role_id', null)
            ->assertJsonPath('goals.0.org_role_name', null);
        $this->postJson(route('users-new-chat-publish.index'), ['chat_id' => $chat->id])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Link every goal to a role before publishing.');
    }

    public function test_opening_a_published_strategy_does_not_change_its_links(): void
    {
        $org = $this->org();
        $this->role($org, 'Director');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['Director']);
        $chat->forceFill(['status' => 'published', 'published_by' => $author->id, 'published_at' => now()])->save();

        $this->actingAs($author)->getJson(route('users-new-chat-resources.show', ['chat' => $chat->id]))->assertOk();

        $this->assertNull(ExpectedState::first()->org_role_id);
    }
}
