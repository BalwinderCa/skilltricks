<?php

namespace App\Http\Controllers\Backend\Appearance;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\VideouploadRequestForm;

class VideoUploadController extends Controller
{
    public function index()
    {

    }
    public function store(VideoUploadRequestForm $request)
    {
        
        $path = 'public/uploads/';
        $file = $request->file;

        try {
            if ($file && getSetting('hero_video')) {
                $exit_file_path = base_path(getSetting('hero_video'));
                if (file_exists($exit_file_path)) {
                    unlink($exit_file_path);
                }
            }
            //code...
        } catch (\Throwable $th) {
            Log::info('error frontend video unlink', $th->getMessage());
        }

        $setting = SystemSetting::where('entity', 'hero_video')->first();
        if ($setting != null) {
            $setting->value = fileUpload($path, $file);
            $setting->save();
        } else {
            $setting = new SystemSetting;
            $setting->entity = $request->entity;
            $setting->value = fileUpload($path, $file);
            $setting->save();
        }
        cacheClear();
        flash(localize("Video uploaded Successfully"))->success();
        return back();
    }
}
