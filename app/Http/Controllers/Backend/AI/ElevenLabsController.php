<?php

namespace App\Http\Controllers\Backend\AI;

use Illuminate\Support\Str;
use App\Models\TextToSpeech;
use Illuminate\Http\Request;
use App\Traits\GenerateVoice;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ElevenLabsController extends Controller
{
    use GenerateVoice;
    public function __construct()
    {
        if (getSetting('enable_eleven_labs') == '0') {
            flash(localize('Eleven Labs is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }
    public function index()
    {
        $user = user();

        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_eleven_labs == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('text_to_speech_eleven_labs')) {
                abort(403);
            }
        }
        $ttsSettings = voiceSettingCredential('eleven_labs');
           if (!$ttsSettings && env('DEMO_MODE') == 'Off') {
                flash(localize('Your Eleven Lab API not connected ! Please connect API From Voice Settings'))->error();
           }
        $data = $this->loadData();
        return view('backend.pages.elevenLabs.index', $data);
    }
    public function voiceList(Request $request)
    {
        $model_id = $request->model_id;
        $voiceLists = $this->elevenLabsVoiceList($model_id);
        return response()->json($voiceLists);

    }
    public function generateSpeech(Request $request)
    {
        try {

           if(env('DEMO_MODE') == 'On'){
                flash(localize('Operation  Turn off for demo'))->warning();
                return redirect()->back();
           }
           $ttsSettings = voiceSettingCredential('eleven_labs');
           if (!$ttsSettings) {
                flash(localize('Your Eleven Lab API not connected'))->error();
                return redirect()->back();
           }
           if($this->elevenLabsText($request->content)) {
                flash(localize('Content exceeds limit'))->error();
                return redirect()->back();
           }
           $formData = $this->formatParams($request);
           TextToSpeech::create($formData);
            flash(localize('Generate Successfully'))->success();
            return redirect()->back();

        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->back();
        }

    }
    private function formatParams($request, $model_id = null): array
    {
        $user = auth()->user();
        $voiceData = $this->generateTextToSpeech($request);
        $params = [
            'title'        => $request->title,
            'language'     => $request->lang,
            'voice'        => $request->voice,
            'slug'         => Str::random(20) . Str::slug($request->title),
            'text'         => $request->content,
            'response'     => null,
            'speech'       => $voiceData['audioName'],
            'file_path'    => $voiceData['file_path'],
            'credits'      => $voiceData['wordCount'],
            'words'        => $voiceData['wordCount'],
            'storage_type' => $voiceData['storage_type'],
            'type'         => 'eleven_labs'
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
        $data['voiceLists'] = TextToSpeech::latest()->where('type', '=', 'eleven_labs')->where('created_by', auth()->user()->id)->paginate(paginationNumber());
        $data += $this->elevenLabs();
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
            Storage::disk('')->delete($textToVoice->speech);
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
}
