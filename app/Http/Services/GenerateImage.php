<?php

namespace App\Http\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Orhanerday\OpenAi\OpenAi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Backend\AI\ParsePromptsController;

class GenerateImage
{
    # image generate for open ai dalle-e-2/dall-e-3 model
    public function generateImageByOpenAi(object $request = null, $type = null, $prompt = null):array
    {
       
        $data = [
            'status'         => 400,
            'success'        => false,
            'message'        => '',
            'files'          => [],
            'resolution'     => '',
            'n'              => 1,
            'reverse_prompt' => ''
        ];
        # 4. generate prompt in selected language 
        $parsePromptsController = new ParsePromptsController;
        if(!$prompt){
            $prompt                 = $parsePromptsController->images($request->all());
        }
        # image generate for open ai dalle-e-2 model

        # 6. generate image
        $n              = 1;
        $resolution     = $request ? $request->resolution : '1024x1024';
        $n              = $request ? (int)$request->num_of_results : 1;
        $n              = $n >= 5 ? 5 : $n;
        if($request != null) {
            if($request->model){
                $model = $request->model;
            }elseif(getSetting('generate_image_option')== 'dall_e_3'){
                $model = 'dall-e-3';
            }else{
                $model = 'dall-e-2';
            }
        }

        if($type == 'imageChat') {
            $model = getSetting('image_chat_model') ?? 'dall-e-3';
        }
        
        $open_ai = new OpenAi(openAiKey());
        $result = $open_ai->image([
            'model'             => $model,
            'prompt'            => $prompt,
            'size'              => $resolution,
            'n'                 => $n,
            "response_format"   => "url",
        ]);
        $revers_prompt = '';
        # parse response
        $result = json_decode($result, true);
        $files = [];
        if (isset($result['data'])) {
            if(count($result['data']) > 1) {
                foreach ($result['data'] as $key => $value) {
                    $url       = $value['url'];
                    $file_name = Str::random(10) . '.png';
                    $file_path = 'images/' . $file_name;

                    // aws s3 storage
                    if(activeStorage('aws')){   
                        $curl      = curl_init();
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_URL, $url);
                        $contents  = curl_exec($curl);
                        curl_close($curl);                    
                        Storage::disk('s3')->put('images/' . $file_name, $contents, 'public');
                        $file_path = Storage::disk('s3')->url('images/' . $file_name);                           
                    }else{
                        $image = file_get_contents($url);
                        file_put_contents(public_path($file_path), $image);
                    }

                    $files[] = [
                        'file_path' => $file_path,
                        'file_name' => $file_name,
                    ];
                    
                }
            }else{
                $url = $result['data'][0]['url'];
                $file_name = Str::random(10) . '.png';
                $file_path = 'images/' . $file_name;
            

                // aws s3 storage
                if(activeStorage('aws')){ 
                    $curl     = curl_init();
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_URL, $url);
                    $contents = curl_exec($curl);
                    curl_close($curl);                      
                    Storage::disk('s3')->put('images/' . $file_name, $contents, 'public');
                    $file_path = Storage::disk('s3')->url('images/' . $file_name);                           
                }else{
                    $image = file_get_contents($url);
                    file_put_contents(public_path($file_path), $image);
                }
                $files[] = [
                    'file_path' => $file_path,
                    'file_name' => $file_name,
                ];
                $revers_prompt =  $type == 'imageChat' ? $result['data'][0]['revised_prompt'] : '';
            }

