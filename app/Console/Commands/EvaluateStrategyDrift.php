<?php

namespace App\Console\Commands;

use App\Models\SearchUserChat;
use App\Services\DriftIndex;
use Illuminate\Console\Command;

/** Drift grows with time even when nobody reports: re-evaluate daily. */
class EvaluateStrategyDrift extends Command
{
    protected $signature = 'strategies:evaluate-drift';

    protected $description = 'Recalculate the drift index of every published strategy and send threshold alerts';

    public function handle(DriftIndex $drift): int
    {
        SearchUserChat::where('status', 'published')->orderBy('id')->each(fn (SearchUserChat $chat) => $drift->evaluate($chat));

        return self::SUCCESS;
    }
}
