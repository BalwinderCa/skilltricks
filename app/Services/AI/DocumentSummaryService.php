<?php

namespace App\Services\AI;

use Google\Auth\ApplicationDefaultCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates a compact, GoalSync-oriented summary of an uploaded document.
 *
 * The summary is produced ONCE at upload/parse time and stored on the document.
 * The chat then injects this summary instead of the full parsed_text, so the
 * per-request token cost stays bounded regardless of document count or size.
 *
 * Provider routing mirrors AiChatController::aiGenerate() (vertex | openai | gemini)
 * so summaries are built with whatever provider the app is configured to use.
 */
class DocumentSummaryService
{
    /** Roughly how long the stored summary should be (characters, ~1.5k tokens). */
    private const MAX_SUMMARY_CHARS = 6000;

    /** Cap on how much source text we send to the summarizer per document. */
    private const MAX_SOURCE_CHARS = 45000;

    /**
     * Build a summary for one document. Returns the summary text, or null on failure.
     */
    public function summarize(string $parsedText, string $documentName, string $fileType): ?string
    {
        $parsedText = trim($parsedText);
        if ($parsedText === '') {
            return null;
        }

        $source = mb_substr($parsedText, 0, self::MAX_SOURCE_CHARS);

        $system = 'You compress company documents into a dense factual brief for an '
            .'executive strategy assistant. Preserve concrete facts; never invent.';

        $prompt = <<<EOT
Summarize the company document below into a compact brief (max ~400 words).
Keep it factual and specific to this document. Use these sections, omitting any that don't apply:

Document: {$documentName} ({$fileType})

OVERVIEW: 1-2 sentences on what this document is and the company/context.
ROLES & TITLES: list the EXACT role/job titles mentioned, verbatim, comma-separated (do not invent or rephrase titles).
GOALS & PRIORITIES: bullet the stated goals, objectives, KPIs or priorities.
STRATEGY & OPERATIONS: bullet key strategies, initiatives, processes or structure.
CONSTRAINTS & RISKS: bullet budgets, deadlines, dependencies, risks or limitations.
KEY FACTS: any other numbers, names, dates or facts a strategist would need.

Be terse. Bullets, not paragraphs. Output plain text only.

--- DOCUMENT TEXT ---
{$source}
--- END DOCUMENT TEXT ---
EOT;

        try {
            $text = $this->generate($system, $prompt);
        } catch (\Throwable $e) {
            Log::warning('Document summarization failed', [
                'document' => $documentName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, self::MAX_SUMMARY_CHARS);
    }

    /** Route to the configured provider and return the response text (or '' ). */
    private function generate(string $system, string $prompt): string
    {
        $provider = strtolower((string) config('custom.ai_provider'));

        if ($provider === 'openai' || $provider === 'chatgpt') {
            return $this->openAi($system, $prompt);
        }

        if (in_array($provider, ['vertex', 'vertexai', 'vertex-ai'], true)) {
            return $this->vertex($system, $prompt);
        }

        return $this->gemini($system, $prompt);
    }

    private function openAi(string $system, string $prompt): string
    {
        $response = Http::withToken(config('custom.openai_api_key'))
            ->timeout(90)->connectTimeout(10)->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('custom.openai_model'),
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'temperature' => 0.2,
                'max_tokens' => min(2000, (int) config('custom.openai_max_tokens')),
            ]);

        return $response->successful() ? (string) $response->json('choices.0.message.content', '') : '';
    }

    private function vertex(string $system, string $prompt): string
    {
        $project = $this->vertexProjectId();
        $location = config('custom.vertex_location');
        $models = array_filter(array_map('trim', explode(',', (string) config('custom.vertex_models'))));
        $host = $location === 'global' ? 'aiplatform.googleapis.com' : "{$location}-aiplatform.googleapis.com";

        $payload = [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 2000,
                'thinkingConfig' => ['thinkingBudget' => 0],
            ],
        ];

        $token = $this->vertexAccessToken();

        foreach ($models as $model) {
            $url = "https://{$host}/v1/projects/{$project}/locations/{$location}/publishers/google/models/{$model}:generateContent";
            $response = Http::withToken($token)->timeout(90)->connectTimeout(10)->retry(2, 1000)->post($url, $payload);

            if ($response->successful()) {
                $text = $this->extractGeminiText($response->json());
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    private function gemini(string $system, string $prompt): string
    {
        $model = config('custom.gemini_model', 'gemini-2.5-flash');
        $apiKey = config('custom.gemini_api_key');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(90)->connectTimeout(10)->retry(2, 1000)->post($url, [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 2000],
        ]);

        return $response->successful() ? $this->extractGeminiText($response->json()) : '';
    }

    /**
     * Vertex AI project id — prefers the project owning the active
     * service-account key so a stray GOOGLE_CLOUD_PROJECT can't override it.
     */
    private function vertexProjectId(): ?string
    {
        $path = getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: '';

        if ($path !== '' && is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);

            if (! empty($json['project_id'])) {
                return $json['project_id'];
            }
        }

        return config('custom.google_cloud_project');
    }

    private function vertexAccessToken(): ?string
    {
        return Cache::remember('vertex_access_token', 3300, function () {
            $creds = ApplicationDefaultCredentials::getCredentials('https://www.googleapis.com/auth/cloud-platform');
            $token = $creds->fetchAuthToken();

            return $token['access_token'] ?? null;
        });
    }

    /** Pull visible text from a Gemini/Vertex generateContent response, skipping thought-only parts. */
    private function extractGeminiText($json): string
    {
        $parts = $json['candidates'][0]['content']['parts'] ?? [];
        $out = '';
        foreach ($parts as $part) {
            if (! empty($part['thought'])) {
                continue;
            }
            $out .= $part['text'] ?? '';
        }

        return trim($out);
    }
}
