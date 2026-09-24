<?php

namespace App\Services;

use App\Models\SearchUserChat;

class DriftIndex
{
    /** @return array<string, mixed> */
    public function evaluate(SearchUserChat $chat, bool $alert = true): array
    {
        return [];
    }
}
