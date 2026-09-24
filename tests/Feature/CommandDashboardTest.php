<?php

namespace Tests\Feature;

use App\Mail\EmailManager;
use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\GoalProgressUpdate;
use App\Models\GoalResponse;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Models\WrNotification;
use App\Services\DriftIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function test_drift_follows_the_notion_formula_and_thresholds(): void
    {
        $w = $this->world();
        $drift = app(DriftIndex::class);

        // Baseline 50%. Observed 30 → (50−30)/50 = 40% → red.
        $this->progress($w['rep'], 'Sales', 'in_progress', 30);
        $state = $drift->evaluate($w['chat']->fresh(), alert: false);
        $this->assertEqualsWithDelta(40.0, $state['index'], 0.5);
        $this->assertSame('red', $state['level']);

        $this->progress($w['rep'], 'Sales', 'in_progress', 45); // 10% → yellow
        $this->assertSame('yellow', $drift->evaluate($w['chat']->fresh(), alert: false)['level']);

        $this->progress($w['rep'], 'Sales', 'in_progress', 48); // 4% → green
        $this->assertSame('green', $drift->evaluate($w['chat']->fresh(), alert: false)['level']);

        $this->progress($w['rep'], 'Sales', 'completed', 0); // 100 → no drift
        $this->assertSame(0.0, (float) $drift->evaluate($w['chat']->fresh(), alert: false)['index']);
    }

    public function test_the_index_is_persisted_on_the_strategy(): void
    {
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'in_progress', 30);

        $chat = $w['chat']->fresh();
        $this->assertSame('red', $chat->drift_level);
        $this->assertEqualsWithDelta(40.0, (float) $chat->drift_index, 0.5);
        $this->assertNotNull($chat->drift_checked_at);
    }

    public function test_goals_are_not_measured_in_the_grace_period_or_without_a_target_date(): void
    {
        $w = $this->world();
        $w['chat']->forceFill(['published_at' => now()->subDay()])->save(); // 1/11 of the window < 10%

        $state = app(DriftIndex::class)->evaluate($w['chat']->fresh(), alert: false);

        $this->assertNull($state['index']);
        $this->assertNull($state['level']);
        $product = $state['goals']->firstWhere('goal.role', 'Product');
        $this->assertFalse($product['measured']);
        $this->assertNull($product['expected']);
    }

    public function test_a_target_date_before_publishing_is_not_measured(): void
    {
        $w = $this->world();
        $this->goal('Sales')->update(['target_date' => now()->subDays(20)->toDateString()]);

        $state = app(DriftIndex::class)->evaluate($w['chat']->fresh(), alert: false);

        $this->assertNull($state['index']);
    }

    public function test_a_draft_is_not_persisted(): void
    {
        $w = $this->world(publish: false);

        app(DriftIndex::class)->evaluate($w['chat']->fresh());

        $this->assertNull($w['chat']->fresh()->drift_level);
        $this->assertSame(0, WrNotification::count());
    }

    public function test_projections_follow_the_current_pace(): void
    {
        $w = $this->world();
        $this->progress($w['rep'], 'Sales', 'in_progress', 50); // on baseline after 10 of 20 days

        $row = app(DriftIndex::class)->evaluate($w['chat']->fresh(), alert: false)['goals']->firstWhere('goal.role', 'Sales');

        $this->assertSame(now()->addDays(10)->toDateString(), $row['projected_completion']->toDateString());
        $this->assertEqualsWithDelta(25.0, $row['projected_value'], 0.01);
        $this->assertSame(0, $row['days_behind']);

        $this->progress($w['rep'], 'Sales', 'in_progress', 30);
        $row = app(DriftIndex::class)->evaluate($w['chat']->fresh(), alert: false)['goals']->firstWhere('goal.role', 'Sales');
        $this->assertEqualsWithDelta(15.0, $row['projected_value'], 0.01);
        $this->assertSame(4, $row['days_behind']);
    }

    public function test_yellow_nudges_the_bottleneck_roles_holders_once(): void
    {
        Mail::fake();
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'in_progress', 45); // yellow
        app(DriftIndex::class)->evaluate($w['chat']->fresh()); // same level again

        $nudges = WrNotification::where('type', 'drift_nudge')->get();
        $this->assertSame([$w['rep']->id], $nudges->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        Mail::assertQueued(EmailManager::class, fn ($m) => $m->hasTo('rep@acme.com'));
    }

    public function test_red_alerts_the_author_and_rearms_after_green(): void
    {
        Mail::fake();
        $w = $this->world();

        $this->progress($w['rep'], 'Sales', 'in_progress', 30); // red
        $this->progress($w['rep'], 'Sales', 'in_progress', 50); // green
        $this->progress($w['rep'], 'Sales', 'in_progress', 20); // red again

        $alerts = WrNotification::where('type', 'drift_red')->get();
        $this->assertCount(2, $alerts);
        $this->assertSame($w['ceo']->id, (int) $alerts->first()->user_id);
        $this->assertSame('dashboard/strategies/'.$w['chat']->id, $alerts->first()->url);
        Mail::assertQueued(EmailManager::class, fn ($m) => $m->hasTo('ceo@acme.com'));
    }

    public function test_an_alert_to_someone_without_email_still_lands_in_app(): void
    {
        Mail::fake();
        $w = $this->world();
        $w['ceo']->forceFill(['email' => ''])->save();

        $this->progress($w['rep'], 'Sales', 'in_progress', 30);

        $this->assertSame(1, WrNotification::where('type', 'drift_red')->count());
        Mail::assertNotQueued(EmailManager::class, fn ($m) => $m->hasTo(''));
    }

    public function test_goal_revisions_are_emailed_too(): void
    {
        Mail::fake();
        $w = $this->world();
        $w['rep']->forceFill(['manager_id' => null])->save();
        User::where('id', $w['pm']->id)->update(['manager_id' => $w['rep']->id]); // rep leads someone → leader

        $this->actingAs($w['rep'])->post(route('my-goals.revise'), ['goal_id' => $this->goal('Sales')->id, 'text' => 'New wording']);

        Mail::assertQueued(EmailManager::class, fn ($m) => $m->hasTo('ceo@acme.com'));
    }

    public function test_the_daily_command_evaluates_published_strategies(): void
    {
        Mail::fake();
        $w = $this->world();
        GoalResponse::create(['expected_state_id' => $this->goal('Sales')->id, 'user_id' => $w['rep']->id, 'decision' => 'act_on_it', 'progress_status' => 'in_progress', 'progress_pct' => 30]);

        $this->artisan('strategies:evaluate-drift')->assertExitCode(0);

        $this->assertSame('red', $w['chat']->fresh()->drift_level);
        $this->assertSame(1, WrNotification::where('type', 'drift_red')->count());
    }
}
