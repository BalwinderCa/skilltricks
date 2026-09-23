<?php

namespace Tests\Feature;

use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\RoleGoalLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
