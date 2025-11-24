<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CustomTemplateCategoryRequestForm extends FormRequest
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
        $rules =  [];
        if ($this->lang_key == env("DEFAULT_LANGUAGE")) {
            $rules['name'] =  ['required', Rule::unique('custom_template_categories', 'name')->where('user_id', auth()->user()->id)->ignore($this->id)];
        } elseif ($this->lang_key != env("DEFAULT_LANGUAGE") && $this->id) {
            $rules['name'] =  ['required'];
        } else {
            $rules['name'] =  ['required', Rule::unique('custom_template_categories', 'name')->where('user_id', auth()->user()->id)->ignore($this->id)];
        }
        return $rules;
    }
}
