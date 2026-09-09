<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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

    public function test_the_owner_sees_every_member_and_edits_them_in_the_dialog(): void
    {
        [, $owner, $member] = $this->orgWithOwnerAndMember();

        $response = $this->actingAs($owner)->get(route('organization.index'));

        $response->assertOk();
        $response->assertSee($member->email);
        // The owner is kept off the roster for now.
        $response->assertDontSee($owner->email);
        // The roster cells are plain text; the Role select lives in the dialog,
        // and the old inline per-row rank form and its endpoint are gone.
        $response->assertSee('<dialog id="memberDialog"', false);
        $response->assertSee(route('organization.members.update'), false);
        $response->assertDontSee('organization/member-rank', false);
        $this->assertFalse(Route::has('organization.member-rank'));
    }

    public function test_a_member_sees_the_roster_as_read_only_text(): void
    {
        [, , $member] = $this->orgWithOwnerAndMember();

        $response = $this->actingAs($member)->get(route('organization.index'));

        $response->assertOk();
        // Hidden for everyone, not just for the owner looking at themselves.
        $response->assertDontSee('owner@acme.com');
        $response->assertSee($member->email);
        // Role is shown as text; a non-owner gets no dialog and no controls.
        $response->assertSee('Manager');
        $response->assertDontSee('name="rank"', false);
        $response->assertDontSee('<dialog id="memberDialog"', false);
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
        [$org, $owner, $member] = $this->orgWithOwnerAndMember();

        app(OrganizationService::class)->recordContext($org, $owner, 50, [
            'role' => 'Chief Executive Officer',
            'rank' => 50, 'scale' => 'x', 'governance' => 'y',
            'frictions' => [], 'summary_bullets' => [],
        ]);

        $response = $this->actingAs($owner->fresh())->get(route('organization.index'));

        $response->assertOk();
        $response->assertSee($member->email);
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

    public function test_opening_new_chat_repeatedly_does_not_stack_empty_rows(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();

        $this->actingAs($owner)->get(route('newusers-new-chat.index'));
        $this->actingAs($owner)->get(route('newusers-new-chat.index'));
        $this->actingAs($owner)->get(route('newusers-new-chat.index'));

        // One untouched chat is reused, not three shells left behind.
        $this->assertSame(1, SearchUserChat::where('user_id', $owner->id)->count());
    }

    public function test_a_used_chat_is_never_reused_for_a_new_one(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();

        SearchUserChat::create(['user_id' => $owner->id, 'status1' => 1, 'response' => 'a real answer']);

        $this->actingAs($owner)->get(route('newusers-new-chat.index'));

        // The conversation is untouched and a fresh chat exists alongside it.
        $this->assertSame(2, SearchUserChat::where('user_id', $owner->id)->count());
        $this->assertSame(1, SearchUserChat::where('user_id', $owner->id)->whereNotNull('response')->count());
    }

    public function test_the_ranking_interview_is_no_longer_linked_from_the_app(): void
    {
        [, $owner] = $this->orgWithOwnerAndMember();

        // Seniority is changed by editing the Role field, not by re-running an
        // interview, so neither page offers one.
        foreach ([route('writebot.dashboard'), route('dashboard.profile'), route('organization.index')] as $url) {
            $response = $this->actingAs($owner)->get($url);
            $response->assertOk();
            $response->assertDontSee(route('onboarding.index'), false);
        }
    }

    public function test_the_roster_paginates_at_ten_per_page(): void
    {
        [$org, $owner] = $this->orgWithOwnerAndMember();

        // The helper's member + 11 here = 12 listed; the owner is hidden, so
        // page two holds two.
        foreach (range(1, 11) as $n) {
            User::factory()->create([
                'name' => sprintf('Member %02d', $n),
                'email' => "m{$n}@acme.com",
                'user_type' => 'customer',
                'organization_id' => $org->id,
                'hierarchy_rank' => 10,
            ]);
        }

        $page1 = $this->actingAs($owner)->get(route('organization.index'));
        $page1->assertOk();
        $this->assertSame(10, substr_count($page1->getContent(), "data-member-edit\n"));
        $page1->assertSee('Showing', false);
        $page1->assertSee('12', false);
        // Bootstrap's paginator, not Laravel's Tailwind default, which this
        // theme carries no classes for.
        $page1->assertSee('class="pagination', false);

        $page2 = $this->actingAs($owner)->get(route('organization.index', ['page' => 2]));
        $page2->assertOk();
        $this->assertSame(2, substr_count($page2->getContent(), "data-member-edit\n"));
    }

    public function test_a_user_without_an_organization_is_pointed_at_their_profile(): void
    {
        $user = User::factory()->create([
            'user_type' => 'customer', 'email_verified_at' => now(),
            'organization_id' => null, 'hierarchy_rank' => null,
        ]);

        $response = $this->actingAs($user)->get(route('organization.index'));

        // Must not 500 for someone with no organization row yet.
        $response->assertOk();
        $response->assertSee(route('dashboard.profile'), false);
    }
}
