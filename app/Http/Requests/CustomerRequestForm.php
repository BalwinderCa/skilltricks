<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\RequiredIf;

class CustomerRequestForm extends FormRequest
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

        $settings = getSetting('registration_with');
        $rules = [
            'name' => ['required'],
            'email' => ['required', Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($this->id)],
            'password' => ['required'],
            'package' => ['required'],
            'paid_amount' => ['sometimes', 'nullable', 'numeric'],
            'payment_method' => ['sometimes', 'nullable', Rule::requiredIf(function(){
                return $this->paid_amount > 0;
            })],
            'offline_payment_method_id' => ['sometimes', 'nullable', 'required_if:payment_method,offline'],
        ];


        if ($settings == 'email_and_phone') {
            $rules['phone'] = ['required'];
        } elseif ($settings == 'email') {
            $rules['phone'] = ['sometimes', 'nullable'];
        }
        return $rules;
    }
    public function attributes()
    {
        return [
            'offline_payment_method_id'=> ' offline Payment Method'
        ];
    }
}
