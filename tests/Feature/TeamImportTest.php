<?php

namespace Tests\Feature;

use App\Mail\User\TeamInvitationMail;
use App\Models\Department;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamImportTest extends TestCase
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
        app(OrganizationService::class)->seedDefaultRoles($org);

        $owner = User::factory()->create([
            'email' => 'owner@acme.com', 'user_type' => 'customer',
            'email_verified_at' => now(), 'organization_id' => $org->id, 'hierarchy_rank' => 50,
        ]);

        $org->forceFill(['owner_user_id' => $owner->id])->save();

        return [$org, $owner];
    }

    /** One of the fixture organization's seeded roles, by name. */
    private function roleId(string $name): int
    {
        return (int) OrgRole::whereHas('organization', fn ($q) => $q->where('domain', 'acme.com'))
            ->where('name', $name)
            ->value('id');
    }

    private function department(Organization $org, string $name): Department
    {
        return Department::firstOrCreate(
            ['organization_id' => $org->id, 'name' => $name],
            ['color' => Department::PALETTE[0]]
        );
    }

    private function csv(string $body): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('team.csv', $body);
    }

    public function test_the_owner_bulk_adds_members_from_a_csv(): void
    {
        Mail::fake();

        [$org, $owner] = $this->ownedOrg();

        $file = $this->csv(
            "name,email,department\n".
            "Jane Doe,jane@acme.com,Marketing\n".
            "John Smith,JOHN@acme.com,\n"
        );

        $this->actingAs($owner)
            ->post(route('organization.members.import'), ['members' => $file, 'send_invites' => 1])
            ->assertRedirect();

        $jane = User::where('email', 'jane@acme.com')->first();
        $john = User::where('email', 'john@acme.com')->first();

        $this->assertNotNull($jane);
        // No role column any more: they arrive unassigned, at the floor rung.
        $this->assertNull($jane->org_role_id);
        $this->assertSame(10, (int) $jane->hierarchy_rank);
        // The CSV named a department that did not exist; it was created.
        $this->assertSame('Marketing', $jane->department->name);
        $this->assertContains($jane->department->color, Department::PALETTE);
        $this->assertSame((int) $org->id, (int) $jane->organization_id);

        // The address is normalised on the way in.
        $this->assertNotNull($john);
        $this->assertSame(10, (int) $john->hierarchy_rank);
        $this->assertNull($john->department_id);

        Mail::assertSent(TeamInvitationMail::class, 2);
        // Invited members are marked verified — the temporary password exists
        // only in that mailbox — and are held on the password form.
        $this->assertNotNull($jane->email_verified_at);
        $this->assertTrue((bool) $jane->must_change_password);
    }

    public function test_rows_that_are_invalid_or_already_registered_are_skipped(): void
    {
        Mail::fake();

        [, $owner] = $this->ownedOrg();

        $before = User::count();

        $file = $this->csv(
            "name,email,department\n".
            "No Email,,Ops\n".
            "Bad Address,not-an-email,Ops\n".
            ",jane@acme.com,Ops\n".
            "Already Here,owner@acme.com,Ops\n"
        );

        $this->actingAs($owner)->post(route('organization.members.import'), ['members' => $file]);

        $this->assertSame($before, User::count());
        // The duplicate row must not have promoted the existing account.
        $this->assertSame(50, (int) $owner->fresh()->hierarchy_rank);
    }

    public function test_a_member_who_is_not_the_owner_cannot_import(): void
    {
        [$org] = $this->ownedOrg();

        $member = User::factory()->create([
            'email' => 'member@acme.com', 'user_type' => 'customer',
            'email_verified_at' => now(), 'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $this->actingAs($member)
            ->post(route('organization.members.import'), ['members' => $this->csv("name,email,department\nJane,jane@acme.com,Ops\n")])
            ->assertForbidden();

        $this->assertNull(User::where('email', 'jane@acme.com')->first());
    }

    public function test_the_owner_adds_one_member_from_the_dialog(): void
    {
        Mail::fake();

        [$org, $owner] = $this->ownedOrg();

        $this->actingAs($owner)->post(route('organization.members.store'), [
            'name' => 'Grace Hopper',
            'email' => 'GRACE@acme.com',
            'org_role_id' => $this->roleId('Vice President'),
            'department_id' => $this->department($org, 'Engineering')->id,
            'send_invite' => 1,
        ])->assertRedirect();

        $grace = User::where('email', 'grace@acme.com')->first();

        $this->assertNotNull($grace);
        $this->assertSame('Vice President', $grace->orgRole->name);
        // Roles carry no seniority, so everyone added starts at the floor rung.
        $this->assertSame(10, (int) $grace->hierarchy_rank);
        $this->assertSame('Engineering', $grace->department->name);
        $this->assertSame((int) $org->id, (int) $grace->organization_id);
        Mail::assertSent(TeamInvitationMail::class);
    }

    public function test_adding_an_address_that_already_exists_creates_nothing(): void
    {
        Mail::fake();

        [, $owner] = $this->ownedOrg();
        $before = User::count();

        $this->actingAs($owner)->post(route('organization.members.store'), [
            'name' => 'Impostor', 'email' => 'owner@acme.com', 'org_role_id' => $this->roleId('Board'),
        ])->assertRedirect();

        $this->assertSame($before, User::count());
        $this->assertSame(50, (int) $owner->fresh()->hierarchy_rank);
    }

    public function test_the_owner_edits_a_members_name_role_and_department(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'name' => 'Grace H', 'email' => 'grace@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
            'department_id' => $this->department($org, 'Ops')->id,
        ]);

        $this->actingAs($owner)->post(route('organization.members.update'), [
            'user_id' => $member->id,
            'name' => 'Grace Hopper',
            'email' => 'grace@acme.com',
            'org_role_id' => $this->roleId('Vice President'),
            'department_id' => $this->department($org, 'Engineering')->id,
        ])->assertRedirect();

        $fresh = $member->fresh();

        $this->assertSame('Grace Hopper', $fresh->name);
        $this->assertSame('Vice President', $fresh->orgRole->name);
        // Changing a role must not move the rank the governance election reads.
        $this->assertSame(20, (int) $fresh->hierarchy_rank);
        $this->assertSame('Engineering', $fresh->department->name);
        // The address is never taken from the edit form.
        $this->assertSame('grace@acme.com', $fresh->email);
    }

    /** An owner must not reach outside their own organization by posting an id. */
    public function test_editing_someone_in_another_organization_is_a_404(): void
    {
        [, $owner] = $this->ownedOrg();

        $outsider = User::factory()->create([
            'name' => 'Outsider', 'email' => 'someone@globex.com', 'user_type' => 'customer',
            'organization_id' => Organization::create(['domain' => 'globex.com'])->id,
            'hierarchy_rank' => 10,
        ]);

        $this->actingAs($owner)->post(route('organization.members.update'), [
            'user_id' => $outsider->id, 'name' => 'Hijacked',
            'email' => 'someone@globex.com', 'org_role_id' => $this->roleId('Board'),
        ])->assertNotFound();

        $this->assertSame('Outsider', $outsider->fresh()->name);
    }

    public function test_removing_a_member_clears_membership_but_keeps_the_account(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'email' => 'grace@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 40,
            'department_id' => $this->department($org, 'Engineering')->id,
        ]);

        $this->actingAs($owner)->post(route('organization.members.remove'), [
            'user_id' => $member->id,
        ])->assertRedirect();

        $fresh = $member->fresh();

        $this->assertNotNull($fresh);
        $this->assertNull($fresh->organization_id);
        $this->assertNull($fresh->hierarchy_rank);
        $this->assertNull($fresh->department_id);
        $this->assertNull($fresh->deleted_at);
    }

    /** An evicted member's declaration must stop governing the organization. */
    public function test_removing_a_member_re_elects_the_active_context(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'email' => 'grace@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 10,
        ]);

        $service = app(OrganizationService::class);
        $service->recordContext($org, $owner, 50, ['role' => 'Chief Executive Officer', 'rank' => 50]);
        $service->recordContext($org, $member, 60, ['role' => 'Board seat', 'rank' => 60]);

        $this->assertSame('Board seat', $org->fresh()->activeContext->profile['role']);

        $this->actingAs($owner)->post(route('organization.members.remove'), ['user_id' => $member->id]);

        $this->assertSame('Chief Executive Officer', $org->fresh()->activeContext->profile['role']);
    }

    public function test_the_owner_cannot_be_removed(): void
    {
        [, $owner] = $this->ownedOrg();

        $this->actingAs($owner)->post(route('organization.members.remove'), [
            'user_id' => $owner->id,
        ])->assertRedirect();

        $this->assertNotNull($owner->fresh()->organization_id);
    }

    public function test_a_member_who_is_not_the_owner_cannot_add_edit_or_remove(): void
    {
        [$org] = $this->ownedOrg();

        $member = User::factory()->create([
            'email' => 'member@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $this->actingAs($member)->post(route('organization.members.store'), [
            'name' => 'Sneaky', 'email' => 'sneaky@acme.com', 'org_role_id' => $this->roleId('Board'),
        ])->assertForbidden();

        $this->actingAs($member)->post(route('organization.members.update'), [
            'user_id' => $member->id, 'name' => 'Promoted',
            'email' => $member->email, 'org_role_id' => $this->roleId('Board'),
        ])->assertForbidden();

        $this->actingAs($member)->post(route('organization.members.remove'), [
            'user_id' => $member->id,
        ])->assertForbidden();

        $this->assertNull(User::where('email', 'sneaky@acme.com')->first());
        $this->assertSame(20, (int) $member->fresh()->hierarchy_rank);
    }

    public function test_changing_an_email_unverifies_it(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'name' => 'Grace Hopper', 'email' => 'gracee@acme.com', 'user_type' => 'customer',
            'email_verified_at' => now(), 'email_or_otp_verified' => 1,
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $this->actingAs($owner)->post(route('organization.members.update'), [
            'user_id' => $member->id, 'name' => 'Grace Hopper',
            'email' => 'GRACE@acme.com', 'org_role_id' => $this->roleId('Manager'),
        ])->assertRedirect();

        $fresh = $member->fresh();

        $this->assertSame('grace@acme.com', $fresh->email);
        // Nobody proved the new mailbox, so the old verification cannot carry over.
        $this->assertNull($fresh->email_verified_at);
        $this->assertSame(0, (int) $fresh->email_or_otp_verified);
    }

    public function test_an_unchanged_email_keeps_its_verification(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'name' => 'Grace', 'email' => 'grace@acme.com', 'user_type' => 'customer',
            'email_verified_at' => now(), 'email_or_otp_verified' => 1,
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $this->actingAs($owner)->post(route('organization.members.update'), [
            'user_id' => $member->id, 'name' => 'Grace Hopper',
            'email' => 'grace@acme.com', 'org_role_id' => $this->roleId('Director'),
        ])->assertRedirect();

        $this->assertNotNull($member->fresh()->email_verified_at);
    }

    public function test_an_email_already_taken_is_refused(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'name' => 'Grace', 'email' => 'grace@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $this->actingAs($owner)->post(route('organization.members.update'), [
            'user_id' => $member->id, 'name' => 'Grace',
            'email' => 'owner@acme.com', 'org_role_id' => $this->roleId('Manager'),
        ])->assertRedirect();

        $this->assertSame('grace@acme.com', $member->fresh()->email);
    }

    public function test_the_owner_bulk_removes_the_checked_members(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $ids = collect(['a', 'b', 'c'])->map(fn ($n) => User::factory()->create([
            'email' => "{$n}@acme.com", 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
            'department_id' => $this->department($org, 'Ops')->id,
        ])->id);

        $this->actingAs($owner)->post(route('organization.members.remove'), [
            'user_ids' => [$ids[0], $ids[2]],
        ])->assertRedirect();

        $this->assertNull(User::find($ids[0])->organization_id);
        $this->assertNull(User::find($ids[2])->organization_id);
        // The unticked one is untouched.
        $this->assertSame((int) $org->id, (int) User::find($ids[1])->organization_id);
        $this->assertSame('Ops', User::find($ids[1])->department->name);
    }

    /** A single row's Delete must not sweep up whatever else happens to be ticked. */
    public function test_a_single_row_delete_ignores_the_checkboxes(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $target = User::factory()->create([
            'email' => 'target@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);
        $bystander = User::factory()->create([
            'email' => 'bystander@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $this->actingAs($owner)->post(route('organization.members.remove'), [
            'user_id' => $target->id,
            'user_ids' => [$bystander->id],
        ])->assertRedirect();

        $this->assertNull($target->fresh()->organization_id);
        $this->assertSame((int) $org->id, (int) $bystander->fresh()->organization_id);
    }

    public function test_a_bulk_remove_skips_the_owner_and_outsiders(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'email' => 'member@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);
        $outsider = User::factory()->create([
            'email' => 'someone@globex.com', 'user_type' => 'customer',
            'organization_id' => Organization::create(['domain' => 'globex.com'])->id,
            'hierarchy_rank' => 20,
        ]);

        $this->actingAs($owner)->post(route('organization.members.remove'), [
            'user_ids' => [$owner->id, $member->id, $outsider->id],
        ])->assertRedirect();

        $this->assertNotNull($owner->fresh()->organization_id);
        $this->assertNull($member->fresh()->organization_id);
        $this->assertNotNull($outsider->fresh()->organization_id);
    }

    public function test_removing_with_nothing_selected_changes_nothing(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'email' => 'member@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $this->actingAs($owner)->post(route('organization.members.remove'), [])->assertRedirect();

        $this->assertSame((int) $org->id, (int) $member->fresh()->organization_id);
    }

    public function test_the_owner_is_not_listed_on_the_roster(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'name' => 'Grace Hopper', 'email' => 'grace@acme.com', 'user_type' => 'customer',
            'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $response = $this->actingAs($owner)->get(route('organization.index'));

        $response->assertOk();
        $response->assertSee($member->email);
        $response->assertDontSee($owner->email);
        // Hiding the row is display only — the server rule that the owner
        // cannot be evicted still stands.
        $this->actingAs($owner)->post(route('organization.members.remove'), ['user_id' => $owner->id]);
        $this->assertNotNull($owner->fresh()->organization_id);
    }

    public function test_only_the_owner_is_offered_the_bulk_add_form(): void
    {
        [$org, $owner] = $this->ownedOrg();

        $member = User::factory()->create([
            'email' => 'member@acme.com', 'user_type' => 'customer',
            'email_verified_at' => now(), 'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);

        $ownerView = $this->actingAs($owner)->get(route('organization.index'));
        $ownerView->assertOk();
        $ownerView->assertSee(route('organization.sample-csv'), false);
        $ownerView->assertSee(route('organization.members.import'), false);
        $ownerView->assertSee('data-member-add', false);
        $ownerView->assertSee('data-member-edit', false);
        $ownerView->assertSee(route('organization.members.remove'), false);
        // Delete goes through the site's own confirm dialog, never the browser's.
        $ownerView->assertSee('data-confirm-variant="danger"', false);
        $ownerView->assertSee('name="user_ids[]"', false);
        $ownerView->assertSee('id="checkAll"', false);
        $ownerView->assertSee('data-bulk-open', false);
        // The bulk bar ships hidden — it only has something to act on once a
        // box is ticked.
        $ownerView->assertSee('id="bulkBar" hidden', false);
        $ownerView->assertDontSee('onclick="return confirm', false);

        $memberView = $this->actingAs($member)->get(route('organization.index'));
        $memberView->assertOk();
        $memberView->assertDontSee(route('organization.members.import'), false);
        $memberView->assertDontSee('data-member-edit', false);
        $memberView->assertDontSee('name="user_ids[]"', false);
        $memberView->assertDontSee(route('organization.members.remove'), false);
    }

    public function test_the_sample_csv_uses_the_organizations_own_domain(): void
    {
        [, $owner] = $this->ownedOrg();

        $response = $this->actingAs($owner)->get(route('organization.sample-csv'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('name,email,department', false);
        $response->assertSee('jane@acme.com', false);
    }
}
