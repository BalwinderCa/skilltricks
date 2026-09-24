<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\GoalProgressUpdate;
use App\Models\GoalResponse;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Executive command dashboard (Features spec, phase 6 / Notion Epic 4):
 * progress telemetry, the drift index and its alerts, projections, savings.
 */
class CommandDashboardTest extends TestCase
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
     * Acme: ceo (owner, author), rep (Sales, Sales dept), pm (Product).
     * One strategy published 10 days ago. Sales: target in 10 days (so the
     * baseline is 50% today), target value 25, depends on Product. Product:
     * no target date.
     *
     * @return array<string, mixed>
     */
    private function world(bool $publish = true): array
    {
        $this->freezeTime();
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $sales = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $product = OrgRole::create(['organization_id' => $org->id, 'name' => 'Product']);
        $dept = Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E']);
        $ceo = User::factory()->create(['email' => 'ceo@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id]);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $rep = User::factory()->create(['email' => 'rep@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id, 'org_role_id' => $sales->id, 'department_id' => $dept->id, 'manager_id' => $ceo->id]);
        $pm = User::factory()->create(['email' => 'pm@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id, 'org_role_id' => $product->id, 'manager_id' => $ceo->id]);

        $chat = SearchUserChat::create(['user_id' => $ceo->id, 'status1' => 0, 'selected_strategy' => 'Upsell', 'leadership_brief' => 'Brief']);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $ceo->id, 'search' => 'Grow revenue 30%', 'response' => 'ok']);
        $productGoal = ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'Product', 'recommended_action' => 'Ship SOC2', 'org_role_id' => $product->id]);
        ExpectedState::create([
            'search_user_chat_id' => $chat->id, 'role' => 'Sales', 'recommended_action' => 'Launch the upgrade motion',
            'org_role_id' => $sales->id, 'target_value' => '25', 'target_date' => now()->addDays(10)->toDateString(),
            'depends_on_id' => $productGoal->id,
        ]);
        if ($publish) {
            // Published as long before now as the target's end is after it, so
            // the Sales baseline is exactly 50% whatever the time of day.
            $end = now()->addDays(10)->endOfDay();
            $publishedAt = now()->subSeconds($end->getTimestamp() - now()->getTimestamp());
            $chat->forceFill(['status' => 'published', 'published_by' => $ceo->id, 'published_at' => $publishedAt, 'organization_id' => $org->id])->save();
        }

        return compact('org', 'sales', 'product', 'dept', 'ceo', 'rep', 'pm', 'chat');
    }

    private function goal(string $role): ExpectedState
    {
        return ExpectedState::where('role', $role)->firstOrFail();
    }

    private function progress(User $user, string $role, string $status, int $pct, ?string $note = null)
    {
        return $this->actingAs($user)->post(route('my-goals.progress'), ['goal_id' => $this->goal($role)->id, 'status' => $status, 'pct' => $pct, 'note' => $note]);
    }

    public function test_a_holder_posts_progress_and_it_is_kept_with_history(): void
    {
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'in_progress', 30, 'Drafting the contract')->assertRedirect();
        $this->progress($w['rep'], 'Sales', 'in_progress', 40);

        $response = GoalResponse::first();
        $this->assertSame(['in_progress', 40, null], [$response->progress_status, $response->progress_pct, $response->progress_note]);
        $this->assertSame('act_on_it', $response->decision);
        $this->assertSame(2, GoalProgressUpdate::count());
        $this->assertSame('Drafting the contract', GoalProgressUpdate::orderBy('id')->first()->note);
    }

    public function test_completed_counts_as_one_hundred_percent(): void
    {
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'completed', 20);

        $this->assertSame(100, GoalResponse::first()->progress_pct);
    }

    public function test_bad_progress_input_is_refused(): void
    {
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'done-ish', 10)->assertSessionHasErrors('status');
        $this->progress($w['rep'], 'Sales', 'in_progress', 101)->assertSessionHasErrors('pct');
        $this->progress($w['pm'], 'Sales', 'in_progress', 10)->assertNotFound();
        $this->assertSame(0, GoalProgressUpdate::count());
    }

    public function test_the_card_offers_a_status_update_after_act_on_it(): void
    {
        $w = $this->world();
        GoalResponse::create(['expected_state_id' => $this->goal('Sales')->id, 'user_id' => $w['rep']->id, 'decision' => 'act_on_it']);

        $this->actingAs($w['rep'])->get('/dashboard')
            ->assertOk()
            ->assertSee(route('my-goals.progress'), false)
            ->assertSee('Blocked — flag a bottleneck');
    }
}
