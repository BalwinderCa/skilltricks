<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DepartmentTest extends TestCase
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

    /** @return array{0: Organization, 1: User} */
    private function ownedOrg(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);

        $owner = User::factory()->create([
            'email' => 'owner@acme.com', 'user_type' => 'customer',
            'email_verified_at' => now(), 'organization_id' => $org->id, 'hierarchy_rank' => 50,
        ]);

        $org->forceFill(['owner_user_id' => $owner->id])->save();

        return [$org, $owner];
    }

    public function test_the_owner_adds_a_department_with_a_colour(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $this->actingAs($owner)->post(route('organization.departments.store'), [
            'name' => 'Design',
            'color' => Department::PALETTE[1],
        ])->assertRedirect();

        $department = Department::where('organization_id', $org->id)->first();

        $this->assertNotNull($department);
        $this->assertSame('Design', $department->name);
        $this->assertSame(Department::PALETTE[1], $department->color);
    }

    /** Only colours from the palette: an arbitrary hex is not legible in both themes. */
    public function test_a_colour_outside_the_palette_is_rejected(): void
    {
        [, $owner] = $this->ownedOrg();

        $this->actingAs($owner)->post(route('organization.departments.store'), [
            'name' => 'Design', 'color' => '#000000',
        ])->assertSessionHasErrors('color');

        $this->assertSame(0, Department::count());
    }

    public function test_a_department_without_a_colour_gets_the_next_one(): void
    {
        [$org, $owner] = $this->ownedOrg();

        Department::create(['organization_id' => $org->id, 'name' => 'One', 'color' => Department::PALETTE[0]]);

        $this->actingAs($owner)->post(route('organization.departments.store'), ['name' => 'Two']);

        $this->assertSame(Department::PALETTE[1], Department::where('name', 'Two')->first()->color);
    }

    public function test_a_duplicate_name_in_the_same_organization_is_refused(): void
    {
        [$org, $owner] = $this->ownedOrg();

        Department::create(['organization_id' => $org->id, 'name' => 'Design', 'color' => Department::PALETTE[0]]);

        $this->actingAs($owner)->post(route('organization.departments.store'), ['name' => 'Design'])
            ->assertRedirect();

        $this->assertSame(1, Department::where('organization_id', $org->id)->count());
    }

    public function test_a_non_owner_cannot_add_one(): void
    {
        [$org] = $this->ownedOrg();

        $member = User::factory()->create([
            'email' => 'member@acme.com', 'user_type' => 'customer', 'email_verified_at' => now(),
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $this->actingAs($member)->post(route('organization.departments.store'), ['name' => 'Design'])
            ->assertForbidden();

        $this->assertSame(0, Department::count());
    }

    public function test_deleting_a_department_keeps_its_members(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $department = Department::create([
            'organization_id' => $org->id, 'name' => 'Design', 'color' => Department::PALETTE[0],
        ]);

        $member = User::factory()->create([
            'email' => 'member@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20, 'department_id' => $department->id,
        ]);

        $this->actingAs($owner)->post(route('organization.departments.destroy'), [
            'department_id' => $department->id,
        ])->assertRedirect();

        $this->assertSame(0, Department::count());
        $this->assertNotNull($member->fresh());
        $this->assertNull($member->fresh()->department_id);
    }

    public function test_another_organizations_department_cannot_be_deleted(): void
    {
        [, $owner] = $this->ownedOrg();

        $other = Organization::create(['domain' => 'globex.com']);
        $theirs = Department::create([
            'organization_id' => $other->id, 'name' => 'Design', 'color' => Department::PALETTE[0],
        ]);

        $this->actingAs($owner)->post(route('organization.departments.destroy'), [
            'department_id' => $theirs->id,
        ])->assertNotFound();

        $this->assertNotNull($theirs->fresh());
    }

    /** A member cannot be filed under another organization's department. */
    public function test_a_foreign_department_id_is_ignored_when_editing(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'name' => 'Grace', 'email' => 'grace@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $foreign = Department::create([
            'organization_id' => Organization::create(['domain' => 'globex.com'])->id,
            'name' => 'Theirs', 'color' => Department::PALETTE[0],
        ]);

        $this->actingAs($owner)->post(route('organization.members.update'), [
            'user_id' => $member->id, 'name' => 'Grace',
            'email' => 'grace@acme.com', 'rank' => 20, 'department_id' => $foreign->id,
        ])->assertRedirect();

        $this->assertNull($member->fresh()->department_id);
    }

    public function test_the_sidebar_lists_departments_with_their_colour(): void
    {
        [$org, $owner] = $this->ownedOrg();

        Department::create(['organization_id' => $org->id, 'name' => 'Design', 'color' => Department::PALETTE[1]]);

        $response = $this->actingAs($owner)->get(route('writebot.dashboard'));

        $response->assertOk();
        $response->assertSee('Design');
        $response->assertSee(Department::PALETTE[1], false);
        $response->assertSee('data-department-add', false);
        $response->assertSee(route('organization.index', ['department' => 1]), false);
    }

    public function test_a_member_sees_the_list_but_no_add_button(): void
    {
        [$org] = $this->ownedOrg();

        Department::create(['organization_id' => $org->id, 'name' => 'Design', 'color' => Department::PALETTE[1]]);

        $member = User::factory()->create([
            'email' => 'member@acme.com', 'user_type' => 'customer', 'email_verified_at' => now(),
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $response = $this->actingAs($member)->get(route('writebot.dashboard'));

        $response->assertOk();
        $response->assertSee('Design');
        $response->assertDontSee('data-department-add', false);
    }

    /** @return array{0: Organization, 1: User} */
    private function orgWithChart(): array
    {
        [$org, $owner] = $this->ownedOrg();

        $design = Department::create([
            'organization_id' => $org->id, 'name' => 'Design', 'color' => Department::PALETTE[1],
        ]);
        $eng = Department::create([
            'organization_id' => $org->id, 'name' => 'Engineering', 'color' => Department::PALETTE[0],
        ]);

        foreach ([
            ['Dana Director', 'dana@acme.com', 30, $design->id],
            ['Ivan Ic', 'ivan@acme.com', 10, $design->id],
            ['Vera Veep', 'vera@acme.com', 40, $eng->id],
            ['Nora None', 'nora@acme.com', 20, null],
        ] as [$name, $email, $rank, $departmentId]) {
            User::factory()->create([
                'name' => $name, 'email' => $email, 'user_type' => 'customer',
                'organization_id' => $org->id, 'hierarchy_rank' => $rank, 'department_id' => $departmentId,
            ]);
        }

        return [$org, $owner];
    }

    public function test_the_chart_tab_leads_with_the_owner_and_one_branch_per_department(): void
    {
        [, $owner] = $this->orgWithChart();

        $response = $this->actingAs($owner)->get(route('organization.index', ['view' => 'chart']));

        $response->assertOk();
        // The owner heads the chart even though Vera outranks them here only by
        // department; ownership wins the root slot.
        $response->assertSee('Organization chart');
        $response->assertSee($owner->name);
        $response->assertSee('Design');
        $response->assertSee('Engineering');
        // Everyone is drawn exactly once, including the person with no
        // department. Counted on the rendered name, not the data attribute the
        // edit hook also carries.
        foreach (['Dana Director', 'Ivan Ic', 'Vera Veep', 'Nora None'] as $name) {
            $this->assertSame(1, substr_count($response->getContent(), '<strong>'.$name.'</strong>'), $name);
        }
        $response->assertSee('No department');
        // The band wears the department's own colour.
        $response->assertSee(Department::PALETTE[1], false);
    }

    /** Inside a department the highest role leads; the rest hang beneath. */
    public function test_the_highest_ranked_member_heads_their_department(): void
    {
        [, $owner] = $this->orgWithChart();

        $content = $this->actingAs($owner)
            ->get(route('organization.index', ['view' => 'chart']))->getContent();

        $head = strpos($content, 'Dana Director');
        $report = strpos($content, 'Ivan Ic');

        $this->assertNotFalse($head);
        $this->assertNotFalse($report);
        $this->assertLessThan($report, $head, 'The Director should be drawn above the Individual Contributor.');
    }

    public function test_the_owner_picks_a_department_head(): void
    {
        [$org, $owner] = $this->orgWithChart();

        $design = Department::where('name', 'Design')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();

        $this->actingAs($owner)->post(route('organization.departments.head'), [
            'department_id' => $design->id,
            'head_user_id' => $ivan->id,
        ])->assertRedirect();

        $this->assertSame((int) $ivan->id, (int) $design->fresh()->head_user_id);

        $content = $this->actingAs($owner)
            ->get(route('organization.index', ['view' => 'chart']))->getContent();

        // Ivan is an Individual Contributor and Dana a Director, so only an
        // explicit pick can put Ivan above her.
        $this->assertLessThan(
            strpos($content, 'Dana Director'),
            strpos($content, 'Ivan Ic'),
            'The chosen head should be drawn above the more senior member.'
        );
    }

    public function test_clearing_the_head_returns_to_seniority(): void
    {
        [, $owner] = $this->orgWithChart();

        $design = Department::where('name', 'Design')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();
        $design->forceFill(['head_user_id' => $ivan->id])->save();

        $this->actingAs($owner)->post(route('organization.departments.head'), [
            'department_id' => $design->id,
            'head_user_id' => null,
        ])->assertRedirect();

        $this->assertNull($design->fresh()->head_user_id);

        $content = $this->actingAs($owner)
            ->get(route('organization.index', ['view' => 'chart']))->getContent();

        $this->assertLessThan(strpos($content, 'Ivan Ic'), strpos($content, 'Dana Director'));
    }

    /** The head is drawn inside the branch, so an outsider cannot hold the slot. */
    public function test_someone_outside_the_department_cannot_be_its_head(): void
    {
        [, $owner] = $this->orgWithChart();

        $design = Department::where('name', 'Design')->first();
        $vera = User::where('email', 'vera@acme.com')->first();

        $this->actingAs($owner)->post(route('organization.departments.head'), [
            'department_id' => $design->id,
            'head_user_id' => $vera->id,
        ])->assertRedirect();

        $this->assertNull($design->fresh()->head_user_id);
    }

    /** A head who moves department must not empty the branch they left. */
    public function test_a_stale_head_falls_back_to_seniority(): void
    {
        [$org, $owner] = $this->orgWithChart();

        $design = Department::where('name', 'Design')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();
        $design->forceFill(['head_user_id' => $ivan->id])->save();

        // Ivan is moved out; the pick is now stale.
        $ivan->forceFill(['department_id' => Department::where('name', 'Engineering')->first()->id])->save();

        $response = $this->actingAs($owner)->get(route('organization.index', ['view' => 'chart']));

        $response->assertOk();
        $response->assertSee('Dana Director');
        $this->assertSame(1, substr_count($response->getContent(), '<strong>Ivan Ic</strong>'));
    }

    public function test_a_non_owner_cannot_pick_a_head(): void
    {
        [$org] = $this->orgWithChart();

        $design = Department::where('name', 'Design')->first();
        $member = User::where('email', 'ivan@acme.com')->first();
        $member->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($member)->post(route('organization.departments.head'), [
            'department_id' => $design->id, 'head_user_id' => $member->id,
        ])->assertForbidden();

        $this->assertNull($design->fresh()->head_user_id);
    }

    public function test_dragging_a_member_onto_a_department_moves_them(): void
    {
        [, $owner] = $this->orgWithChart();

        $eng = Department::where('name', 'Engineering')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();

        $this->actingAs($owner)->post(route('organization.chart.move'), [
            'user_id' => $ivan->id,
            'department_id' => $eng->id,
        ])->assertRedirect();

        $this->assertSame((int) $eng->id, (int) $ivan->fresh()->department_id);
    }

    public function test_dropping_on_the_head_card_moves_them_and_hands_them_the_branch(): void
    {
        [, $owner] = $this->orgWithChart();

        $eng = Department::where('name', 'Engineering')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();

        $this->actingAs($owner)->post(route('organization.chart.move'), [
            'user_id' => $ivan->id,
            'department_id' => $eng->id,
            'as_head' => 1,
        ])->assertRedirect();

        $this->assertSame((int) $eng->id, (int) $ivan->fresh()->department_id);
        $this->assertSame((int) $ivan->id, (int) $eng->fresh()->head_user_id);
    }

    /** Dropping outside any band unassigns, which is a real state on the chart. */
    public function test_a_move_with_no_department_unassigns_them(): void
    {
        [, $owner] = $this->orgWithChart();

        $ivan = User::where('email', 'ivan@acme.com')->first();

        $this->actingAs($owner)->post(route('organization.chart.move'), [
            'user_id' => $ivan->id, 'department_id' => null,
        ])->assertRedirect();

        $this->assertNull($ivan->fresh()->department_id);
    }

    /** Leaving a department they led must not leave the pick pointing at them. */
    public function test_moving_a_head_away_clears_the_pick(): void
    {
        [, $owner] = $this->orgWithChart();

        $design = Department::where('name', 'Design')->first();
        $eng = Department::where('name', 'Engineering')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();

        $design->forceFill(['head_user_id' => $ivan->id])->save();

        $this->actingAs($owner)->post(route('organization.chart.move'), [
            'user_id' => $ivan->id, 'department_id' => $eng->id,
        ])->assertRedirect();

        $this->assertNull($design->fresh()->head_user_id);
    }

    public function test_a_move_into_another_organizations_department_is_a_404(): void
    {
        [, $owner] = $this->orgWithChart();

        $theirs = Department::create([
            'organization_id' => Organization::create(['domain' => 'globex.com'])->id,
            'name' => 'Theirs', 'color' => Department::PALETTE[0],
        ]);
        $ivan = User::where('email', 'ivan@acme.com')->first();
        $before = $ivan->department_id;

        $this->actingAs($owner)->post(route('organization.chart.move'), [
            'user_id' => $ivan->id, 'department_id' => $theirs->id,
        ])->assertNotFound();

        $this->assertSame((int) $before, (int) $ivan->fresh()->department_id);
    }

    public function test_a_non_owner_cannot_drag_anyone(): void
    {
        $this->orgWithChart();

        $eng = Department::where('name', 'Engineering')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();
        $ivan->forceFill(['email_verified_at' => now()])->save();
        $before = $ivan->department_id;

        $this->actingAs($ivan)->post(route('organization.chart.move'), [
            'user_id' => $ivan->id, 'department_id' => $eng->id,
        ])->assertForbidden();

        $this->assertSame((int) $before, (int) $ivan->fresh()->department_id);
    }

    /** A drop answers with the redrawn chart, so the page never has to reload. */
    public function test_a_move_returns_the_redrawn_chart(): void
    {
        [, $owner] = $this->orgWithChart();

        $eng = Department::where('name', 'Engineering')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();

        $response = $this->actingAs($owner)
            ->postJson(route('organization.chart.move'), [
                'user_id' => $ivan->id, 'department_id' => $eng->id,
            ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $html = $response->json('html');

        $this->assertNotEmpty($html);
        // Redrawn, not the page: the fragment carries cards but no page chrome.
        $this->assertStringContainsString('tt-chart-card', $html);
        $this->assertStringContainsString('Ivan Ic', $html);
        $this->assertStringNotContainsString('<html', $html);
    }

    public function test_dropping_on_the_remove_zone_takes_them_off_the_roster(): void
    {
        [$org, $owner] = $this->orgWithChart();

        $ivan = User::where('email', 'ivan@acme.com')->first();

        $response = $this->actingAs($owner)
            ->postJson(route('organization.members.remove'), ['user_id' => $ivan->id]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $fresh = $ivan->fresh();

        // Off the roster, account intact — the same rule the Members tab follows.
        $this->assertNotNull($fresh);
        $this->assertNull($fresh->organization_id);
        $this->assertNull($fresh->deleted_at);
        $this->assertStringNotContainsString('Ivan Ic', $response->json('html'));
    }

    public function test_the_remove_zone_refuses_to_evict_the_owner(): void
    {
        [, $owner] = $this->orgWithChart();

        $this->actingAs($owner)
            ->postJson(route('organization.members.remove'), ['user_id' => $owner->id])
            ->assertStatus(422);

        $this->assertNotNull($owner->fresh()->organization_id);
    }

    public function test_the_remove_zone_only_renders_in_edit_mode_for_the_owner(): void
    {
        [, $owner] = $this->orgWithChart();

        $ownerView = $this->actingAs($owner)->get(route('organization.index', ['view' => 'chart']));
        // Present but hidden: the toggle reveals it.
        $ownerView->assertSee('data-drop-remove hidden', false);

        $member = User::where('email', 'ivan@acme.com')->first();
        $member->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($member)->get(route('organization.index', ['view' => 'chart']))
            ->assertDontSee('data-drop-remove', false);
    }

    /**
     * A department nobody is in still needs a column — otherwise the one you
     * just created has nothing to drag the first person onto.
     */
    public function test_an_empty_department_still_gets_a_column(): void
    {
        [$org, $owner] = $this->orgWithChart();

        Department::create([
            'organization_id' => $org->id, 'name' => 'Brand New', 'color' => Department::PALETTE[4],
        ]);

        $response = $this->actingAs($owner)->get(route('organization.index', ['view' => 'chart']));

        $response->assertOk();
        $response->assertSee('Brand New');
        $response->assertSee('Nobody yet');
    }

    public function test_someone_can_be_dragged_into_an_empty_department(): void
    {
        [$org, $owner] = $this->orgWithChart();

        $empty = Department::create([
            'organization_id' => $org->id, 'name' => 'Brand New', 'color' => Department::PALETTE[4],
        ]);
        $ivan = User::where('email', 'ivan@acme.com')->first();

        $response = $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $ivan->id, 'department_id' => $empty->id,
        ]);

        $response->assertOk();
        $this->assertSame((int) $empty->id, (int) $ivan->fresh()->department_id);
        // The redrawn chart shows them leading it, since they are all there is.
        $this->assertStringContainsString('Ivan Ic', $response->json('html'));
    }

    public function test_only_the_owner_is_offered_edit_mode(): void
    {
        [, $owner] = $this->orgWithChart();

        $ownerView = $this->actingAs($owner)->get(route('organization.index', ['view' => 'chart']));
        $ownerView->assertSee('chartEditToggle', false);
        $ownerView->assertSee('data-drop-department', false);
        $ownerView->assertSee(route('organization.chart.move'), false);

        $member = User::where('email', 'ivan@acme.com')->first();
        $member->forceFill(['email_verified_at' => now()])->save();

        $memberView = $this->actingAs($member)->get(route('organization.index', ['view' => 'chart']));
        $memberView->assertOk();
        $memberView->assertDontSee('chartEditToggle', false);
    }

    /** A card is the editor's trigger, so the chart can change people too. */
    public function test_chart_cards_open_the_same_member_editor(): void
    {
        [, $owner] = $this->orgWithChart();

        $response = $this->actingAs($owner)->get(route('organization.index', ['view' => 'chart']));

        $response->assertOk();
        // The shared dialog is on this tab...
        $response->assertSee('id="memberDialog"', false);
        $response->assertSee(route('organization.members.update'), false);
        // ...and the cards carry what it reads.
        $response->assertSee('data-member-edit', false);
        $response->assertSee('data-rank=', false);
    }

    public function test_the_chart_offers_search_fit_and_collapsible_branches(): void
    {
        [, $owner] = $this->orgWithChart();

        $response = $this->actingAs($owner)->get(route('organization.index', ['view' => 'chart']));

        $response->assertOk();
        $response->assertSee('id="chartSearch"', false);
        $response->assertSee('data-chart-fit', false);
        $response->assertSee('data-branch-toggle', false);
        // Each band states its own size.
        $response->assertSee('tt-chart-band-count', false);
    }

    public function test_a_member_gets_the_chart_without_any_editing_hooks(): void
    {
        $this->orgWithChart();

        $member = User::where('email', 'ivan@acme.com')->first();
        $member->forceFill(['email_verified_at' => now()])->save();

        $response = $this->actingAs($member)->get(route('organization.index', ['view' => 'chart']));

        $response->assertOk();
        // Search and collapse are for everyone; editing is not.
        $response->assertSee('id="chartSearch"', false);
        $response->assertSee('data-branch-toggle', false);
        $response->assertDontSee('data-member-edit', false);
        $response->assertDontSee('id="memberDialog"', false);
    }

    public function test_dropping_a_card_on_a_person_makes_them_report_to_them(): void
    {
        [, $owner] = $this->orgWithChart();

        $dana = User::where('email', 'dana@acme.com')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();

        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $ivan->id, 'manager_id' => $dana->id,
        ])->assertOk();

        $fresh = $ivan->fresh();

        $this->assertSame((int) $dana->id, (int) $fresh->manager_id);
        // A report lives in their manager's column, so the drop carried it.
        $this->assertSame((int) $dana->department_id, (int) $fresh->department_id);
    }

    /** Reporting nests without limit — a report can have reports. */
    public function test_reports_nest_more_than_one_level_deep(): void
    {
        [$org, $owner] = $this->orgWithChart();

        $dana = User::where('email', 'dana@acme.com')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();
        $deep = User::factory()->create([
            'name' => 'Deep Report', 'email' => 'deep@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 10, 'department_id' => $dana->department_id,
        ]);

        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $ivan->id, 'manager_id' => $dana->id,
        ])->assertOk();

        $response = $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $deep->id, 'manager_id' => $ivan->id,
        ]);

        $response->assertOk();
        $this->assertSame((int) $ivan->id, (int) $deep->fresh()->manager_id);

        // Drawn as a list inside a list, not flattened.
        $html = $response->json('html');
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'tt-chart-reports'));
    }

    public function test_dropping_on_a_band_lifts_them_back_to_the_top_level(): void
    {
        [, $owner] = $this->orgWithChart();

        $dana = User::where('email', 'dana@acme.com')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();
        $ivan->forceFill(['manager_id' => $dana->id])->save();

        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $ivan->id, 'department_id' => $dana->department_id,
        ])->assertOk();

        $this->assertNull($ivan->fresh()->manager_id);
    }

    /** The loop that would make the drawing recurse forever. */
    public function test_a_manager_cannot_be_made_to_report_to_their_own_report(): void
    {
        [, $owner] = $this->orgWithChart();

        $dana = User::where('email', 'dana@acme.com')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();
        $ivan->forceFill(['manager_id' => $dana->id])->save();

        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $dana->id, 'manager_id' => $ivan->id,
        ])->assertStatus(422);

        $this->assertNull($dana->fresh()->manager_id);
    }

    public function test_a_deeper_loop_is_refused_too(): void
    {
        [$org, $owner] = $this->orgWithChart();

        $a = User::where('email', 'dana@acme.com')->first();
        $b = User::where('email', 'ivan@acme.com')->first();
        $c = User::factory()->create([
            'name' => 'Third Level', 'email' => 'third@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 10, 'department_id' => $a->department_id,
        ]);

        $b->forceFill(['manager_id' => $a->id])->save();
        $c->forceFill(['manager_id' => $b->id])->save();

        // A under C would close the ring A -> B -> C -> A.
        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $a->id, 'manager_id' => $c->id,
        ])->assertStatus(422);

        $this->assertNull($a->fresh()->manager_id);
    }

    public function test_nobody_can_be_made_to_report_to_themselves(): void
    {
        [, $owner] = $this->orgWithChart();

        $ivan = User::where('email', 'ivan@acme.com')->first();

        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $ivan->id, 'manager_id' => $ivan->id,
        ])->assertStatus(422);

        $this->assertNull($ivan->fresh()->manager_id);
    }

    public function test_a_manager_in_another_organization_is_a_404(): void
    {
        [, $owner] = $this->orgWithChart();

        $outsider = User::factory()->create([
            'email' => 'someone@globex.com', 'user_type' => 'customer',
            'organization_id' => Organization::create(['domain' => 'globex.com'])->id,
        ]);
        $ivan = User::where('email', 'ivan@acme.com')->first();

        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $ivan->id, 'manager_id' => $outsider->id,
        ])->assertNotFound();

        $this->assertNull($ivan->fresh()->manager_id);
    }

    /** Moving a manager elsewhere must not leave lines running across columns. */
    public function test_moving_a_manager_to_another_department_frees_their_reports(): void
    {
        [, $owner] = $this->orgWithChart();

        $dana = User::where('email', 'dana@acme.com')->first();
        $ivan = User::where('email', 'ivan@acme.com')->first();
        $ivan->forceFill(['manager_id' => $dana->id])->save();

        $eng = Department::where('name', 'Engineering')->first();

        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $dana->id, 'department_id' => $eng->id,
        ])->assertOk();

        $this->assertNull($ivan->fresh()->manager_id);
        // Ivan stays put; only the line to his old manager goes.
        $this->assertNotNull($ivan->fresh()->department_id);
    }

    /** @return array{0: Organization, 1: User, 2: Collection} */
    private function orgWithARow(): array
    {
        [$org, $owner] = $this->ownedOrg();

        $dept = Department::create([
            'organization_id' => $org->id, 'name' => 'Row', 'color' => Department::PALETTE[0],
        ]);

        // Same rank, so only name — and then sort_order — decides the order.
        $people = collect(['Anna', 'Bob', 'Cara'])->map(fn ($name) => User::factory()->create([
            'name' => $name, 'email' => strtolower($name).'@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20, 'department_id' => $dept->id,
        ]));

        return [$org, $owner, $people];
    }

    private function orderIn(string $html, array $names): array
    {
        $found = [];

        foreach ($names as $name) {
            $found[$name] = strpos($html, '<strong>'.$name.'</strong>');
        }

        asort($found);

        return array_keys($found);
    }

    public function test_a_card_dropped_in_a_gap_lands_between_its_siblings(): void
    {
        [, $owner, $people] = $this->orgWithARow();

        $anna = $people[0];
        $cara = $people[2];

        // Cara before Anna: she was last by name, now she leads the row.
        $response = $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $cara->id, 'before_id' => $anna->id,
        ]);

        $response->assertOk();
        $this->assertSame(['Cara', 'Anna', 'Bob'], $this->orderIn($response->json('html'), ['Anna', 'Bob', 'Cara']));
    }

    public function test_dropping_after_the_last_card_puts_them_at_the_end(): void
    {
        [, $owner, $people] = $this->orgWithARow();

        $anna = $people[0];
        $cara = $people[2];

        $response = $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $anna->id, 'after_id' => $cara->id,
        ]);

        $response->assertOk();
        $this->assertSame(['Bob', 'Cara', 'Anna'], $this->orderIn($response->json('html'), ['Anna', 'Bob', 'Cara']));
    }

    /** The order has to survive a reload, not just the response it came in. */
    public function test_the_order_is_stored_not_just_drawn(): void
    {
        [, $owner, $people] = $this->orgWithARow();

        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $people[2]->id, 'before_id' => $people[0]->id,
        ])->assertOk();

        $html = $this->actingAs($owner)
            ->get(route('organization.index', ['view' => 'chart']))->getContent();

        $this->assertSame(['Cara', 'Anna', 'Bob'], $this->orderIn($html, ['Anna', 'Bob', 'Cara']));
    }

    /** A gap belongs to a row, so landing in one joins that row's manager. */
    public function test_a_gap_carries_the_manager_of_the_row_it_belongs_to(): void
    {
        [$org, $owner, $people] = $this->orgWithARow();

        $boss = $people[0];
        $report = $people[1];
        $outsider = $people[2];

        $report->forceFill(['manager_id' => $boss->id])->save();

        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $outsider->id, 'before_id' => $report->id,
        ])->assertOk();

        $this->assertSame((int) $boss->id, (int) $outsider->fresh()->manager_id);
    }

    public function test_a_gap_next_to_someone_in_another_organization_is_a_404(): void
    {
        [, $owner, $people] = $this->orgWithARow();

        $theirs = User::factory()->create([
            'email' => 'someone@globex.com', 'user_type' => 'customer',
            'organization_id' => Organization::create(['domain' => 'globex.com'])->id,
        ]);

        $this->actingAs($owner)->postJson(route('organization.chart.move'), [
            'user_id' => $people[0]->id, 'before_id' => $theirs->id,
        ])->assertNotFound();
    }

    public function test_the_chart_renders_insertion_gaps(): void
    {
        [, $owner] = $this->orgWithARow();

        $response = $this->actingAs($owner)->get(route('organization.index', ['view' => 'chart']));

        $response->assertOk();
        $response->assertSee('data-drop-before', false);
        $response->assertSee('data-drop-after', false);
    }

    public function test_the_members_tab_is_still_the_default(): void
    {
        [, $owner] = $this->orgWithChart();

        $response = $this->actingAs($owner)->get(route('organization.index'));

        $response->assertOk();
        $response->assertSee('id="removeForm"', false);
        $response->assertDontSee('id="orgChart"', false);
        // Both tabs are offered.
        $response->assertSee(route('organization.index', ['view' => 'chart']), false);
    }

    public function test_the_roster_filters_to_one_department(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $design = Department::create([
            'organization_id' => $org->id, 'name' => 'Design', 'color' => Department::PALETTE[0],
        ]);

        User::factory()->create([
            'name' => 'In Design', 'email' => 'in@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20, 'department_id' => $design->id,
        ]);
        User::factory()->create([
            'name' => 'Out Of It', 'email' => 'out@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $response = $this->actingAs($owner)->get(route('organization.index', ['department' => $design->id]));

        $response->assertOk();
        $response->assertSee('in@acme.com');
        $response->assertDontSee('out@acme.com');
        $response->assertSee('Show everyone', false);

        // Unfiltered, both are listed.
        $all = $this->actingAs($owner)->get(route('organization.index'));
        $all->assertSee('in@acme.com');
        $all->assertSee('out@acme.com');
    }
}
