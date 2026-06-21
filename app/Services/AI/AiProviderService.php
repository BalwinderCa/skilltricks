<?php

namespace App\Services\AI;

use App\Models\SearchUserChat;
use Google\Auth\ApplicationDefaultCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Unified AI provider service.
 *
 * Dispatches to the configured provider (Gemini, Vertex AI, or OpenAI) and
 * normalises every response to the same Gemini generateContent JSON shape so
 * callers stay provider-agnostic.
 *
 * Provider is selected via AI_PROVIDER in .env:
 *   "gemini"  → AI Studio Gemini API key
 *   "vertex"  → Vertex AI (OAuth / service account)
 *   "openai"  → OpenAI Chat Completions
 */
class AiProviderService
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Send a system + user message to the configured provider.
     * Returns a normalised HTTP response (Gemini generateContent shape).
     */
    public function generate(
        string $systemMessage,
        string $userText,
        int    $maxOutputTokens = 3000,
        float  $temperature     = 0.7,
        bool   $jsonMode        = false
    ) {
        $provider = $this->providerKey();

        if (in_array($provider, ['openai', 'chatgpt'])) {
            return $this->openAiGenerate($systemMessage, $userText, $maxOutputTokens, $temperature, $jsonMode);
        }

        if (in_array($provider, ['vertex', 'vertexai', 'vertex-ai'])) {
            return $this->vertexGenerate($systemMessage, $userText, $maxOutputTokens, $temperature, $jsonMode);
        }

        return $this->geminiGenerate($systemMessage, $userText, $maxOutputTokens, $temperature, $jsonMode);
    }

    /**
     * Pull visible answer text from a generateContent response.
     * Skips thought-only parts; falls back to thought text if nothing else.
     */
    public function extractText($response): string
    {
        $parts = $response->json('candidates.0.content.parts', []);

        if (!is_array($parts) || empty($parts)) {
            return (string) $response->json('candidates.0.content.parts.0.text', '');
        }

        $visible = [];
        $thought = [];

        foreach ($parts as $part) {
            if (empty($part['text'])) {
                continue;
            }

            if (!empty($part['thought'])) {
                $thought[] = $part['text'];
            } else {
                $visible[] = $part['text'];
            }
        }

        return !empty($visible) ? implode("\n", $visible) : implode("\n", $thought);
    }

    /**
     * Pull token usage from a response.
     * Works for both Gemini/Vertex (usageMetadata) and OpenAI (mapped onto
     * the same shape in openAiGenerate).
     */
    public function extractUsage($response): array
    {
        $meta = $response->json('usageMetadata', []);

        $prompt     = (int) ($meta['promptTokenCount'] ?? 0);
        $completion = (int) ($meta['candidatesTokenCount'] ?? 0);
        $total      = (int) ($meta['totalTokenCount'] ?? ($prompt + $completion));

        return [
            'prompt_tokens'     => $prompt,
            'completion_tokens' => $completion,
            'total_tokens'      => $total,
        ];
    }

    /**
     * Increment the chat's lifetime token total and return the new cumulative value.
     */
    public function recordChatTokens($chatId, $response): int
    {
        $total = (int) ($this->extractUsage($response)['total_tokens'] ?? 0);

        if (!$chatId) {
            return 0;
        }

        try {
            $chat = SearchUserChat::find($chatId);

            if (!$chat) {
                return 0;
            }

            $chat->incrementTokens($total);

            return (int) $chat->fresh()->total_tokens;
        } catch (\Exception $e) {
            Log::warning('Failed to record chat tokens', ['chat_id' => $chatId, 'error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Build a JsonResponse for any AI API exception.
     * Call inside a catch block and return the result immediately.
     */
    public function handleException(\Throwable $e, string $context, $userId = null, $chatId = null): \Illuminate\Http\JsonResponse
    {
        $provider = $this->providerLabel();

        if ($e instanceof ConnectionException) {
            Log::error("{$provider} Connection Exception - {$context}", [
                'user_id' => $userId,
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);

            return response()->json([
                'error'   => "{$provider} connection timeout. The request took too long to complete.",
                'details' => [
                    'message'    => $e->getMessage(),
                    'type'       => 'ConnectionException',
                    'suggestion' => "Please try again. If the issue persists, {$provider} may be experiencing high load.",
                ],
            ], 504);
        }

        if ($e instanceof RequestException) {
            Log::error("{$provider} Request Exception - {$context}", [
                'user_id'         => $userId,
                'chat_id'         => $chatId,
                'error'           => $e->getMessage(),
                'response_status' => $e->response?->status(),
                'response_body'   => $e->response?->body(),
            ]);

            return response()->json([
                'error'   => "{$provider} API request failed.",
                'details' => [
                    'message'  => $e->getMessage(),
                    'type'     => 'RequestException',
                    'response' => $e->response?->body(),
                ],
            ], 500);
        }

        Log::error("{$provider} General Exception - {$context}", [
            'user_id' => $userId,
            'chat_id' => $chatId,
            'error'   => $e->getMessage(),
            'class'   => get_class($e),
        ]);

        return response()->json([
            'error'   => 'An unexpected error occurred while processing your request.',
            'details' => ['message' => $e->getMessage(), 'type' => get_class($e)],
        ], 500);
    }

    /** Human-readable provider name for logs and error messages. */
    public function providerLabel(): string
    {
        return match ($this->providerKey()) {
            'openai', 'chatgpt'               => 'OpenAI',
            'vertex', 'vertexai', 'vertex-ai' => 'Vertex AI',
            default                           => 'Gemini',
        };
    }

    /**
     * Tolerant JSON object extractor. Strips ``` fences and any prose the
     * model may add around the object, then decodes. Returns an associative
     * array or null. Shared by all JSON-mode generation callers.
     */
    public function parseJson($text): ?array
    {
        if (!$text) {
            return null;
        }

        $clean = trim(preg_replace(['/^```(?:json)?/i', '/```$/'], '', trim($text)));

        $start = strpos($clean, '{');
        $end   = strrpos($clean, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $clean = substr($clean, $start, $end - $start + 1);
        }

        $data = json_decode($clean, true);

        return is_array($data) ? $data : null;
    }

    // -------------------------------------------------------------------------
    // Private: provider implementations
    // -------------------------------------------------------------------------

    private function providerKey(): string
    {
        return strtolower(config('custom.ai_provider', 'gemini'));
    }

    /**
     * AI Studio Gemini API.
     * Primary: gemini-3.1-pro-preview, fallback: gemini-3.5-flash-lite.
     */
    private function geminiGenerate(string $systemMessage, string $userText, int $maxOutputTokens, float $temperature, bool $jsonMode)
    {
        $models  = ['gemini-3.1-pro-preview', 'gemini-3.5-flash-lite'];
        $payload = $this->buildGeminiPayload($systemMessage, $userText, $maxOutputTokens, $temperature, $jsonMode);

        $response = null;

        foreach ($models as $model) {
            $response = Http::withHeaders(['x-goog-api-key' => config('custom.gemini_api_key')])
                ->timeout(90)
                ->connectTimeout(10)
                ->retry(2, 1000)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload);

            if ($response->successful()) {
                return $response;
            }

            Log::warning('Gemini model failed, trying fallback', ['model' => $model, 'status' => $response->status()]);
        }

        return $response;
    }

    /**
     * Vertex AI Gemini (OAuth / project-based auth).
     * Models read from VERTEX_MODELS (comma-separated).
     */
    private function vertexGenerate(string $systemMessage, string $userText, int $maxOutputTokens, float $temperature, bool $jsonMode)
    {
        $project  = config('custom.google_cloud_project');
        $location = config('custom.vertex_location');
        $models   = array_filter(array_map('trim', explode(',', config('custom.vertex_models'))));
        $host     = $location === 'global' ? 'aiplatform.googleapis.com' : "{$location}-aiplatform.googleapis.com";
        $payload  = $this->buildGeminiPayload($systemMessage, $userText, $maxOutputTokens, $temperature, $jsonMode);
        $token    = $this->vertexAccessToken();

        if (empty($token)) {
            Log::error('Vertex AI auth failed: no access token (check GOOGLE_APPLICATION_CREDENTIALS / ADC)');
        }

        $response = null;

        foreach ($models as $model) {
            $url = "https://{$host}/v1/projects/{$project}/locations/{$location}/publishers/google/models/{$model}:generateContent";

            $response = Http::withToken($token)
                ->timeout(90)
                ->connectTimeout(10)
                ->retry(2, 1000)
                ->post($url, $payload);

            if ($response->successful() && $this->extractText($response) !== '') {
                return $response;
            }

            Log::warning('Vertex AI model failed or returned empty text, trying fallback', [
                'model'         => $model,
                'status'        => $response->status(),
                'finish_reason' => $response->json('candidates.0.finishReason'),
            ]);
        }

        return $response;
    }

    /**
     * OpenAI Chat Completions.
     * Returns a response normalised to Gemini's generateContent shape so all
     * callers (extractText, extractUsage) stay provider-agnostic.
     */
    private function openAiGenerate(string $systemMessage, string $userText, int $maxOutputTokens, float $temperature, bool $jsonMode)
    {
        $model = config('custom.openai_model');

        // gpt-4-turbo caps completion at 4096 tokens; clamp larger budgets.
        $maxOutputTokens = min($maxOutputTokens, (int) config('custom.openai_max_tokens'));

        $payload = [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user',   'content' => $userText],
            ],
            'temperature' => $temperature,
            'max_tokens'  => $maxOutputTokens,
        ];

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::withToken(config('custom.openai_api_key'))
            ->timeout(90)
            ->connectTimeout(10)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if ($response->successful()) {
            $text  = $response->json('choices.0.message.content', '');
            $usage = $response->json('usage', []);

            return $this->fakeGeminiResponse([
                'candidates'    => [['content' => ['parts' => [['text' => $text]]]]],
                'usageMetadata' => [
                    'promptTokenCount'     => (int) ($usage['prompt_tokens'] ?? 0),
                    'candidatesTokenCount' => (int) ($usage['completion_tokens'] ?? 0),
                    'totalTokenCount'      => (int) ($usage['total_tokens'] ?? 0),
                ],
            ]);
        }

        Log::warning('OpenAI model failed', ['model' => $model, 'status' => $response->status(), 'body' => $response->body()]);

        return $response;
    }

    // -------------------------------------------------------------------------
    // Private: shared builder helpers
    // -------------------------------------------------------------------------

    private function buildGeminiPayload(string $systemMessage, string $userText, int $maxOutputTokens, float $temperature, bool $jsonMode): array
    {
        $generationConfig = [
            'temperature'     => $temperature,
            'maxOutputTokens' => $maxOutputTokens,
            // thinkingBudget=0 prevents thinking tokens from truncating visible JSON.
            'thinkingConfig'  => ['thinkingBudget' => 0],
        ];

        if ($jsonMode) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        return [
            'systemInstruction' => ['parts' => [['text' => $systemMessage]]],
            'contents'          => [['role' => 'user', 'parts' => [['text' => $userText]]]],
            'generationConfig'  => $generationConfig,
        ];
    }

    /** OAuth token for Vertex AI, cached just under the 1h token lifetime. */
    private function vertexAccessToken(): ?string
    {
        return Cache::remember('vertex_access_token', 3300, function () {
            $creds = ApplicationDefaultCredentials::getCredentials('https://www.googleapis.com/auth/cloud-platform');
            $token = $creds->fetchAuthToken();

            return $token['access_token'] ?? null;
        });
    }

    /** Wrap a plain array as a 200 HTTP response so callers can call ->json() on it. */
    private function fakeGeminiResponse(array $data)
    {
        return new \Illuminate\Http\Client\Response(
            new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], json_encode($data))
        );
    }
}
