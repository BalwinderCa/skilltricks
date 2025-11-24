<?php

namespace App\Http\Controllers\Backend\AI;

use Illuminate\Http\Request;
use App\Models\GoogleTTSSettings;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use App\Http\Requests\TTSRequestForm;
use Illuminate\Support\Facades\Storage;

class GtsSettingController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:google_tts'])->only(['index', 'update']);
    }

    #
    public function index()
    {
        $ttsSettings = GoogleTTSSettings::first();
        return view('backend.pages.gtsSettings.index', compact('ttsSettings'));
    }

    #
    public function update(TTSRequestForm $request)
    {
        $file = $request->file;
        $path = 'public/uploads/tts/';
        $ttsSettings = GoogleTTSSettings::first();
        if (!$ttsSettings) {
            $ttsSettings = new GoogleTTSSettings();
        }
        $ttsSettings->file_name = $file->getClientOriginalName();
        $ttsSettings->path = fileUpload($path, $file, true);
        $ttsSettings->project_name = $request->project_name;
        $ttsSettings->save();
        File::move(public_path('uploads/tts/' . $ttsSettings->file_name), base_path('storage/' . $ttsSettings->file_name));
        flash(localize('Google TTS Configuration successfully'))->success();
        return back();
    }
}
