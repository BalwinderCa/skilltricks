<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheck extends Command
{
    protected $signature = 'health:check';

    protected $description = 'Basic application health check for monitoring';

    public function handle(): int
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        foreach ($checks as $name => $ok) {
            $this->line(sprintf('%s: %s', $name, $ok ? 'ok' : 'failed'));
        }

        return collect($checks)->every(fn ($ok) => $ok)
            ? Command::SUCCESS
            : Command::FAILURE;
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        if (config('database.redis.default.host') === null) {
            return true;
        }

        try {
            Redis::connection()->ping();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
