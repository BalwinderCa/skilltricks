<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Models\StorageManager;
use App\Http\Controllers\Controller;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\FileStorageRequestForm;

class FileStorageManagerController extends Controller
{
     # construct
     public function __construct()
     {
         $this->middleware(['permission:storage_manager'])->only('index');
        
     }
    public function index()
    {
        $awsS3      = StorageManager::where('type', 'aws')->first();
        $gcs        = StorageManager::where('type', 'gcs')->first();
        $storages   = StorageManager::where('is_deActive', '!=', 1)->where('is_active', 1)->get();
        return view('backend.pages.storage_management.index', compact('gcs', 'storages', 'awsS3'));
    }
    public function update(FileStorageRequestForm $request)
    {
        if (env('DEMO_MODE') == "On") {
            flash(localize("This is turned off in demo"))->warning();
            return back();
        }
        
        if($request->type == 'gcs') {
            $file = $request->file;
            $gcs  = StorageManager::where('type', 'gcs')->first();
            if ($file && $gcs) { 
                if($gcs->file_name) {
                    $exit_file_path = base_path($gcs->file_name);
                    if (file_exists($exit_file_path)) {
                        unlink($exit_file_path);
                    }
                }
                
            }
            $gcs->file_name = $file->getClientOriginalName();
            $gcs->path      = fileUpload(base_path(), $file, true); 
            $gcs->bucket    = $request->bucket_name;  
            $gcs->is_active = $request->is_active == 'on' ? 1 : 0;  
            $gcs->save();
        }
        if($request->type == 'aws') {
            StorageManager::updateOrCreate([
                'type'=>$request->type,
            ], 
                [
                    'access_key'=>$request->access_key,
                    'secret_key'=>$request->secret_key,
                    'region'    =>$request->region,
                    'bucket'    =>$request->bucket_name,
                    'is_active' =>$request->is_active == 'on' ? 1 : 0
                ]
            );
            writeToEnvFile('AWS_ACCESS_KEY_ID', $request->access_key);
            writeToEnvFile('AWS_SECRET_ACCESS_KEY', $request->secret_key);
            writeToEnvFile('AWS_DEFAULT_REGION',  $request->region);
            writeToEnvFile('AWS_BUCKET', $request->bucket_name);
        }


        flash(localize('Inserted successfully'))->success();
        return back();
    }

    public function activeStorage(Request $request)
    {
        if (env('DEMO_MODE') == "On") {
            return [
                'status' => 'success',
                'message' => localize('This is turned off in demo')
            ];
        }
        SystemSetting::updateOrCreate(
            [
                'entity' => 'active_storage'
            ],

            ['value' => $request->method]
        );
        cacheClear();
        return [
            'status' => 'success',
            'message' => localize('Activated successfully')
        ];
    }
    private function gcp()
    {

    }
}
