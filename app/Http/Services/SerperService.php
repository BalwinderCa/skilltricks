<?php

namespace App\Http\Services;

class SerperService
{
    private array $headers;
    private array $contentTypes;
    private int $timeout = 0;
    private array $curlInfo = [];
    public const ORIGIN = 'https://google.serper.dev/';
    public const SERPER_BASE_URL = self::ORIGIN;

    public function __construct($api_key)
    {
        $this->contentTypes = [
            "application/json"    => "Content-Type: application/json",
            "multipart/form-data" => "Content-Type: multipart/form-data",
        ];

        $this->headers = [
            $this->contentTypes["application/json"],
            "X-API-KEY: $api_key"
        ];
    }
    // search content
    public function search($opts)
    {
        $url = self::SERPER_BASE_URL.'search';       
        return $this->sendRequest($url, 'POST', $opts);
    }
    // search image
    public function images($opts)
    {
        $url = self::SERPER_BASE_URL.'images';       
        return $this->sendRequest($url, 'POST', $opts);
    }
    // search videos
    public function videos($opts)
    {
        $url = self::SERPER_BASE_URL.'videos';       
        return $this->sendRequest($url, 'POST', $opts);
    }
    // search places
    public function places($opts)
    {
        $url = self::SERPER_BASE_URL.'places';       
        return $this->sendRequest($url, 'POST', $opts);
    }
    // search news
    public function news($opts)
    {
        $url = self::SERPER_BASE_URL.'news';       
        return $this->sendRequest($url, 'POST', $opts);
    } 
    // search shopping
    public function shopping($opts)
    {
        $url = self::SERPER_BASE_URL.'shopping';       
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
            CURLOPT_FOLLOWLOCATION => true,
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
   
        $info           = curl_getinfo($curl);
        $this->curlInfo = $info;

        curl_close($curl);

        // if (!$response) throw new Exception(curl_error($curl));
        
        return $response;
    }


}
