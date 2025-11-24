<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ThemeRequestForm extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name'=>['required', Rule::unique('name', 'themes')->ignore($this->id)],
            'is_default'=>['required'],
            'preview_image'=>['sometimes', 'nullable', 'mimes:jpg,jpeg,png'],
            'full_image'=>['sometimes', 'nullable', 'mimes:jpg,jpeg,png']
        ];
    }
}
