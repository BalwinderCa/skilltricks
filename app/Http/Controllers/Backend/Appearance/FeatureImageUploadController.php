<?php

namespace App\Http\Controllers\Backend\Appearance;

use App\Models\Project;
use Illuminate\Support\Str;
use App\Models\MediaManager;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FeatureImageUploadController extends Controller
{
    public function index()
    {
        $projects = Project::where('content_type', 'image')->where('is_published', 1)->paginate(paginationNumber());
        $newTab = false;
        return view('backend.pages.appearance.homepage.feature-images', compact('projects', 'newTab'));
    }

    public function store(Request $request)
    {
       

        try {
            if($request->images) {
                $images = explode(',', $request->images);
                foreach($images as $imageId) {
                    
                    $media = MediaManager::where('id', $imageId)->first();
                    $user = auth()->user();
                    $project = new Project;
                    $project->user_id       = $user->id;
                    $project->title         = $media->media_name;
                    $project->slug          = preg_replace('/\s+/', '-', trim($project->title)) . '-' . strtolower(Str::random(5));
                    $project->content_type  = 'image';
        
                    $project->content       = $media->media_file;
                    $project->engine        = 'local';
                    $project->storage_type  ='local';
                    $project->is_published  = 1;
                    $project->save();
                }
            }

            flash(localize('Image upload has been created successfully'))->success();
            return redirect()->route('admin.appearance.homepage.feature-images');
        } catch (\Throwable $th) {
            dd($th->getMessage());
            flash(localize('Image upload has been created failed'))->error();
            return redirect()->back();
        }
    }
    
}
