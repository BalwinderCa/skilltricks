<?php

namespace App\Http\Requests\UserRegistration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRegistrationStoreReqeust extends FormRequest
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
        $request = $this->request;

        return [
            "name"  => "required",
            "email" => "nullable",
            "phone" => ["nullable"],
            "score" => "required|numeric|min:0.9"
        ];
    }

    protected function prepareForValidation()
    {
        $score = recaptchaValidation($this->request);

        $this->merge([ "score" => $score ]);
    }

    public function messages()
    {
        return [
            "score.required" => localize("Google recaptcha is required"),
            'score.min'      => localize('Google recaptcha validation error, seems like you are not a human.')
        ];
    }

}
