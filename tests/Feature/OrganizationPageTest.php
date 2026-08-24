<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPageTest extends TestCase
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

    /** @return array{0: Organization, 1: User, 2: User} */
    private function orgWithOwnerAndMember(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);

        $owner = User::factory()->create([
            'email' => 'owner@acme.com', 'user_type' => 'customer',
            'email_verified_at' => now(), 'organization_id' => $org->id, 'hierarchy_rank' => 50,
        ]);
        $member = User::factory()->create([
            'email' => 'member@acme.com', 'user_type' => 'customer',
            'email_verified_at' => now(), 'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $org->forceFill(['owner_user_id' => $owner->id])->save();

        return [$org, $owner, $member];
    }

    public function test_the_owner_sees_every_member_and_can_edit_ranks(): void
    {
        [, $owner, $member] = $this->orgWithOwnerAndMember();

        $response = $this->actingAs($owner)->get(route('organization.index'));

        $response->assertOk();
        $response->assertSee($owner->email);
        $response->assertSee($member->email);
        // The editable control only renders for the owner.
        $response->assertSee('organization/member-rank', false);
        $response->assertSee('name="rank"', false);
    }

    public function test_a_member_sees_the_roster_but_cannot_edit_ranks(): void
    {
        [, , $member] = $this->orgWithOwnerAndMember();

        $response = $this->actingAs($member)->get(route('organization.index'));

        $response->assertOk();
        $response->assertSee('owner@acme.com');
        // Rank is shown as text, with no form to change it.
        $response->assertSee('Manager');
        $response->assertDontSee('name="rank"', false);
        $response->assertDontSee('organization/member-rank', false);
    }

    public function test_the_dashboard_shows_who_governs_the_active_context(): void
    {
        // The active context lives on the dashboard, not the Teams page: it
        // describes what the platform is working from, not who is in the org.
        [$org, $owner] = $this->orgWithOwnerAndMember();

        app(OrganizationService::class)->recordContext($org, $owner, 50, [
            'role' => 'Chief Executive Officer',
            'rank' => 50,
            'scale' => '12 people across product and engineering',
            'governance' => 'Quarterly OKRs',
            'frictions' => ['Execution drift between teams'],
            'summary_bullets' => [],
        ]);

        $response = $this->actingAs($owner->fresh())->get(route('writebot.dashboard'));

        $response->assertOk();
        $response->assertSee('Chief Executive Officer');
        $response->assertSee('12 people across product and engineering');
        $response->assertSee('Quarterly OKRs');
        $response->assertSee('Execution drift between teams');
    }

    public function test_the_teams_page_does_not_repeat_the_active_context(): void
    {
        [$org, $owner] = $this->orgWithOwnerAndMember();

        app(OrganizationService::class)->recordContext($org, $owner, 50, [
            'role' => 'Chief Executive Officer',
            'rank' => 50, 'scale' => 'x', 'governance' => 'y',
            'frictions' => [], 'summary_bullets' => [],
        ]);

        $response = $this->actingAs($owner->fresh())->get(route('organization.index'));

        $response->assertOk();
        $response->assertSee($owner->email);
        $response->assertDontSee('Active strategic context');
    }

    public function test_the_dashboard_counts_are_scoped_to_the_organization(): void
    {
        [$org, $owner, $member] = $this->orgWithOwnerAndMember();

        // An unrelated user in a different organization must not be counted.
        $outsider = User::factory()->create([
            'email' => 'someone@globex.com', 'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => Organization::create(['domain' => 'globex.com'])->id,
            'hierarchy_rank' => 30,
        ]);

        foreach ([$owner->id, $member->id, $outsider->id] as $uid) {
            \DB::table('documents')->insert([
                'user_id' => $uid, 'name' => 'doc', 'file_path' => '/tmp/doc.pdf',
                'file_name' => 'doc.pdf', 'file_type' => 'pdf',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        SearchUserChat::create(['user_id' => $owner->id, 'status1' => 0, 'response' => 'an answer']);
        SearchUserChat::create(['user_id' => $outsider->id, 'status1' => 0, 'response' => 'an answer']);

        $response = $this->actingAs($owner->fresh())->get(route('writebot.dashboard'));

        $response->assertOk();
        $response->assertSee('Members');
        $response->assertSee('Documents uploaded');
        $response->assertSee('Strategy chats');

        // 2 members, 2 of the 3 documents, 1 of the 2 chats — the outsider's are excluded.
        $response->assertViewHas('orgMemberCount', 2);
        $response->assertViewHas('orgDocumentCount', 2);
        $response->assertViewHas('orgChatCount', 1);
    }

    public function test_the_chat_card_opens_the_users_latest_chat(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();

        SearchUserChat::create(['user_id' => $owner->id, 'status1' => 0, 'response' => 'first answer']);
        $latest = SearchUserChat::create(['user_id' => $owner->id, 'status1' => 0, 'response' => 'second answer']);

        $response = $this->actingAs($owner->fresh())->get(route('writebot.dashboard'));

        $response->assertOk();
        $response->assertViewHas('latestChatId', $latest->id);
        $response->assertSee(route('users-new-chat.index', $latest->id), false);
    }

    public function test_the_chat_card_offers_to_start_one_when_there_are_none(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();

        $response = $this->actingAs($owner->fresh())->get(route('writebot.dashboard'));

        $response->assertOk();
        $response->assertViewHas('latestChatId', null);
        $response->assertSee(route('newusers-new-chat.index'), false);
    }

    public function test_empty_chat_shells_are_not_counted(): void
    {
        // Opening "New Chat" creates a SearchUserChat row before the user has
        // said anything. A brand-new account had seven of these and the card
        // reported seven conversations.
        [, $owner] = $this->orgWithOwnerAndMember();

        SearchUserChat::create(['user_id' => $owner->id, 'status1' => 0]);
        SearchUserChat::create(['user_id' => $owner->id, 'status1' => 0]);

        $response = $this->actingAs($owner->fresh())->get(route('writebot.dashboard'));

        $response->assertOk();
        $response->assertViewHas('orgChatCount', 0);
        // With nothing real to open, the card offers to start one.
        $response->assertViewHas('latestChatId', null);
        $response->assertSee(route('newusers-new-chat.index'), false);
    }

    public function test_a_user_without_an_organization_is_pointed_at_calibration(): void
    {
        $user = User::factory()->create([
            'user_type' => 'customer', 'email_verified_at' => now(),
            'organization_id' => null, 'hierarchy_rank' => null,
        ]);

        $response = $this->actingAs($user)->get(route('organization.index'));

        // Must not 500 for someone who has not calibrated yet.
        $response->assertOk();
        $response->assertSee(route('onboarding.index'), false);
    }
}
