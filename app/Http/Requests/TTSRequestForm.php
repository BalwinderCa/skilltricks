<?php

namespace App\Http\Requests;

use App\Models\TextToSpeechSetting;
use Illuminate\Foundation\Http\FormRequest;

class TTSRequestForm extends FormRequest
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
        $google = TextToSpeechSetting::where('type', 'google')->first();

        $rules = [
            'type' => ['required'],
            'project_name' => ['sometimes', 'nullable'],
            'maximum_character' => ['sometimes', 'nullable', 'integer']
        ];
        if ($google) {
            $exit_file_path = base_path('storage/' . $google->file_name);
            if (file_exists($exit_file_path)) {

                $rules['file'] = ['sometimes', 'nullable', 'mimes:json'];
            } else {
                $rules['file'] = ['required_if:type,google', 'mimes:json'];
            }
        } else {
            $rules['file'] = ['required_if:type,google', 'mimes:json'];
        }

        return $rules;
    }
}
