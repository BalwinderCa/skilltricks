<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PageRequestForm extends FormRequest
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
            'title'            => ['required', Rule::unique('pages', 'title')->ignore($this->id)],
            'content'          => ['nullable'],
            'meta_title'       => ['nullable'],
            'meta_description' => ['nullable'],
            'meta_image'       => ['nullable'],
        ];
    }
}
