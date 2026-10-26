<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\GoalVariant;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\GoalVariants;
use App\Services\MyGoals;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Mockery;
use Tests\TestCase;

/**
 * Each person in a goal's department sees it worded for their own role.
 */
class GoalVariantsTest extends TestCase
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
     * IT department: the goal's lead (IT Analyst), an IT Head and a Technician,
     * plus a Super Admin role holder. The goal's budget is booked to IT.
     */
    private function world(bool $published = true): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $it = Department::create(['organization_id' => $org->id, 'name' => 'IT', 'color' => '#22C55E']);
        $role = fn (string $name, bool $admin = false) => OrgRole::create(['organization_id' => $org->id, 'name' => $name, 'is_admin' => $admin]);
        $analyst = $role('IT Analyst');
        $head = $role('IT Head');
        $tech = $role('IT Technician');
        $admin = $role('Super Admin', true);
        $make = fn (string $email, OrgRole $r) => User::factory()->create([
            'email' => $email, 'name' => ucfirst(strtok($email, '@')), 'user_type' => 'customer',
            'organization_id' => $org->id, 'org_role_id' => $r->id, 'department_id' => $it->id,
        ]);
        $owner = $make('owner@acme.com', $admin);
        $org->forceFill(['owner_user_id' => $owner->id])->save();
        $lead = $make('lead@acme.com', $analyst);
        $boss = $make('boss@acme.com', $head);
        $fixer = $make('fixer@acme.com', $tech);

        $chat = SearchUserChat::create(['user_id' => $owner->id, 'status1' => 0, 'leadership_brief' => 'Brief', 'organization_id' => $org->id]);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $owner->id, 'search' => 'Cut downtime 30%', 'response' => 'ok']);
        $goal = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'IT Analyst (IT)', 'recommended_action' => 'Report progress weekly', 'org_role_id' => $analyst->id, 'target_date' => now()->addMonths(3)]);
        $chat->resources()->create(['expected_state_id' => $goal->id, 'department_id' => $it->id, 'department_name' => 'IT', 'budget' => 5000]);
        if ($published) {
            $chat->forceFill(['status' => 'published', 'published_by' => $owner->id, 'published_at' => now()])->save();
        }

        return compact('org', 'it', 'analyst', 'head', 'tech', 'admin', 'owner', 'lead', 'boss', 'fixer', 'chat', 'goal');
    }

    private function fakeAi(callable $answer): void
    {
        $ai = Mockery::mock(AiProviderService::class)->shouldIgnoreMissing();
        $ai->shouldReceive('generate')->andReturn(new ClientResponse(new PsrResponse(200, [], '{}')));
        $ai->shouldReceive('extractText')->andReturnUsing($answer);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    public function test_pairs_cover_every_other_role_in_the_department_but_not_the_lead_or_admins(): void
    {
        $w = $this->world();

        $pairs = app(GoalVariants::class)->pairs($w['chat']);

        $this->assertEqualsCanonicalizing(['IT Head', 'IT Technician'], $pairs->map(fn ($p) => $p['role']->name)->all());
    }

    public function test_generate_stores_one_version_per_role_and_ignores_strays(): void
    {
        $w = $this->world();
        $this->fakeAi(fn () => json_encode(['variants' => [
            ['goal_id' => $w['goal']->id, 'role_id' => $w['head']->id, 'text' => 'Review the weekly report and unblock the team.'],
            ['goal_id' => $w['goal']->id, 'role_id' => $w['tech']->id, 'text' => 'Log each outage fix in the weekly report.'],
            ['goal_id' => $w['goal']->id, 'role_id' => $w['admin']->id, 'text' => 'Not asked for.'],
            ['goal_id' => 999, 'role_id' => $w['head']->id, 'text' => 'Unknown goal.'],
        ]]));

        $this->assertSame(2, app(GoalVariants::class)->generate($w['chat']));
        $this->assertSame(2, GoalVariant::count());
    }

    public function test_each_member_sees_their_own_version_on_the_dashboard(): void
    {
        $w = $this->world();
        GoalVariant::create(['expected_state_id' => $w['goal']->id, 'org_role_id' => $w['head']->id, 'text' => 'Review the weekly report and unblock the team.']);
        GoalVariant::create(['expected_state_id' => $w['goal']->id, 'org_role_id' => $w['tech']->id, 'text' => 'Log each outage fix in the weekly report.']);

        $this->assertSame('Log each outage fix in the weekly report.', app(MyGoals::class)->forDepartment($w['fixer'])->first()['my_version']);

        $this->actingAs($w['boss'])->get(route('writebot.dashboard'))->assertOk()
            ->assertSee('Your part')
            ->assertSee('Review the weekly report and unblock the team.')
            ->assertDontSee('Log each outage fix in the weekly report.')
            ->assertSee('Report progress weekly');
    }

    public function test_publishing_writes_the_versions(): void
    {
        $w = $this->world(published: false);
        $this->fakeAi(fn () => json_encode(['variants' => [
            ['goal_id' => $w['goal']->id, 'role_id' => $w['head']->id, 'text' => 'Head version.'],
            ['goal_id' => $w['goal']->id, 'role_id' => $w['tech']->id, 'text' => 'Technician version.'],
        ]]));

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $w['chat']->id])
            ->assertOk()->assertJsonPath('status', 'published');

        $this->assertSame('Technician version.', GoalVariant::where('org_role_id', $w['tech']->id)->value('text'));
    }

    public function test_an_ai_failure_still_publishes_and_members_keep_the_original_wording(): void
    {
        $w = $this->world(published: false);
        $this->fakeAi(fn () => 'not json');

        $this->actingAs($w['owner'])->postJson(route('users-new-chat-publish.index'), ['chat_id' => $w['chat']->id])
            ->assertOk()->assertJsonPath('status', 'published');

        $this->assertSame(0, GoalVariant::count());
        $this->assertNull(app(MyGoals::class)->forDepartment($w['fixer'])->first()['my_version']);
    }
}
