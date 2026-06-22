<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SettingsEnvKeyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate([
            'name' => 'general_settings',
            'guard_name' => 'web',
        ], [
            'group_name' => 'system_settings',
        ]);

        config(['custom.demo_mode' => 'Off']);
    }

    public function test_env_key_update_blocks_sensitive_keys(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'admin',
            'email_verified_at' => now(),
        ]);
        $admin->givePermissionTo('general_settings');

        $response = $this->actingAs($admin)->post(route('admin.envKey.update'), [
            'types' => ['APP_KEY'],
            'APP_KEY' => 'base64:stolen-key',
        ]);

        $response->assertForbidden();
    }

    public function test_env_key_update_blocks_db_credentials(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'admin',
            'email_verified_at' => now(),
        ]);
        $admin->givePermissionTo('general_settings');

        $response = $this->actingAs($admin)->post(route('admin.envKey.update'), [
            'types' => ['DB_PASSWORD'],
            'DB_PASSWORD' => 'hacked',
        ]);

        $response->assertForbidden();
    }

    public function test_env_key_update_allows_whitelisted_keys(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'admin',
            'email_verified_at' => now(),
        ]);
        $admin->givePermissionTo('general_settings');

        $response = $this->actingAs($admin)->post(route('admin.envKey.update'), [
            'types' => ['TRACKING_ID'],
            'TRACKING_ID' => 'G-TEST123',
        ]);

        $response->assertRedirect();
    }
}
