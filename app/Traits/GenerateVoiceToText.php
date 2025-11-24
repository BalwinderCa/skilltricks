<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Orhanerday\OpenAi\OpenAi;
use Illuminate\Support\Facades\Log;

trait GenerateVoiceToText
{
    public function openAiVoiceToText($request)
    {
        $user = auth()->user();
        $file = $request->file('file');
        $path = 'public/voice/audio/';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $file_name = Str::random(4) . '-' . Str::slug($user->name) . '-audio.' . $file->getClientOriginalExtension();

        
        //Audio Extension Control
        $audioType = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];
        if (!in_array(Str::lower($file->getClientOriginalExtension()), $audioType)) {
            $data = array(
                'errors' => ['Invalid extension, accepted extensions are mp3, mp4, mpeg, mpga, m4a, wav, and webm.'],
            );
          
            return 419;
        }
      
        try {

            $file->move($path, $file_name);
            $audioPath = $path . $file_name;
            $file = curl_file_create($audioPath);
            $open_ai = new OpenAi(openAiKey());
            $opts = [
                'file'=>$file,
                'model'=>'whisper-1',
                'response_format' => 'json',
            ];            
            $response = $open_ai->transcribe($opts);
            $response = json_decode($response);
            unlink($audioPath);
            $text  = $response;
            if ($user->user_type == "customer") {               
                updateDataBalance('words', strlen($text->text), $user);
            }
        } catch (\Exception $e) {
            Log::info('recorder voice issues : '.$e->getMessage());
            $text = "";
        }
        return $text;
    }
}

?>