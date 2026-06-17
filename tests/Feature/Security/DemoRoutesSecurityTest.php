<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class DemoRoutesSecurityTest extends TestCase
{
    public function test_demo_db_cron_returns_404_when_not_local(): void
    {
        config(['custom.demo_mode' => 'On']);

        $response = $this->get('/demo/db-cron');

        $response->assertNotFound();
    }

    public function test_demo_folder_cron_returns_404_when_not_local(): void
    {
        config(['custom.demo_mode' => 'On']);

        $response = $this->get('/demo/folder-cron');

        $response->assertNotFound();
    }

    public function test_demo_db_cron_returns_404_in_production_even_with_demo_mode_on(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['custom.demo_mode' => 'On']);

        $response = $this->get('/demo/db-cron');

        $response->assertNotFound();
    }

    public function test_stream_route_returns_404_when_not_local(): void
    {
        config(['custom.demo_mode' => 'On']);

        $response = $this->get('/stream');

        $response->assertNotFound();
    }
}
