<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class LlamaParseService
{
    private string $apiKey;
    private int $timeout = 300; // LlamaParse can take time for large files (5 minutes)
    public const BASE_URL = 'https://api.cloud.llamaindex.ai/api/v1/parsing';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Parse PDF file using LlamaParse API
     * 
     * @param string $filePath Full path to the PDF file
     * @param string $resultType 'markdown' or 'text'
     * @return string Parsed text content
     */
    public function parsePdf(string $filePath, string $resultType = 'text'): string
    {
        try {
            // Step 1: Upload the file
            $response = Http::timeout(60)
                ->connectTimeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post(self::BASE_URL . '/upload', [
                    'max_pages' => 100, // Maximum pages to parse
                    'parse_mode' => 'parse_page_with_agent',
                    'model' => 'openai-gpt-4o-mini',
                    'high_res_ocr' => false, // Set to true for better quality but slower
                    'adaptive_long_table' => true,
                    'outlined_table_extraction' => true,
                    'output_tables_as_HTML' => true,
                    'precise_bounding_box' => false,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Get job ID from response
                if (isset($data['id'])) {
                    $jobId = $data['id'];
                    Log::info('LlamaParse: File uploaded, Job ID: ' . $jobId);
                    
                    // Step 2: Poll for results
                    return $this->pollForResults($jobId, $resultType);
                } else {
                    Log::error('LlamaParse: No job ID in response', ['response' => $data]);
                    throw new \Exception('No job ID returned from LlamaParse API');
                }
            } else {
                $error = $response->json();
                Log::error('LlamaParse API upload error', [
                    'status' => $response->status(),
                    'error' => $error
                ]);
                throw new \Exception('LlamaParse API error: ' . ($error['detail'] ?? $error['message'] ?? $response->body()));
            }
        } catch (\Exception $e) {
            Log::error('LlamaParse parsing failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Poll for parsing results when job is async
     */
    private function pollForResults(string $jobId, string $resultType = 'text', int $maxAttempts = 120): string
    {
        $attempt = 0;
        $resultEndpoint = $resultType === 'markdown' ? 'markdown' : 'text';
        
        while ($attempt < $maxAttempts) {
            sleep(5); // Wait 5 seconds between polls (as per API docs)
            
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                    ])
                    ->get(self::BASE_URL . '/job/' . $jobId . '/result/' . $resultEndpoint);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Check if we have the result
                    if (isset($data[$resultEndpoint])) {
                        Log::info('LlamaParse: Parsing completed successfully');
                        return $data[$resultEndpoint];
                    } elseif (isset($data['text'])) {
                        return $data['text'];
                    } elseif (isset($data['markdown'])) {
                        return $data['markdown'];
                    } else {
                        Log::error('LlamaParse: Unexpected result format', ['response' => $data]);
                        throw new \Exception('Unexpected result format from LlamaParse API');
                    }
                } elseif ($response->status() === 400) {
                    $error = $response->json();
                    
                    // Job not completed yet - continue polling
                    if (isset($error['detail']) && $error['detail'] === 'Job not completed yet') {
                        Log::info('LlamaParse: Job still processing... (attempt ' . ($attempt + 1) . ')');
                        $attempt++;
                        continue;
                    } else {
                        throw new \Exception('LlamaParse job error: ' . json_encode($error));
                    }
                } else {
                    $errorText = $response->body();
                    Log::error('LlamaParse: Error checking job status', [
                        'status' => $response->status(),
                        'error' => $errorText
                    ]);
                    throw new \Exception('Error checking job status: ' . $errorText);
                }
            } catch (\Exception $e) {
                // If it's not a "job not completed" error, throw it
                if (strpos($e->getMessage(), 'Job not completed yet') === false) {
                    throw $e;
                }
            }
            
            $attempt++;
        }
        
        throw new \Exception('LlamaParse job timed out after ' . ($maxAttempts * 5) . ' seconds');
    }
}
