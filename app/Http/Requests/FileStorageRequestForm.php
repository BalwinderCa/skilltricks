<?php

namespace App\Http\Requests;

use App\Models\StorageManager;
use Illuminate\Foundation\Http\FormRequest;

class FileStorageRequestForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if($this->type == 'aws') {
            $rules = [
                'type'=>['required'],
                'access_key'=>['required'],
                'secret_key'=>['required'],
                'region'=>['required'],
                'bucket_name'=>['required'],
            ];
        }
       if($this->type == 'gcs') {
            $gcs = StorageManager::where('type', 'gcs')->first();
            $rules = [
                'type'=>['required'],           
                'bucket_name'=>['required'],
            ];
            if ($gcs) {
                $exit_file_path = base_path($gcs->file_name);
                if (file_exists($exit_file_path)) {    
                    $rules['file'] = ['sometimes', 'nullable', 'mimes:json'];
                } else {
                    $rules['file'] = ['required_if:type,gcs', 'mimes:json'];
                }
            } else {
                $rules['file'] = ['required_if:type,gcs', 'mimes:json'];
            }
       }
       return $rules;
    }
}
