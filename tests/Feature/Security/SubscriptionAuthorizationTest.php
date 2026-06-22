<?php

namespace Tests\Feature\Security;

use App\Models\SubscriptionPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SubscriptionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate([
            'name' => 'subscriptions',
            'guard_name' => 'web',
        ], [
            'group_name' => 'subscriptions',
        ]);
    }

    public function test_subscription_delete_requires_subscriptions_permission(): void
    {
        $staff = User::factory()->create([
            'user_type' => 'staff',
            'email_verified_at' => now(),
        ]);

        $package = SubscriptionPackage::create([
            'title' => 'Test Package',
            'slug' => 'test-package-' . time(),
            'description' => 'Test',
            'package_type' => 'monthly',
            'price' => 9.99,
            'is_active' => 1,
            'openai_model_id' => 5,
        ]);

        $response = $this->actingAs($staff)->get(route('subscriptions.delete', $package->id));

        $response->assertForbidden();
    }

    public function test_subscription_delete_allowed_with_subscriptions_permission(): void
    {
        $staff = User::factory()->create([
            'user_type' => 'staff',
            'email_verified_at' => now(),
        ]);
        $staff->givePermissionTo('subscriptions');

        $package = SubscriptionPackage::create([
            'title' => 'Deletable Package',
            'slug' => 'deletable-package-' . time(),
            'description' => 'Test',
            'package_type' => 'monthly',
            'price' => 19.99,
            'is_active' => 1,
            'openai_model_id' => 5,
        ]);

        $response = $this->actingAs($staff)->get(route('subscriptions.delete', $package->id));

        $response->assertRedirect();
        $this->assertSoftDeleted('subscription_packages', ['id' => $package->id]);
    }
}