                // end
            $data = [
                    'status'        => 200,
                    'success'       => true,
                    'message'       => '',
                    'files'         => $files,
                    'resolution'    => $resolution,
                    'n'             => $n,     
                    'revers_prompt' => $revers_prompt               
                ];
            
        } else {
            $message = $result['error']['message'];
            $data = [
                'status'        => 400,
                'success'       => false,
                'message'       => $message,
                'files'         => $files,
                'resolution'    => $resolution,
                'n'             => $n,  
                'revers_prompt' => $revers_prompt
            ];
            return $data;
        }        
        return $data;

    }
    #image generate for open ai Stable Diffusion
    public function generateImageByOpenAiStableDiffusion(object $request):array
    {
        
        $data = [
            'status'    => 400,
            'success'   => false,
            'message'   => '',
            'files'     => [],
            'resolution'=> '',
            'n'         => '',
        ];
        # 1. init api key
        $api_key = openAiKey('sd');
       
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
        $resolution     = $request->resolution ?? '256x256';
        $n              = (int)$request->num_of_results;
        $n              = $n >= 5 ? 5 : $n;

        $key2 = 1; 
        $parsePromptsController = new ParsePromptsController; 

        if ($request->type == "text-to-image") { 
            $prompt = $parsePromptsController->images($request->all());
            $data['text_prompts'][0]['text']    = $prompt;
            $data['text_prompts'][0]['weight']  = 1;
        }else if($request->type == "image-to-image"){  

            $validData = $this->__validateImage($request->file('imageFile'));
            if($validData['success'] == false){
                return $validData;
            }
            $prompt = $parsePromptsController->imageToImage($request->all());
            $data['text_prompts[0][text]'] = $prompt;
            $data['text_prompts[0][weight]'] = 1;
            $data["image_strength"] = 0.35;
            $data["init_image_mode"] = "IMAGE_STRENGTH";
            $data["steps"] = 30; 
            // make the file 
            $uploadedImage = $request->file('imageFile');
            $uploadedImageUrl = $uploadedImage->store('images'); 
            $imagePath = public_path() . DIRECTORY_SEPARATOR . $uploadedImageUrl; 
            $file = curl_file_create($imagePath);  
            $data['init_image'] = $file;

        } else if($type == "upscale"){
            $validData = $this->__validateImage($request->file('imageUpscaleFile'));
            if($validData['success'] == false){
                return $validData;
            }
            
            // make the file 
            $uploadedImage = $request->file('imageUpscaleFile');
            $uploadedImageUrl = $uploadedImage->store('images'); 
            $imagePath = public_path() . DIRECTORY_SEPARATOR . $uploadedImageUrl; 
            $file = curl_file_create($imagePath); 

            $dataUpscale = []; 
            $dataUpscale['image'] = $file;
            // $dataUpscale['height'] = 512;
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
            $data['text_prompts'][$key2]['text']    = $request->negative_prompts;
            $data['text_prompts'][$key2]['weight']  = -1;
        }else if($request->negative_prompts && $request->type == "image-to-image"){
            $data["text_prompts[$key2][text]"]   = $request->negative_prompts;
            $data["text_prompts[$key2][weight]"] = -1;
        }

        if($resolution){
            $resolutions = explode('x', $resolution);
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
                $data['width']  = (int)$width;
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
 
        $files = [];
        # parse response
        $result = json_decode($result, true); 
        if($type == "image-to-image" || $type == "upscale"){
            fileDelete($uploadedImageUrl);
        }
        if (isset($result['artifacts'])) {
            foreach ($result['artifacts'] as $key => $value) {
                $file_name  = Str::random(10) . '.png';
                $image      = base64_decode($value['base64']);
                $file_path  = 'images/' . $file_name;

                if(activeStorage('aws')) {  
                    Storage::disk('s3')->put('images/' . $file_name, $image, 'public');
                    $file_path = Storage::disk('s3')->url('images/' . $file_name);                           
                }else {
                    file_put_contents(public_path($file_path), $image);
                }
                $files[] = [
                    'file_path' => $file_path,
                    'file_name' => $file_name,
                ];
            }
            $data = [
                'status'    => 200,
                'success'   => true,
                'message'  => '',
                'files'     => $files,
                'resolution'=> $resolution,
                'n'         => $n,  
            ];

            return $data;
        } else {
            if($result && $result["message"]) {
                $message = $result["message"];
            } else {
                $message = localize('There was an issue generating your AI Image, please try again or contact support team');
            }
            $data = [
                'status'  => 400,
                'success' => false,
                'message' => $message,
                'files'   => $files,
                'resolution'=> '',
                'n'         => '',  

            ];
            return $data;
        }
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

}