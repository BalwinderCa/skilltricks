<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Models\WrNotification;
use App\Services\AI\AiProviderService;
use App\Services\MyGoals;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
use Tests\TestCase;

/**
 * Middle-out initiatives (Features spec, phase 9 / Notion Illustration 2):
 * matched to a C-suite priority, approved upstream, rolled up.
 */
class MiddleOutTest extends TestCase
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

    private function strategy(User $author, string $question, Organization $org, OrgRole $role, bool $published): SearchUserChat
    {
        $chat = SearchUserChat::create(['user_id' => $author->id, 'status1' => 0, 'selected_strategy' => 'Path for '.$question, 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $author->id, 'search' => $question, 'response' => 'ok']);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => $role->name, 'recommended_action' => 'Act on '.$question, 'org_role_id' => $role->id]);
        $chat->resources()->create(['department_id' => null, 'department_name' => 'Whole organization', 'budget' => 1000]);
        $chat->forceFill(['organization_id' => $org->id] + ($published ? ['status' => 'published', 'published_by' => $author->id, 'published_at' => now()] : []))->save();

        return $chat;
    }

    /**
     * ceo (owner) with a published C-suite priority; director (Sales dept head)
     * with a draft initiative; manager (another leader); rep (Sales).
     *
     * @return array<string, mixed>
     */
    private function world(bool $withPriority = true): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $make = fn (string $email, array $extra = []) => User::factory()->create(['email' => $email, 'user_type' => 'customer', 'organization_id' => $org->id] + $extra);
        $ceo = $make('ceo@acme.com', ['name' => 'Casey CEO']);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $director = $make('director@acme.com', ['name' => 'Dana Director', 'manager_id' => $ceo->id]);
        Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E', 'head_user_id' => $director->id]);
        $manager = $make('manager@acme.com', ['manager_id' => $ceo->id]);
        $rep = $make('rep@acme.com', ['org_role_id' => $sales->id, 'manager_id' => $manager->id]);

        $priority = $withPriority ? $this->strategy($ceo, 'Grow revenue 30%', $org, $sales, true) : null;
        $initiative = $this->strategy($director, 'Automate contract review', $org, $sales, false);

        return compact('org', 'sales', 'ceo', 'director', 'manager', 'rep', 'priority', 'initiative');
    }

    private function fakeAi(string $text, int $status = 200): void
    {
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturn(new ClientResponse(new PsrResponse($status, [], '{}')));
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    private function link(array $w): void
    {
        $this->actingAs($w['director'])->postJson(route('users-new-chat-parent.index'), ['chat_id' => $w['initiative']->id, 'parent_chat_id' => $w['priority']->id])->assertOk();
    }

    private function sendForApproval(array $w)
    {
        return $this->actingAs($w['director'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $w['initiative']->id]);
    }

    public function test_a_director_must_link_an_initiative_before_sending_it(): void
    {
        $w = $this->world();

        $this->sendForApproval($w)->assertStatus(422)->assertJsonPath('error', 'Link this initiative to a corporate priority before sending it for approval.');
        $this->assertSame('draft', $w['initiative']->fresh()->status);
    }

    public function test_a_linked_initiative_goes_to_approval_and_is_locked(): void
    {
        $w = $this->world();
        $this->link($w);

        $this->sendForApproval($w)->assertOk()->assertJsonPath('status', 'pending_approval')->assertJsonPath('approval.approver', 'Casey CEO');

        $this->assertSame([$w['ceo']->id], WrNotification::where('type', 'approval_request')->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        $this->assertCount(1, app(MyGoals::class)->for($w['rep']));  // the priority's goal only
        $this->actingAs($w['director'])->postJson(route('users-new-chat-resources-save.index'), ['chat_id' => $w['initiative']->id, 'rows' => []])->assertStatus(409);
        $this->actingAs($w['director'])->postJson(route('users-new-chat-rank-goals.index'), ['chat_id' => $w['initiative']->id])->assertStatus(409);
        $this->actingAs($w['director'])->postJson(route('users-new-chat-parent.index'), ['chat_id' => $w['initiative']->id, 'parent_chat_id' => $w['priority']->id])->assertStatus(409);
    }

    public function test_an_org_without_priorities_publishes_directly(): void
    {
        $w = $this->world(withPriority: false);

        $this->sendForApproval($w)->assertOk()->assertJsonPath('status', 'published');
    }

    public function test_the_owner_publishes_directly(): void
    {
        $w = $this->world();
        $ownerDraft = $this->strategy($w['ceo'], 'Second priority', $w['org'], $w['sales'], false);

        $this->actingAs($w['ceo'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $ownerDraft->id])->assertOk()->assertJsonPath('status', 'published');
    }

    public function test_the_ai_links_the_best_matching_priority(): void
    {
        $w = $this->world();
        $second = $this->strategy($w['ceo'], 'Cut costs 10%', $w['org'], $w['sales'], true);
        $this->fakeAi(json_encode(['matches' => [
            ['id' => $w['priority']->id, 'score' => 45, 'reason' => 'Loosely related'],
            ['id' => $second->id, 'score' => '88', 'reason' => 'Automation cuts legal cost'],
            ['id' => $w['initiative']->id, 'score' => 100, 'reason' => 'itself'],
        ]]));

        $this->actingAs($w['director'])->postJson(route('users-new-chat-match.index'), ['chat_id' => $w['initiative']->id])
            ->assertOk()->assertJsonPath('parent.id', $second->id)->assertJsonPath('parent.score', 88);

        $this->assertSame('Automate contract review', app(MyGoals::class)->companyGoal($w['initiative']->fresh()));
        $this->assertSame('Automation cuts legal cost', $w['initiative']->fresh()->correlation_reason);
    }

    public function test_an_ai_failure_links_nothing(): void
    {
        $w = $this->world();
        $this->fakeAi('nothing useful', 500);

        $this->actingAs($w['director'])->postJson(route('users-new-chat-match.index'), ['chat_id' => $w['initiative']->id])->assertStatus(502);
        $this->assertNull($w['initiative']->fresh()->parent_chat_id);
    }

    public function test_only_corporate_priorities_can_be_parents(): void
    {
        $w = $this->world();
        $other = Organization::create(['domain' => 'globex.com', 'name' => 'Globex']);
        $foreign = $this->strategy(User::factory()->create(['email' => 'x@globex.com', 'user_type' => 'customer', 'organization_id' => $other->id]), 'Theirs', $other, $w['sales'], true);
        $directorPublished = $this->strategy($w['director'], 'Director plan', $w['org'], $w['sales'], true);
        $post = fn (int $parent) => $this->actingAs($w['director'])->postJson(route('users-new-chat-parent.index'), ['chat_id' => $w['initiative']->id, 'parent_chat_id' => $parent]);

        $post($foreign->id)->assertStatus(422);
        $post($directorPublished->id)->assertStatus(422);   // not authored by the owner
        $post($w['initiative']->id)->assertStatus(422);     // itself
    }

    public function test_a_parent_that_stops_being_a_candidate_blocks_sending(): void
    {
        $w = $this->world();
        $this->strategy($w['ceo'], 'Cut costs 10%', $w['org'], $w['sales'], true); // approval still required
        $this->link($w);
        $w['priority']->forceFill(['status' => 'draft'])->save();

        $this->sendForApproval($w)->assertStatus(422);
    }

    public function test_the_approver_approves_once(): void
    {
        $w = $this->world();
        $this->link($w);
        $this->sendForApproval($w);

        $this->actingAs($w['ceo'])->post(route('strategies.approve', $w['initiative']->id))->assertRedirect();
        $this->actingAs($w['ceo'])->post(route('strategies.approve', $w['initiative']->id))->assertNotFound();

        $chat = $w['initiative']->fresh();
        $this->assertSame('published', $chat->status);
        $this->assertNotNull($chat->published_at);
        $this->assertSame([$w['director']->id, $w['ceo']->id], [(int) $chat->published_by, (int) $chat->approval_decided_by]);
        $this->assertSame(1, WrNotification::where('type', 'approval_decision')->where('user_id', $w['director']->id)->count());
        $this->assertCount(2, app(MyGoals::class)->for($w['rep']));
    }

    public function test_rejection_returns_the_initiative_to_draft_with_a_note(): void
    {
        $w = $this->world();
        $this->link($w);
        $this->sendForApproval($w);

        $this->actingAs($w['ceo'])->post(route('strategies.reject', $w['initiative']->id), ['note' => ''])->assertSessionHasErrors('note');
        $this->actingAs($w['ceo'])->post(route('strategies.reject', $w['initiative']->id), ['note' => 'Budget too high for Q3'])->assertRedirect();

        $this->assertSame(['draft', 'Budget too high for Q3'], [$w['initiative']->fresh()->status, $w['initiative']->fresh()->approval_note]);
        $this->assertSame(1, WrNotification::where('type', 'approval_decision')->count());
        $this->actingAs($w['director'])->getJson(route('users-new-chat-resources.show', ['chat' => $w['initiative']->id]))
            ->assertOk()->assertJsonPath('rejection.note', 'Budget too high for Q3');
    }

    public function test_only_approvers_decide_and_only_pending_initiatives(): void
    {
        $w = $this->world();
        $this->actingAs($w['ceo'])->post(route('strategies.approve', $w['initiative']->id))->assertNotFound(); // still a draft

        $this->link($w);
        $this->sendForApproval($w);
        $this->actingAs($w['manager'])->post(route('strategies.approve', $w['initiative']->id))->assertForbidden();
    }

    public function test_the_match_route_is_throttled(): void
    {
        $route = app('router')->getRoutes()->getByName('users-new-chat-match.index');

        $this->assertTrue(collect($route->gatherMiddleware())->contains(fn ($m) => str_starts_with($m, 'throttle:')));
    }

    private function approved(array $w): void
    {
        $this->link($w);
        $this->sendForApproval($w);
        $this->actingAs($w['ceo'])->post(route('strategies.approve', $w['initiative']->id));
    }

    public function test_approvals_are_listed_for_the_approver(): void
    {
        $w = $this->world();
        $this->link($w);
        $this->sendForApproval($w);

        $this->actingAs($w['ceo'])->get(route('strategies.index'))
            ->assertOk()
            ->assertSee('Awaiting your approval')
            ->assertSee('Automate contract review')
            ->assertSee('Dana Director')
            ->assertSee(route('strategies.approve', $w['initiative']->id), false)
            ->assertSee(route('strategies.reject', $w['initiative']->id), false);
    }

    public function test_initiatives_roll_up_into_their_priority(): void
    {
        $w = $this->world();
        $this->approved($w);

        $this->actingAs($w['ceo'])->get(route('strategies.index'))
            ->assertOk()->assertSee('Supports: Grow revenue 30%')->assertSee('1 supporting initiative');
        $this->actingAs($w['ceo'])->get(route('strategies.show', $w['priority']->id))
            ->assertOk()->assertSee('Supporting initiatives')->assertSee('Automate contract review')->assertSee('Dana Director');
    }

    public function test_the_card_carries_the_priority_controls(): void
    {
        $w = $this->world();

        $this->actingAs($w['director'])->get('/dashboard/users-new-chat/'.$w['initiative']->id)
            ->assertOk()->assertSee(route('users-new-chat-match.index'), false)->assertSee(route('users-new-chat-parent.index'), false);
    }
}
