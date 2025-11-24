<?php

namespace App\Traits;

use Illuminate\Support\Str;
use App\Models\TextToSpeech;
use App\Models\GoogleTTSSettings;
use App\Models\TextToSpeechSetting;
use Illuminate\Support\Facades\Log;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Storage;
use App\Http\Services\ElevenLabsService;
use App\Http\Services\OpenAiCustomService;
use App\Models\ElevenLabsModel;
use App\Models\ElevenLabsModelVoice;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;


trait GenerateVoice
{
    #return voice file name, langsAndVoices, path, wordCount
    public function googleVoiceGenerate($voice = 'en-US-News-K', $lang = 'en-US', $pace = 'medium', $break = 1, $speeches = []): array
    {

        $ttsSettings = voiceSettingCredential();
        if (!$ttsSettings) {
            return $data = [];
        }
        $credentials = storage_path($ttsSettings->file_name);
        $client = new TextToSpeechClient([
            'credentials' => $credentials,
            // 'project_id' => 'poised-epigram-392311',
        ]);

        // $speeches = json_decode($speeches, true);

        //Variables and arrays for store
        $wordCount = 0;
        $langsAndVoices = [];
        $speech = [];
        $text = $this->text($speeches);

        $wordCount = strlen($text);
        // Convert the text to SSML format

        $ssml = '<speak>';

        $ssml .= sprintf(
            '<lang xml:lang="%3$s">
                            <prosody rate="%4$s">
                                <voice name="%1$s">%2$s</voice>
                                <break time="%5$ss"/>
                            </prosody>
                        </lang>',
            $voice,
            $text,
            $lang,
            $pace,
            $break,
        );

        $langsAndVoices['language'][] = $lang;
        $langsAndVoices['voices'][] = $voice;


        $ssml .= '</speak>';

        // Set the SSML as the synthesis input
        $synthesisInputSsml = (new SynthesisInput())
            ->setSsml($ssml);

        // Set the SSML as the synthesis input
        $synthesisInputSsml = (new SynthesisInput())
            ->setSsml($ssml);

        // Build the voice request, select the language code ("en-US") and the ssml voice gender

        $voice = (new VoiceSelectionParams())
            ->setLanguageCode('en-US')
            ->setSsmlGender(SsmlVoiceGender::FEMALE);


        // Effects profile
        // $effectsProfileId = 'telephony-class-application';

        // select the type of audio file you want returned
        $audioConfig = (new AudioConfig())
            ->setAudioEncoding(AudioEncoding::MP3);
        //->setEffectsProfileId(array($effectsProfileId));

        // Perform text-to-speech request on the SSML input with selected voice parameters and audio file type
        $response = $client->synthesizeSpeech($synthesisInputSsml, $voice, $audioConfig);
        $audioContent = $response->getAudioContent();

        $user = auth()->user();

        $audioName = $user->id . '-' . Str::random(20) . '.mp3';

        Storage::disk('audio')->put($audioName, $audioContent);

        $file_path = 'voice/audio/' . $audioName;
        if (activeStorage('aws')) {
            Storage::disk('s3')->writeStream($audioName, Storage::disk('audio')->readStream($audioName));
            $file_path = Storage::disk('s3')->url($audioName);
            Storage::disk('audio')->delete($audioName);
        } elseif (activeStorage('gcp')) {
            $path = public_path() . '/' . $file_path;
            $this->storeFileGcp($path);
        }
        $client->close();

        $data = [];
        $data['langsAndVoices'] =  $langsAndVoices;
        $data['audioName'] =  $audioName;
        $data['wordCount'] =  $wordCount;
        $data['file_path'] =  $file_path;
        $data['storage_type'] =  getSetting('active_storage') ?? 'local';
        return $data;
    }
    public function azureVoiceGenerate($voice, $lang = 'en-US', $pace = 'medium', $break = 1, $format = 'mp3', $speeches = []): array
    {
        $data = [];
        $wordCount = 0;
        $langsAndVoices = [];
        $azureKey = voiceSettingCredential()->key;
        $azureRegion = voiceSettingCredential()->region;
        $azureEndpoint = 'https://' . $azureRegion . '.tts.speech.microsoft.com/cognitiveservices/v1';
        // $azureEndpoint = 'https://eastus.api.cognitive.microsoft.com/sts/v1.0/issuetoken';

        # Audio Format

        if ($format == 'mp3') {
            $audio_type = 'audio/mpeg';
        } elseif ($format == 'wav') {
            $audio_type = 'audio/wav';
        } elseif ($format == 'ogg') {
            $audio_type = 'audio/ogg';
        } elseif ($format == 'webm') {
            $audio_type = 'audio/webm';
        }
        # Name and extention of the result audio file
        $name = str_replace(' ', '_', strtolower(auth()->user()->name)) . Str::random(10);

        if ($format === 'mp3') {
            $temp_file_name = $name . '.mp3';
        } elseif ($format === 'ogg') {
            $temp_file_name = $name . '.ogg';
        } elseif ($format === 'webm') {
            $temp_file_name = $name . '.webm';
        } elseif ($format === 'wav') {
            $temp_file_name = $name . '.wav';
        } else {
            $temp_file_name = $name . '.mp3';
        }
        $format = 'mp3';
        $file_name = $temp_file_name;

        if ($format == 'mp3') {
            $output_format = 'audio-24khz-48kbitrate-mono-mp3';
        } elseif ($format == 'ogg') {
            $output_format = 'ogg-24khz-16bit-mono-opus';
        } elseif ($format == 'webm') {
            $output_format = 'webm-24khz-16bit-mono-opus';
        }

        $text = $this->text($speeches);
        $wordCount = strlen($text);

        $text = preg_replace("/\&/", "&amp;", $text);
        $text = preg_replace("/(^|(?<=\s))<((?=\s)|$)/i", "&lt;", $text);
        $text = preg_replace("/(^|(?<=\s))>((?=\s)|$)/i", "&gt;", $text);


        $ssml_text = '<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xmlns:mstts="http://www.w3.org/2001/mstts" xmlns:emo="http://www.w3.org/2009/10/emotionml" xml:lang="' . $lang . '"><voice name="' . $voice . '">' . $text . '</voice></speak>';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $azureEndpoint);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Ocp-Apim-Subscription-Key: ' . $azureKey,
            'Content-Type: application/ssml+xml',
            'X-Microsoft-OutputFormat:' . $output_format,
            'User-Agent: Berkine',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ssml_text);

        $audio_stream = curl_exec($ch);

        if (curl_errno($ch)) {
            return response()->json(["error" => "Azure Synthesize Error. Please notify support team."], 422);
            Log::error(curl_error($ch) . ' ' . $audio_stream);
        }

        curl_close($ch);

        Storage::disk('audio')->put($file_name, $audio_stream);
        $file_path = Storage::url($file_name);
        if (activeStorage('aws')) {
            Storage::disk('s3')->writeStream($file_name, Storage::disk('audio')->readStream($file_name));
            $file_path = Storage::disk('s3')->url($file_name);
            Storage::disk('audio')->delete($file_name);
        }
        $data['file_path'] = $file_path;
        $data['audioName'] = $file_name;
        $data['langsAndVoices'] =  $langsAndVoices;
        $data['wordCount'] =  $wordCount;
        $data['storage_type'] =  activeStorage('aws') ? 'aws' : 'local';
        return $data;
    }
    public function openAiTTS($model = 'tts-1', $voice = "alloy", $speed = 1, $response_format = 'mp3', $content, $lang = 'en-US')
    {
        $opts = [
            'model' => $model,
            'voice' => $voice,
            'speed' => $speed,          
            'response_format' => $response_format,
            'input' => $content,
        ];
        $openAiCustomService = new OpenAiCustomService(openAiKey());

        $tts = $openAiCustomService->tts($opts);
      
        $format = $response_format;
        if ($format == 'mp3') {
            $audio_type = 'audio/mpeg';
        } elseif ($format == 'wav') {
            $audio_type = 'audio/wav';
        } elseif ($format == 'ogg') {
            $audio_type = 'audio/ogg';
        } elseif ($format == 'webm') {
            $audio_type = 'audio/webm';
        }
        # Name and extention of the result audio file
        $name = str_replace(' ', '_', strtolower(auth()->user()->name)) . Str::random(10);

        if ($format === 'mp3') {
            $temp_file_name = $name . '.mp3';
        } elseif ($format === 'ogg') {
            $temp_file_name = $name . '.ogg';
        } elseif ($format === 'webm') {
            $temp_file_name = $name . '.webm';
        } elseif ($format === 'wav') {
            $temp_file_name = $name . '.wav';
        } else {
            $temp_file_name = $name . '.mp3';
        }
        $format = 'mp3';
        $file_name = $temp_file_name;

        if ($format == 'mp3') {
            $output_format = 'audio-24khz-48kbitrate-mono-mp3';
        } elseif ($format == 'ogg') {
            $output_format = 'ogg-24khz-16bit-mono-opus';
        } elseif ($format == 'webm') {
            $output_format = 'webm-24khz-16bit-mono-opus';
        }
        Storage::disk('audio')->put($file_name, $tts);
        $file_path = Storage::url($file_name);
        if (activeStorage('aws')) {
            Storage::disk('s3')->writeStream($file_name, Storage::disk('audio')->readStream($file_name));
            $file_path = Storage::disk('s3')->url($file_name);
            Storage::disk('audio')->delete($file_name);
        }
        $data['langsAndVoices'] = '';
        $data['file_path'] = $file_path;
        $data['audioName'] = $file_name;
        $data['wordCount'] =  strlen($content);;
        $data['storage_type'] = activeStorage('aws') ? 'aws' : 'local';
        return $data;
    }
    public function text($speeches)
    {
        $ttsSettings = voiceSettingCredential();

        $text = '';
        $maximum_character = $ttsSettings ? $ttsSettings->maximum_character : null;
        $wordCount         = 0;

        foreach ($speeches as $key => $speech) {
            $value = $speech['content'];
            $text .= preg_replace('/<[\s\S]+?>/', '', $value) . '. ';
        }

        if ($ttsSettings) {

            $maximum_character = $ttsSettings->maximum_character && auth()->user()->user_type == 'customer' ? $ttsSettings->maximum_character : false;
        }
        if ($maximum_character) {
            if (strlen($text) > $maximum_character) {
                $text = substr($text, 0, $maximum_character);
            }
        }

        return $text;
    }
    // upload file into google cloud
    public function storeFileGcp($filepath)
    {
        $storage = new StorageClient([
            'keyFilePath' => base_path() . '/gcp.json',
        ]);
        $bucketName = env('GOOGLE_CLOUD_BUCKET');
        $bucket = $storage->bucket($bucketName);

        $object = $bucket->upload(
            fopen($filepath, 'r'),
            [
                'predefinedAcl' => 'publicRead'
            ]
        );
    }
    public function elevenLabs(): array
    {
        $data = [];
        $ttsSettings = voiceSettingCredential('eleven_labs');
        if (!$ttsSettings) {
            return $data;
        }
        $elevenLabService            = new ElevenLabsService($ttsSettings->key);
        $data['user_info']           = json_decode($elevenLabService->userInfo());
        $data['ttsModels']           = ElevenLabsModel::where('is_active', 1)->where('can_do_text_to_speech', 1)->get();
        $data['stsModels']           = ElevenLabsModel::where('is_active', 1)->where('can_do_voice_conversion', 1)->get();
        $data['defaultVoiceSetting'] = json_decode($elevenLabService->defaultVoiceSetting());
        $data['languages_voices']    = ElevenLabsModelVoice::where('is_active', 1)->get();
       
        return $data;
    }


    # eleven labs voice list
    public function elevenLabsVoiceList($model_id)
    {
        $ttsSettings = voiceSettingCredential('eleven_labs');
        $elevenLabService = new ElevenLabsService($ttsSettings->key);
        $voices = json_decode($elevenLabService->voices($model_id));
        return $voices;
    }

    # generate elvenlabs text to speech    
    public function generateTextToSpeech($request)
    {
        $voice_id = $request->voice;
        $opts     = [
            'model_id' => $request->model,
            'text'  => $request->content,
            'voice_settings' => [
                'stability' => $request->stability /100,
                'similarity_boost' => $request->similarity_boost /100,
                'style' => $request->style/100,
                'use_speaker_boost' => $request->use_speaker_boost == 'on' ? true : false,
            ]
        ];
         
        $ttsSettings = voiceSettingCredential('eleven_labs');
        $elevenLabService = new ElevenLabsService($ttsSettings->key);
        $tts = $elevenLabService->tts($opts, $voice_id);
        
        $wordCount = strlen($request->content);
        $format = 'mp3';
        if ($format == 'mp3') {
            $audio_type = 'audio/mpeg';
        } elseif ($format == 'wav') {
            $audio_type = 'audio/wav';
        } elseif ($format == 'ogg') {
            $audio_type = 'audio/ogg';
        } elseif ($format == 'webm') {
            $audio_type = 'audio/webm';
        }
        # Name and extention of the result audio file
        $name = str_replace(' ', '_', strtolower(auth()->user()->name)) . Str::random(10);

        if ($format === 'mp3') {
            $temp_file_name = $name . '.mp3';
        } elseif ($format === 'ogg') {
            $temp_file_name = $name . '.ogg';
        } elseif ($format === 'webm') {
            $temp_file_name = $name . '.webm';
        } elseif ($format === 'wav') {
            $temp_file_name = $name . '.wav';
        } else {
            $temp_file_name = $name . '.mp3';
        }
        $format = 'mp3';
        $file_name = $temp_file_name;

        if ($format == 'mp3') {
            $output_format = 'audio-24khz-48kbitrate-mono-mp3';
        } elseif ($format == 'ogg') {
            $output_format = 'ogg-24khz-16bit-mono-opus';
        } elseif ($format == 'webm') {
            $output_format = 'webm-24khz-16bit-mono-opus';
        }
        Storage::disk('audio')->put($file_name, $tts);
        $file_path = Storage::url($file_name);
        if (activeStorage('aws')) {
            Storage::disk('s3')->writeStream($file_name, Storage::disk('audio')->readStream($file_name));
            $file_path = Storage::disk('s3')->url($file_name);
            Storage::disk('audio')->delete($file_name);
        }
        $data['file_path'] = $file_path;
        $data['audioName'] = $file_name;
        $data['wordCount'] = $wordCount;
        $data['message']   = $wordCount;
        $data['storage_type'] =  activeStorage('aws') ? 'aws' : 'local';
        return $data;
    }
    public function elevenLabsText($text)
    {
        $ttsSettings = voiceSettingCredential('eleven_labs');      
        $maximum_character = $ttsSettings ? $ttsSettings->maximum_character : null;
        $wordCount = strlen($text);

        $text = preg_replace('/<[\s\S]+?>/', '', $text);
   
        if ($ttsSettings) {
            $maximum_character = $ttsSettings->maximum_character && auth()->user()->user_type == 'customer' ? $ttsSettings->maximum_character : false;
        }
        if ($maximum_character) {
            if ($wordCount > $maximum_character && $wordCount > 2500) {
                return true;
            }
        }elseif($wordCount > 2500) {
            return true;
        }

        return false;

    }
    public function openAiTextCount($text)
    {
        $ttsSettings = voiceSettingCredential('open_ai_tts');      
        $maximum_character = $ttsSettings ? $ttsSettings->maximum_character : null;
        $wordCount = strlen($text);

        $text = preg_replace('/<[\s\S]+?>/', '', $text);
   
        if ($ttsSettings) {
            $maximum_character = $ttsSettings->maximum_character && auth()->user()->user_type == 'customer' ? $ttsSettings->maximum_character : false;
        }
        if ($maximum_character) {
            if ($wordCount > $maximum_character && $wordCount > 4096) {
                return true;
            }
        }elseif($wordCount > 4096) {
            return true;
        }

        return false;

    }
}
