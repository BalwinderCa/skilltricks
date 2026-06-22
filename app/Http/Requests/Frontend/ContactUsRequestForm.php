<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ContactUsRequestForm extends FormRequest
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
       
        $rules = [
            'name'=>['required', 'max:30'],
            'email'=>['required', 'email:rfc,dns'],
            'phone'=>['required', 'max:30'],
            'message'=>['required'],
          
        ];
   
        if(getSetting('enable_recaptcha') == 1) {
            $rules['score'] = 'required|numeric|min:0.9';
        }
        return $rules;
    }
    public function attributes()
    {
        return [
            'g_recaptcha_response' => 'g-recaptcha-response'
        ];
    }
}
