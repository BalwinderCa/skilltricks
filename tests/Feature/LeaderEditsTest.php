<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Leader edits (Features spec, phase 5): leaders refine their goals, and the
 * reporting chain up to the strategy's author hears about it.
 */
class LeaderEditsTest extends TestCase
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
     * ceo (owner, author) ← vp (Sales dept head) ← lead (has a report) ← rep.
     * vp, lead and rep hold the Sales role.
     *
     * @return array<string, mixed>
     */
    private function world(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $product = OrgRole::create(['organization_id' => $org->id, 'name' => 'Product']);
        $make = fn (string $email, ?OrgRole $role, ?User $manager) => User::factory()->create([
            'email' => $email, 'user_type' => 'customer', 'organization_id' => $org->id,
            'org_role_id' => $role?->id, 'manager_id' => $manager?->id,
        ]);
        $ceo = $make('ceo@acme.com', null, null);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $vp = $make('vp@acme.com', $sales, $ceo);
        $lead = $make('lead@acme.com', $sales, $vp);
        $rep = $make('rep@acme.com', $sales, $lead);
        Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E', 'head_user_id' => $vp->id]);

        return compact('org', 'sales', 'product', 'ceo', 'vp', 'lead', 'rep');
    }

    private function published(array $w): SearchUserChat
    {
        $chat = SearchUserChat::create(['user_id' => $w['ceo']->id, 'status1' => 0, 'selected_strategy' => 'Upsell', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $w['ceo']->id, 'search' => 'Grow revenue', 'response' => 'ok']);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Old wording', 'org_role_id' => $w['sales']->id, 'starting_options' => ['A', 'B']]);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Product', 'recommended_action' => 'Ship it', 'org_role_id' => $w['product']->id]);
        $chat->forceFill(['status' => 'published', 'published_by' => $w['ceo']->id, 'published_at' => now(), 'organization_id' => $w['org']->id])->save();

        return $chat;
    }

    private function goal(string $role): ExpectedState
    {
        return ExpectedState::where('role', $role)->firstOrFail();
    }

    public function test_leaders_come_from_the_org_chart(): void
    {
        $w = $this->world();
        $orgs = app(OrganizationService::class);

        $this->assertTrue($orgs->isLeader($w['ceo']), 'owner');
        $this->assertTrue($orgs->isLeader($w['vp']), 'department head');
        $this->assertTrue($orgs->isLeader($w['lead']), 'has a report');
        $this->assertFalse($orgs->isLeader($w['rep']), 'no reports');
        $this->assertSame($orgs->isLeader($w['rep']), $orgs->canPublish($w['rep']));
    }

    public function test_the_manager_chain_walks_up_to_the_top(): void
    {
        $w = $this->world();

        $this->assertSame(
            [$w['lead']->id, $w['vp']->id, $w['ceo']->id],
            app(OrganizationService::class)->managerChain($w['rep'])->pluck('id')->map(fn ($id) => (int) $id)->all(),
        );
    }

    public function test_the_manager_chain_stops_at_a_loop_and_at_the_organization_edge(): void
    {
        $w = $this->world();
        $w['ceo']->forceFill(['manager_id' => $w['rep']->id])->save();
        $orgs = app(OrganizationService::class);

        $this->assertSame([$w['lead']->id, $w['vp']->id, $w['ceo']->id], $orgs->managerChain($w['rep']->fresh())->pluck('id')->map(fn ($id) => (int) $id)->all());

        $outsider = User::factory()->create(['email' => 'x@globex.com', 'user_type' => 'customer', 'organization_id' => Organization::create(['domain' => 'globex.com'])->id]);
        $w['ceo']->forceFill(['manager_id' => $outsider->id])->save();
        $this->assertNotContains($outsider->id, $orgs->managerChain($w['rep']->fresh())->pluck('id')->map(fn ($id) => (int) $id)->all());
    }
}
