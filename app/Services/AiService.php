<?php

namespace App\Services;

use App\Http\Controllers\Backend\AI\ParsePromptsController;
use App\Models\Project;
use App\Models\Template;

class AiService
{
    public function filterBadWords(array $payloads)
    {
        $parsePromptController = new ParsePromptsController;
        $foundBadWords = $parsePromptController->filterBadWords($payloads);

        if (!empty($foundBadWords)) {
            $prompt = "bad_words_found_#themeTags" . $foundBadWords;
            if (preg_match("/bad_words_found/i", $prompt) == 1) {
                $badWords =  explode('_#themeTags', rtrim($prompt, ","));
                $data = [
                    'status'  => 400,
                    'success' => false,
                    'message' => localize('Please remove these words from your inputs') . '-' . $badWords[1],
                ];
                return $data;
            }
        }

        return null;
    }


    public function promtMaker($topic, $lang, $numberOfArticle)
    {

        return  "Generate $numberOfArticle seo friendly keywords in $lang language based on this topic: $topic, each keywords must be an array element, give the output as an array.";
    }

    /**
     * $temperature == 1 means High
     * $numberOfResults total number of result
     * */
    public function setAiParams($model, int $temperature = 1, int $numberOfResults = 1, $prompt)
    {
        return [
            'model'       => $model,
            'temperature' => $temperature,
            'n'           => $numberOfResults,
            'messages' => [
                [
                    "role"    => "user",
                    "content" => $prompt
                ]
            ]
        ];
    }


    public function getTemplateByCode($templateCode, $isFirst = false)
    {
        $query = Template::query()->code($templateCode);

        return $isFirst ? $query->first() : $query->firstOrFail();
    }

}
