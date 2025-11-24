<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfflinePaymentRequestForm extends FormRequest
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

        return [
            'history_id' => ['required', 'integer'],
            'offline_payment_method' => ['required', 'integer'],
            'amount' => ['sometimes', 'nullable', 'integer'],
            'payment_detail' => ['required'],
            'note' => ['sometimes', 'nullable'],
            'file' => ['sometimes', 'nullable', 'mimes:png,jpg,jpeg']
        ];
    }
    public function attributes()
    {
        return [
            'offline_payment_method' => ['payment_method']
        ];
    }
}
