<?php

namespace App\Http\Controllers\Backend\AI;


use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Http\Request; 
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Http\Services\GenerateImage;
use Illuminate\Support\Facades\Storage; 

class GenerateSdImagesController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_ai_images') == '0') {
            redirect()->route('writebot.dashboard')->send();
        }
    }

    # images
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->user_type == "customer") {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_sd_images == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('stable_diffusion')) {
                abort(403);
            }
        }

        $searchKey = null;
        $images = Project::where('content_type', 'image')->where('engine', 'SD')->where('user_id', auth()->user()->id)->latest();

        if ($request->search != null) {
            $images = $images->where('title', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $images = $images->paginate(paginationNumber(18));
        return view('backend.pages.templates.generate-sd-images', ['images' => $images, 'searchKey' => $searchKey]);
    }

    # generate images
    public function generate(Request $request)
    {  
      
        if (env('DEMO_MODE') == "On") {
            $data = [
                'status'  => 400,
                'success' => false,
                'message' => localize('Image generation is turned off in demo')
            ];
            return $data;
        }

        $user = auth()->user();

        # 1. init api key
        $api_key = openAiKey('sd');
        # 2. verify if user has access to the template [template available in subscription package]
        if ($user->user_type == "customer") {
            $data = activePackageBalance('allow_sd_images');
            if (!empty($data)) {
                return $data;
            }

            // check images limit  
            if (availableDataCheck('images')  < (int)$request->num_of_results) {
                $data = [
                    'status'  => 400,
                    'success' => false,
                    'message' => localize('Your limit is low, please upgrade you plan'),
                ];
                return $data;
            }
        }

        $type = $request->type;
        if($type == "multi-prompt"){
            $type = "text-to-image";
        }

        // 
        if($type == "upscale"){
            $uEngine = getSetting('image_upscaler_engine') ?? "esrgan-v1-x2plus";
            $url = 'https://api.stability.ai/v1/generation/' . $uEngine . "/image-to-image/$type"; 
        }else{
            $url = 'https://api.stability.ai/v1/generation/' . getSetting('image_stable_diffusion_engine') . "/$type";
        } 
        
        if($type == "text-to-image"){
            $headers = [
                'Authorization:' . $api_key,
                'Content-Type: application/json',
            ];
        }else{
            $headers = [
                'Authorization:' . "Bearer " . $api_key,
                'Content-Type: multipart/form-data',
                'Accept: application/json'
            ];
        }
       

        $n              = 1;
        $n              = (int)$request->num_of_results;
        $n              = (int)$n >= 5 ? 5 : $n;
       

        $key2 = 1; 
        $parsePromptsController = new ParsePromptsController; 

        if ($request->type == "text-to-image") { 
            $prompt                            = $parsePromptsController->images($request->all());
            $data['text_prompts'][0]['text']   = $prompt;
            $data['text_prompts'][0]['weight'] = 1;
        }else if($request->type == "image-to-image"){  

            $validData = $this->__validateImage($request->file('imageFile'));
            if($validData['success'] == false){
                return $validData;
            }
            $prompt = $parsePromptsController->imageToImage($request->all());
            $data['text_prompts[0][text]']   = $prompt;
            $data['text_prompts[0][weight]'] = 1;
            $data["image_strength"]          = 0.35;
            $data["init_image_mode"]         = "IMAGE_STRENGTH";
            $data["steps"]                   = 30; 
            // make the file 
            $uploadedImage      = $request->file('imageFile');
            $uploadedImageUrl   = $uploadedImage->store('images'); 
            $imagePath          = public_path() . DIRECTORY_SEPARATOR . $uploadedImageUrl; 
            $file               = curl_file_create($imagePath);  
            $data['init_image'] = $file;

        } else if($type == "upscale"){
            $validData = $this->__validateImage($request->file('imageUpscaleFile'));
            if($validData['success'] == false){
                return $validData;
            }
            
            // make the file 
            $uploadedImage    = $request->file('imageUpscaleFile');
            $uploadedImageUrl = $uploadedImage->store('images'); 
            $imagePath        = public_path() . DIRECTORY_SEPARATOR . $uploadedImageUrl; 
            $file             = curl_file_create($imagePath); 

            $dataUpscale = []; 
            $dataUpscale['image'] = $file;
            // $dataUpscale['height'] = 1062;
        } else {
            $style = $request->style;
            $mood = $request->mood;
            if ($style == "none") {
                $style = "";
            }
            if ($mood == "none") {
                $mood = "";
            }

            $proTitle = '';
            foreach ($request->titles as $key => $title) {
                $prompt2 = $title;
                $proTitle .= $title . ', ';
                if ($style != "") {
                    $prompt2 .= ',' . $style;
                }

                if ($mood != "") {
                    $mood .= ',' . $mood;
                }
                $data['text_prompts'][$key]['text'] = $prompt2;
                $data['text_prompts'][$key]['weight'] = 1;
                $key2 += 1;
            }
        }

        if ($request->negative_prompts && $request->type == "text-to-image") {
            $data['text_prompts'][$key2]['text'] = $request->negative_prompts;
            $data['text_prompts'][$key2]['weight'] = -1;
        }else if($request->negative_prompts && $request->type == "image-to-image"){
            $data["text_prompts[$key2][text]"] = $request->negative_prompts;
            $data["text_prompts[$key2][weight]"] = -1;
        }

        if($request->resolution){
            $resolutions = explode('x', $request->resolution);
            $width = $resolutions[0];
            $height = $resolutions[1];
        }else if($type == "image-to-image"){
            $fileSize = getimagesize($imagePath);
            $width = $fileSize[0];
            $height = $fileSize[1];   
        }else if($type == "upscale"){
            $fileSize = getimagesize($imagePath);
            $width = $fileSize[0];
            $height = $fileSize[1];  
        }

        if($type != "upscale"){ 
            $data['clip_guidance_preset'] = $request->preset;
            if($type != 'image-to-image'){
                $data['height'] = (int)$height;
                $data['width'] = (int)$width;
            }
            if ($request->diffusion_samples != 'none') {
                $data['sampler'] = $request->diffusion_samples;
            }

            $data['samples'] = $n;
            if ($request->style != 'none') {
                $data['style_preset'] = $request->style;
            } 
        }

        if($type == "text-to-image"){
            $postData = json_encode($data);
        }else if($type == "upscale"){ 
            $postData = $dataUpscale;
        }else{
            $postData = $data;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch); 
        curl_close($ch);
 
        # parse response
        $result = json_decode($result, true); 

        if($type == "image-to-image" || $type == "upscale"){
            fileDelete($uploadedImageUrl);
        }
        if (isset($result['artifacts'])) {
           
            foreach ($result['artifacts'] as $key => $value) {
                $name = Str::random(10) . '.png';
                $image = base64_decode($value['base64']);
                $file_path = 'images/' . $name;

                if(activeStorage('aws')) {  
                    Storage::disk('s3')->put('images/' . $name, $image, 'public');
                    $file_path  = Storage::disk('s3')->url('images/' . $name); 
                    $resolution =   imageDimension($file_path, true);                     
                }else {                  
                    file_put_contents(public_path($file_path), $image);
                    $resolution =   imageDimension(public_path($file_path), true); 
                }
                $project = new Project;
                $project->user_id       = $user->id;
                $project->title         = $request->title ?? $request->title ??  $request->titleImage ?? $request->titleUpscale ?? $proTitle . '-' . ($key + 1);
                $project->slug          = preg_replace('/\s+/', '-', trim($project->title)) . '-' . strtolower(Str::random(5));
                $project->content_type  = 'image';
                $project->resolution    = $resolution;
                $project->content       = $file_path;
                $project->engine        = 'SD';
                $project->storage_type  = activeStorage('aws') ? 'aws' : 'local';
                $project->save();
            }

            $latestPackage = activePackageHistory();
            $previousBalance = $latestPackage ? $latestPackage->this_month_available_images : null;
            $after_balance = $latestPackage ? $latestPackage->this_month_available_images - 1 : null;
            # keep log
            $logData = [
                'user_id' => $project->user_id,
                'project_id' => $project->id,
                'subscription_history_id' => optional(activePackageHistory())->id,
                'subscription_package_id' => optional(activePackageHistory())->subscription_package_id,
                'content' => $project->content,
                'content_type' => $project->content_type,
                'resolution' => $project->resolution,
                'previous_balance' => $previousBalance,
                'after_balance' => $after_balance
            ];
            $generateController = new GenerateContentsController();
            $generateController->createLog($logData);

            # Update credit balance
            $this->updateUserImages($user, $n);

            $images = Project::where('content_type', 'image')->where('engine', 'SD')->where('user_id', auth()->user()->id)->latest();
            $images = $images->paginate(paginationNumber(18));

            $data = [
                'status'            => 200,
                'success'           => true,
                'images'            => view('backend.pages.templates.inc.images-list', compact('images'))->render(),
                'usedPercentage'    => view('backend.pages.templates.inc.used-images-percentage')->render(),
            ];
            return $data;
        } else {
            if($result && $result["message"]){
                $message = $result["message"];
            }else{
                $message = localize('There was an issue generating your AI Image, please try again or contact support team');
            }
            $data = [
                'status'  => 400,
                'success' => false,
                'message' => $message
            ];
            return $data;
        }

        $data = [
            'status'  => 500,
            'success' => false,
        ];
        return $data;
    }

    # validate image 
    private function __validateImage($file){
    
        $type = getFileType(Str::lower($file->getClientOriginalExtension()));
       return [
        'status'  => $type == "image" ? 200 :400,
        'success' => $type == "image" ? true : false,
        'message' => $type == "image" ? "" : localize("Only jpg, png, webp images are allowed")
       ];
    }

    # updateUserImages - take n
    public function updateUserImages($user, $n)
    {
        if ($user->user_type == "customer") {
            updateDataBalance('images', $n, $user);
        }
    }

    # delete image
    public function delete($id)
    {
        $image = Project::where('user_id', auth()->user()->id)->where('id', $id)->first();
        if (!is_null($image)) {
            try {
             
                if($image->storage_type == 'aws') {
                    $image_name = explode('.com/', $image->content);         
                    Storage::disk('s3')->delete($image_name[1]);
                }else{
                    fileDelete($image->content);
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
            $image->delete();
        }
        flash(localize('Image has been deleted successfully'))->success();
        return back();
    }
}
