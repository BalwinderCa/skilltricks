<?php

namespace App\Services\Integration;

/**
 * Class IntegrationService.
 */

use App\Services\AI\ClaudeAiService;
use App\Services\AI\DeepseekAiService;
use App\Services\AI\OpenAiService;

class IntegrationService
{
    # set params
    public function setParams($engine, $request, $model)
    {
        $appStatic = appStatic();

        return match ($engine){
            $appStatic::OPEN_AI_ENGINE          => (new OpenAiService())->setParams($request, $model),
            $appStatic::CLAUDE_AI_ENGINE        => (new ClaudeAiService())->setParams($request, $model),
            $appStatic::DEEPSEEK_AI_ENGINE      => (new DeepseekAiService())->setParams($request, $model),
        };
    }

    # content generator
    public function contentGenerator($engine, $request)
    {
        $appStatic = appStatic();
        
        return match ($engine){
            $appStatic::OPEN_AI_ENGINE          => (new OpenAiService())->contentGenerator($request),
            $appStatic::CLAUDE_AI_ENGINE        => (new ClaudeAiService())->contentGenerator($request),
            $appStatic::DEEPSEEK_AI_ENGINE      => (new DeepseekAiService())->contentGenerator($request),
        };
    }
}
