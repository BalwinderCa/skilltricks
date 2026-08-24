<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The profile page posts one section at a time, so updateProfile() has to write
 * only what the submitted form carried. It used to assign every column from the
 * request unconditionally, which was safe only while all the inputs lived in a
 * single form -- with the sections split, that would wipe whatever the posting
 * section does not contain.
 */
class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'custom.demo_mode' => 'Off',
        ]);
    }

    private function customer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'name' => 'Ada Lovelace',
            'phone' => '+15550700',
            'company_name' => 'Analytical Engines',
            'company_address' => '1 Difference Way',
            'number_employess' => '10-20',
            'company_category' => 'Software',
            'about_company' => 'We compute.',
        ], $overrides));
    }

    public function test_saving_basic_information_leaves_the_company_fields_alone(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post(route('dashboard.profile.update'), [
            'name' => 'Ada King',
            'phone' => '+15550701',
            'avatar' => null,
        ]);

        $fresh = $user->fresh();

        $this->assertSame('Ada King', $fresh->name);
        $this->assertSame('Analytical Engines', $fresh->company_name);
        $this->assertSame('1 Difference Way', $fresh->company_address);
        $this->assertSame('10-20', $fresh->number_employess);
        $this->assertSame('Software', $fresh->company_category);
        $this->assertSame('We compute.', $fresh->about_company);
    }

    public function test_saving_company_information_leaves_the_basic_fields_alone(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post(route('dashboard.profile.update'), [
            'company_name' => 'Babbage Ltd',
            'company_address' => '2 Engine Row',
            'number_employess' => '20-50',
            'company_category' => 'Hardware',
            'about_company' => 'We build.',
        ]);

        $fresh = $user->fresh();

        $this->assertSame('Babbage Ltd', $fresh->company_name);
        $this->assertSame('We build.', $fresh->about_company);
        $this->assertSame('Ada Lovelace', $fresh->name);
        $this->assertNotNull($fresh->phone);
    }

    public function test_updating_the_password_touches_nothing_else(): void
    {
        $user = $this->customer(['password' => Hash::make('old-secret')]);

        $this->actingAs($user)->post(route('dashboard.profile.update'), [
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ]);

        $fresh = $user->fresh();

        $this->assertTrue(Hash::check('new-secret', $fresh->password));
        $this->assertSame('Ada Lovelace', $fresh->name);
        $this->assertSame('Analytical Engines', $fresh->company_name);
    }

    public function test_a_mismatched_confirmation_does_not_change_the_password(): void
    {
        $user = $this->customer(['password' => Hash::make('old-secret')]);

        $this->actingAs($user)->post(route('dashboard.profile.update'), [
            'password' => 'new-secret',
            'password_confirmation' => 'different',
        ]);

        $this->assertTrue(Hash::check('old-secret', $user->fresh()->password));
    }

    /**
     * A field submitted empty is the user clearing it, not an absent field, so
     * has() is the right check rather than filled(). Laravel's global
     * ConvertEmptyStringsToNull turns the "" into null on the way in, so the
     * cleared value lands as null.
     */
    public function test_a_submitted_empty_field_is_still_written(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post(route('dashboard.profile.update'), [
            'about_company' => '',
        ]);

        $this->assertNull($user->fresh()->about_company);
    }
}
