<?php

namespace Tests\Feature;

use App\Mail\User\TeamInvitationMail;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The invitation round trip: a temporary password goes out, it gets the member
 * in, and nothing else in the dashboard opens until they replace it.
 */
class TeamInvitationTest extends TestCase
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
        Mail::fake();
    }

    /** @return array{0: Organization, 1: User} */
    /** The seeded role at one rung of the fixture organization's ladder. */
    private function roleId(string $name): int
    {
        return (int) OrgRole::whereHas('organization', fn ($q) => $q->where('domain', 'acme.com'))
            ->where('name', $name)
            ->value('id');
    }

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

    private function member(Organization $org, string $email = 'grace@acme.com'): User
    {
        return User::factory()->create([
            'name' => 'Grace Hopper', 'email' => $email, 'user_type' => 'customer',
            'email_verified_at' => null, 'organization_id' => $org->id, 'hierarchy_rank' => 20,
        ]);
    }

    public function test_adding_without_the_invite_box_sends_nothing(): void
    {
        [, $owner] = $this->ownedOrg();

        $this->actingAs($owner)->post(route('organization.members.store'), [
            'name' => 'Grace Hopper', 'email' => 'grace@acme.com', 'org_role_id' => $this->roleId('Manager'),
        ])->assertRedirect();

        $grace = User::where('email', 'grace@acme.com')->first();

        $this->assertNotNull($grace);
        $this->assertFalse((bool) $grace->must_change_password);
        Mail::assertNothingSent();
    }

    public function test_importing_without_the_invite_box_sends_nothing(): void
    {
        [, $owner] = $this->ownedOrg();

        $file = UploadedFile::fake()->createWithContent(
            'team.csv',
            "name,email,role,department\nGrace Hopper,grace@acme.com,Manager,Ops\n"
        );

        $this->actingAs($owner)
            ->post(route('organization.members.import'), ['members' => $file])
            ->assertRedirect();

        $this->assertNotNull(User::where('email', 'grace@acme.com')->first());
        Mail::assertNothingSent();
    }

    public function test_the_owner_invites_the_checked_members_afterwards(): void
    {
        [$org, $owner] = $this->ownedOrg();
        $member = $this->member($org);

        $this->actingAs($owner)->post(route('organization.members.invite'), [
            'user_ids' => [$member->id],
        ])->assertRedirect();

        $fresh = $member->fresh();

        $this->assertTrue((bool) $fresh->must_change_password);
        // Verified by construction: the temporary password reaches nobody but
        // that mailbox, so using it proves the address.
        $this->assertNotNull($fresh->email_verified_at);
        $this->assertSame(1, (int) $fresh->email_or_otp_verified);
        // The password was replaced with the one that was mailed.
        $this->assertNotSame($member->password, $fresh->password);

        Mail::assertSent(TeamInvitationMail::class, 1);
    }

    /** The mailed password is the one that works, and it is not stored in clear. */
    public function test_the_mailed_temporary_password_signs_them_in(): void
    {
        [$org, $owner] = $this->ownedOrg();
        $member = $this->member($org);

        $this->actingAs($owner)->post(route('organization.members.invite'), ['user_ids' => [$member->id]]);

        $sent = null;
        Mail::assertSent(TeamInvitationMail::class, function ($mail) use (&$sent) {
            $mail->build();
            $sent = $mail->viewData['temporaryPassword'] ?? null;

            return true;
        });

        $this->assertNotNull($sent);
        $this->assertTrue(Hash::check($sent, $member->fresh()->password));
    }

    public function test_inviting_someone_in_another_organization_sends_nothing(): void
    {
        [, $owner] = $this->ownedOrg();

        $outsider = User::factory()->create([
            'email' => 'someone@globex.com', 'user_type' => 'customer',
            'organization_id' => Organization::create(['domain' => 'globex.com'])->id,
            'hierarchy_rank' => 20,
        ]);

        $this->actingAs($owner)->post(route('organization.members.invite'), [
            'user_ids' => [$outsider->id],
        ])->assertRedirect();

        $this->assertFalse((bool) $outsider->fresh()->must_change_password);
        Mail::assertNothingSent();
    }

    public function test_a_non_owner_cannot_invite(): void
    {
        [$org] = $this->ownedOrg();
        $member = $this->member($org);
        $member->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($member)->post(route('organization.members.invite'), [
            'user_ids' => [$member->id],
        ])->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_an_invited_member_is_held_on_the_password_form(): void
    {
        [$org] = $this->ownedOrg();
        $member = $this->member($org);
        $member->forceFill(['must_change_password' => true, 'email_verified_at' => now()])->save();

        // Every dashboard route, not just the landing one.
        foreach (['writebot.dashboard', 'organization.index', 'dashboard.profile'] as $name) {
            $this->actingAs($member)->get(route($name))->assertRedirect(route('password.change'));
        }

        $this->actingAs($member)->get(route('password.change'))->assertOk();
    }

    public function test_the_blocked_page_carries_no_sidebar(): void
    {
        [$org] = $this->ownedOrg();
        $member = $this->member($org);
        $member->forceFill(['must_change_password' => true, 'email_verified_at' => now()])->save();

        $response = $this->actingAs($member)->get(route('password.change'));

        $response->assertOk();
        $response->assertSee('setPasswordDialog', false);
        // The sidebar's own nav links are what "hidden" has to mean here.
        $response->assertDontSee(route('organization.index'), false);
    }

    public function test_setting_a_password_lifts_the_block(): void
    {
        [$org] = $this->ownedOrg();
        $member = $this->member($org);
        $member->forceFill(['must_change_password' => true, 'email_verified_at' => now()])->save();

        $this->actingAs($member)->post(route('password.change.store'), [
            'password' => 'chosen-secret',
            'password_confirmation' => 'chosen-secret',
        ])->assertRedirect(route('writebot.dashboard'));

        $fresh = $member->fresh();

        $this->assertFalse((bool) $fresh->must_change_password);
        $this->assertTrue(Hash::check('chosen-secret', $fresh->password));

        $this->actingAs($fresh)->get(route('organization.index'))->assertOk();
    }

    public function test_a_mismatched_confirmation_leaves_the_block_in_place(): void
    {
        [$org] = $this->ownedOrg();
        $member = $this->member($org);
        $member->forceFill(['must_change_password' => true, 'email_verified_at' => now()])->save();

        $this->actingAs($member)->post(route('password.change.store'), [
            'password' => 'chosen-secret',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertTrue((bool) $member->fresh()->must_change_password);
    }

    /** Nobody who has not been invited is affected by any of this. */
    public function test_an_ordinary_member_is_not_blocked(): void
    {
        [$org] = $this->ownedOrg();
        $member = $this->member($org);
        $member->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($member)->get(route('organization.index'))->assertOk();
    }
}
