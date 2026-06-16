<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke test: the application boots in the testing environment and the
 * config/custom.php map (introduced by the env() -> config() refactor) is
 * present with the keys the app reads. No database required.
 */
class CustomConfigTest extends TestCase
{
    public function test_application_boots(): void
    {
        $this->assertTrue($this->app->bound('config'));
    }

    public function test_custom_config_exposes_expected_keys(): void
    {
        $custom = config('custom');

        $this->assertIsArray($custom);

        $expected = [
            'default_language', 'demo_mode', 'app_name', 'app_url',
            'ai_provider', 'openai_model', 'openai_max_tokens',
            'stripe_secret', 'stripe_key', 'paypal_notify_url',
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $custom, "config/custom.php is missing '{$key}'");
        }
    }
}
