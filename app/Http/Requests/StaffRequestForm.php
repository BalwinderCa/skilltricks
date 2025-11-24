<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StaffRequestForm extends FormRequest
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
        $staff = null;
        if ($this->id) {
            $staff =  User::findOrFail($this->id);
        }
        $settings = getSetting('registration_with');

        $rules = [
            'name' => ['required'],
            'email' => ['required', Rule::unique('users', 'email')->ignore($this->id)],
            'role_id' => ['required'],
            'password' => ['required', 'min:6'],
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
            'role_id' => 'role'
        ];
    }
}
