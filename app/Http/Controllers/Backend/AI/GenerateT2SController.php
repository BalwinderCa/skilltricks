<?php

namespace App\Http\Controllers\Backend\AI;

use Carbon\Carbon;
use App\Traits\Language;
use Illuminate\Support\Str;
use App\Models\TextToSpeech;
use Illuminate\Http\Request;
use App\Traits\GenerateVoice;
use App\Models\GoogleTTSSettings;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class GenerateT2SController extends Controller
{
    use Language;
    use GenerateVoice;

    public function __construct()
    {
        if (getSetting('enable_text_to_speech') == '0') {
            redirect()->route('writebot.dashboard')->send();
        }
    }

    # t2s
    public function index()
    {
        // $data = json_encode($this->languageVoicesData());

        $user = auth()->user();
        if ($user->user_type == "customer") {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_text_to_speech == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('speech_to_text')) {
                abort(403);
            }
        }


        if(getSetting('default_voiceover') == appStatic()::OpenAiTTS){
            $data = $this->loadDataForOpenAi();
            return view('backend.pages.templates.generate-t2s-open-ai', $data);
        }
        $data = $this->loadData();
        return view('backend.pages.templates.generate-t2s', $data);
    }

    # generate t2s
    public function generate(Request $request)
    {
        if($request->status == appStatic()::OpenAiTTS){
            return $this->openAiTextToSpeech('open_ai', $request);
        }
        $user = auth()->user();
        $speeches = json_decode($request->speeches, true);

        $data['empty'] = '';
        $data += $this->loadData();

        if (config('custom.demo_mode') == 'On') {
            $response = [
                'status'    => false,
                'message'   => localize('In demo mode, this feature is disabled'),
                'view'      => view('backend.pages.templates.inc.voice-list', $data)->render()
            ];
            return response()->json(compact('response'));
        }
        // check enable voice over
        if (voiceOverEnable() == false) {
            $response = [
                'status'    => false,
                'message'   => localize('Please Enable Voice Over from Voice Settings'),
                'view'      => view('backend.pages.templates.inc.voice-list', $data)->render()
            ];
            return response()->json(compact('response'));
        }

        # 2. verify if user has access to the template [template available in subscription package]
        if ($user->user_type == "customer") {
            $data = activePackageBalance('allow_text_to_speech');
            if (!empty($data)) {

                $response = [
                    'status'    => $data['status'],
                    'message'   => $data['message'],
                    'view'      => view('backend.pages.templates.inc.voice-list', $data)->render()
                ];
                return response()->json(compact('response'));
            }
            // check word limit  
            if (availableDataCheck('words') <= 0) {
                $response = [
                    'status'    => false,
                    'message'   => localize('Your word balance is low, please upgrade you plan'),
                    'view'      => view('backend.pages.templates.inc.voice-list', $data)->render()
                ];
                return response()->json(compact('response'));
            }
        }
        
        $formData = $this->formatParams($request);
        TextToSpeech::create($formData);
        $newData =  $this->loadData();
        $response = [
            'status'    => true,
            'message'   => localize('Speech generate Successfully'),
            'view'      => view('backend.pages.templates.inc.voice-list', $newData)->render()
        ];
        return response()->json(compact('response'));
    }

    public function edit($id)
    {
        // 
    }
    // update
    public function update(Request $request)
    {
        $model = TextToSpeech::findOrFail($request->id);
        $formData = $this->formatParams($request, $request->id);
        $model->update($formData);
    }
    // format data 
    private function formatParams($request, $model_id = null): array
    {
        $user = auth()->user();
        $speeches = json_decode($request->speeches, true);
        // generate voice for enable voice over
        if (getSetting('default_voiceover') == 'google') {
            $voiceData = $this->googleVoiceGenerate($voice = $request->voice, $lang = $request->lang, $pace = $request->speed, $break = $request->b_reak, $speeches);
        } else if (getSetting('default_voiceover') == 'azure') {
            $voiceData = $this->azureVoiceGenerate($voice = $request->voice, $lang = $request->lang, $pace = $request->speed, $break = $request->b_reak, $format = 'mp3', $speeches);
        }else if(getSetting('default_voiceover') == appStatic()::OpenAiTTS){
            $model     = $request->model;
            $voice     = $request->voice;
            $speed     = $request->speed;
            $format    = $request->response_format;
            $content   = $request->content;
            $voiceData = $this->openAiTTS($model, $voice, $speed, $format, $content);
        }
        
        $params = [
            'title'        => $request->title,
            'language'     => $request->lang,
            'voice'        => $request->voice,
            'speed'        => $request->speed,
            'break'        => $request->b_reak,
            'slug'         => Str::random(20) . Str::slug($request->title),
            'text'         => $request->content ? $request->content : $request->speeches,
            'response'     => $voiceData['langsAndVoices'] != ''? json_encode($voiceData['langsAndVoices']) : '',
            'speech'       => $voiceData['audioName'],
            'file_path'    => $voiceData['file_path'],
            'credits'      => $voiceData['wordCount'],
            'words'        => $voiceData['wordCount'],
            'storage_type' => $voiceData['storage_type'],
            'type'         => getSetting('default_voiceover')
        ];

        $this->updateUserT2S($voiceData['wordCount'], auth()->user());

        if ($model_id) {
            $params['updated_by'] = $user->id;
        } else {
            $params['created_by'] = $user->id;
            $params['hash'] = Str::random(256);
        }
        return $params;
    }
    // load data
    private function loadData(): array
    {
        $data = [];
        $data['voiceLists'] = TextToSpeech::latest()->where('type', '!=', 'eleven_labs')->where('created_by', auth()->user()->id)->paginate(paginationNumber());
        $data['languages'] = $this->languageList();

        $data['languages_voices'] = $this->languageVoicesData();

        $data['status'] = voiceOverEnable();
        return $data;
    }
    // delete text to speech
    public function delete($id)
    {

        $textToVoice = TextToSpeech::findOrFail($id); 
   
        $exit_file_path = base_path('public/' . $textToVoice->file_path);
        if (file_exists($exit_file_path)) {
            unlink($exit_file_path);
        }
        if($textToVoice->storage_type == 'aws') {
            Storage::disk('s3')->delete($textToVoice->speech);
        }
        $textToVoice->delete();

        flash(localize('Generate Voice has been deleted successfully'))->success();
        return redirect()->route('t2s.index');
    }

    # updateUserT2S - take token as word
    public function updateUserT2S($tokens, $user)
    {
        if ($user->user_type == "customer") {
            updateDataBalance('words', $tokens, $user);
        }
    }
    public function openAiTextToSpeech($type, $request)
    {
        if(config('custom.demo_mode') == 'On'){
            flash(localize('Operation  Turn off for demo'))->success();
            return redirect()->back();
       }
       if($this->elevenLabsText($request->content)) {
            flash(localize('Content exceeds limit'))->error();
            return redirect()->back();
        }
        $user = auth()->user();
        if ($user->user_type == "customer") {
            $data = activePackageBalance('allow_text_to_speech');
            if (!empty($data)) {
                flash($data['message'])->error();
                return redirect()->route('t2s.index');
            }
            // check word limit  
            if (availableDataCheck('words') <= 0) {
                flash(localize('Your word balance is low, please upgrade you plan'));
                return redirect()->route('t2s.index');
            }
        }
        try {
         
            $formData = $this->formatParams($request);
            TextToSpeech::create($formData);

            flash(localize('Speech generate Successfully'))->success();
            return redirect()->route('t2s.index');

        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->route('t2s.index');
        }
    }
    # render data for open ai text to speech
    public function loadDataForOpenAi()
    {
        $voices = "alloy, echo, fable, onyx, nova, shimmer";
        $data = [];
        $data['speeds'] = ['0.25', '0.50', '0.75', '1', '1.25', '1.5', '1.75', '2.0', '2.25', '2.5', '2.75', '3.0', '3.25', '3.5', '3.75', '4.0'];
        $data['models'] = ['tts-1', 'tts-1-hd'];
        $data['response_formats'] = ['mp3', 'opus', 'aac', 'flac'];
        $data['languages'] = $this->openAiTextToSpeechLanguage();
        $data['languages_voices'] = explode(',', $voices);
        $data['status'] = 'open_ai_tts';
        $data['voiceLists'] = TextToSpeech::latest()->where('type', '!=', 'eleven_labs')->where('created_by', auth()->user()->id)->paginate(paginationNumber());
        return $data;
    }
}
