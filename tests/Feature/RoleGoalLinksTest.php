<?php

namespace Tests\Feature;

use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\AI\AiProviderService;
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
            ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => $text, 'recommended_action' => 'Act for '.$text]);
        }

        return $chat;
    }

    public function test_auto_link_matches_role_names_ignoring_case_and_spacing(): void
    {
        $org = $this->org();
        $sales = $this->role($org, 'VP of  Sales');
        $rnd = $this->role($org, 'R&D --- Ops');
        $author = $this->member($org, 'ceo@acme.com');
        $chat = $this->chatWithGoals($author, ['vp of sales ', 'R&D Ops', 'Chief Dreamer']);

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
        $this->assertStringContainsString('up to 3', $captured);
        $this->assertStringNotContainsString('from documents', $captured);
        $this->assertStringNotContainsString('in the documents', $captured);
        $this->assertStringNotContainsString('in the company documents', $captured);
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
}
