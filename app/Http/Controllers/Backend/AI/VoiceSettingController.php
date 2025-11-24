<?php

namespace App\Http\Controllers\Backend\AI;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Models\ElevenLabsModel;
use Illuminate\Support\Facades\DB;
use App\Models\TextToSpeechSetting;
use App\Http\Controllers\Controller;
use App\Models\ElevenLabsModelVoice;
use Illuminate\Support\Facades\File;
use App\Http\Requests\TTSRequestForm;
use Illuminate\Support\Facades\Storage;
use App\Http\Services\ElevenLabsService;

class VoiceSettingController extends Controller
{
    
    # construct
    public function __construct()
    {
        $this->middleware(['permission:voice_settings'])->only(['index', 'update']);
    }
    //index 
    public function index()
    {
        $allVoiceSettings = TextToSpeechSetting::where('is_active', 1)->where('type', '!=','eleven_labs')->get();
        $gtsSetting = TextToSpeechSetting::where('type', 'google')->first();
        $azureSetting = TextToSpeechSetting::where('type', 'azure')->first();
        $elevenLabsSetting = TextToSpeechSetting::where('type', 'eleven_labs')->first();
        $openAiSetting = TextToSpeechSetting::where('type', 'open_ai_tts')->first();

        return view('backend.pages.voiceOverSetting.index', compact('gtsSetting', 'azureSetting', 'allVoiceSettings', 'elevenLabsSetting', 'openAiSetting'));
    }

    // update or create voice setting
    public function update(TTSRequestForm $request)
    {
        $type = $request->type;
        $file = $request->file;
        $path = 'public/uploads/tts/';
        $countVoiceSetting = TextToSpeechSetting::all()->count();
        $voiceSetting = TextToSpeechSetting::where('type', $type)->first();
      
        if ($file && $voiceSetting) {
            $exit_file_path = base_path('storage/' . $voiceSetting->file_name);
            if (file_exists($exit_file_path)) {
                unlink($exit_file_path);
            }
        }

        if (!$voiceSetting) {
            $voiceSetting = new TextToSpeechSetting();
        }

        if ($type == 'google' && $file) {
            $voiceSetting->file_name = $file->getClientOriginalName();
            $voiceSetting->path = fileUpload($path, $file, true);
        }

        if ($request->filled('project_name')) {
            $voiceSetting->project_name = $request->project_name;
        }

        if ($type == 'azure') {
            $voiceSetting->key = $request->azure_key;
            $voiceSetting->region = $request->azure_region;
        }
        if($type == 'eleven_labs') {
            $voiceSetting->key = $request->eleven_labs_api_key;
        }
        $voiceSetting->type = $request->type;
        $voiceSetting->maximum_character = $request->maximum_character;
        $voiceSetting->created_by = auth()->user()->id;
        $voiceSetting->save();

        if($voiceSetting->type == 'eleven_labs' && $request->filled('eleven_labs_api_key')) {
            $this->storeElevenLabsData($request->eleven_labs_api_key);
        }
        // move file for google
        if ($type == 'google' && $file) {
            File::move(public_path('uploads/tts/' . $voiceSetting->file_name), base_path('storage/' . $voiceSetting->file_name));
        }

        if ($type == 'google') {
            $message = localize('Google TTS Configuration successfully');
        } elseif ($type == 'azure') {
            $message = localize('Azure Configuration successfully');
        } else {
            $message = localize('Operation successfully');
        }
        // enable default voice over at first if not have any data
        if ($countVoiceSetting == 0) {
            $this->activeVoiceSettings($request->type);
        }

        flash($message)->success();
        return back();
    }

    // default voice over request
    public function defaultVoiceOver(Request $request)
    {
        if (env('DEMO_MODE') == "On") {
            return [
                'status' => 'success',
                'message' => localize('This is turned off in demo')
            ];
        }
        if (!$request->method) {
            $exitDefaultVoiceover = SystemSetting::where('entity', 'default_voiceover')->first();
            if ($exitDefaultVoiceover) {
                $exitDefaultVoiceover->delete();
                $status = 'success';
                $message = localize('Disable Voice over Method successfully');
                cacheClear();
            } else {
                $status = 'warning';
                $message = localize('No Enable Voice Method Found');
            }

            return [
                'status' => $status,
                'message' => $message
            ];
        }
        $this->activeVoiceSettings($request->method);

        return [
            'status' => 'success',
            'message' => localize('Enable Voice over Method successfully')
        ];
    }

    //enable voice over system settings
    private function activeVoiceSettings($method)
    {
        SystemSetting::updateOrCreate(
            [
                'entity' => 'default_voiceover'
            ],

            ['value' => $method]
        );
        cacheClear();
    }
    //  eleven labs
    public function storeElevenLabsData($api_key)
    {
        try {
            $elevenLabService = new ElevenLabsService($api_key);
            $models = json_decode($elevenLabService->models());      
     
    
            ElevenLabsModel::where('is_active', '=', 1)->update(['is_active'=>0]);
           
            foreach($models as $item){        
              $model = new ElevenLabsModel;
              $model->model_id = $item->model_id;
              $model->name = $item->name;
              $model->can_do_text_to_speech = $item->can_do_text_to_speech;
              $model->can_do_voice_conversion = $item->can_do_voice_conversion;
              $model->can_be_finetuned = $item->can_be_finetuned;
              $model->can_use_style = $item->can_use_style;
              $model->response = $item;
              $model->save();
            }
    
            $use_case = 'use case';
            ElevenLabsModelVoice::where('is_active', '=', 1)->update(['is_active'=>0]);
            $voices = json_decode($elevenLabService->voices());
            $modify_voices = [];
            foreach($voices as $voice){           
              $modify_voices = $voice;
            }
      
            foreach($modify_voices as $modify_voice){   
                 
              $modelVoice = new ElevenLabsModelVoice();
              $modelVoice->voice_id = $modify_voice->voice_id;
              $modelVoice->name = $modify_voice->name;
              $modelVoice->accent = $modify_voice->labels->accent ?? '';
              $modelVoice->description = $modify_voice->labels->description ?? '';
              $modelVoice->use_case = $modify_voice->labels->$use_case ?? '';
              $modelVoice->response = $modify_voice;
              $modelVoice->save();
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
       
    }
}
