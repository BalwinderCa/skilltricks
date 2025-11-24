<?php
namespace App\Http\Services;

class ElevenLabsService {
     
    private array $headers;
    private array $contentTypes;
    private string $customUrl = "";
    private int $timeout = 0;
    private array $curlInfo = [];
    private string $proxy = "";
    private object $stream_method;
    public const ORIGIN = 'https://api.elevenlabs.io';
    public const API_VERSION = 'v1';
    public const ELEVEN_LABS_URL = self::ORIGIN . "/" . self::API_VERSION;

    public function __construct($api_key)
    {
        $this->contentTypes = [
            "application/json"    => "Content-Type: application/json",
            "multipart/form-data" => "Content-Type: multipart/form-data",
        ];

        $this->headers = [
            $this->contentTypes["application/json"],
            "xi-api-key: $api_key"
        ];
    }

    # models
    public function models()
    {
        $url = self::ELEVEN_LABS_URL."/models";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }

    # voices
    public function voices()
    {
        $url = self::ELEVEN_LABS_URL."/voices";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }

    # default voice setting
    public function defaultVoiceSetting()
    {
        $url = self::ELEVEN_LABS_URL."/voices/settings/default";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }

    # Returns metadata about a specific voice.
    public function getVoice($voice_id)
    {
        $url = self::ELEVEN_LABS_URL."/voices/{$voice_id}";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }

    # Add voice.
    public function addVoice($opts)
    {
        $url = self::ELEVEN_LABS_URL."/voices/add";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    # Add voice.
    public function editVoice($voice_id)
    {
        $url = self::ELEVEN_LABS_URL."/voices/{$voice_id}/edit";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }

    # delete voice.
    public function deleteVoice($voice_id)
    {
        $url = self::ELEVEN_LABS_URL."/voices/{$voice_id}";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'DELETE');
    }
    # default voice setting
    public function voiceSetting($voice_id)
    {
        $url = self::ELEVEN_LABS_URL."/voices/{$voice_id}/settings";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    
    # delete voice.
    public function editVoiceSettings($voice_id, $opts)
    {
        $url = self::ELEVEN_LABS_URL."/voices/{$voice_id}/settings/edit";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }




    # user info
    public function userInfo()
    {
        $url = self::ELEVEN_LABS_URL."/user";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    # text to speech
    public function tts($opts, $voice_id)
    {
        $url = self::ELEVEN_LABS_URL."/text-to-speech/{$voice_id}";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }

    # text to speech stream
    public function ttsStream($opts, $voice_id)
    {
        $url = self::ELEVEN_LABS_URL."/text-to-speech/{$voice_id}/stream";       
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }

        /**
     * @param  string  $url
     * @param  string  $method
     * @param  array   $opts
     * @return bool|string
     */
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

        if (!empty($this->proxy)) {
            $curl_info[CURLOPT_PROXY] = $this->proxy;
        }

        if (array_key_exists('stream', $opts) && $opts['stream']) {
            $curl_info[CURLOPT_WRITEFUNCTION] = $this->stream_method;
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

    /**
     * @param  string  $url
     */
    private function baseUrl(string &$url)
    {
        if ($this->customUrl != "") {
            $url = str_replace(self::ORIGIN, $this->customUrl, $url);
        }
    }
}