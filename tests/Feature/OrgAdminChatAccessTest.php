<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\AI\AiProviderService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
use Tests\TestCase;

/**
 * An organization admin (the owner, or a Super Admin role holder) can open and
 * write in any member's chat. Other members still see only their own.
 */
class OrgAdminChatAccessTest extends TestCase
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

    /** @return array{org: Organization, owner: User, superAdmin: User, member: User, colleague: User, chat: SearchUserChat} */
    private function world(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $make = fn (string $email, array $extra = []) => User::factory()->create(array_merge([
            'email' => $email, 'user_type' => 'customer', 'organization_id' => $org->id,
        ], $extra));

        $owner = $make('owner@acme.com');
        $org->forceFill(['owner_user_id' => $owner->id])->save();

        $adminRole = OrgRole::create(['organization_id' => $org->id, 'name' => 'Super Admin', 'is_admin' => true]);
        $superAdmin = $make('superadmin@acme.com', ['org_role_id' => $adminRole->id]);
        $member = $make('arthur@acme.com', ['name' => 'Arthur Member']);
        $colleague = $make('colleague@acme.com');

        $chat = SearchUserChat::create(['user_id' => $member->id, 'status1' => 1, 'search' => 'Grow IT', 'response' => 'PLAN']);
        SearchUserChatData::create([
            'search_user_chat_id' => $chat->id, 'user_id' => $member->id,
            'search' => 'how do we modernise IT?', 'response' => 'ARTHUR PRIVATE PLAN',
        ]);

        return compact('org', 'owner', 'superAdmin', 'member', 'colleague', 'chat');
    }

    private function fakeAi(string $text): void
    {
        $response = new ClientResponse(new PsrResponse(200, [], '{}'));
        $ai = Mockery::mock(AiProviderService::class);
        $ai->shouldReceive('providerLabel')->andReturn('Fake');
        $ai->shouldReceive('generate')->andReturn($response);
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('extractUsage')->andReturn([]);
        $ai->shouldReceive('recordChatTokens')->andReturn(0);
        $ai->shouldReceive('parseJson')->andReturn(null);
        $this->instance(AiProviderService::class, $ai);
    }

    public function test_a_super_admin_opens_a_members_chat(): void
    {
        $w = $this->world();

        $this->actingAs($w['superAdmin'])->get('/dashboard/users-new-chat/'.$w['chat']->id)
            ->assertOk()
            ->assertSee('ARTHUR PRIVATE PLAN')
            ->assertSee('Chat of')
            ->assertSee('Arthur Member');
    }

    public function test_the_owner_opens_a_members_chat(): void
    {
        $w = $this->world();

        $this->actingAs($w['owner'])->get('/dashboard/users-new-chat/'.$w['chat']->id)
            ->assertOk()
            ->assertSee('ARTHUR PRIVATE PLAN');
    }

    public function test_an_admins_sidebar_lists_members_chats(): void
    {
        $w = $this->world();
        $own = SearchUserChat::create(['user_id' => $w['superAdmin']->id, 'status1' => 0]);

        $this->actingAs($w['superAdmin'])->get('/dashboard/users-new-chat/'.$own->id)
            ->assertOk()
            ->assertSee(url('dashboard/users-new-chat/'.$w['chat']->id), false)
            ->assertSee('Arthur Member');
    }

    public function test_a_plain_member_still_cannot_open_a_colleagues_chat(): void
    {
        $w = $this->world();

        $this->actingAs($w['colleague'])->get('/dashboard/users-new-chat/'.$w['chat']->id)
            ->assertRedirect(route('newusers-new-chat.index'));
    }

    public function test_an_admin_of_another_organization_cannot_open_the_chat(): void
    {
        $w = $this->world();
        $other = Organization::create(['domain' => 'other.com', 'name' => 'Other']);
        $outsider = User::factory()->create(['email' => 'boss@other.com', 'user_type' => 'customer', 'organization_id' => $other->id]);
        $other->forceFill(['owner_user_id' => $outsider->id])->save();

        $this->actingAs($outsider)->get('/dashboard/users-new-chat/'.$w['chat']->id)
            ->assertRedirect(route('newusers-new-chat.index'));
    }

    public function test_an_admin_writing_in_a_members_chat_keeps_it_the_members(): void
    {
        $w = $this->world();
        $this->fakeAi('Admin follow-up answer');

        $this->actingAs($w['superAdmin'])->postJson(route('users-new-chat-ask.index'), [
            'chat_id' => $w['chat']->id, 'question' => 'add a budget line',
        ])->assertOk()->assertJson(['chat_id' => $w['chat']->id]);

        $this->assertSame($w['member']->id, (int) $w['chat']->fresh()->user_id);
        $this->assertSame(1, SearchUserChat::count());
        $this->assertTrue(SearchUserChatData::where('search_user_chat_id', $w['chat']->id)
            ->where('user_id', $w['member']->id)->where('search', 'add a budget line')->exists());
    }

    public function test_an_admin_can_delete_a_members_chat(): void
    {
        $w = $this->world();

        $this->actingAs($w['superAdmin'])->get('/dashboard/users-chat-search-delete/'.$w['chat']->id);

        $this->assertNull(SearchUserChat::find($w['chat']->id));
    }
}
