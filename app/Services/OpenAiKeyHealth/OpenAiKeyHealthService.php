<?php

namespace App\Services\OpenAiKeyHealth;

use App\Models\OpenAiKey;
use App\Models\Setting;
use GuzzleHttp\Client;


class OpenAiKeyHealthService
{

    public function echoOpenAiApiKeyHealth()
    {
        $openAiKeys = $this->getAllKeys(1);

        if($openAiKeys->count() > 0){
            foreach ($openAiKeys as $openAiKey){
                $apiKey = $openAiKey->api_key;

                try {
                    $client = $this->initClient($openAiKey->api_key);
                    $this->callChatCompletion($client);
                    echo ' <p class="btn btn-success btn-block w-100 text-center text-white p-2"> Connected #'.$apiKey.'</p>';
                } catch (\Exception $e) {
                    commonLog("Open Ai Key Health", errorArray($e));
                    flash("You exceeded your current quota, please check your plan and billing details. # Your key is : {$apiKey}")->error();
                    return redirect()->route("admin.multiOpenAi.index");
                }
            }
        }else{
            flash("No Open AI Key Found")->error();
            return redirect()->route("admin.multiOpenAi.index");
        }
    }

    public function initClient($apiKey)
    {
        return new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function callChatCompletion($client, $configurations = [])
    {
        $configurations = empty($configurations) ? $this->defaultChatCompletionConfig() : $configurations;

        return $client->post('chat/completions', $configurations);
    }

    public function defaultChatCompletionConfig()
    {
        return [
            [
                'json' => [
                    'model' => "gpt-3.5-turbo",
                    'messages' => [
                        [
                            "role" => "user",
                            "content" => "Api Key Health Check"
                        ]
                    ],
                    'temperature' => 0.7,
                ],
            ],
        ];
    }

    public function getAllKeys(int $getOpenAiAPiKeys = null, $isActive = null)
    {
        $appStatic = appStatic();

        $query = OpenAiKey::query();

        !empty($getOpenAiAPiKeys) ? $query->where("engine", $appStatic::ENGINE_OPEN_AI) : false;

        return $query->get();
    }
}
