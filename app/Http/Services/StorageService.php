<?php
namespace App\Http\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Orhanerday\OpenAi\OpenAi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Backend\AI\ParsePromptsController;

class StorageService
{
    public function store($response_format = 'b64_json')
    {
        $nameOfImage = Str::random(12) . ".png";
        if(activeStorage('aws')) {  
            Storage::disk('s3')->put('images/' . $file_name, $image, 'public');
            $file_path = Storage::disk('s3')->url('images/' . $file_name);                           
        }else {
            file_put_contents(public_path($file_path), $image);
        }
    }
}