<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Regression guard for the env() -> config() refactor.
 *
 * Calling env() outside config files returns null once `php artisan
 * config:cache` runs, silently breaking production. App code must read
 * configuration via config('custom.*') instead. This test fails if any
 * env() call is reintroduced under app/.
 */
class NoDirectEnvUsageTest extends TestCase
{
    public function test_app_directory_has_no_direct_env_calls(): void
    {
        $appPath = dirname(__DIR__, 2).'/app';
        $offenders = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($appPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if (preg_match('/\benv\s*\(\s*[\'"]/', $contents)) {
                $offenders[] = str_replace($appPath, 'app', $file->getPathname());
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Direct env() calls found in app/ (use config('custom.*') instead):\n".implode("\n", $offenders)
        );
    }
}
