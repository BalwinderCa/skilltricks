<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Log;

class PlagiarismService
{
    private array $headers;
    private array $contentTypes;
    private int $timeout = 30;
    private array $curlInfo = [];
    public const ORIGIN = 'https://api.gowinston.ai/functions';
    public const API_VERSION = 'v1';
    public const BASE_URL =  self::ORIGIN . "/" . self::API_VERSION;

    public function __construct($token)
    {
        $this->contentTypes = [
            "application/json"    => "Content-Type: application/json",
            "multipart/form-data" => "Content-Type: multipart/form-data",
        ];

        $this->headers = [
            $this->contentTypes["application/json"],
            "Authorization: Bearer $token",
        ];
    }

    // textDetector
    public function textDetector($opts)
    {
        $url = self::BASE_URL."/predict";  
        return $this->sendRequest($url, 'POST', $opts);
    }

    // textDetector
    public function plagiarismCheck($opts)
    {
        $url = self::BASE_URL."/plagiarism";       
        return $this->sendRequest($url, 'POST', $opts);
    }

    private function sendRequest(string $url, string $method, array $opts = [])
    {
        $post_fields = json_encode($opts);
        if (array_key_exists('file', $opts) || array_key_exists('image', $opts)) {
            $this->headers[0] = $this->contentTypes["multipart/form-data"];
            $post_fields      = $opts;
        } else {
            $this->headers[0] = $this->contentTypes["application/json"];
        }
        $curl_info = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $post_fields,
            CURLOPT_HTTPHEADER     => $this->headers,
        ];
     
        if ($opts == []) {
            unset($curl_info[CURLOPT_POSTFIELDS]);
        }

        $curl = curl_init();

        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);
        // dd($response);
        $info           = curl_getinfo($curl);
        $this->curlInfo = $info;

        curl_close($curl);

        //  if (!$response) throw Log::info((curl_error($curl)));
        
        return $response;
    }


}
