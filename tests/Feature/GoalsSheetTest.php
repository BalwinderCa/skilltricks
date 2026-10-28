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
use App\Services\GoalsSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The publish card's "Export goals to Excel": each person's department goal,
 * its wording for their role, and their own goal, for one strategy.
 */
class GoalsSheetTest extends TestCase
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

    /** IT: the goal's lead (IT Analyst) and an IT Head; HR: someone with no goal. */
    private function world(bool $published = true): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $it = Department::create(['organization_id' => $org->id, 'name' => 'IT', 'color' => '#22C55E']);
        $hr = Department::create(['organization_id' => $org->id, 'name' => 'HR', 'color' => '#3B82F6']);
        $role = fn (string $name, bool $admin = false) => OrgRole::create(['organization_id' => $org->id, 'name' => $name, 'is_admin' => $admin]);
        $analyst = $role('IT Analyst');
        $head = $role('IT Head');
        $make = fn (string $email, OrgRole $r, Department $d) => User::factory()->create([
            'email' => $email, 'name' => ucfirst(strtok($email, '@')), 'user_type' => 'customer',
            'organization_id' => $org->id, 'org_role_id' => $r->id, 'department_id' => $d->id,
        ]);
        $owner = $make('owner@acme.com', $role('Super Admin', true), $it);
        $org->forceFill(['owner_user_id' => $owner->id])->save();
        $lead = $make('lead@acme.com', $analyst, $it);
        $boss = $make('boss@acme.com', $head, $it);
        $make('hr@acme.com', $role('HR Manager'), $hr);

        $chat = SearchUserChat::create(['user_id' => $owner->id, 'status1' => 0, 'leadership_brief' => 'Brief', 'organization_id' => $org->id]);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $owner->id, 'search' => 'Cut downtime 30%', 'response' => 'ok']);
        $goal = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'IT Analyst (IT)', 'recommended_action' => 'Report progress weekly', 'org_role_id' => $analyst->id]);
        $chat->resources()->create(['expected_state_id' => $goal->id, 'department_id' => $it->id, 'department_name' => 'IT', 'budget' => 5000]);
        GoalVariant::create(['expected_state_id' => $goal->id, 'org_role_id' => $head->id, 'text' => 'Review the weekly report.']);
        if ($published) {
            $chat->forceFill(['status' => 'published', 'published_by' => $owner->id, 'published_at' => now()])->save();
        }

        return compact('owner', 'lead', 'boss', 'chat');
    }

    public function test_rows_show_what_each_person_sees(): void
    {
        $w = $this->world();

        $rows = collect(app(GoalsSheet::class)->rows($w['chat']))->keyBy(1);

        $this->assertSame(['Lead', 'lead@acme.com', 'IT', 'IT Analyst', 'Cut downtime 30%', '', '', 'Report progress weekly'], $rows['lead@acme.com']);
        $this->assertSame(['Boss', 'boss@acme.com', 'IT', 'IT Head', 'Cut downtime 30%', 'Report progress weekly', 'Review the weekly report.', ''], $rows['boss@acme.com']);
        // People with no goal in this strategy are still listed.
        $this->assertSame(['Hr', 'hr@acme.com', 'HR', 'HR Manager', '', '', '', ''], $rows['hr@acme.com']);
    }

    public function test_the_author_downloads_a_spreadsheet(): void
    {
        $w = $this->world();

        $res = $this->actingAs($w['owner'])->get(route('users-new-chat-goals-export.show', ['chat' => $w['chat']->id]))->assertOk();

        $this->assertStringContainsString('goals-cut-downtime-30-', (string) $res->headers->get('content-disposition'));
    }

    public function test_a_draft_cannot_be_exported(): void
    {
        $w = $this->world(published: false);

        $this->actingAs($w['owner'])->getJson(route('users-new-chat-goals-export.show', ['chat' => $w['chat']->id]))->assertStatus(409);
    }

    public function test_someone_outside_the_strategy_cannot_export_it(): void
    {
        $w = $this->world();
        $stranger = User::factory()->create(['email' => 'x@other.com', 'user_type' => 'customer']);

        $this->actingAs($stranger)->getJson(route('users-new-chat-goals-export.show', ['chat' => $w['chat']->id]))->assertStatus(403);
    }
}
